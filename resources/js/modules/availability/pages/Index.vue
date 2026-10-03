<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import availability from '@/routes/availability';
import { kickOff } from '../types';
import type { FixtureSummary, Status } from '../types';

defineProps<{
    fixtures: (FixtureSummary & {
        squad: number;
        counts: Record<Status, number>;
    })[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Availability', href: availability.index() }],
    },
});

function noReply(fixture: {
    squad: number;
    counts: Record<Status, number>;
}): number {
    return Math.max(
        0,
        fixture.squad -
            fixture.counts.available -
            fixture.counts.maybe -
            fixture.counts.unavailable,
    );
}
</script>

<template>
    <Head title="Availability" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Availability"
            description="Who can make the next matches"
        />

        <p v-if="fixtures.length === 0" class="text-sm text-muted-foreground">
            No upcoming fixtures for your teams.
        </p>

        <ul v-else class="divide-y rounded-xl border text-sm">
            <li v-for="fixture in fixtures" :key="fixture.id">
                <Link
                    :href="availability.show(fixture.id)"
                    class="flex flex-col gap-1 p-3 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span class="grid gap-1">
                        <span class="font-medium">
                            {{ fixture.team }}
                            {{ fixture.is_home ? 'v' : '@' }}
                            {{ fixture.opponent }}
                        </span>
                        <time
                            :datetime="fixture.kick_off_at"
                            class="text-muted-foreground"
                        >
                            {{ kickOff.format(new Date(fixture.kick_off_at)) }}
                        </time>
                    </span>
                    <span class="flex gap-3 text-muted-foreground">
                        <span>{{ fixture.counts.available }} available</span>
                        <span>{{ fixture.counts.maybe }} maybe</span>
                        <span>{{ fixture.counts.unavailable }} out</span>
                        <span>{{ noReply(fixture) }} no reply</span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
