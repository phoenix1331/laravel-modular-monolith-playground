<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import ProductController from '@/actions/Modules/KitShop/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import kitShop from '@/routes/kit-shop';
import { pounds } from '../types';
import type { Product } from '../types';

defineProps<{
    products: Product[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kit shop', href: kitShop.index() },
            { title: 'Products', href: kitShop.products.index() },
        ],
    },
});

function setStock(product: Product, size: string, event: Event): void {
    const quantity = Number((event.target as HTMLInputElement).value);

    router.put(
        ProductController.stock.url(product.id),
        { size, quantity },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Kit products" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Kit products"
            description="The club's kit range and stock by size"
        />

        <ul v-if="products.length" class="divide-y rounded-xl border text-sm">
            <li
                v-for="product in products"
                :key="product.id"
                class="grid gap-2 p-3"
            >
                <p class="font-medium">
                    {{ product.name }}
                    <span class="text-muted-foreground">{{
                        pounds(product.price_pence)
                    }}</span>
                </p>
                <div class="flex flex-wrap gap-3">
                    <label
                        v-for="line in product.stock"
                        :key="line.size"
                        class="flex items-center gap-1"
                    >
                        <span class="w-8 text-muted-foreground">{{
                            line.size
                        }}</span>
                        <Input
                            type="number"
                            min="0"
                            class="w-20"
                            :default-value="line.quantity"
                            @change="setStock(product, line.size, $event)"
                        />
                    </label>
                </div>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">No products yet.</p>

        <Form
            v-bind="ProductController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <h2 class="text-sm font-medium">Add a product</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        placeholder="Home shirt"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="price">Price (£)</Label>
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                    />
                    <InputError :message="errors.price" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="stock">Sizes and stock</Label>
                <Input
                    id="stock"
                    name="stock"
                    required
                    placeholder="YS=10, YM=12, S=8"
                />
                <InputError :message="errors.stock" />
            </div>
            <Button :disabled="processing" class="w-fit">Add product</Button>
        </Form>
    </div>
</template>
