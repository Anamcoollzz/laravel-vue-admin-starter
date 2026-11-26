<script setup lang="ts">
import { Form, Head, Link, usePage } from "@inertiajs/vue3";
import {
    AtSign,
    Briefcase,
    Camera,
    Code,
    Trash2,
    Upload,
    Users,
} from "@lucide/vue";
import type { Component } from "vue";
import { computed, ref } from "vue";
import ProfileController from "@/actions/App/Http/Controllers/Settings/ProfileController";
import DeleteUser from "@/components/DeleteUser.vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useInitials } from "@/composables/useInitials";
import { edit } from "@/routes/profile";
import { send } from "@/routes/verification";
import type { User } from "@/types";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "Profile settings",
                href: edit(),
            },
        ],
    },
});

type SocialField = {
    name: keyof Pick<
        User,
        | "github_url"
        | "linkedin_url"
        | "facebook_url"
        | "instagram_url"
        | "x_url"
    >;
    label: string;
    placeholder: string;
    icon: Component;
};

const page = usePage();
const user = computed(() => page.props.auth.user as User);
const { getInitials } = useInitials();

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const removeAvatar = ref(false);

const displayedAvatar = computed(() => {
    if (avatarPreview.value) {
        return avatarPreview.value;
    }

    return removeAvatar.value ? null : (user.value.avatar ?? null);
});

const socialFields: SocialField[] = [
    {
        name: "github_url",
        label: "GitHub",
        placeholder: "https://github.com/username",
        icon: Code,
    },
    {
        name: "linkedin_url",
        label: "LinkedIn",
        placeholder: "https://linkedin.com/in/username",
        icon: Briefcase,
    },
    {
        name: "facebook_url",
        label: "Facebook",
        placeholder: "https://facebook.com/username",
        icon: Users,
    },
    {
        name: "instagram_url",
        label: "Instagram",
        placeholder: "https://instagram.com/username",
        icon: Camera,
    },
    {
        name: "x_url",
        label: "X (Twitter)",
        placeholder: "https://x.com/username",
        icon: AtSign,
    },
];

const onAvatarChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    avatarPreview.value = URL.createObjectURL(file);
    removeAvatar.value = false;
};

const onRemoveAvatar = () => {
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    avatarPreview.value = null;
    removeAvatar.value = true;

    if (avatarInput.value) {
        avatarInput.value.value = "";
    }
};

const onSaved = () => {
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    avatarPreview.value = null;
    removeAvatar.value = false;

    if (avatarInput.value) {
        avatarInput.value.value = "";
    }
};
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your avatar, name, email address, and social links"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing }"
            @success="onSaved"
        >
            <div class="grid gap-2">
                <Label for="avatar">Avatar</Label>
                <div class="flex items-center gap-4">
                    <Avatar class="size-20 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="displayedAvatar"
                            :src="displayedAvatar"
                            :alt="user.name"
                        />
                        <AvatarFallback class="rounded-full text-lg">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="avatarInput?.click()"
                            >
                                <Upload class="mr-1.5 size-4" />
                                Upload photo
                            </Button>
                            <Button
                                v-if="displayedAvatar"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="onRemoveAvatar"
                            >
                                <Trash2 class="mr-1.5 size-4" />
                                Remove
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            JPG, PNG, or WEBP. Max 2 MB.
                        </p>
                    </div>
                </div>

                <input
                    id="avatar"
                    ref="avatarInput"
                    type="file"
                    name="avatar"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onAvatarChange"
                />
                <input
                    v-if="removeAvatar"
                    type="hidden"
                    name="remove_avatar"
                    value="1"
                />
                <InputError class="mt-2" :message="errors.avatar" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="space-y-4 border-t border-border pt-6">
                <div>
                    <h3 class="text-sm font-medium">Social links</h3>
                    <p class="text-sm text-muted-foreground">
                        Add links to your social media profiles (optional).
                    </p>
                </div>

                <div
                    v-for="field in socialFields"
                    :key="field.name"
                    class="grid gap-2"
                >
                    <Label :for="field.name">{{ field.label }}</Label>
                    <div class="relative">
                        <component
                            :is="field.icon"
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            :id="field.name"
                            type="url"
                            class="pl-9"
                            :name="field.name"
                            :default-value="user[field.name] ?? ''"
                            :placeholder="field.placeholder"
                        />
                    </div>
                    <InputError :message="errors[field.name]" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
