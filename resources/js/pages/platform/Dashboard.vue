<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import platform from '@/routes/platform';

type Organisation = {
    id: number;
    name: string;
    users_count: number;
    teams_count: number;
    enabled_modules: string[];
};

defineProps<{
    organisations: Organisation[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Platform', href: platform.dashboard() }],
    },
});
</script>

<template>
    <Head title="Platform" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Organisations"
            description="Every club on the platform and the modules each one has enabled"
        />

        <p
            v-if="organisations.length === 0"
            class="text-sm text-muted-foreground"
        >
            No organisations yet.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li v-for="organisation in organisations" :key="organisation.id">
                <Link
                    :href="platform.organisations.show(organisation.id)"
                    class="flex flex-col gap-2 p-4 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span class="font-medium">{{ organisation.name }}</span>
                    <span
                        class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span>{{ organisation.users_count }} users</span>
                        <span>{{ organisation.teams_count }} teams</span>
                        <Badge
                            v-for="module in organisation.enabled_modules"
                            :key="module"
                            variant="secondary"
                        >
                            {{ module }}
                        </Badge>
                        <span v-if="organisation.enabled_modules.length === 0">
                            Core only
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
