import type { CalendarTask } from '@/types'

/** Cores do calendário conforme prazo/status da tarefa. */
export type CalColor = 'green' | 'yellow' | 'red' | 'blue'

/**
 * Regra de cor (na ordem):
 *  - azul   → tarefa concluída
 *  - vermelho → atrasada
 *  - amarelo  → prazo próximo (faltam ≤ 3 dias)
 *  - verde    → dentro do prazo
 */
export function taskColor(task: Pick<CalendarTask, 'status' | 'due_date' | 'is_overdue'>): CalColor {
  if (task.status === 'done') return 'blue'
  if (task.is_overdue) return 'red'
  if (task.due_date) {
    const diff = Math.ceil((parseYmd(task.due_date).getTime() + 86399000 - Date.now()) / 86400000)
    if (diff <= 3) return 'yellow'
  }
  return 'green'
}

/** Classes da faixa (barra) no calendário. */
export const BAR_CLASSES: Record<CalColor, string> = {
  green:  'bg-emerald-500/80 hover:bg-emerald-500 text-emerald-50',
  yellow: 'bg-amber-500/80 hover:bg-amber-500 text-amber-950',
  red:    'bg-red-500/80 hover:bg-red-500 text-red-50',
  blue:   'bg-blue-500/75 hover:bg-blue-500 text-blue-50',
}

/** Classes de "ponto" / chip de cor (legenda, painel). */
export const DOT_CLASSES: Record<CalColor, string> = {
  green: 'bg-emerald-500', yellow: 'bg-amber-500', red: 'bg-red-500', blue: 'bg-blue-500',
}

export const COLOR_LABELS: Record<CalColor, string> = {
  green: 'No prazo', yellow: 'Prazo próximo', red: 'Atrasada', blue: 'Concluída',
}

// ── Datas (sempre em horário local, sem fuso) ─────────────────────────────

/** "2026-06-22" → Date local à meia-noite. */
export function parseYmd(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y, m - 1, d)
}

/** Date → "YYYY-MM-DD" (local). */
export function ymd(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function addDays(d: Date, n: number): Date {
  const r = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  r.setDate(r.getDate() + n)
  return r
}

/** Diferença em dias inteiros (a - b), ignorando horas. */
export function diffDays(a: Date, b: Date): number {
  const da = new Date(a.getFullYear(), a.getMonth(), a.getDate()).getTime()
  const db = new Date(b.getFullYear(), b.getMonth(), b.getDate()).getTime()
  return Math.round((da - db) / 86400000)
}

/** Intervalo [início, fim] da tarefa (datas locais). Usa due quando não há start e vice-versa. */
export function taskRange(task: Pick<CalendarTask, 'start_date' | 'due_date'>): { start: Date; end: Date } | null {
  const s = task.start_date ? parseYmd(task.start_date) : (task.due_date ? parseYmd(task.due_date) : null)
  const e = task.due_date ? parseYmd(task.due_date) : (task.start_date ? parseYmd(task.start_date) : null)
  if (!s || !e) return null
  return s.getTime() <= e.getTime() ? { start: s, end: e } : { start: e, end: s }
}

/** A tarefa ocupa o dia `day` (string YYYY-MM-DD)? */
export function taskCoversDay(task: Pick<CalendarTask, 'start_date' | 'due_date'>, day: string): boolean {
  const r = taskRange(task)
  if (!r) return false
  const d = parseYmd(day)
  return d.getTime() >= r.start.getTime() && d.getTime() <= r.end.getTime()
}
