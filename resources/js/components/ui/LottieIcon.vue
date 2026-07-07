<template>
  <div
    ref="el"
    class="lottie-icon"
    :style="{ width: `${size}px`, height: `${size}px` }"
    @mouseenter="onEnter"
    @mouseleave="onLeave"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import lottie, { type AnimationItem } from 'lottie-web'

const props = withDefaults(defineProps<{
  /** Objeto JSON do Lottie (import de @/assets/lottie/*.json). */
  animationData?: object
  /** URL alternativa (se não usar animationData). */
  path?: string
  loop?: boolean
  autoplay?: boolean
  /** Toca apenas ao passar o mouse (bom para ícones interativos). */
  hover?: boolean
  size?: number
  speed?: number
}>(), { loop: true, autoplay: true, hover: false, size: 120, speed: 1 })

const el = ref<HTMLElement | null>(null)
let anim: AnimationItem | null = null

const reduce = typeof window !== 'undefined'
  && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

onMounted(() => {
  if (!el.value) return
  anim = lottie.loadAnimation({
    container: el.value,
    renderer: 'svg',
    loop: props.loop,
    autoplay: !props.hover && !reduce && props.autoplay,
    animationData: props.animationData,
    path: props.animationData ? undefined : props.path,
  })
  anim.setSpeed(props.speed)
  // Com movimento reduzido, mostra apenas o quadro final (estático).
  if (reduce) anim.addEventListener('DOMLoaded', () => anim?.goToAndStop(anim.totalFrames - 1, true))
})

function onEnter() {
  if (!anim || reduce) return
  if (props.hover) anim.goToAndPlay(0, true)
}
function onLeave() {
  if (!anim || reduce || !props.hover) return
  anim.pause()
}

onBeforeUnmount(() => { anim?.destroy(); anim = null })
</script>

<style scoped>
.lottie-icon { display: inline-block; line-height: 0; }
.lottie-icon :deep(svg) { display: block; }
</style>
