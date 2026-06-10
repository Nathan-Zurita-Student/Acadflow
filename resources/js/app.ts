import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import router from './router'

// Apply saved theme synchronously to prevent flash of unstyled content
const savedTheme = localStorage.getItem('acadflow-theme') ?? 'ocean'
document.documentElement.setAttribute('data-theme', savedTheme)

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true }) as Record<string, any>
    return pages[`./Pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .use(router)
      .use(VueApexCharts)
      .mount(el)
  },
})
