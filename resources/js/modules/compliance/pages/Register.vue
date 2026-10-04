<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import CertificateController from '@/actions/Modules/Compliance/Http/Controllers/CertificateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import compliance from '@/routes/compliance';
import CertificateList from '../components/CertificateList.vue';
import { statusLabel, statusVariant } from '../types';
import type { CertificateRow, Status } from '../types';

defineProps<{
    filter: 'attention' | 'expired' | 'expiring' | 'missing' | 'all';
    workers: {
        id: number;
        name: string;
        status: Status;
        certificates: CertificateRow[];
    }[];
    options: { workers: { id: number; name: string }[]; types: string[] };
    expiringWithinDays: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Compliance', href: compliance.index() }],
    },
});

const filters = [
    { value: 'attention', label: 'Needs attention' },
    { value: 'expired', label: 'Expired' },
    { value: 'expiring', label: 'Expiring' },
    { value: 'missing', label: 'Missing' },
    { value: 'all', label: 'Everyone' },
] as const;

function remove(certificate: CertificateRow): void {
    if (confirm(`Remove this ${certificate.type} certificate?`)) {
        router.delete(CertificateController.destroy.url(certificate.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Compliance" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Compliance"
            :description="`Coaches' certificates. Expiring means within ${expiringWithinDays} days.`"
        />

        <nav class="flex flex-wrap gap-2">
            <Button
                v-for="option in filters"
                :key="option.value"
                size="sm"
                :variant="filter === option.value ? 'default' : 'outline'"
                as-child
            >
                <Link
                    :href="
                        compliance.index({ query: { status: option.value } })
                    "
                    preserve-scroll
                    >{{ option.label }}</Link
                >
            </Button>
        </nav>

        <p v-if="workers.length === 0" class="text-sm text-muted-foreground">
            Nobody here. Every coach is up to date.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="worker in workers"
                :key="worker.id"
                class="grid gap-2 p-3"
            >
                <div class="flex items-center gap-2">
                    <span class="font-medium">{{ worker.name }}</span>
                    <Badge :variant="statusVariant[worker.status]">
                        {{ statusLabel[worker.status] }}
                    </Badge>
                </div>
                <CertificateList
                    v-if="worker.certificates.length"
                    :certificates="worker.certificates"
                >
                    <template #default="{ certificate }">
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="remove(certificate)"
                            >Remove</Button
                        >
                    </template>
                </CertificateList>
            </li>
        </ul>

        <Form
            v-bind="CertificateController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <h2 class="text-sm font-medium">Record a certificate</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="user_id">Coach</Label>
                    <NativeSelect id="user_id" name="user_id">
                        <option
                            v-for="worker in options.workers"
                            :key="worker.id"
                            :value="worker.id"
                        >
                            {{ worker.name }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.user_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="type">Type</Label>
                    <NativeSelect id="type" name="type">
                        <option
                            v-for="type in options.types"
                            :key="type"
                            :value="type"
                        >
                            {{ type }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.type" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="reference">Reference</Label>
                    <Input id="reference" name="reference" />
                    <InputError :message="errors.reference" />
                </div>
                <div class="grid gap-2">
                    <Label for="issued_on">Issued</Label>
                    <Input
                        id="issued_on"
                        name="issued_on"
                        type="date"
                        required
                    />
                    <InputError :message="errors.issued_on" />
                </div>
                <div class="grid gap-2">
                    <Label for="expires_on">Expires</Label>
                    <Input
                        id="expires_on"
                        name="expires_on"
                        type="date"
                        required
                    />
                    <InputError :message="errors.expires_on" />
                </div>
            </div>
            <Button :disabled="processing" class="w-fit">Record</Button>
        </Form>
    </div>
</template>
