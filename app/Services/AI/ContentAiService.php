<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class ContentAiService
{
    public function __construct(
        protected DeepSeekService $deepSeekService,
        protected GeminiService $geminiService
    ) {}

    /**
     * Get active AI provider ('deepseek' or 'gemini').
     */
    public function getProvider(): string
    {
        return Setting::get('ai_provider', 'deepseek');
    }

    /**
     * Check if any configured AI service is enabled.
     */
    public function isEnabled(): bool
    {
        $provider = $this->getProvider();

        if ($provider === 'deepseek') {
            return $this->deepSeekService->isEnabled() || $this->geminiService->isEnabled();
        }

        return $this->geminiService->isEnabled() || $this->deepSeekService->isEnabled();
    }

    /**
     * Generate content using the active provider with automatic fallback.
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
        $provider = $this->getProvider();

        if ($provider === 'deepseek') {
            if ($this->deepSeekService->isEnabled()) {
                $content = $this->deepSeekService->generateAppContent($name, $category, $version, $shortDesc, $scrapedDesc, $categorySlug);
                if ($content) {
                    return $content;
                }
                Log::warning("ContentAiService: DeepSeek failed for '{$name}', attempting fallback to Gemini.");
            }

            if ($this->geminiService->isEnabled()) {
                return $this->geminiService->generateAppContent($name, $category, $version, $shortDesc, $scrapedDesc, $categorySlug);
            }
        } else {
            if ($this->geminiService->isEnabled()) {
                $content = $this->geminiService->generateAppContent($name, $category, $version, $shortDesc, $scrapedDesc, $categorySlug);
                if ($content) {
                    return $content;
                }
                Log::warning("ContentAiService: Gemini failed for '{$name}', attempting fallback to DeepSeek.");
            }

            if ($this->deepSeekService->isEnabled()) {
                return $this->deepSeekService->generateAppContent($name, $category, $version, $shortDesc, $scrapedDesc, $categorySlug);
            }
        }

        return null;
    }
}
