<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FixtureController from '@/actions/Modules/Fixtures/Http/Controllers/FixtureController';
import { Badge } from '@/components/ui/badge';
import type { FixtureRow } from '../types';

defineProps<{
    fixtures: FixtureRow[];
    empty: string;
}>();

const kickOff = new Intl.DateTimeFormat('en-GB', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});
</script>

<template>
    <p v-if="fixtures.length === 0" class="text-sm text-muted-foreground">
        {{ empty }}
    </p>

    <ul v-else class="divide-y rounded-xl border text-sm">
        <li
            v-for="fixture in fixtures"
            :key="fixture.id"
            class="flex flex-col gap-1 p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="grid gap-1">
                <span class="font-medium">
                    {{ fixture.team }}
                    {{ fixture.is_home ? 'v' : '@' }}
                    {{ fixture.opponent }}
                </span>
                <span class="text-muted-foreground">
                    <time :datetime="fixture.kick_off_at">
                        {{ kickOff.format(new Date(fixture.kick_off_at)) }}
                    </time>
                    <template v-if="fixture.venue">
                        at {{ fixture.venue }}</template
                    >
                </span>
            </div>
            <div class="flex items-center gap-2">
                <Badge v-if="fixture.competition" variant="secondary">
                    {{ fixture.competition }}
                </Badge>
                <Badge variant="outline">
                    {{ fixture.is_home ? 'Home' : 'Away' }}
                </Badge>
                <Link
                    v-if="fixture.can_update"
                    :href="FixtureController.edit(fixture.id)"
                    class="text-sm underline underline-offset-4"
                >
                    Edit
                </Link>
            </div>
        </li>
    </ul>
</template>
