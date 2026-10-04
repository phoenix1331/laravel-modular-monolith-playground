<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ShopController from '@/actions/Modules/KitShop/Http/Controllers/ShopController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import kitShop from '@/routes/kit-shop';
import { pounds } from '../types';
import type { Product } from '../types';

const props = defineProps<{
    products: Product[];
    players: { id: number; name: string; team: string; paid_up: boolean }[];
    orders: {
        id: number;
        player: string;
        product: string;
        size: string;
        quantity: number;
        total_pence: number;
        fulfilled: boolean;
        can_fulfil: boolean;
    }[];
    membershipTracked: boolean;
    can: { manageProducts: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kit shop', href: kitShop.index() }],
    },
});

const page = usePage();
const orderError = computed(
    () => page.props.errors?.order as string | undefined,
);

const productId = ref<number | undefined>(props.products[0]?.id);
const sizes = computed(
    () =>
        props.products.find((product) => product.id === productId.value)
            ?.stock ?? [],
);

function fulfil(id: number): void {
    router.post(ShopController.fulfil.url(id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kit shop" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Kit shop"
                description="Order club kit for players"
            />
            <Button v-if="can.manageProducts" variant="outline" as-child>
                <Link :href="kitShop.products.index()">Manage products</Link>
            </Button>
        </div>

        <p
            v-if="membershipTracked"
            class="rounded-md border px-3 py-2 text-sm text-muted-foreground"
        >
            Orders are limited to paid-up members, because this club uses
            Subscriptions.
        </p>

        <ul v-if="products.length" class="grid gap-3 sm:grid-cols-2">
            <li
                v-for="product in products"
                :key="product.id"
                class="rounded-xl border p-4 text-sm"
            >
                <p class="font-medium">
                    {{ product.name }}
                    <span class="text-muted-foreground">
                        {{ pounds(product.price_pence) }}</span
                    >
                </p>
                <p class="mt-2 flex flex-wrap gap-1">
                    <Badge
                        v-for="line in product.stock"
                        :key="line.size"
                        :variant="line.quantity > 0 ? 'secondary' : 'outline'"
                    >
                        {{ line.size }}: {{ line.quantity }}
                    </Badge>
                </p>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
            No kit in the shop yet.
        </p>

        <Form
            v-if="products.length && players.length"
            v-bind="ShopController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            v-slot="{ errors, processing }"
        >
            <h2 class="text-sm font-medium">Place an order</h2>
            <InputError :message="orderError" />
            <div class="grid gap-2">
                <Label for="player_id">Player</Label>
                <NativeSelect id="player_id" name="player_id">
                    <option
                        v-for="player in players"
                        :key="player.id"
                        :value="player.id"
                    >
                        {{ player.name }} ({{ player.team }}){{
                            player.paid_up ? '' : ' · overdue subs'
                        }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.player_id" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="product_id">Item</Label>
                    <NativeSelect
                        id="product_id"
                        :value="productId"
                        @change="
                            productId = Number(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                        name="product_id"
                    >
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }}
                        </option>
                    </NativeSelect>
                </div>
                <div class="grid gap-2">
                    <Label for="size">Size</Label>
                    <NativeSelect id="size" name="size">
                        <option
                            v-for="line in sizes"
                            :key="line.size"
                            :value="line.size"
                            :disabled="line.quantity === 0"
                        >
                            {{ line.size }}
                        </option>
                    </NativeSelect>
                </div>
                <div class="grid gap-2">
                    <Label for="quantity">Quantity</Label>
                    <Input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        max="10"
                        :default-value="1"
                    />
                    <InputError :message="errors.quantity" />
                </div>
            </div>
            <Button :disabled="processing" class="w-fit">Place order</Button>
        </Form>

        <section class="grid gap-3">
            <h2 class="text-sm font-medium">Recent orders</h2>
            <p v-if="orders.length === 0" class="text-sm text-muted-foreground">
                No orders yet.
            </p>
            <ul v-else class="divide-y rounded-xl border text-sm">
                <li
                    v-for="order in orders"
                    :key="order.id"
                    class="flex items-center justify-between gap-4 p-3"
                >
                    <span>
                        <span class="font-medium">{{ order.player }}</span>
                        <span class="text-muted-foreground">
                            · {{ order.quantity }} × {{ order.product }} ({{
                                order.size
                            }})</span
                        >
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="tabular-nums">{{
                            pounds(order.total_pence)
                        }}</span>
                        <Badge v-if="order.fulfilled" variant="secondary"
                            >Handed out</Badge
                        >
                        <Button
                            v-else-if="order.can_fulfil"
                            size="sm"
                            variant="outline"
                            @click="fulfil(order.id)"
                            >Mark handed out</Button
                        >
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
