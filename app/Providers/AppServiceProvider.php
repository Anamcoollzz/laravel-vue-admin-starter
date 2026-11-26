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

            if (! empty($settings['security_max_login_attempts'])) {
                config(['security.max_login_attempts' => max(1, (int) $settings['security_max_login_attempts'])]);
            }

            if (! empty($settings['security_lockout_minutes'])) {
                config(['security.lockout_minutes' => max(1, (int) $settings['security_lockout_minutes'])]);
            }

            if (! empty($settings['security_session_lifetime'])) {
                config(['session.lifetime' => max(5, (int) $settings['security_session_lifetime'])]);
            }

            if (isset($settings['security_password_min_length'])) {
                $minLength = max(6, (int) $settings['security_password_min_length']);
                $mixedCase = ($settings['security_password_mixed_case'] ?? '0') === '1';
                $numbers = ($settings['security_password_numbers'] ?? '0') === '1';
                $symbols = ($settings['security_password_symbols'] ?? '0') === '1';
                $uncompromised = ($settings['security_password_uncompromised'] ?? '0') === '1';

                Password::defaults(function () use ($minLength, $mixedCase, $numbers, $symbols, $uncompromised): Password {
                    $rule = Password::min($minLength);

                    if ($mixedCase) {
                        $rule->mixedCase();
                    }

                    if ($numbers) {
                        $rule->numbers();
                    }

                    if ($symbols) {
                        $rule->symbols();
                    }

                    if ($uncompromised) {
                        $rule->uncompromised();
                    }

                    return $rule;
                });
            }
        } catch (Throwable) {
            // Skip runtime overrides when the database is unavailable (e.g. during composer scripts).
        }
    }
}
