<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Download, Upload } from '@lucide/vue';
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
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    exportUrl: string;
    importUrl: string;
    columnsHint: string;
}>();

const { t } = useTranslation();

const isImportOpen = ref(false);

const importForm = useForm<{ file: File | null }>({
    file: null,
});

const openImportDialog = () => {
    importForm.reset();
    importForm.clearErrors();
    isImportOpen.value = true;
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    importForm.file = target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(props.importUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isImportOpen.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <Button variant="outline" as-child>
            <a :href="exportUrl">
                <Download class="mr-2 size-4" />
                {{ t('common.export') }}
            </a>
        </Button>

        <Button variant="outline" @click="openImportDialog">
            <Upload class="mr-2 size-4" />
            {{ t('common.import') }}
        </Button>

        <Dialog v-model:open="isImportOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('common.import') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('common.import_columns') }}:
                        <code class="rounded bg-muted px-1 py-0.5 text-xs">
                            {{ columnsHint }}
                        </code>
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitImport">
                    <div class="grid gap-2">
                        <Label for="excel-import-file">
                            {{ t('common.import_file') }}
                        </Label>
                        <Input
                            id="excel-import-file"
                            type="file"
                            accept=".xlsx,.csv"
                            required
                            @change="onFileChange"
                        />
                        <InputError :message="importForm.errors.file" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isImportOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            :disabled="importForm.processing || !importForm.file"
                        >
                            {{
                                importForm.processing
                                    ? t('common.importing')
                                    : t('common.import')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
