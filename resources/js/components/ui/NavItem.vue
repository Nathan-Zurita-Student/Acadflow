<template>
  <RouterLink
    :to="to"
    :exact="exact"
    class="relative flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-all duration-200 group"
    :class="[
      isActive
        ? 'bg-accent-600/20 text-accent-400 font-medium'
        : 'text-dark-400 hover:text-dark-100 hover:bg-dark-800',
      collapsed ? 'justify-center px-2' : '',
    ]"
    :title="collapsed ? label : undefined"
  >
    <!-- Barra indicadora de item ativo -->
    <span
      v-if="isActive && !collapsed"
      class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent-400"
    />
    <AnimatedIcon :name="icon" :active="isActive" :size="18" class="flex-shrink-0" />
    <span v-if="!collapsed" class="flex-1 truncate">{{ label }}</span>
    <slot v-if="!collapsed" name="badge" />
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AnimatedIcon from '@/components/ui/AnimatedIcon.vue'

const props = defineProps<{
  to: string
  label: string
  icon: string
  exact?: boolean
  collapsed?: boolean
}>()

defineSlots<{ badge?: () => any }>()

const route = useRoute()
const isActive = computed(() =>
  props.exact
    ? route.path === props.to
    : (route.path.startsWith(props.to) && props.to !== '/') || (props.to === '/' && route.path === '/')
)
</script>
