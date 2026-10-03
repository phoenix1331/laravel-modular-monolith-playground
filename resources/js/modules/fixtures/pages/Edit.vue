<script setup lang="ts">
import { Form, Head, router, setLayoutProps } from '@inertiajs/vue3';
import FixtureController from '@/actions/Modules/Fixtures/Http/Controllers/FixtureController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import fixtures from '@/routes/fixtures';
import FixtureFields from '../components/FixtureFields.vue';
import type { Option } from '../types';

const props = defineProps<{
    fixture: {
        id: number;
        team: string;
        venue_id: number | null;
        opponent: string;
        kick_off_at: string;
        is_home: boolean;
        competition: string | null;
    };
    venues: Option[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Fixtures', href: fixtures.index() },
        {
            title: `${props.fixture.team} v ${props.fixture.opponent}`,
            href: fixtures.edit(props.fixture.id),
        },
    ],
});

function remove(): void {
    if (confirm('Remove this fixture?')) {
        router.delete(FixtureController.destroy.url(props.fixture.id));
    }
}
</script>

<template>
    <Head title="Edit fixture" />

    <div class="flex max-w-xl flex-col gap-6 p-4">
        <Heading :title="`${fixture.team} v ${fixture.opponent}`" />

        <Form
            v-bind="FixtureController.update.form(fixture.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <FixtureFields
                :errors="errors"
                :venues="venues"
                :defaults="fixture"
            />
            <div class="flex gap-2">
                <Button :disabled="processing">Save</Button>
                <Button type="button" variant="destructive" @click="remove">
                    Remove
                </Button>
            </div>
        </Form>
    </div>
</template>
