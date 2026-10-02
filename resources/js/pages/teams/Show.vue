<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import PlayerController from '@/actions/App/Http/Controllers/Teams/PlayerController';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type Player = {
    id: number;
    full_name: string;
    shirt_number: number | null;
    date_of_birth: string | null;
};

const props = defineProps<{
    team: { id: number; name: string; age_group: string | null };
    players: Player[];
    workers: { id: number; name: string }[];
    can: { update: boolean; delete: boolean; managePlayers: boolean };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Teams', href: TeamController.index() },
        { title: props.team.name, href: TeamController.show(props.team.id) },
    ],
});

function deleteTeam(): void {
    if (confirm(`Delete ${props.team.name} and all of its players?`)) {
        router.delete(TeamController.destroy.url(props.team.id));
    }
}

function removePlayer(player: Player): void {
    if (confirm(`Remove ${player.full_name} from the team?`)) {
        router.delete(PlayerController.destroy.url(player.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="team.name" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="team.name"
                :description="team.age_group ?? undefined"
            />
            <div class="flex gap-2">
                <Button v-if="can.update" variant="outline" as-child>
                    <Link :href="TeamController.edit(team.id)">Edit</Link>
                </Button>
                <Button
                    v-if="can.delete"
                    variant="destructive"
                    @click="deleteTeam"
                >
                    Delete
                </Button>
            </div>
        </div>

        <section class="grid gap-2">
            <h2 class="text-sm font-medium">Coaches and managers</h2>
            <p
                v-if="workers.length === 0"
                class="text-sm text-muted-foreground"
            >
                Nobody assigned yet.
            </p>
            <p v-else class="text-sm">
                {{ workers.map((worker) => worker.name).join(', ') }}
            </p>
        </section>

        <section class="grid gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium">Players</h2>
                <Button v-if="can.managePlayers" size="sm" as-child>
                    <Link :href="PlayerController.create(team.id)">
                        Add player
                    </Link>
                </Button>
            </div>

            <p
                v-if="players.length === 0"
                class="text-sm text-muted-foreground"
            >
                No players yet.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="player in players"
                    :key="player.id"
                    class="flex items-center justify-between gap-4 p-3 text-sm"
                >
                    <span class="flex items-center gap-3">
                        <span class="w-6 text-right text-muted-foreground">
                            {{ player.shirt_number ?? '' }}
                        </span>
                        <span class="font-medium">{{ player.full_name }}</span>
                    </span>
                    <span v-if="can.managePlayers" class="flex gap-2">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="PlayerController.edit(player.id)">
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="removePlayer(player)"
                        >
                            Remove
                        </Button>
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
