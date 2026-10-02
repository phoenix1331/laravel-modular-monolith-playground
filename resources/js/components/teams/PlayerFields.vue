<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    errors: Record<string, string>;
    defaults?: {
        first_name?: string;
        last_name?: string;
        date_of_birth?: string | null;
        shirt_number?: number | null;
    };
}>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="first_name">First name</Label>
            <Input
                id="first_name"
                name="first_name"
                :default-value="defaults?.first_name"
                required
            />
            <InputError :message="errors.first_name" />
        </div>

        <div class="grid gap-2">
            <Label for="last_name">Last name</Label>
            <Input
                id="last_name"
                name="last_name"
                :default-value="defaults?.last_name"
                required
            />
            <InputError :message="errors.last_name" />
        </div>

        <div class="grid gap-2">
            <Label for="date_of_birth">Date of birth</Label>
            <Input
                id="date_of_birth"
                name="date_of_birth"
                type="date"
                :default-value="defaults?.date_of_birth ?? undefined"
            />
            <InputError :message="errors.date_of_birth" />
        </div>

        <div class="grid gap-2">
            <Label for="shirt_number">Shirt number</Label>
            <Input
                id="shirt_number"
                name="shirt_number"
                type="number"
                min="1"
                max="99"
                :default-value="defaults?.shirt_number ?? undefined"
            />
            <InputError :message="errors.shirt_number" />
        </div>
    </div>
</template>
