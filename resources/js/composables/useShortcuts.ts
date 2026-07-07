import { ref } from 'vue'

/**
 * Atalhos globais de teclado (frente #7) + estado dos overlays globais
 * (Busca Global e ajuda de atalhos). Estado em nível de módulo = singleton
 * compartilhado por todos os componentes que importarem o composable.
 *
 *   Ctrl/⌘ + K  → abre/fecha a Busca Global
 *   /           → abre a Busca Global (fora de campos de texto)
 *   N           → abre a Busca Global focada em criar (fora de campos)
 *   ?           → abre/fecha a ajuda de atalhos
 *   Esc         → fecha os overlays abertos
 */
const paletteOpen = ref(false)
const helpOpen = ref(false)
let installed = false

function isTyping(): boolean {
  const el = document.activeElement as HTMLElement | null
  if (!el) return false
  const tag = el.tagName
  return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable
}

function onKeydown(e: KeyboardEvent) {
  // Fechar overlays com Esc (mesmo com foco no input da busca).
  if (e.key === 'Escape') {
    if (paletteOpen.value) { paletteOpen.value = false; e.preventDefault() }
    if (helpOpen.value) helpOpen.value = false
    return
  }

  // Ctrl/⌘ + K funciona mesmo digitando.
  if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
    e.preventDefault()
    helpOpen.value = false
    paletteOpen.value = !paletteOpen.value
    return
  }

  // Os atalhos de tecla única não disparam dentro de campos de texto.
  if (isTyping() || e.ctrlKey || e.metaKey || e.altKey) return

  if (e.key === '/') {
    e.preventDefault()
    paletteOpen.value = true
  } else if (e.key === '?') {
    e.preventDefault()
    helpOpen.value = !helpOpen.value
  } else if (e.key === 'n' || e.key === 'N') {
    e.preventDefault()
    paletteOpen.value = true
  }
}

export function useShortcuts() {
  function install() {
    if (installed) return
    installed = true
    window.addEventListener('keydown', onKeydown)
  }
  function uninstall() {
    if (!installed) return
    installed = false
    window.removeEventListener('keydown', onKeydown)
  }

  return {
    paletteOpen,
    helpOpen,
    install,
    uninstall,
    openPalette: () => { paletteOpen.value = true },
    closePalette: () => { paletteOpen.value = false },
    openHelp: () => { helpOpen.value = true },
    closeHelp: () => { helpOpen.value = false },
  }
}
