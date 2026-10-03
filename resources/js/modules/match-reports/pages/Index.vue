<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import matchReports from '@/routes/match-reports';
import { matchDate } from '../types';
import type { FixtureSummary, Result } from '../types';

defineProps<{
    results: (FixtureSummary & {
        report: {
            goals_for: number;
            goals_against: number;
            result: Result;
        } | null;
    })[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Match reports', href: matchReports.index() }],
    },
});
</script>

<template>
    <Head title="Match reports" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Match reports"
            description="Played fixtures for your teams, newest first"
        />

        <p v-if="results.length === 0" class="text-sm text-muted-foreground">
            No matches played yet.
        </p>

        <ul v-else class="divide-y rounded-xl border text-sm">
            <li v-for="match in results" :key="match.id">
                <Link
                    :href="matchReports.show(match.id)"
                    class="flex items-center justify-between gap-4 p-3 hover:bg-muted/50"
                >
                    <span class="grid gap-1">
                        <span class="font-medium">
                            {{ match.team }}
                            {{ match.is_home ? 'v' : '@' }}
                            {{ match.opponent }}
                        </span>
                        <span class="text-muted-foreground">
                            {{ matchDate.format(new Date(match.kick_off_at)) }}
                        </span>
                    </span>
                    <span v-if="match.report" class="flex items-center gap-2">
                        <span class="font-medium tabular-nums">
                            {{ match.report.goals_for }} -
                            {{ match.report.goals_against }}
                        </span>
                        <Badge variant="secondary">{{
                            match.report.result
                        }}</Badge>
                    </span>
                    <Badge v-else variant="outline">No report</Badge>
                </Link>
            </li>
        </ul>
    </div>
</template>
