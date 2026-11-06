<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:setup {--clear-posts : Borrar todos los posts de aplicaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar credenciales de administrador y opcionalmente limpiar posts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = 'itzcarlosandres@gmail.com';
        $password = 'Carlos1995*';

        $user = User::where('email', $email)->first() ?? User::first();

        if ($user) {
            $user->update([
                'name' => 'Carlos',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->info("Usuario existente actualizado: {$email}");
        } else {
            User::create([
                'name' => 'Carlos',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->info("Nuevo usuario administrador creado: {$email}");
        }

        Setting::firstOrCreate(['key' => 'site_name'], ['value' => 'Picho', 'group' => 'general']);
        Setting::firstOrCreate(['key' => 'site_name_highlight'], ['value' => 'Descargas', 'group' => 'general']);
        Setting::firstOrCreate(['key' => 'seo_meta_title'], ['value' => 'PichoDescargas - Descarga Aplicaciones, Juegos y Software', 'group' => 'seo']);
        Setting::firstOrCreate(['key' => 'seo_meta_description'], ['value' => 'Descubre y descarga las mejores aplicaciones y juegos para Windows, Mac, Android y más. Rápido, seguro y verificado.', 'group' => 'seo']);
        Setting::firstOrCreate(['key' => 'seo_keywords'], ['value' => 'pichodescargas, descargas, aplicaciones pc, programas windows, juegos pc, mac apps, android apk, utilidades', 'group' => 'seo']);
        Setting::firstOrCreate(['key' => 'seo_app_title_pattern'], ['value' => '{title} for {category} - {site_name}', 'group' => 'seo']);
        Setting::firstOrCreate(['key' => 'seo_app_description_pattern'], ['value' => '{title} for {category}. Safe, clean and verified software for {category}.', 'group' => 'seo']);
        Setting::firstOrCreate(['key' => 'contact_email'], ['value' => $email, 'group' => 'general']);

        Category::firstOrCreate(
            ['slug' => 'peliculas-gratis'],
            [
                'name' => 'Free Movies',
                'description' => 'Películas, estrenos y series en Full HD',
                'icon' => 'peliculas-gratis',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        if ($this->option('clear-posts')) {
            $count = Application::count();
            Application::query()->delete();
            $this->warn("Se eliminaron {$count} publicaciones de aplicaciones.");
        }

        cache()->flush();
        $this->info('Caché del sistema vaciada exitosamente.');
        $this->newLine();
        $this->info('=== CREDENCIALES CONFIGURADAS ===');
        $this->line("Email:    <fg=cyan>{$email}</>");
        $this->line("Password: <fg=cyan>{$password}</>");

        return self::SUCCESS;
    }
}
