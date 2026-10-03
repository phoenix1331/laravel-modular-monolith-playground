<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FixtureController from '@/actions/Modules/Fixtures/Http/Controllers/FixtureController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import fixtures from '@/routes/fixtures';
import FixtureFields from '../components/FixtureFields.vue';
import type { Option } from '../types';

defineProps<{
    teams: Option[];
    venues: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Fixtures', href: fixtures.index() },
            { title: 'Schedule', href: fixtures.create() },
        ],
    },
});
</script>

<template>
    <Head title="Schedule fixture" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading title="Schedule fixture" />

        <Form
            v-bind="FixtureController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <FixtureFields :errors="errors" :teams="teams" :venues="venues" />
            <Button :disabled="processing">Schedule</Button>
        </Form>
    </div>
</template>
