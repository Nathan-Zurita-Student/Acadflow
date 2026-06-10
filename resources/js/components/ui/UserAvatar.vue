<template>
  <div class="overflow-hidden flex items-center justify-center" v-bind="$attrs">
    <img
      v-if="user?.avatar"
      :src="user.avatar"
      :alt="user?.name ?? 'Avatar'"
      class="w-full h-full object-cover"
    />
    <span v-else class="font-semibold leading-none select-none">{{ initial }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  user?: { name?: string; avatar?: string | null } | null
}>()

const initial = computed(() => props.user?.name?.[0]?.toUpperCase() ?? '?')
</script>
