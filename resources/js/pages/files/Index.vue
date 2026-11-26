<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    Download,
    ExternalLink,
    File as FileIcon,
    Folder,
    FolderPlus,
    Home,
    Trash2,
    Upload,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type DirectoryItem = {
    name: string;
    path: string;
};

type FileItem = {
    name: string;
    path: string;
    size: string;
    url: string;
    is_image: boolean;
    last_modified: string;
};

type BreadcrumbSegment = {
    name: string;
    path: string;
};

const props = defineProps<{
    currentPath: string;
    breadcrumbs: BreadcrumbSegment[];
    directories: DirectoryItem[];
    files: FileItem[];
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'File Manager',
                href: '/app/files',
            },
        ],
    },
});

// New folder dialog
const isFolderOpen = ref(false);
const folderForm = useForm({
    name: '',
    path: '',
});

const openFolderDialog = () => {
    folderForm.reset();
    folderForm.clearErrors();
    folderForm.path = props.currentPath;
    isFolderOpen.value = true;
};

const submitFolder = () => {
    folderForm.post('/app/files/folder', {
        preserveScroll: true,
        onSuccess: () => {
            isFolderOpen.value = false;
        },
    });
};

// Upload file dialog
const isUploadOpen = ref(false);
const uploadForm = useForm<{ file: File | null; path: string }>({
    file: null,
    path: '',
});

const openUploadDialog = () => {
    uploadForm.reset();
    uploadForm.clearErrors();
    uploadForm.path = props.currentPath;
    isUploadOpen.value = true;
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    uploadForm.file = target.files?.[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post('/app/files/upload', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isUploadOpen.value = false;
            uploadForm.reset();
        },
    });
};

const deleteItem = (path: string, type: 'file' | 'directory') => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete('/app/files', {
        data: { path, type },
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('files.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('files.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('files.description') }}
                </p>
            </div>

            <div
                v-if="can('files.create')"
                class="flex flex-wrap items-center gap-2"
            >
                <Button variant="outline" @click="openFolderDialog">
                    <FolderPlus class="mr-2 size-4" />
                    {{ t('files.new_folder') }}
                </Button>
                <Button @click="openUploadDialog">
                    <Upload class="mr-2 size-4" />
                    {{ t('files.upload') }}
                </Button>
            </div>
        </div>

        <!-- Path Breadcrumbs -->
        <nav
            class="flex flex-wrap items-center gap-1 rounded-lg border border-border bg-muted/30 px-3 py-2 text-sm"
            aria-label="Folder path"
        >
            <Link
                href="/app/files"
                class="inline-flex items-center gap-1 text-muted-foreground transition-colors hover:text-foreground"
            >
                <Home class="size-3.5" />
                {{ t('files.root') }}
            </Link>
            <template v-for="crumb in breadcrumbs" :key="crumb.path">
                <ChevronRight class="size-3.5 text-muted-foreground" />
                <Link
                    :href="`/app/files?path=${encodeURIComponent(crumb.path)}`"
                    class="font-medium text-foreground hover:underline"
                >
                    {{ crumb.name }}
                </Link>
            </template>
        </nav>

        <!-- Directories & Files Table -->
        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('common.name') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('files.size') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('files.modified') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="dir in directories"
                            :key="dir.path"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    :href="`/app/files?path=${encodeURIComponent(dir.path)}`"
                                    class="inline-flex items-center gap-2.5 hover:underline"
                                >
                                    <Folder
                                        class="size-5 shrink-0 text-amber-500"
                                    />
                                    <span>{{ dir.name }}</span>
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">-</td>
                            <td class="px-4 py-3 text-muted-foreground">-</td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    v-if="can('files.delete')"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    :title="t('common.delete')"
                                    @click="deleteItem(dir.path, 'directory')"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>

                        <tr
                            v-for="file in files"
                            :key="file.path"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <img
                                        v-if="file.is_image"
                                        :src="file.url"
                                        :alt="file.name"
                                        class="size-8 shrink-0 rounded border border-border object-cover"
                                    />
                                    <FileIcon
                                        v-else
                                        class="size-5 shrink-0 text-muted-foreground"
                                    />
                                    <span class="truncate font-medium">
                                        {{ file.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ file.size }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ file.last_modified }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <a
                                            :href="file.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <ExternalLink class="size-4" />
                                        </a>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <a
                                            :href="`/app/files/download?path=${encodeURIComponent(file.path)}`"
                                        >
                                            <Download class="size-4" />
                                        </a>
                                    </Button>
                                    <Button
                                        v-if="can('files.delete')"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="deleteItem(file.path, 'file')"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr
                            v-if="
                                directories.length === 0 && files.length === 0
                            "
                        >
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('files.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- New Folder Dialog -->
        <Dialog v-model:open="isFolderOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('files.new_folder') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('files.new_folder_desc') }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitFolder">
                    <div class="grid gap-2">
                        <Label for="folder-name">
                            {{ t('files.folder_name') }}
                        </Label>
                        <Input
                            id="folder-name"
                            v-model="folderForm.name"
                            type="text"
                            required
                        />
                        <InputError :message="folderForm.errors.name" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isFolderOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="folderForm.processing">
                            {{
                                folderForm.processing
                                    ? t('common.saving')
                                    : t('common.create')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Upload File Dialog -->
        <Dialog v-model:open="isUploadOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('files.upload') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('files.upload_desc') }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitUpload">
                    <div class="grid gap-2">
                        <Label for="upload-file-input">
                            {{ t('files.choose_file') }}
                        </Label>
                        <Input
                            id="upload-file-input"
                            type="file"
                            required
                            @change="onFileChange"
                        />
                        <InputError :message="uploadForm.errors.file" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isUploadOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            :disabled="uploadForm.processing || !uploadForm.file"
                        >
                            {{
                                uploadForm.processing
                                    ? t('files.uploading')
                                    : t('files.upload')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
