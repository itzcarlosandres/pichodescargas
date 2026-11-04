<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeepSeekService
{
    protected string $baseUrl = 'https://api.deepseek.com';

    /**
     * Get configured DeepSeek API key from settings.
     */
    public function getApiKey(): ?string
    {
        $key = Setting::get('deepseek_api_key');

        return ! empty($key) ? trim($key) : null;
    }

    /**
     * Get configured DeepSeek model.
     */
    public function getModel(): string
    {
        return Setting::get('deepseek_model', 'deepseek-chat') ?: 'deepseek-chat';
    }

    /**
     * Check if DeepSeek is enabled and has a valid API key.
     */
    public function isEnabled(): bool
    {
        $enabled = Setting::get('deepseek_auto_generate', '1');

        return in_array($enabled, [true, 1, '1', 'true'], true) && ! empty($this->getApiKey());
    }

    /**
     * Test DeepSeek API connection with a minimal prompt.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $key = $apiKey ? trim($apiKey) : $this->getApiKey();
        $selectedModel = $model ? trim($model) : $this->getModel();

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key de DeepSeek no proporcionada.',
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $selectedModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Respond with the single word CONNECTED if you can read this.',
                        ],
                    ],
                    'max_tokens' => 20,
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['choices'][0]['message']['content'] ?? '';

                return [
                    'success' => true,
                    'message' => '¡Conexión exitosa con DeepSeek ('.htmlspecialchars($selectedModel).')! Respuesta: '.trim($text),
                ];
            }

            $errorMsg = $response->json('error.message')
                ?? $response->json('message')
                ?? 'HTTP '.$response->status().' '.$response->reason();

            return [
                'success' => false,
                'message' => 'Error de DeepSeek: '.$errorMsg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Excepción de red al conectar con DeepSeek: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Generate concise English description (~4 lines / 45-65 words with internal category SEO link) and features.
     *
     * @return array{description: string, features: string}|null
     */
    public function generateAppContent(
        string $name,
        ?string $category = null,
        ?string $version = null,
        ?string $shortDesc = null,
        ?string $scrapedDesc = null,
        ?string $categorySlug = null
    ): ?array {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            Log::warning("DeepSeekService: Cannot generate content for '{$name}', API key is missing.");

            return null;
        }

        $model = $this->getModel();
        $targetWords = (int) Setting::get('deepseek_target_words', Setting::get('gemini_target_words', 60));
        if ($targetWords < 30 || $targetWords > 300) {
            $targetWords = 60;
        }

        $featuresCount = (int) Setting::get('deepseek_features_count', Setting::get('gemini_features_count', 7));
        if ($featuresCount < 3 || $featuresCount > 15) {
            $featuresCount = 7;
        }

        $categoryName = $category ?: 'Software';
        $categorySlug = $categorySlug ?: Str::slug($categoryName);
        $categoryUrl = "/category/{$categorySlug}";

        // Clean scraped reference description from HTML tags
        $cleanScraped = ! empty($scrapedDesc) ? trim(strip_tags($scrapedDesc)) : '';
        if (strlen($cleanScraped) > 1000) {
            $cleanScraped = substr($cleanScraped, 0, 1000).'...';
        }

        $systemPrompt = <<<'SYS'
You are an expert software editor, tech journalist, and SEO copywriter.
Your goal is to write ultra-clean, concise, and engaging software summaries in professional English.
Always output valid JSON without markdown formatting fences, strictly containing two keys: "description" and "features".
SYS;

        $userPrompt = <<<PROMPT
Generate concise, SEO-optimized editorial content in English for this application/game:
- Name: {$name}
- Category: {$categoryName}
- Category URL: {$categoryUrl}
- Version: {$version}
- Context: {$shortDesc}
- Scraped Details: {$cleanScraped}

STRICT REQUIREMENTS:
1. "description":
   - Write a VERY CONCISE, IMPACTFUL summary that takes EXACTLY ~4 lines of text (between 45 and 65 words total).
   - Format: EXACTLY ONE single HTML paragraph (<p>...</p>). Never output multiple paragraphs or markdown.
   - INTERNAL SEO LINKING (MANDATORY): You MUST naturally embed an internal hyperlink linking to its category: <a href="{$categoryUrl}">{$categoryName}</a> (for example: "...stands out as an essential tool in <a href="{$categoryUrl}">{$categoryName}</a>...").
   - Highlight core functionality, key benefit, and performance in 2 to 3 fluid sentences. Keep it punchy and modern.

2. "features":
   - Provide an array of EXACTLY {$featuresCount} objects.
   - Each object must have two string keys:
     - "title": A concise feature name (3-5 words).
     - "description": A punchy sentence describing the capability (12-20 words).

JSON OUTPUT FORMAT:
{
  "description": "<p>A concise ~4 lines paragraph with an internal link to <a href=\"{$categoryUrl}\">{$categoryName}</a>...</p>",
  "features": [
    {"title": "Title 1", "description": "Description 1"},
    ... {$featuresCount} items total
  ]
}
PROMPT;

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->retry(2, 600)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.6,
                    'max_tokens' => 1200,
                ]);

            if (! $response->successful()) {
                Log::error("DeepSeekService API error for '{$name}': HTTP {$response->status()} - {$response->body()}");

                return null;
            }

            $body = $response->json();
            $rawJson = $body['choices'][0]['message']['content'] ?? '';

            if (empty($rawJson)) {
                Log::warning("DeepSeekService: Empty content received for '{$name}'.");

                return null;
            }

            // Strip possible markdown fences
            $rawJson = trim($rawJson);
            if (str_starts_with($rawJson, '```json')) {
                $rawJson = substr($rawJson, 7);
            } elseif (str_starts_with($rawJson, '```')) {
                $rawJson = substr($rawJson, 3);
            }
            if (str_ends_with($rawJson, '```')) {
                $rawJson = substr($rawJson, 0, -3);
            }
            $rawJson = trim($rawJson);

            $parsed = json_decode($rawJson, true);
            if (! is_array($parsed) || empty($parsed['description'])) {
                Log::warning("DeepSeekService: Failed to parse JSON response for '{$name}'. Raw: {$rawJson}");

                return null;
            }

            $description = trim($parsed['description']);

            // Ensure single <p> tag
            if (! str_starts_with($description, '<p>')) {
                $description = '<p>'.$description;
            }
            if (! str_ends_with($description, '</p>')) {
                $description .= '</p>';
            }

            // Extract only the first paragraph if multiple were returned
            if (preg_match('/<p>(.*?)<\/p>/is', $description, $matches)) {
                $firstParaContent = trim($matches[1]);
                $description = '<p>'.$firstParaContent.'</p>';
            }

            // Validate and enforce internal category link
            if (! str_contains($description, 'href=') && ! empty($categoryUrl)) {
                $pattern = '/\b('.preg_quote($categoryName, '/').')\b/i';
                if (preg_match($pattern, $description)) {
                    $description = preg_replace($pattern, '<a href="'.$categoryUrl.'">$1</a>', $description, 1);
                } else {
                    $description = str_replace('</p>', ' Explore more in <a href="'.$categoryUrl.'">'.$categoryName.'</a>.</p>', $description);
                }
            }

            // Build features HTML list
            $featuresList = $parsed['features'] ?? [];
            if (! is_array($featuresList) || empty($featuresList)) {
                $featuresHtml = '<ul><li>High performance and optimized workflow.</li><li>Clean, modern and intuitive user interface.</li></ul>';
            } else {
                $featuresHtml = '<ul class="space-y-2">';
                foreach ($featuresList as $feat) {
                    if (is_array($feat)) {
                        $fTitle = ! empty($feat['title']) ? '<strong>'.htmlspecialchars(trim($feat['title'])).':</strong> ' : '';
                        $fDesc = ! empty($feat['description']) ? htmlspecialchars(trim($feat['description'])) : '';
                        $featuresHtml .= '<li>'.$fTitle.$fDesc.'</li>';
                    } elseif (is_string($feat)) {
                        $featuresHtml .= '<li>'.htmlspecialchars(trim($feat)).'</li>';
                    }
                }
                $featuresHtml .= '</ul>';
            }

            Log::info("DeepSeekService: Successfully generated content for '{$name}'.");

            return [
                'description' => $description,
                'features' => $featuresHtml,
            ];
        } catch (\Throwable $e) {
            Log::error("DeepSeekService Exception for '{$name}': {$e->getMessage()}");

            return null;
        }
    }
}
