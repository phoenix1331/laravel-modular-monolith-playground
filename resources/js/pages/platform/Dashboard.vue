<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import platform from '@/routes/platform';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Platform',
                href: platform.dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Platform" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <Heading
            title="Platform"
            description="Every organisation on the platform, and the modules each one has enabled."
        />
    </div>
</template>
