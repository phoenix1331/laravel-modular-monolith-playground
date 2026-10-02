<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import PlayerController from '@/actions/App/Http/Controllers/Teams/PlayerController';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import PlayerFields from '@/components/teams/PlayerFields.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    team: { id: number; name: string };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Teams', href: TeamController.index() },
        { title: props.team.name, href: TeamController.show(props.team.id) },
        { title: 'Add player', href: PlayerController.create(props.team.id) },
    ],
});
</script>

<template>
    <Head :title="`Add player to ${team.name}`" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading :title="`Add player to ${team.name}`" />

        <Form
            v-bind="PlayerController.store.form(team.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <PlayerFields :errors="errors" />
            <Button :disabled="processing">Add player</Button>
        </Form>
    </div>
</template>
