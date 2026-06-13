import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({ breaks: true, gfm: true })

/**
 * Renderiza Markdown para HTML seguro (sanitizado).
 * Usado nas Notas e pode ser reutilizado em outros lugares (ex.: comentários).
 */
export function useMarkdown() {
  function render(src: string | null | undefined): string {
    if (!src) return ''
    const html = marked.parse(src, { async: false }) as string
    return DOMPurify.sanitize(html)
  }

  return { render }
}
