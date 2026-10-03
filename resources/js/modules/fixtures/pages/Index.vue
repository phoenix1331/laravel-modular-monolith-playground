<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FixtureController from '@/actions/Modules/Fixtures/Http/Controllers/FixtureController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import fixtures from '@/routes/fixtures';
import FixtureList from '../components/FixtureList.vue';
import type { FixtureRow } from '../types';

defineProps<{
    upcoming: FixtureRow[];
    results: FixtureRow[];
    can: { create: boolean; manageVenues: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Fixtures', href: fixtures.index() }],
    },
});
</script>

<template>
    <Head title="Fixtures" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Fixtures"
                description="Matches for the teams you can see"
            />
            <div class="flex gap-2">
                <Button v-if="can.manageVenues" variant="outline" as-child>
                    <Link :href="fixtures.venues.index()">Venues</Link>
                </Button>
                <Button v-if="can.create" as-child>
                    <Link :href="FixtureController.create()"
                        >Schedule fixture</Link
                    >
                </Button>
            </div>
        </div>

        <section class="grid gap-3">
            <h2 class="text-sm font-medium">Upcoming</h2>
            <FixtureList :fixtures="upcoming" empty="Nothing scheduled." />
        </section>

        <section class="grid gap-3">
            <h2 class="text-sm font-medium">Recent</h2>
            <FixtureList :fixtures="results" empty="No matches played yet." />
        </section>
    </div>
</template>
