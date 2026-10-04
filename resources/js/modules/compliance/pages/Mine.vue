<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import compliance from '@/routes/compliance';
import CertificateList from '../components/CertificateList.vue';
import type { CertificateRow } from '../types';

defineProps<{
    certificates: CertificateRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My certificates', href: compliance.index() }],
    },
});
</script>

<template>
    <Head title="My certificates" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="My certificates"
            description="Your club admin keeps these up to date"
        />

        <p
            v-if="certificates.length === 0"
            class="text-sm text-muted-foreground"
        >
            No certificates recorded for you yet. Speak to your club admin.
        </p>
        <CertificateList v-else :certificates="certificates" />
    </div>
</template>
