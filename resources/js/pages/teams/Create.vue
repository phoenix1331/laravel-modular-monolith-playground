<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import TeamFields from '@/components/teams/TeamFields.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    workers: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teams', href: TeamController.index() },
            { title: 'New team', href: TeamController.create() },
        ],
    },
});
</script>

<template>
    <Head title="New team" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading title="New team" />

        <Form
            v-bind="TeamController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <TeamFields :errors="errors" :workers="workers" />
            <Button :disabled="processing">Create team</Button>
        </Form>
    </div>
</template>
