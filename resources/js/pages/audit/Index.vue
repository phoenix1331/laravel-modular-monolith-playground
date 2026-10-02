<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Change = { from: unknown; to: unknown };

type Entry = {
    id: number;
    created_at: string;
    action: string;
    action_label: string;
    subject_type: string;
    subject_id: string | null;
    subject_label: string | null;
    changes: Record<string, Change> | null;
    user: string | null;
    organisation: string | null;
    ip_address: string | null;
};

defineProps<{
    entries: {
        data: Entry[];
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        last_page: number;
    };
    showOrganisation: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit log', href: '' }],
    },
});

const dateFormat = new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function display(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return 'empty';
    }

    return String(value);
}
</script>

<template>
    <Head title="Audit log" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Audit log"
            description="Every change recorded automatically, newest first"
        />

        <p
            v-if="entries.data.length === 0"
            class="text-sm text-muted-foreground"
        >
            Nothing recorded yet.
        </p>

        <ul v-else class="divide-y rounded-xl border text-sm">
            <li
                v-for="entry in entries.data"
                :key="entry.id"
                class="grid gap-1 p-3"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{{ entry.action_label }}</Badge>
                    <span class="font-medium">
                        {{ entry.subject_type }}
                        <template v-if="entry.subject_label">
                            {{ entry.subject_label }}
                        </template>
                        <template v-else-if="entry.subject_id">
                            #{{ entry.subject_id }}
                        </template>
                    </span>
                    <span class="text-muted-foreground">
                        by {{ entry.user ?? 'system' }}
                    </span>
                    <Badge
                        v-if="showOrganisation"
                        variant="outline"
                        class="ml-auto"
                    >
                        {{ entry.organisation ?? 'Platform' }}
                    </Badge>
                    <time
                        :datetime="entry.created_at"
                        class="text-muted-foreground"
                        :class="{ 'ml-auto': !showOrganisation }"
                    >
                        {{ dateFormat.format(new Date(entry.created_at)) }}
                    </time>
                </div>

                <ul v-if="entry.changes" class="ml-1 text-muted-foreground">
                    <li v-for="(change, field) in entry.changes" :key="field">
                        {{ field }}: {{ display(change.from) }} →
                        {{ display(change.to) }}
                    </li>
                </ul>
            </li>
        </ul>

        <div v-if="entries.last_page > 1" class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                v-if="entries.prev_page_url"
                as-child
            >
                <Link :href="entries.prev_page_url" preserve-scroll
                    >Newer</Link
                >
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ entries.current_page }} of {{ entries.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                v-if="entries.next_page_url"
                as-child
            >
                <Link :href="entries.next_page_url" preserve-scroll
                    >Older</Link
                >
            </Button>
        </div>
    </div>
</template>
