<script setup lang="ts">
import { Head, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import platform from '@/routes/platform';

type Module = {
    key: string;
    name: string;
    description: string;
    enabled: boolean;
    hard_dependencies: string[];
    soft_dependencies: string[];
    enable_blocked_by: string[];
    disable_blocked_by: string[];
};

const props = defineProps<{
    organisation: { id: number; name: string };
    modules: Module[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Platform', href: platform.dashboard() },
        {
            title: props.organisation.name,
            href: platform.organisations.show(props.organisation.id),
        },
    ],
});

const page = usePage();
const error = computed(() => page.props.errors?.module as string | undefined);

/**
 * Why a toggle is unavailable, from the same rules the server enforces.
 */
function blockedReason(module: Module): string | null {
    if (!module.enabled && module.enable_blocked_by.length > 0) {
        return `Enable ${module.enable_blocked_by.join(', ')} first`;
    }

    if (module.enabled && module.disable_blocked_by.length > 0) {
        return `Disable ${module.disable_blocked_by.join(', ')} first`;
    }

    return null;
}

function toggle(module: Module): void {
    router.put(
        platform.organisations.modules.update.url({
            organisation: props.organisation.id,
            module: module.key,
        }),
        { enabled: !module.enabled },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="organisation.name" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            :title="organisation.name"
            description="Installed modules. Switching one off hides it from the club but keeps its data."
        />

        <InputError :message="error" />

        <p v-if="modules.length === 0" class="text-sm text-muted-foreground">
            No modules are installed. Add them to config/modules.php.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="module in modules"
                :key="module.key"
                class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="grid gap-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ module.name }}</span>
                        <Badge
                            :variant="module.enabled ? 'default' : 'outline'"
                        >
                            {{ module.enabled ? 'Enabled' : 'Disabled' }}
                        </Badge>
                    </div>
                    <p
                        v-if="module.description"
                        class="text-sm text-muted-foreground"
                    >
                        {{ module.description }}
                    </p>
                    <p
                        v-if="module.hard_dependencies.length"
                        class="text-xs text-muted-foreground"
                    >
                        Requires {{ module.hard_dependencies.join(', ') }}
                    </p>
                    <p
                        v-if="module.soft_dependencies.length"
                        class="text-xs text-muted-foreground"
                    >
                        Works with {{ module.soft_dependencies.join(', ') }}
                    </p>
                </div>

                <div class="flex flex-col items-start gap-1 sm:items-end">
                    <Button
                        :variant="module.enabled ? 'outline' : 'default'"
                        size="sm"
                        :disabled="blockedReason(module) !== null"
                        @click="toggle(module)"
                    >
                        {{ module.enabled ? 'Disable' : 'Enable' }}
                    </Button>
                    <span
                        v-if="blockedReason(module)"
                        class="text-xs text-muted-foreground"
                    >
                        {{ blockedReason(module) }}
                    </span>
                </div>
            </li>
        </ul>
    </div>
</template>
