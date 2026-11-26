<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingController extends Controller
{
    /**
     * Display the grouped application settings page (General, SMTP, and Privacy Policy).
     */
    public function index(): Response
    {
        $general = Setting::getGroup('general');
        $smtp = Setting::getGroup('smtp');
        $privacy = Setting::getGroup('privacy');
        $security = Setting::getGroup('security');

        return Inertia::render('app-settings/Index', [
            'general' => [
                'app_name' => $general['app_name'] ?? (string) config('app.name'),
                'app_logo' => $general['app_logo'] ?? null,
                'app_favicon' => $general['app_favicon'] ?? null,
                'country' => $general['country'] ?? '',
                'city' => $general['city'] ?? '',
                'year' => $general['year'] ?? (string) now()->year,
            ],
            'smtp' => [
                'smtp_host' => $smtp['smtp_host'] ?? '',
                'smtp_port' => $smtp['smtp_port'] ?? '587',
                'smtp_username' => $smtp['smtp_username'] ?? '',
                'smtp_has_password' => ! empty($smtp['smtp_password']),
                'smtp_encryption' => $smtp['smtp_encryption'] ?? 'tls',
                'smtp_from_address' => $smtp['smtp_from_address'] ?? '',
                'smtp_from_name' => $smtp['smtp_from_name'] ?? (string) config('app.name'),
            ],
            'privacy' => [
                'privacy_policy' => $privacy['privacy_policy'] ?? '',
            ],
            'security' => [
                'max_login_attempts' => (int) ($security['security_max_login_attempts'] ?? 5),
                'lockout_minutes' => (int) ($security['security_lockout_minutes'] ?? 1),
                'password_min_length' => (int) ($security['security_password_min_length'] ?? 8),
                'password_mixed_case' => ($security['security_password_mixed_case'] ?? '0') === '1',
                'password_numbers' => ($security['security_password_numbers'] ?? '0') === '1',
                'password_symbols' => ($security['security_password_symbols'] ?? '0') === '1',
                'password_uncompromised' => ($security['security_password_uncompromised'] ?? '0') === '1',
                'session_lifetime' => (int) ($security['security_session_lifetime'] ?? config('session.lifetime', 120)),
            ],
        ]);
    }

    /**
     * Update general application settings including name, location, year, logo, and favicon.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'digits:4'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg,jpg,jpeg,webp', 'max:1024'],
        ]);

        Setting::setValue('general', 'app_name', $validated['app_name']);
        Setting::setValue('general', 'country', $validated['country'] ?? '');
        Setting::setValue('general', 'city', $validated['city'] ?? '');
        Setting::setValue('general', 'year', $validated['year'] ?? (string) now()->year);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            if (is_string($path)) {
                $this->deleteStoredFile(Setting::getValue('app_logo'));
                Setting::setValue('general', 'app_logo', '/storage/'.ltrim($path, '/'));
            }
        }

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('settings', 'public');
            if (is_string($path)) {
                $this->deleteStoredFile(Setting::getValue('app_favicon'));
                Setting::setValue('general', 'app_favicon', '/storage/'.ltrim($path, '/'));
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General settings updated.')]);

        return back();
    }

    /**
     * Update SMTP mail server settings, preserving the existing password when left blank.
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::setValue('smtp', 'smtp_host', $validated['smtp_host'] ?? '');
        Setting::setValue('smtp', 'smtp_port', isset($validated['smtp_port']) ? (string) $validated['smtp_port'] : '');
        Setting::setValue('smtp', 'smtp_username', $validated['smtp_username'] ?? '');
        Setting::setValue('smtp', 'smtp_encryption', $validated['smtp_encryption'] ?? 'tls');
        Setting::setValue('smtp', 'smtp_from_address', $validated['smtp_from_address'] ?? '');
        Setting::setValue('smtp', 'smtp_from_name', $validated['smtp_from_name'] ?? '');

        if (! empty($validated['smtp_password'])) {
            Setting::setValue('smtp', 'smtp_password', $validated['smtp_password']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SMTP settings updated.')]);

        return back();
    }

    /**
     * Update the application privacy policy content.
     */
    public function updatePrivacy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'privacy_policy' => ['nullable', 'string'],
        ]);

        Setting::setValue('privacy', 'privacy_policy', $validated['privacy_policy'] ?? '');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Privacy policy updated.')]);

        return back();
    }

    /**
     * Update security settings including login throttling, password policy, and session lifetime.
     */
    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_login_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'password_min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'password_mixed_case' => ['boolean'],
            'password_numbers' => ['boolean'],
            'password_symbols' => ['boolean'],
            'password_uncompromised' => ['boolean'],
            'session_lifetime' => ['required', 'integer', 'min:5', 'max:43200'],
        ]);

        Setting::setValue('security', 'security_max_login_attempts', (string) $validated['max_login_attempts']);
        Setting::setValue('security', 'security_lockout_minutes', (string) $validated['lockout_minutes']);
        Setting::setValue('security', 'security_password_min_length', (string) $validated['password_min_length']);
        Setting::setValue('security', 'security_password_mixed_case', ! empty($validated['password_mixed_case']) ? '1' : '0');
        Setting::setValue('security', 'security_password_numbers', ! empty($validated['password_numbers']) ? '1' : '0');
        Setting::setValue('security', 'security_password_symbols', ! empty($validated['password_symbols']) ? '1' : '0');
        Setting::setValue('security', 'security_password_uncompromised', ! empty($validated['password_uncompromised']) ? '1' : '0');
        Setting::setValue('security', 'security_session_lifetime', (string) $validated['session_lifetime']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Security settings updated.')]);

        return back();
    }

    /**
     * Remove a previously uploaded settings asset from the public disk if it exists.
     */
    private function deleteStoredFile(?string $publicUrl): void
    {
        if (! $publicUrl || ! str_starts_with($publicUrl, '/storage/')) {
            return;
        }

        $relativePath = substr($publicUrl, strlen('/storage/'));

        if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
