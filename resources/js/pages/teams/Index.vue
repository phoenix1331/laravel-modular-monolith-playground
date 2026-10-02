<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Team = {
    id: number;
    name: string;
    age_group: string | null;
    players_count: number;
};

defineProps<{
    teams: Team[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Teams', href: TeamController.index() }],
    },
});
</script>

<template>
    <Head title="Teams" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Teams" description="Squads in your club" />
            <Button v-if="can.create" as-child>
                <Link :href="TeamController.create()">New team</Link>
            </Button>
        </div>

        <p v-if="teams.length === 0" class="text-sm text-muted-foreground">
            No teams to show yet.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li v-for="team in teams" :key="team.id">
                <Link
                    :href="TeamController.show(team.id)"
                    class="flex items-center justify-between gap-4 p-4 hover:bg-muted/50"
                >
                    <span class="font-medium">{{ team.name }}</span>
                    <span class="flex items-center gap-2">
                        <Badge v-if="team.age_group" variant="secondary">
                            {{ team.age_group }}
                        </Badge>
                        <span class="text-sm text-muted-foreground">
                            {{ team.players_count }} players
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
