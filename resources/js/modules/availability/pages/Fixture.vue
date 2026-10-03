<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import availability from '@/routes/availability';
import { kickOff, statuses } from '../types';
import type { FixtureSummary, Status } from '../types';

type Player = {
    id: number;
    name: string;
    shirt_number: number | null;
    status: Status | null;
    note: string | null;
};

const props = defineProps<{
    fixture: FixtureSummary;
    players: Player[];
    can: { mark: boolean };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Availability', href: availability.index() },
        {
            title: `${props.fixture.team} v ${props.fixture.opponent}`,
            href: availability.show(props.fixture.id),
        },
    ],
});

function mark(player: Player, status: Status): void {
    router.put(
        availability.update.url({
            fixture: props.fixture.id,
            player: player.id,
        }),
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Availability: ${fixture.team} v ${fixture.opponent}`" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            :title="`${fixture.team} ${fixture.is_home ? 'v' : '@'} ${fixture.opponent}`"
            :description="kickOff.format(new Date(fixture.kick_off_at))"
        />

        <p v-if="players.length === 0" class="text-sm text-muted-foreground">
            This team has no players yet.
        </p>

        <ul v-else class="divide-y rounded-xl border text-sm">
            <li
                v-for="player in players"
                :key="player.id"
                class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="flex items-center gap-3">
                    <span class="w-6 text-right text-muted-foreground">
                        {{ player.shirt_number ?? '' }}
                    </span>
                    <span class="font-medium">{{ player.name }}</span>
                </span>

                <span v-if="can.mark" class="flex gap-1">
                    <Button
                        v-for="option in statuses"
                        :key="option.value"
                        size="sm"
                        :variant="
                            player.status === option.value
                                ? 'default'
                                : 'outline'
                        "
                        @click="mark(player, option.value)"
                    >
                        {{ option.label }}
                    </Button>
                </span>
                <span v-else class="text-muted-foreground">
                    {{
                        statuses.find(
                            (option) => option.value === player.status,
                        )?.label ?? 'No reply'
                    }}
                </span>
            </li>
        </ul>
    </div>
</template>
