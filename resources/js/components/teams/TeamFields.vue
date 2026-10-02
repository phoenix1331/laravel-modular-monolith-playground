<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Worker = { id: number; name: string };

defineProps<{
    errors: Record<string, string>;
    workers: Worker[];
    defaults?: {
        name?: string;
        age_group?: string | null;
        worker_ids?: number[];
    };
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Name</Label>
        <Input
            id="name"
            name="name"
            :default-value="defaults?.name"
            required
            placeholder="Under 12 Lions"
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid gap-2">
        <Label for="age_group">Age group</Label>
        <Input
            id="age_group"
            name="age_group"
            :default-value="defaults?.age_group ?? undefined"
            placeholder="U12"
        />
        <InputError :message="errors.age_group" />
    </div>

    <fieldset class="grid gap-2">
        <legend class="mb-2 text-sm font-medium">Coaches and managers</legend>
        <p v-if="workers.length === 0" class="text-sm text-muted-foreground">
            No workers in this club yet.
        </p>
        <label
            v-for="worker in workers"
            :key="worker.id"
            class="flex items-center gap-2 text-sm"
        >
            <input
                type="checkbox"
                name="worker_ids[]"
                :value="worker.id"
                :checked="defaults?.worker_ids?.includes(worker.id)"
                class="size-4 rounded border-input"
            />
            {{ worker.name }}
        </label>
        <InputError :message="errors.worker_ids" />
    </fieldset>
</template>
