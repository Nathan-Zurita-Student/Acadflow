import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ThemeName = 'ocean' | 'light' | 'forest' | 'purple' | 'sunset'

export interface Theme {
  name: ThemeName
  label: string
  preview: string   // CSS color for the swatch circle
  description: string
}

export const THEMES: Theme[] = [
  { name: 'ocean',  label: 'Dark Ocean',       preview: '#080f1e', description: 'Azul escuro (padrão)' },
  { name: 'light',  label: 'Light Cloud',       preview: '#f1f5f9', description: 'Claro e limpo' },
  { name: 'forest', label: 'Dark Forest',       preview: '#050d0a', description: 'Verde escuro' },
  { name: 'purple', label: 'Midnight Purple',   preview: '#0d0520', description: 'Roxo escuro' },
  { name: 'sunset', label: 'Amber Sunset',      preview: '#150a03', description: 'Âmbar quente' },
]

const STORAGE_KEY = 'acadflow-theme'

export const useThemeStore = defineStore('theme', () => {
  const current = ref<ThemeName>(
    (localStorage.getItem(STORAGE_KEY) as ThemeName) ?? 'ocean'
  )

  function setTheme(name: ThemeName) {
    current.value = name
    localStorage.setItem(STORAGE_KEY, name)
    document.documentElement.setAttribute('data-theme', name)
  }

  function init() {
    document.documentElement.setAttribute('data-theme', current.value)
  }

  return { current, setTheme, init }
})
