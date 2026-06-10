import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import router from './router'

const pages = import.meta.glob('./Pages/**/*.vue')

createInertiaApp({
  resolve: async (name) => {
    const mod = await (pages[`./Pages/${name}.vue`] as () => Promise<any>)()
    return mod.default
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
