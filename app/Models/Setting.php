<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * In-memory cache of settings for current request.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cachedSettings = null;

    /**
     * Default fallback settings if not configured in database.
     *
     * @var array<string, mixed>
     */
    protected static array $defaults = [
        'logo_type' => 'text_icon',
        'site_name' => 'Picho',
        'site_name_highlight' => 'Descargas',
        'logo_icon' => 'download',
        'logo_image' => null,
        'favicon_image' => null,
        'seo_meta_title' => 'PichoDescargas - Descarga Aplicaciones, Juegos y Software',
        'seo_meta_description' => 'Descubre y descarga las mejores aplicaciones y juegos para Windows, Mac, Android y más. Rápido, seguro y verificado.',
        'seo_keywords' => 'pichodescargas, descargas, aplicaciones pc, programas windows, juegos pc, mac apps, android apk, utilidades',
        'seo_og_image' => 'images/og-share.jpg',
        'seo_app_title_pattern' => '{title} for {category} - {site_name}',
        'seo_app_description_pattern' => '{title} for {category}. Safe, clean and verified software for {category}.',
        'site_tagline' => 'Las mejores descargas de programas, apps y juegos',
        'site_description' => 'Descargas directas y verificadas de aplicaciones para Windows, Mac, Android y juegos de PC.',
        'footer_text' => '© 2026 PichoDescargas. Todos los derechos reservados. Descargas directas, limpias y verificadas.',
        'contact_email' => 'contacto@pichodescargas.com',
        'telegram_channel' => 'https://t.me/pichodescargas',
        'twitter_url' => 'https://x.com/pichodescargas',
        'facebook_url' => 'https://facebook.com',
        'instagram_url' => 'https://instagram.com',
        'discord_url' => 'https://discord.gg/pichodescargas',
        'ai_provider' => 'deepseek',
        'deepseek_api_key' => '',
        'deepseek_model' => 'deepseek-chat',
        'deepseek_auto_generate' => '1',
        'deepseek_target_words' => '60',
        'deepseek_features_count' => '7',
        'gemini_api_key' => '',
        'gemini_model' => 'gemini-2.5-flash',
        'gemini_auto_generate' => '1',
        'gemini_target_words' => '60',
        'gemini_features_count' => '7',
        'r2_account_id' => '',
        'r2_access_key_id' => '',
        'r2_secret_access_key' => '',
        'r2_bucket' => '',
        'r2_url' => '',
        'torrentmac_storage_disk' => 'local',
        'logo_size' => '24',
        'custom_head_code' => '',
        'custom_body_code' => '',
        'custom_footer_code' => '',
        'scraper_cron_enabled' => '1',
        'scraper_cron_time' => '03:00',
        'scraper_cron_pages' => '2',
        'scraper_cron_limit' => '10',
        'scraper_cron_frequency' => '2hours',
        'scraper_drip_feed_mode' => '0',
        'torrentmac_cron_enabled' => '1',
        'cybermania_cron_enabled' => '1',
        'telegram_enabled' => '0',
        'telegram_bot_token' => '',
        'telegram_channel_id' => '',
        'telegram_channel_url' => 'https://t.me/pichodescargas',
        'telegram_button_tooltip' => '¿Problemas con la descarga o buscas otra app? Únete a nuestro Telegram',
        'telegram_auto_pilot' => '0',
        'telegram_daily_limit' => '20',
        'telegram_floating_button_enabled' => '1',
        'telegram_sent_today_count' => '0',
        'telegram_last_sent_date' => '',
        'telegram_total_sent_count' => '0',
        'telegram_template' => "🚀 <b>NUEVA APP PUBLICADA!</b>\n\n📱 <b>Título:</b> {title}\n📂 <b>Categoría:</b> {category}\n🏷️ <b>Versión:</b> {version}\n💾 <b>Tamaño:</b> {size}\n⚡ <b>Tipo:</b> {type}\n\n🔗 <a href=\"{url}\">Descargar en PichoDescargas</a>",
        'discord_enabled' => '0',
        'discord_webhook_url' => '',
        'notify_on_new_app' => '1',
        'notify_on_update' => '1',
        'notify_on_broken_link' => '1',
    ];

    /**
     * Retrieve a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$cachedSettings === null) {
            static::loadSettings();
        }

        if (array_key_exists($key, static::$cachedSettings)) {
            $val = static::$cachedSettings[$key];

            return ($val !== null && $val !== '') ? $val : ($default ?? static::$defaults[$key] ?? null);
        }

        return $default ?? static::$defaults[$key] ?? null;
    }

    /**
     * Set a setting key and value.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        if (static::$cachedSettings !== null) {
            static::$cachedSettings[$key] = $value;
        }

        return $setting;
    }

    /**
     * Set multiple settings at once.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value, $group);
        }
    }

    /**
     * Load all settings from the database into memory.
     */
    public static function loadSettings(): void
    {
        try {
            $dbSettings = static::all()->pluck('value', 'key')->toArray();
            static::$cachedSettings = array_merge(static::$defaults, $dbSettings);
        } catch (\Throwable) {
            static::$cachedSettings = static::$defaults;
        }
    }

    /**
     * Get all settings merged with defaults.
     *
     * @return array<string, mixed>
     */
    public static function getAll(): array
    {
        if (static::$cachedSettings === null) {
            static::loadSettings();
        }

        return static::$cachedSettings ?? static::$defaults;
    }

    /**
     * Reset cached settings.
     */
    public static function clearCache(): void
    {
        static::$cachedSettings = null;
    }
}
