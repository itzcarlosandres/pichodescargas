<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get(route('admin.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_settings_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertSee('Configuración del Portal');
        $response->assertSee('Identidad & Logo', false);
        $response->assertSee('SEO & Metadatos', false);
        $response->assertSee('Botón VIP', false);
        $response->assertSee('Scripts & Analytics', false);
        $response->assertSee('Información & Textos', false);
        $response->assertSee('Cloudflare R2', false);
        $response->assertSee('Notificaciones', false);
        $response->assertSee('telegram_channel_id', false);
        $response->assertSee('SEO PARA PÁGINAS DE PROGRAMAS / APPS', false);
        $response->assertSee('seo_app_title_pattern', false);
        $response->assertSee('seo_app_description_pattern', false);
        $response->assertSee('Guardar Cambios');
    }

    public function test_admin_can_update_logo_and_general_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'logo_type' => 'text_icon',
            'site_name' => 'Nova',
            'site_name_highlight' => 'Apps',
            'logo_icon' => 'command',
            'site_tagline' => 'La mejor tienda de aplicaciones',
            'site_description' => 'Descarga software garantizado y seguro para tu equipo.',
            'footer_text' => '© 2026 NovaApps Inc. Todos los derechos reservados.',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('success');

        $this->assertEquals('Nova', Setting::get('site_name'));
        $this->assertEquals('Apps', Setting::get('site_name_highlight'));
        $this->assertEquals('command', Setting::get('logo_icon'));
        $this->assertEquals('La mejor tienda de aplicaciones', Setting::get('site_tagline'));

        // Verify public home page reflects updated branding
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Nova');
        $homeResponse->assertSee('Apps');
    }

    public function test_admin_can_update_seo_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'seo_meta_title' => 'Portal de Descargas Mac Pro - 2026',
            'seo_meta_description' => 'Encuentra las mejores aplicaciones de diseño, utilidades y productividad.',
            'seo_keywords' => 'mac apps, descargar mac, software mac, utilidades',
        ]);

        $response->assertRedirect(route('admin.settings'));

        $this->assertEquals('Portal de Descargas Mac Pro - 2026', Setting::get('seo_meta_title'));
        $this->assertEquals('Encuentra las mejores aplicaciones de diseño, utilidades y productividad.', Setting::get('seo_meta_description'));
        $this->assertEquals('mac apps, descargar mac, software mac, utilidades', Setting::get('seo_keywords'));

        // Verify public home page reflects updated SEO meta
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Portal de Descargas Mac Pro - 2026');
        $homeResponse->assertSee('Encuentra las mejores aplicaciones de diseño, utilidades y productividad.');
    }

    public function test_admin_can_upload_custom_logo_image(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $file = UploadedFile::fake()->image('custom_logo.png', 200, 50);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'logo_type' => 'image',
            'logo_image' => $file,
        ]);

        $response->assertRedirect(route('admin.settings'));

        $savedPath = Setting::get('logo_image');
        $this->assertNotEmpty($savedPath);
        $this->assertEquals('image', Setting::get('logo_type'));

        // Verify file stored
        $relativeDiskPath = str_replace('/storage/', '', $savedPath);
        Storage::disk('public')->assertExists($relativeDiskPath);
    }

    public function test_admin_can_fetch_r2_stats(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.settings.r2.stats'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'is_configured',
            'r2_enabled',
            'current_disk',
            'total_torrents',
            'in_r2',
            'in_local',
            'pending_external',
            'fallback_magnet',
        ]);
    }

    public function test_admin_can_toggle_r2_master_switch(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.r2.toggle'), [
            'enabled' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'r2_enabled' => true,
            'current_disk' => 'r2',
        ]);
        $this->assertEquals('1', Setting::get('r2_enabled'));
        $this->assertEquals('r2', Setting::get('torrentmac_storage_disk'));

        // Toggle OFF
        $responseOff = $this->actingAs($admin)->post(route('admin.settings.r2.toggle'), [
            'enabled' => false,
        ]);

        $responseOff->assertStatus(200);
        $responseOff->assertJson([
            'success' => true,
            'r2_enabled' => false,
            'current_disk' => 'local',
        ]);
        $this->assertEquals('0', Setting::get('r2_enabled'));
        $this->assertEquals('local', Setting::get('torrentmac_storage_disk'));
    }

    public function test_admin_cannot_migrate_r2_batch_when_unconfigured(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.r2.migrate-batch'), [
            'mode' => 'pending_only',
            'limit' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_admin_can_migrate_r2_batch_when_configured(): void
    {
        $admin = $this->createAdminUser();

        Setting::set('r2_account_id', 'test_account', 'storage');
        Setting::set('r2_access_key_id', 'test_key', 'storage');
        Setting::set('r2_secret_access_key', 'test_secret', 'storage');
        Setting::set('r2_bucket', 'test_bucket', 'storage');

        $response = $this->actingAs($admin)->post(route('admin.settings.r2.migrate-batch'), [
            'mode' => 'pending_only',
            'limit' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'processed',
            'count',
            'remaining',
            'done',
        ]);
    }

    public function test_admin_can_update_app_seo_patterns(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'seo_app_title_pattern' => '{title} for {category} - {site_name}',
            'seo_app_description_pattern' => '{title} for {category}. Safe, clean and verified software for {category}.',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $this->assertEquals('{title} for {category} - {site_name}', Setting::get('seo_app_title_pattern'));
        $this->assertEquals('{title} for {category}. Safe, clean and verified software for {category}.', Setting::get('seo_app_description_pattern'));
    }

    public function test_application_resolves_seo_title_and_description_with_custom_pattern_and_avoids_duplicate_version(): void
    {
        Setting::set('site_name', 'PichoDescargas');
        Setting::set('seo_app_title_pattern', '{title} for {category} - {site_name}');
        Setting::set('seo_app_description_pattern', '{title} for {category}. Safe and verified.');

        $category = Category::create([
            'name' => 'Programas PC',
            'slug' => 'programas',
            'icon' => 'laptop',
            'order' => 1,
        ]);

        // Case 1: Name already contains version "NWinfo 1.6.7"
        $appWithVersionInName = Application::create([
            'category_id' => $category->id,
            'name' => 'NWinfo 1.6.7',
            'slug' => 'nwinfo-1-6-7',
            'version' => '1.6.7',
            'platform' => 'Windows 10/11',
            'short_description' => 'Herramienta de diagnóstico de hardware.',
            'published' => true,
        ]);

        // With pattern '{title} for {category} - {site_name}', should be clean without duplicate
        $title1 = $appWithVersionInName->resolveSeoTitle();
        $this->assertEquals('NWinfo 1.6.7 for Windows - PichoDescargas', $title1);

        // Case 2: Pattern with explicit {title} {version} should NOT duplicate version if name has it
        Setting::set('seo_app_title_pattern', '{title} {version} for {category} - {site_name}');
        $title2 = $appWithVersionInName->resolveSeoTitle();
        $this->assertEquals('NWinfo 1.6.7 for Windows - PichoDescargas', $title2);

        // Case 3: App name without version
        $appClean = Application::create([
            'category_id' => $category->id,
            'name' => 'NWinfo',
            'slug' => 'nwinfo',
            'version' => '1.6.7',
            'platform' => 'Windows',
            'published' => true,
        ]);
        $title3 = $appClean->resolveSeoTitle();
        $this->assertEquals('NWinfo 1.6.7 for Windows - PichoDescargas', $title3);

        // Verify public app-detail view renders the resolved title
        $response = $this->get(route('app', $appWithVersionInName->slug));
        $response->assertStatus(200);
        $response->assertSee('<title>NWinfo 1.6.7 for Windows - PichoDescargas</title>', false);
    }
}
