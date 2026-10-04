<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option } from '../types';

defineProps<{
    errors: Record<string, string>;
    venues: Option[];
    teams?: Option[];
    defaults?: {
        venue_id?: number | null;
        opponent?: string;
        kick_off_at?: string;
        is_home?: boolean;
        competition?: string | null;
    };
}>();
</script>

<template>
    <div v-if="teams" class="grid gap-2">
        <Label for="team_id">Team</Label>
        <NativeSelect id="team_id" name="team_id" required>
            <option v-for="team in teams" :key="team.id" :value="team.id">
                {{ team.name }}
            </option>
        </NativeSelect>
        <InputError :message="errors.team_id" />
    </div>

    <div class="grid gap-2">
        <Label for="opponent">Opponent</Label>
        <Input
            id="opponent"
            name="opponent"
            :default-value="defaults?.opponent"
            required
            placeholder="Harbour Town"
        />
        <InputError :message="errors.opponent" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="kick_off_at">Kick-off</Label>
            <Input
                id="kick_off_at"
                name="kick_off_at"
                type="datetime-local"
                :default-value="defaults?.kick_off_at"
                required
            />
            <InputError :message="errors.kick_off_at" />
        </div>

        <div class="grid gap-2">
            <Label for="is_home">Home or away</Label>
            <NativeSelect id="is_home" name="is_home">
                <option value="1" :selected="defaults?.is_home !== false">
                    Home
                </option>
                <option value="0" :selected="defaults?.is_home === false">
                    Away
                </option>
            </NativeSelect>
            <InputError :message="errors.is_home" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="venue_id">Venue</Label>
            <NativeSelect id="venue_id" name="venue_id">
                <option value="">Not set</option>
                <option
                    v-for="venue in venues"
                    :key="venue.id"
                    :value="venue.id"
                    :selected="defaults?.venue_id === venue.id"
                >
                    {{ venue.name }}
                </option>
            </NativeSelect>
            <InputError :message="errors.venue_id" />
        </div>

        <div class="grid gap-2">
            <Label for="competition">Competition</Label>
            <Input
                id="competition"
                name="competition"
                :default-value="defaults?.competition ?? undefined"
                placeholder="League"
            />
            <InputError :message="errors.competition" />
        </div>
    </div>
</template>
