<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->applyDatabaseSettings();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Override runtime application and mail configuration using values stored in the settings table.
     */
    protected function applyDatabaseSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            /** @var array<string, string|null> $settings */
            $settings = Setting::query()->pluck('value', 'key')->all();

            if (! empty($settings['app_name'])) {
                config(['app.name' => $settings['app_name']]);
            }

            if (! empty($settings['app_logo'])) {
                config(['app.logo' => $settings['app_logo']]);
            }

            if (! empty($settings['app_favicon'])) {
                config(['app.favicon' => $settings['app_favicon']]);
            }

            if (! empty($settings['smtp_host'])) {
                $encryption = ($settings['smtp_encryption'] ?? 'tls') === 'none'
                    ? null
                    : ($settings['smtp_encryption'] ?? 'tls');

                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings['smtp_host'],
                    'mail.mailers.smtp.port' => ! empty($settings['smtp_port']) ? (int) $settings['smtp_port'] : 587,
                    'mail.mailers.smtp.username' => $settings['smtp_username'] ?? null,
                    'mail.mailers.smtp.encryption' => $encryption,
                ]);

                if (! empty($settings['smtp_password'])) {
                    config(['mail.mailers.smtp.password' => $settings['smtp_password']]);
                }
            }

            if (! empty($settings['smtp_from_address'])) {
                config(['mail.from.address' => $settings['smtp_from_address']]);
            }

            if (! empty($settings['smtp_from_name'])) {
                config(['mail.from.name' => $settings['smtp_from_name']]);
            }
        } catch (Throwable) {
            // Skip runtime overrides when the database is unavailable (e.g. during composer scripts).
        }
    }
}
