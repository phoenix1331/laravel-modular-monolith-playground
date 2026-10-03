<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import VenueController from '@/actions/Modules/Fixtures/Http/Controllers/VenueController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import fixtures from '@/routes/fixtures';
import type { Venue } from '../types';

defineProps<{
    venues: Venue[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Fixtures', href: fixtures.index() },
            { title: 'Venues', href: fixtures.venues.index() },
        ],
    },
});

function remove(venue: Venue): void {
    if (confirm(`Remove ${venue.name}? Its fixtures keep their dates.`)) {
        router.delete(VenueController.destroy.url(venue.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Venues" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Venues" description="Where your teams play" />

        <ul v-if="venues.length" class="divide-y rounded-xl border text-sm">
            <li
                v-for="venue in venues"
                :key="venue.id"
                class="flex items-center justify-between gap-4 p-3"
            >
                <span>
                    <span class="font-medium">{{ venue.name }}</span>
                    <span v-if="venue.postcode" class="text-muted-foreground">
                        {{ venue.postcode }}</span
                    >
                </span>
                <Button variant="ghost" size="sm" @click="remove(venue)">
                    Remove
                </Button>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">No venues yet.</p>

        <Form
            v-bind="VenueController.store.form()"
            class="grid max-w-xl gap-4"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <h2 class="text-sm font-medium">Add a venue</h2>
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="address">Address</Label>
                    <Input id="address" name="address" />
                    <InputError :message="errors.address" />
                </div>
                <div class="grid gap-2">
                    <Label for="postcode">Postcode</Label>
                    <Input id="postcode" name="postcode" />
                    <InputError :message="errors.postcode" />
                </div>
            </div>
            <Button :disabled="processing" class="w-fit">Add venue</Button>
        </Form>
    </div>
</template>
