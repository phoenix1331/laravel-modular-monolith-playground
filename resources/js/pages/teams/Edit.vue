<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import TeamFields from '@/components/teams/TeamFields.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    team: {
        id: number;
        name: string;
        age_group: string | null;
        worker_ids: number[];
    };
    workers: { id: number; name: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Teams', href: TeamController.index() },
        { title: props.team.name, href: TeamController.show(props.team.id) },
        { title: 'Edit', href: TeamController.edit(props.team.id) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${team.name}`" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading :title="`Edit ${team.name}`" />

        <Form
            v-bind="TeamController.update.form(team.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <TeamFields :errors="errors" :workers="workers" :defaults="team" />
            <Button :disabled="processing">Save</Button>
        </Form>
    </div>
</template>
