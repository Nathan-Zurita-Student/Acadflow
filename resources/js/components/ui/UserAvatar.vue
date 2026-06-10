<template>
  <div
    class="overflow-hidden flex items-center justify-center"
    :style="!user?.avatar ? { background: avatarColor } : undefined"
    v-bind="$attrs"
  >
    <img
      v-if="user?.avatar"
      :src="user.avatar"
      :alt="user?.name ?? 'Avatar'"
      class="w-full h-full object-cover"
    />
    <span v-else class="font-semibold leading-none select-none text-white">{{ initial }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  user?: { name?: string; avatar?: string | null } | null
}>()

const initial = computed(() => props.user?.name?.[0]?.toUpperCase() ?? '?')

const AVATAR_COLORS = [
  '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
  '#f97316', '#eab308', '#10b981', '#06b6d4',
  '#3b82f6', '#14b8a6',
]

const avatarColor = computed(() => {
  const name = props.user?.name ?? ''
  if (!name) return AVATAR_COLORS[0]
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
})
</script>
