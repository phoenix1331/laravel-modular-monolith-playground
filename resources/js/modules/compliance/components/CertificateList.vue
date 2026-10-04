<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { longDate, statusLabel, statusVariant } from '../types';
import type { CertificateRow } from '../types';

defineProps<{
    certificates: CertificateRow[];
}>();

defineEmits<{ remove: [certificate: CertificateRow] }>();
</script>

<template>
    <ul class="grid gap-1">
        <li
            v-for="certificate in certificates"
            :key="certificate.id"
            class="flex flex-wrap items-center gap-2 text-sm"
        >
            <span class="font-medium">{{ certificate.type }}</span>
            <span class="text-muted-foreground">
                expires {{ longDate.format(new Date(certificate.expires_on)) }}
            </span>
            <Badge :variant="statusVariant[certificate.status]">
                {{ statusLabel[certificate.status] }}
            </Badge>
            <slot :certificate="certificate" />
        </li>
    </ul>
</template>
