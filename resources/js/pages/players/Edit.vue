<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import PlayerController from '@/actions/App/Http/Controllers/Teams/PlayerController';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import PlayerFields from '@/components/teams/PlayerFields.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    team: { id: number; name: string };
    player: {
        id: number;
        first_name: string;
        last_name: string;
        date_of_birth: string | null;
        shirt_number: number | null;
    };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Teams', href: TeamController.index() },
        { title: props.team.name, href: TeamController.show(props.team.id) },
        { title: 'Edit player', href: PlayerController.edit(props.player.id) },
    ],
});
</script>

<template>
    <Head title="Edit player" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${player.first_name} ${player.last_name}`"
            :description="team.name"
        />

        <Form
            v-bind="PlayerController.update.form(player.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <PlayerFields :errors="errors" :defaults="player" />
            <Button :disabled="processing">Save</Button>
        </Form>
    </div>
</template>
