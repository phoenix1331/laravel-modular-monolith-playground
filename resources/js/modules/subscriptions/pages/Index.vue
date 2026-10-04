<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FeeController from '@/actions/Modules/Subscriptions/Http/Controllers/FeeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import subscriptions from '@/routes/subscriptions';

type Fee = {
    id: number;
    player: string;
    team: string;
    description: string;
    amount_pence: number;
    due_on: string;
    paid_at: string | null;
    overdue: boolean;
};

defineProps<{
    filter: 'due' | 'overdue' | 'paid' | 'all';
    totals: {
        outstanding_pence: number;
        overdue_pence: number;
        collected_pence: number;
    };
    fees: Fee[];
    teams: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Subscriptions', href: subscriptions.index() }],
    },
});

const page = usePage();
const paymentError = computed(
    () => page.props.errors?.payment as string | undefined,
);

const money = new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
});
const pounds = (pence: number): string => money.format(pence / 100);
const date = new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium' });

const filters = [
    { value: 'due', label: 'Due' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'paid', label: 'Paid' },
    { value: 'all', label: 'All' },
] as const;

function pay(fee: Fee): void {
    router.post(FeeController.pay.url(fee.id), {}, { preserveScroll: true });
}

function remove(fee: Fee): void {
    if (confirm(`Remove the unpaid fee for ${fee.player}?`)) {
        router.delete(FeeController.destroy.url(fee.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Subscriptions" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Subscriptions"
            description="Membership fees for every player in the club"
        />

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Outstanding</p>
                <p class="text-2xl font-semibold tabular-nums">
                    {{ pounds(totals.outstanding_pence) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Overdue</p>
                <p class="text-2xl font-semibold tabular-nums">
                    {{ pounds(totals.overdue_pence) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Collected</p>
                <p class="text-2xl font-semibold tabular-nums">
                    {{ pounds(totals.collected_pence) }}
                </p>
            </div>
        </div>

        <nav class="flex gap-2">
            <Button
                v-for="option in filters"
                :key="option.value"
                size="sm"
                :variant="filter === option.value ? 'default' : 'outline'"
                as-child
            >
                <Link
                    :href="
                        subscriptions.index({ query: { status: option.value } })
                    "
                    preserve-scroll
                    >{{ option.label }}</Link
                >
            </Button>
        </nav>

        <InputError :message="paymentError" />

        <p v-if="fees.length === 0" class="text-sm text-muted-foreground">
            No fees here.
        </p>

        <ul v-else class="divide-y rounded-xl border text-sm">
            <li
                v-for="fee in fees"
                :key="fee.id"
                class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="grid gap-1">
                    <span class="font-medium">
                        {{ fee.player }}
                        <span class="text-muted-foreground"
                            >· {{ fee.team }}</span
                        >
                    </span>
                    <span class="text-muted-foreground">
                        {{ fee.description }}, due
                        {{ date.format(new Date(fee.due_on)) }}
                    </span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="font-medium tabular-nums">
                        {{ pounds(fee.amount_pence) }}
                    </span>
                    <Badge v-if="fee.paid_at" variant="secondary">Paid</Badge>
                    <Badge v-else-if="fee.overdue" variant="destructive"
                        >Overdue</Badge
                    >
                    <template v-if="!fee.paid_at">
                        <Button size="sm" @click="pay(fee)"
                            >Take payment</Button
                        >
                        <Button size="sm" variant="ghost" @click="remove(fee)"
                            >Remove</Button
                        >
                    </template>
                </span>
            </li>
        </ul>

        <Form
            v-bind="FeeController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <h2 class="text-sm font-medium">Charge a team</h2>
            <div class="grid gap-2">
                <Label for="team_id">Team</Label>
                <select
                    id="team_id"
                    name="team_id"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                >
                    <option
                        v-for="team in teams"
                        :key="team.id"
                        :value="team.id"
                    >
                        {{ team.name }}
                    </option>
                </select>
                <InputError :message="errors.team_id" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <Input
                    id="description"
                    name="description"
                    required
                    placeholder="2026/27 season subs"
                />
                <InputError :message="errors.description" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="amount">Amount per player (£)</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                    />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="due_on">Due on</Label>
                    <Input id="due_on" name="due_on" type="date" required />
                    <InputError :message="errors.due_on" />
                </div>
            </div>
            <Button :disabled="processing" class="w-fit">Raise fees</Button>
        </Form>
    </div>
</template>
