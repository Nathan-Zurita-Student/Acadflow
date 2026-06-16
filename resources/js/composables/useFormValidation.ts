/**
 * Validação amigável de formulários, com mensagens personalizadas em português.
 *
 * Uso:
 *   const msg = validateFields([
 *     { value: form.email,    label: 'Email', rules: ['required', 'email'] },
 *     { value: form.password, label: 'Senha', rules: ['required', 'min:8'] },
 *   ])
 *   if (msg) { mostrarErro(msg); return }
 */

export type ValidationRule = 'required' | 'email' | `min:${number}` | `max:${number}`

export interface FieldSpec {
  value: unknown
  label: string
  rules?: ValidationRule[] // padrão: ['required']
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function isEmpty(v: unknown): boolean {
  if (v === null || v === undefined) return true
  if (typeof v === 'string') return v.trim() === ''
  if (Array.isArray(v)) return v.length === 0
  return false
}

/**
 * Retorna a primeira mensagem de erro encontrada, ou `null` se tudo válido.
 */
export function validateFields(fields: FieldSpec[]): string | null {
  for (const field of fields) {
    const rules = field.rules ?? ['required']
    const raw = field.value
    const str = typeof raw === 'string' ? raw.trim() : raw

    for (const rule of rules) {
      if (rule === 'required' && isEmpty(raw)) {
        return `Você esqueceu de preencher: "${field.label}".`
      }

      // As regras abaixo só se aplicam quando há valor preenchido.
      if (isEmpty(raw)) continue

      if (rule === 'email' && typeof str === 'string' && !EMAIL_RE.test(str)) {
        return `O campo "${field.label}" precisa ser um e-mail válido.`
      }

      if (rule.startsWith('min:') && typeof str === 'string') {
        const min = Number(rule.slice(4))
        if (str.length < min) {
          return `O campo "${field.label}" precisa ter pelo menos ${min} caracteres.`
        }
      }

      if (rule.startsWith('max:') && typeof str === 'string') {
        const max = Number(rule.slice(4))
        if (str.length > max) {
          return `O campo "${field.label}" pode ter no máximo ${max} caracteres.`
        }
      }
    }
  }

  return null
}
