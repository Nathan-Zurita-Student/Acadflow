import { ref } from 'vue'

export interface ConfirmOptions {
  /** Título do diálogo. Opcional — cai num padrão conforme a variante. */
  title?: string
  /** Texto principal/pergunta. Único campo obrigatório. */
  message: string
  confirmText?: string
  cancelText?: string
  /** 'danger' deixa o botão de confirmar vermelho (ações destrutivas). */
  variant?: 'danger' | 'default'
}

interface ConfirmState extends Required<ConfirmOptions> {
  open: boolean
}

/**
 * Diálogo de confirmação global — substitui o window.confirm() nativo.
 * Padrão singleton (estado no módulo) igual ao useToast: o componente
 * <ConfirmDialog /> é montado uma única vez no AppLayout e qualquer tela
 * chama `await confirm(...)` para obter um booleano.
 */
const state = ref<ConfirmState>({
  open: false,
  title: '',
  message: '',
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  variant: 'default',
})

let resolver: ((value: boolean) => void) | null = null

function respond(value: boolean) {
  state.value.open = false
  resolver?.(value)
  resolver = null
}

export function useConfirm() {
  function confirm(options: ConfirmOptions | string): Promise<boolean> {
    const opts: ConfirmOptions = typeof options === 'string' ? { message: options } : options
    const isDanger = opts.variant === 'danger'

    // Encerra um diálogo anterior eventualmente pendente sem confirmar.
    resolver?.(false)

    state.value = {
      open: true,
      title: opts.title ?? (isDanger ? 'Confirmar exclusão' : 'Confirmar ação'),
      message: opts.message,
      confirmText: opts.confirmText ?? (isDanger ? 'Excluir' : 'Confirmar'),
      cancelText: opts.cancelText ?? 'Cancelar',
      variant: opts.variant ?? 'default',
    }

    return new Promise<boolean>((resolve) => {
      resolver = resolve
    })
  }

  return {
    state,
    confirm,
    accept: () => respond(true),
    cancel: () => respond(false),
  }
}
