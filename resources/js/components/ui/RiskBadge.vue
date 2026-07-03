<!-- <template>
  <span :class="['badge text-xs font-semibold', classes]">
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
    Risco {{ label }}
  </span>
</template> -->

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ level: 'low' | 'medium' | 'high' }>()

const map = {
  low: { cls: 'bg-emerald-600/20 text-emerald-400', dot: 'bg-emerald-400', label: 'baixo' },
  medium: { cls: 'bg-amber-600/20 text-amber-400', dot: 'bg-amber-400', label: 'médio' },
  high: { cls: 'bg-red-600/20 text-red-400', dot: 'bg-red-400', label: 'alto' },
}

const classes = computed(() => map[props.level].cls)
const dotClass = computed(() => map[props.level].dot)
const label = computed(() => map[props.level].label)
</script>
