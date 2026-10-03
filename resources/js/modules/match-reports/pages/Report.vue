<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import MatchReportController from '@/actions/Modules/MatchReports/Http/Controllers/MatchReportController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import matchReports from '@/routes/match-reports';
import { matchDate } from '../types';
import type { FixtureSummary, Result } from '../types';

type PlayerRow = {
    id: number;
    name: string;
    shirt_number: number | null;
    goals: number;
    rating: number | null;
};

const props = defineProps<{
    fixture: FixtureSummary;
    report: {
        goals_for: number;
        goals_against: number;
        summary: string | null;
        result: Result;
    } | null;
    players: PlayerRow[];
    can: { file: boolean; notPlayedYet: boolean };
}>();

const title = `${props.fixture.team} ${props.fixture.is_home ? 'v' : '@'} ${props.fixture.opponent}`;

setLayoutProps({
    breadcrumbs: [
        { title: 'Match reports', href: matchReports.index() },
        { title, href: matchReports.show(props.fixture.id) },
    ],
});
</script>

<template>
    <Head :title="`Report: ${title}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            :title="title"
            :description="matchDate.format(new Date(fixture.kick_off_at))"
        />

        <p v-if="can.notPlayedYet" class="text-sm text-muted-foreground">
            This fixture has not kicked off yet. Reports can be filed once it
            has.
        </p>

        <Form
            v-else-if="can.file"
            v-bind="MatchReportController.update.form(fixture.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-4 sm:max-w-xs">
                <div class="grid gap-2">
                    <Label for="goals_for">{{ fixture.team }}</Label>
                    <Input
                        id="goals_for"
                        name="goals_for"
                        type="number"
                        min="0"
                        :default-value="report?.goals_for ?? 0"
                        required
                    />
                    <InputError :message="errors.goals_for" />
                </div>
                <div class="grid gap-2">
                    <Label for="goals_against">{{ fixture.opponent }}</Label>
                    <Input
                        id="goals_against"
                        name="goals_against"
                        type="number"
                        min="0"
                        :default-value="report?.goals_against ?? 0"
                        required
                    />
                    <InputError :message="errors.goals_against" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="summary">Summary</Label>
                <textarea
                    id="summary"
                    name="summary"
                    rows="3"
                    class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    :value="report?.summary ?? ''"
                />
                <InputError :message="errors.summary" />
            </div>

            <fieldset class="grid gap-2">
                <legend class="mb-2 text-sm font-medium">
                    Goals and ratings (1 to 10)
                </legend>
                <InputError :message="errors.performances" />
                <div
                    v-for="(player, index) in players"
                    :key="player.id"
                    class="grid grid-cols-[1fr_5rem_5rem] items-center gap-2 text-sm"
                >
                    <input
                        type="hidden"
                        :name="`performances[${index}][player_id]`"
                        :value="player.id"
                    />
                    <span>
                        <span class="text-muted-foreground">
                            {{ player.shirt_number ?? '' }}
                        </span>
                        {{ player.name }}
                    </span>
                    <Input
                        :name="`performances[${index}][goals]`"
                        type="number"
                        min="0"
                        :default-value="player.goals"
                        aria-label="Goals"
                    />
                    <Input
                        :name="`performances[${index}][rating]`"
                        type="number"
                        min="1"
                        max="10"
                        :default-value="player.rating ?? undefined"
                        aria-label="Rating"
                    />
                </div>
            </fieldset>

            <Button :disabled="processing">Save report</Button>
        </Form>

        <template v-else-if="report">
            <p class="text-2xl font-semibold tabular-nums">
                {{ report.goals_for }} - {{ report.goals_against }}
            </p>
            <p v-if="report.summary" class="text-sm">{{ report.summary }}</p>
            <ul class="divide-y rounded-xl border text-sm">
                <li
                    v-for="player in players"
                    :key="player.id"
                    class="flex justify-between p-3"
                >
                    <span>{{ player.name }}</span>
                    <span class="text-muted-foreground">
                        {{ player.goals }} goals, rating
                        {{ player.rating ?? '-' }}
                    </span>
                </li>
            </ul>
        </template>

        <p v-else class="text-sm text-muted-foreground">No report filed yet.</p>
    </div>
</template>
