<script setup lang="ts">
/**
 * A styled native <select>, shared by Core and modules.
 *
 * Native selects submit with Inertia's <Form> without extra wiring, and the
 * browser draws the option list itself. That list follows `color-scheme`
 * (set per theme in app.css), and the explicit background and text colours
 * here keep the closed control readable in both themes.
 *
 * Attributes (name, id, required, value, @change) pass straight through.
 */
</script>

<template>
    <select
        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:opacity-50"
    >
        <slot />
    </select>
</template>
