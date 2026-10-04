<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import AnnouncementController from '@/actions/Modules/Messaging/Http/Controllers/AnnouncementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import messaging from '@/routes/messaging';

type Announcement = {
    id: number;
    title: string;
    body: string | null;
    team: string | null;
    audience: 'team' | 'admins';
    source: 'manual' | 'fixtures' | 'subscriptions';
    author: string | null;
    created_at: string;
    can_delete: boolean;
};

defineProps<{
    announcements: Announcement[];
    teams: { id: number; name: string }[];
    can: { postClubWide: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Announcements', href: messaging.index() }],
    },
});

const posted = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

const sourceLabel: Record<Announcement['source'], string> = {
    manual: '',
    fixtures: 'From Fixtures',
    subscriptions: 'From Subscriptions',
};

function remove(announcement: Announcement): void {
    if (confirm('Remove this announcement?')) {
        router.delete(AnnouncementController.destroy.url(announcement.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Announcements" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Announcements"
            description="News for the club and your teams"
        />

        <Form
            v-if="teams.length || can.postClubWide"
            v-bind="AnnouncementController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="team_id">Post to</Label>
                <NativeSelect id="team_id" name="team_id">
                    <option v-if="can.postClubWide" value="">Whole club</option>
                    <option
                        v-for="team in teams"
                        :key="team.id"
                        :value="team.id"
                    >
                        {{ team.name }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.team_id" />
            </div>
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input id="title" name="title" required />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-2">
                <Label for="body">Message</Label>
                <textarea
                    id="body"
                    name="body"
                    rows="3"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                />
                <InputError :message="errors.body" />
            </div>
            <Button :disabled="processing" class="w-fit">Post</Button>
        </Form>

        <p
            v-if="announcements.length === 0"
            class="text-sm text-muted-foreground"
        >
            Nothing posted yet.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="announcement in announcements"
                :key="announcement.id"
                class="grid gap-1 p-3 text-sm"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">{{ announcement.title }}</span>
                    <Badge variant="outline">{{
                        announcement.team ?? 'Whole club'
                    }}</Badge>
                    <Badge
                        v-if="announcement.audience === 'admins'"
                        variant="destructive"
                        >Admins only</Badge
                    >
                    <Badge
                        v-if="sourceLabel[announcement.source]"
                        variant="secondary"
                    >
                        {{ sourceLabel[announcement.source] }}
                    </Badge>
                    <span class="ml-auto text-muted-foreground">
                        {{ posted.format(new Date(announcement.created_at)) }}
                    </span>
                </div>
                <p v-if="announcement.body">{{ announcement.body }}</p>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <span v-if="announcement.author">{{
                        announcement.author
                    }}</span>
                    <Button
                        v-if="announcement.can_delete"
                        size="sm"
                        variant="ghost"
                        class="ml-auto"
                        @click="remove(announcement)"
                        >Remove</Button
                    >
                </div>
            </li>
        </ul>
    </div>
</template>
