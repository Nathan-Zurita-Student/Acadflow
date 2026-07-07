<template>
  <svg
    class="aicon group"
    :class="{ 'is-active': active }"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    :stroke-width="strokeWidth"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <!-- HOME -->
    <g v-if="name === 'home'" class="group-hover:-translate-y-[1.5px]">
      <path d="M3 10.5 12 3l9 7.5" />
      <path d="M5 9.8V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.8" />
      <path d="M9.5 21v-6h5v6" />
    </g>

    <!-- TASK -->
    <g v-else-if="name === 'task'" class="group-hover:scale-110">
      <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
      <path d="M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2 2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2z" />
      <path class="check" d="M9 14l2 2 4-4" />
    </g>

    <!-- FOLDER -->
    <g v-else-if="name === 'folder'" class="group-hover:scale-110 group-hover:-rotate-3">
      <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
    </g>

    <!-- USERS -->
    <g v-else-if="name === 'users'" class="group-hover:scale-110">
      <path d="M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" />
    </g>

    <!-- PAPERCLIP -->
    <g v-else-if="name === 'paperclip'" class="group-hover:-rotate-12 group-hover:scale-110">
      <path d="M15.172 7l-6.586 6.586a2 2 0 1 0 2.828 2.828l6.414-6.586a4 4 0 0 0-5.656-5.656l-6.415 6.585a6 6 0 1 0 8.486 8.486L20.5 13" />
    </g>

    <!-- CALENDAR -->
    <template v-else-if="name === 'calendar'">
      <g class="group-hover:scale-105">
        <path d="M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
        <path d="M7 11h10" />
      </g>
      <path class="hanger" style="transform-origin: bottom" d="M8 7V3" />
      <path class="hanger" style="transform-origin: bottom" d="M16 7V3" />
    </template>

    <!-- NOTES -->
    <g v-else-if="name === 'notes'" class="group-hover:scale-105">
      <path d="M9 12h6m-6 4h4m3 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
    </g>

    <!-- CHART -->
    <template v-else-if="name === 'chart'">
      <path d="M4 20h16" />
      <rect class="bar" style="transform-origin: bottom; transition-delay: 0ms"   x="5.5"  y="13" width="3" height="6" rx="1" />
      <rect class="bar" style="transform-origin: bottom; transition-delay: 70ms"  x="10.5" y="9"  width="3" height="10" rx="1" />
      <rect class="bar" style="transform-origin: bottom; transition-delay: 140ms" x="15.5" y="11" width="3" height="8" rx="1" />
    </template>

    <!-- KANBAN -->
    <template v-else-if="name === 'kanban'">
      <rect class="col" style="transition-delay: 0ms"   x="4"    y="5" width="3.5" height="14" rx="1" />
      <rect class="col" style="transition-delay: 70ms"  x="10.2" y="5" width="3.5" height="10" rx="1" />
      <rect class="col" style="transition-delay: 140ms" x="16.5" y="5" width="3.5" height="12" rx="1" />
    </template>

    <!-- SETTINGS -->
    <template v-else-if="name === 'settings'">
      <path class="gear" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <circle cx="12" cy="12" r="3" />
    </template>

    <!-- CROWN -->
    <g v-else-if="name === 'crown'" class="group-hover:-translate-y-[1.5px] group-hover:scale-105">
      <path d="M5 16 3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5zm0 0h14v3H5v-3z" />
    </g>

    <!-- SEARCH -->
    <g v-else-if="name === 'search'" class="group-hover:-rotate-6 group-hover:scale-110">
      <circle cx="11" cy="11" r="7" />
      <path d="m21 21-4.3-4.3" />
    </g>

    <!-- BELL -->
    <g v-else-if="name === 'bell'" class="bell">
      <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
      <path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </g>

    <!-- PLUS -->
    <g v-else-if="name === 'plus'" class="group-hover:rotate-90">
      <path d="M12 5v14M5 12h14" />
    </g>

    <!-- SPARKLES / AI -->
    <template v-else-if="name === 'sparkles'">
      <path class="star-lg group-hover:rotate-12 group-hover:scale-110" d="M12 3l1.8 4.6L18.4 9.4 13.8 11.2 12 16l-1.8-4.8L5.6 9.4 10.2 7.6 12 3z" />
      <path class="spark" d="M18.6 14.4l.7 1.9 1.9.7-1.9.7-.7 1.9-.7-1.9-1.9-.7 1.9-.7.7-1.9z" />
    </template>

    <!-- Fallback -->
    <circle v-else cx="12" cy="12" r="8" />
  </svg>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  name: string
  size?: number
  active?: boolean
  strokeWidth?: number
}>(), { size: 20, active: false, strokeWidth: 2 })
</script>

<style scoped>
/* Base: transições springy nos elementos internos, com origem/box previsíveis. */
.aicon {
  transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform-origin: center;
  overflow: visible;
}
.aicon :where(path, rect, circle, line, polyline, ellipse, g) {
  transform-box: fill-box;
  transform-origin: center;
  transition: transform 0.42s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}

/* Estado ativo: leve ênfase permanente. */
.aicon.is-active { transform: scale(1.06); }

/* CHART — barras crescem a partir da base. */
.bar { transform: scaleY(0.82); }
.group:hover .bar { transform: scaleY(1.12); }

/* KANBAN — colunas oscilam em cascata. */
.group:hover .col:nth-of-type(1) { transform: translateY(-2.5px); }
.group:hover .col:nth-of-type(2) { transform: translateY(2.5px); }
.group:hover .col:nth-of-type(3) { transform: translateY(-2.5px); }

/* CALENDAR — as "argolas" saltam. */
.group:hover .hanger { transform: translateY(-2px); }

/* SETTINGS — engrenagem gira suave e contínua no hover. */
.gear { transition: transform 0.8s cubic-bezier(0.3, 0.5, 0.3, 1); }
.group:hover .gear { transform: rotate(90deg); }

/* BELL — balança como se tocasse. */
.bell { transform-origin: 12px 4px; }
.group:hover .bell { animation: bell-swing 0.6s ease-in-out; }
@keyframes bell-swing {
  0%, 100% { transform: rotate(0); }
  20% { transform: rotate(12deg); }
  45% { transform: rotate(-9deg); }
  70% { transform: rotate(5deg); }
}

/* SPARKLES — a fagulha menor cintila. */
.spark { opacity: 0.55; transform: scale(0.85); }
.group:hover .spark { opacity: 1; transform: scale(1.25); transition-delay: 0.08s; }

/* TASK — o "check" cresce um pouco mais que o resto. */
.group:hover .check { transform: scale(1.18); }

@media (prefers-reduced-motion: reduce) {
  .aicon, .aicon * { transition: none !important; animation: none !important; }
}
</style>
