<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Globe, Lock, Mail, Shield } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    general: {
        app_name: string;
        app_logo: string | null;
        app_favicon: string | null;
        country: string;
        city: string;
        year: string;
    };
    smtp: {
        smtp_host: string;
        smtp_port: string;
        smtp_username: string;
        smtp_has_password: boolean;
        smtp_encryption: string;
        smtp_from_address: string;
        smtp_from_name: string;
    };
    privacy: {
        privacy_policy: string;
    };
    security: {
        max_login_attempts: number;
        lockout_minutes: number;
        password_min_length: number;
        password_mixed_case: boolean;
        password_numbers: boolean;
        password_symbols: boolean;
        password_uncompromised: boolean;
        session_lifetime: number;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'App Settings',
                href: '/app/app-settings',
            },
        ],
    },
});

const activeGroup = ref<'general' | 'smtp' | 'privacy' | 'security'>('general');

// General form
const generalForm = useForm<{
    app_name: string;
    country: string;
    city: string;
    year: string;
    logo: File | null;
    favicon: File | null;
}>({
    app_name: props.general.app_name,
    country: props.general.country,
    city: props.general.city,
    year: props.general.year,
    logo: null,
    favicon: null,
});

const logoPreview = ref<string | null>(props.general.app_logo);
const faviconPreview = ref<string | null>(props.general.app_favicon);

const onLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    generalForm.logo = file;
    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const onFaviconChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    generalForm.favicon = file;
    if (file) {
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const submitGeneral = () => {
    generalForm.post('/app/app-settings/general', {
        forceFormData: true,
        preserveScroll: true,
    });
};

// SMTP form
const smtpForm = useForm({
    smtp_host: props.smtp.smtp_host,
    smtp_port: props.smtp.smtp_port,
    smtp_username: props.smtp.smtp_username,
    smtp_password: '',
    smtp_encryption: props.smtp.smtp_encryption || 'tls',
    smtp_from_address: props.smtp.smtp_from_address,
    smtp_from_name: props.smtp.smtp_from_name,
});

const submitSmtp = () => {
    smtpForm.put('/app/app-settings/smtp', {
        preserveScroll: true,
        onSuccess: () => {
            smtpForm.reset('smtp_password');
        },
    });
};

// Privacy Policy form
const privacyForm = useForm({
    privacy_policy: props.privacy.privacy_policy,
});

const submitPrivacy = () => {
    privacyForm.put('/app/app-settings/privacy', {
        preserveScroll: true,
    });
};

// Security form
const securityForm = useForm({
    max_login_attempts: props.security.max_login_attempts,
    lockout_minutes: props.security.lockout_minutes,
    password_min_length: props.security.password_min_length,
    password_mixed_case: props.security.password_mixed_case,
    password_numbers: props.security.password_numbers,
    password_symbols: props.security.password_symbols,
    password_uncompromised: props.security.password_uncompromised,
    session_lifetime: props.security.session_lifetime,
});

const submitSecurity = () => {
    securityForm.put('/app/app-settings/security', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('app_settings.title')" />

        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ t('app_settings.title') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ t('app_settings.description') }}
            </p>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <!-- Group Navigation -->
            <aside class="w-full shrink-0 lg:w-56">
                <nav class="flex flex-row gap-1 lg:flex-col">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                        :class="
                            activeGroup === 'general'
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="activeGroup = 'general'"
                    >
                        <Globe class="size-4 shrink-0" />
                        <span>{{ t('app_settings.group_general') }}</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                        :class="
                            activeGroup === 'smtp'
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="activeGroup = 'smtp'"
                    >
                        <Mail class="size-4 shrink-0" />
                        <span>{{ t('app_settings.group_smtp') }}</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                        :class="
                            activeGroup === 'privacy'
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="activeGroup = 'privacy'"
                    >
                        <Shield class="size-4 shrink-0" />
                        <span>{{ t('app_settings.group_privacy') }}</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                        :class="
                            activeGroup === 'security'
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="activeGroup = 'security'"
                    >
                        <Lock class="size-4 shrink-0" />
                        <span>{{ t('app_settings.group_security') }}</span>
                    </button>
                </nav>
            </aside>

            <!-- Group Content -->
            <div class="flex-1">
                <!-- General Settings -->
                <form
                    v-if="activeGroup === 'general'"
                    class="max-w-2xl space-y-5 rounded-xl border border-border bg-card p-6"
                    @submit.prevent="submitGeneral"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('app_settings.group_general') }}
                    </h2>

                    <div class="grid gap-2">
                        <Label for="app-name">
                            {{ t('app_settings.app_name') }}
                        </Label>
                        <Input
                            id="app-name"
                            v-model="generalForm.app_name"
                            type="text"
                            required
                        />
                        <InputError :message="generalForm.errors.app_name" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="app-logo">
                                {{ t('app_settings.logo') }}
                            </Label>
                            <div
                                v-if="logoPreview"
                                class="flex h-16 items-center rounded-lg border border-border bg-muted/30 p-2"
                            >
                                <img
                                    :src="logoPreview"
                                    alt="Logo"
                                    class="max-h-12 object-contain"
                                />
                            </div>
                            <Input
                                id="app-logo"
                                type="file"
                                accept="image/*"
                                @change="onLogoChange"
                            />
                            <InputError :message="generalForm.errors.logo" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="app-favicon">
                                {{ t('app_settings.favicon') }}
                            </Label>
                            <div
                                v-if="faviconPreview"
                                class="flex h-16 items-center rounded-lg border border-border bg-muted/30 p-2"
                            >
                                <img
                                    :src="faviconPreview"
                                    alt="Favicon"
                                    class="size-8 object-contain"
                                />
                            </div>
                            <Input
                                id="app-favicon"
                                type="file"
                                accept=".ico,.png,.svg,.jpg,.jpeg,.webp"
                                @change="onFaviconChange"
                            />
                            <InputError :message="generalForm.errors.favicon" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="app-country">
                                {{ t('app_settings.country') }}
                            </Label>
                            <Input
                                id="app-country"
                                v-model="generalForm.country"
                                type="text"
                                placeholder="Indonesia"
                            />
                            <InputError :message="generalForm.errors.country" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="app-city">
                                {{ t('app_settings.city') }}
                            </Label>
                            <Input
                                id="app-city"
                                v-model="generalForm.city"
                                type="text"
                                placeholder="Jakarta"
                            />
                            <InputError :message="generalForm.errors.city" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="app-year">
                                {{ t('app_settings.year') }}
                            </Label>
                            <Input
                                id="app-year"
                                v-model="generalForm.year"
                                type="text"
                                maxlength="4"
                                placeholder="2026"
                            />
                            <InputError :message="generalForm.errors.year" />
                        </div>
                    </div>

                    <div v-if="can('settings.edit')" class="pt-2">
                        <Button
                            type="submit"
                            :disabled="generalForm.processing"
                        >
                            {{
                                generalForm.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </div>
                </form>

                <!-- SMTP Settings -->
                <form
                    v-else-if="activeGroup === 'smtp'"
                    class="max-w-2xl space-y-5 rounded-xl border border-border bg-card p-6"
                    @submit.prevent="submitSmtp"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('app_settings.group_smtp') }}
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="smtp-host">
                                {{ t('app_settings.smtp_host') }}
                            </Label>
                            <Input
                                id="smtp-host"
                                v-model="smtpForm.smtp_host"
                                type="text"
                                placeholder="smtp.mailtrap.io"
                            />
                            <InputError :message="smtpForm.errors.smtp_host" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="smtp-port">
                                {{ t('app_settings.smtp_port') }}
                            </Label>
                            <Input
                                id="smtp-port"
                                v-model="smtpForm.smtp_port"
                                type="number"
                                placeholder="587"
                            />
                            <InputError :message="smtpForm.errors.smtp_port" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="smtp-username">
                                {{ t('app_settings.smtp_username') }}
                            </Label>
                            <Input
                                id="smtp-username"
                                v-model="smtpForm.smtp_username"
                                type="text"
                            />
                            <InputError
                                :message="smtpForm.errors.smtp_username"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="smtp-password">
                                {{ t('app_settings.smtp_password') }}
                            </Label>
                            <Input
                                id="smtp-password"
                                v-model="smtpForm.smtp_password"
                                type="password"
                                :placeholder="
                                    smtp.smtp_has_password
                                        ? t('app_settings.smtp_password_keep')
                                        : ''
                                "
                            />
                            <InputError
                                :message="smtpForm.errors.smtp_password"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="smtp-encryption">
                                {{ t('app_settings.smtp_encryption') }}
                            </Label>
                            <select
                                id="smtp-encryption"
                                v-model="smtpForm.smtp_encryption"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                            <InputError
                                :message="smtpForm.errors.smtp_encryption"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="smtp-from-address">
                                {{ t('app_settings.smtp_from_address') }}
                            </Label>
                            <Input
                                id="smtp-from-address"
                                v-model="smtpForm.smtp_from_address"
                                type="email"
                                placeholder="noreply@example.com"
                            />
                            <InputError
                                :message="smtpForm.errors.smtp_from_address"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="smtp-from-name">
                                {{ t('app_settings.smtp_from_name') }}
                            </Label>
                            <Input
                                id="smtp-from-name"
                                v-model="smtpForm.smtp_from_name"
                                type="text"
                            />
                            <InputError
                                :message="smtpForm.errors.smtp_from_name"
                            />
                        </div>
                    </div>

                    <div v-if="can('settings.edit')" class="pt-2">
                        <Button type="submit" :disabled="smtpForm.processing">
                            {{
                                smtpForm.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </div>
                </form>

                <!-- Privacy Policy -->
                <form
                    v-else-if="activeGroup === 'privacy'"
                    class="max-w-2xl space-y-5 rounded-xl border border-border bg-card p-6"
                    @submit.prevent="submitPrivacy"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('app_settings.group_privacy') }}
                    </h2>

                    <div class="grid gap-2">
                        <Label for="privacy-policy">
                            {{ t('app_settings.privacy_policy') }}
                        </Label>
                        <textarea
                            id="privacy-policy"
                            v-model="privacyForm.privacy_policy"
                            rows="12"
                            :placeholder="t('app_settings.privacy_placeholder')"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        />
                        <InputError
                            :message="privacyForm.errors.privacy_policy"
                        />
                    </div>

                    <div v-if="can('settings.edit')" class="pt-2">
                        <Button
                            type="submit"
                            :disabled="privacyForm.processing"
                        >
                            {{
                                privacyForm.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </div>
                </form>

                <!-- Security Settings -->
                <form
                    v-else
                    class="max-w-2xl space-y-5 rounded-xl border border-border bg-card p-6"
                    @submit.prevent="submitSecurity"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('app_settings.group_security') }}
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="sec-max-attempts">
                                {{ t('app_settings.max_login_attempts') }}
                            </Label>
                            <Input
                                id="sec-max-attempts"
                                v-model.number="securityForm.max_login_attempts"
                                type="number"
                                min="1"
                                max="100"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ t('app_settings.max_login_attempts_desc') }}
                            </p>
                            <InputError
                                :message="
                                    securityForm.errors.max_login_attempts
                                "
                            />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="sec-lockout">
                                {{ t('app_settings.lockout_minutes') }}
                            </Label>
                            <Input
                                id="sec-lockout"
                                v-model.number="securityForm.lockout_minutes"
                                type="number"
                                min="1"
                                max="1440"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ t('app_settings.lockout_minutes_desc') }}
                            </p>
                            <InputError
                                :message="securityForm.errors.lockout_minutes"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="sec-min-length">
                                {{ t('app_settings.password_min_length') }}
                            </Label>
                            <Input
                                id="sec-min-length"
                                v-model.number="
                                    securityForm.password_min_length
                                "
                                type="number"
                                min="6"
                                max="128"
                                required
                            />
                            <InputError
                                :message="
                                    securityForm.errors.password_min_length
                                "
                            />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="sec-session-lifetime">
                                {{ t('app_settings.session_lifetime') }}
                            </Label>
                            <Input
                                id="sec-session-lifetime"
                                v-model.number="securityForm.session_lifetime"
                                type="number"
                                min="5"
                                max="43200"
                                required
                            />
                            <InputError
                                :message="securityForm.errors.session_lifetime"
                            />
                        </div>
                    </div>

                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <label
                            class="flex cursor-pointer items-start gap-3 text-sm"
                        >
                            <input
                                v-model="securityForm.password_mixed_case"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input accent-primary"
                            />
                            <div>
                                <p class="font-medium">
                                    {{ t('app_settings.password_mixed_case') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t(
                                            'app_settings.password_mixed_case_desc',
                                        )
                                    }}
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 text-sm"
                        >
                            <input
                                v-model="securityForm.password_numbers"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input accent-primary"
                            />
                            <div>
                                <p class="font-medium">
                                    {{ t('app_settings.password_numbers') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t('app_settings.password_numbers_desc')
                                    }}
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 text-sm"
                        >
                            <input
                                v-model="securityForm.password_symbols"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input accent-primary"
                            />
                            <div>
                                <p class="font-medium">
                                    {{ t('app_settings.password_symbols') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t('app_settings.password_symbols_desc')
                                    }}
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 text-sm"
                        >
                            <input
                                v-model="securityForm.password_uncompromised"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input accent-primary"
                            />
                            <div>
                                <p class="font-medium">
                                    {{
                                        t('app_settings.password_uncompromised')
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t(
                                            'app_settings.password_uncompromised_desc',
                                        )
                                    }}
                                </p>
                            </div>
                        </label>
                    </div>

                    <div v-if="can('settings.edit')" class="pt-2">
                        <Button
                            type="submit"
                            :disabled="securityForm.processing"
                        >
                            {{
                                securityForm.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
