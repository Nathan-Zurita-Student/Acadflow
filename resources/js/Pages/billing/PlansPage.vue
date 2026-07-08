<template>
  <div class="space-y-6" :class="embedded ? '' : 'max-w-5xl mx-auto'">
    <!-- Header (oculto quando embutido nas Configurações) -->
    <div v-if="!embedded">
      <h1 class="text-xl font-bold text-white">Planos & Assinatura</h1>
      <p class="text-dark-400 text-sm mt-0.5">
        Escolha o plano ideal para o seu grupo. Pague com Pix, cartão ou boleto.
      </p>
    </div>

    <!-- Plano atual -->
    <div v-if="current" class="card flex items-center justify-between gap-3">
      <div>
        <p class="text-xs text-dark-400">Seu plano atual</p>
        <p class="text-lg font-bold text-white capitalize">
          {{ currentPlanName }}
          <span v-if="current.status === 'overdue'" class="text-xs text-red-400 font-medium ml-2">pagamento atrasado</span>
          <span v-else-if="current.status === 'canceled'" class="text-xs text-amber-400 font-medium ml-2">cancelado</span>
        </p>
        <!-- Aviso de vigência: até quando o acesso continua válido -->
        <p v-if="statusNote" class="text-xs mt-1" :class="statusNoteClass">{{ statusNote }}</p>
        <p v-if="pendingNote" class="text-xs text-accent-300 mt-1">{{ pendingNote }}</p>
      </div>
      <button
        v-if="current.plan !== 'free' && current.status !== 'canceled'"
        class="btn-danger text-sm flex-shrink-0"
        :disabled="busy"
        @click="onCancel"
      >
        Cancelar assinatura
      </button>
    </div>

    <!-- Alternância Mensal / Anual -->
    <div class="flex items-center justify-center gap-2">
      <div class="inline-flex rounded-lg bg-dark-800 p-1">
        <button
          class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
          :class="cycle === 'monthly' ? 'bg-accent-500 text-white' : 'text-dark-300 hover:text-white'"
          @click="cycle = 'monthly'"
        >Mensal</button>
        <button
          class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors flex items-center gap-1.5"
          :class="cycle === 'annual' ? 'bg-accent-500 text-white' : 'text-dark-300 hover:text-white'"
          @click="cycle = 'annual'"
        >
          Anual
          <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-green-500/20 text-green-400">2 MESES GRÁTIS</span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="card animate-pulse h-72" />
    </div>

    <!-- Erro ao carregar -->
    <div v-else-if="error" class="card text-center py-10">
      <p class="text-dark-200 font-medium">Não foi possível carregar os planos.</p>
      <p class="text-dark-500 text-sm mt-1">Verifique sua conexão e tente novamente.</p>
      <button class="btn-primary mt-4" @click="load">Tentar novamente</button>
    </div>

    <!-- Planos -->
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div
        v-for="plan in plans"
        :key="plan.key"
        class="card flex flex-col relative"
        :class="plan.key === 'pro' ? 'ring-2 ring-accent-500' : ''"
      >
        <span
          v-if="plan.key === 'pro'"
          class="absolute -top-2.5 left-1/2 -translate-x-1/2 bg-accent-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"
        >MAIS POPULAR</span>

        <h3 class="text-base font-bold text-white">{{ plan.name }}</h3>
        <p class="text-xs text-dark-400 mt-1 min-h-[2rem]">{{ plan.description }}</p>

        <div class="my-4">
          <span class="text-2xl font-bold text-white">
            {{ priceFor(plan) === 0 ? 'Grátis' : formatPrice(priceFor(plan)) }}
          </span>
          <span v-if="priceFor(plan) > 0" class="text-xs text-dark-400">
            /{{ cycle === 'annual' ? 'ano' : 'mês' }}
          </span>
          <p v-if="cycle === 'annual' && plan.prices.annual > 0" class="text-[11px] text-green-400 mt-1">
            Economize {{ formatPrice(annualSavings(plan)) }} por ano
          </p>
        </div>

        <ul class="space-y-2 text-sm text-dark-200 flex-1">
          <li class="flex items-center gap-2">
            <Icon name="check" :size="16" class="text-green-400 flex-shrink-0" />
            {{ formatLimit(plan.limits.projects) }} projetos
          </li>
          <li class="flex items-center gap-2">
            <Icon name="check" :size="16" class="text-green-400 flex-shrink-0" />
            {{ formatLimit(plan.limits.members) }} membros por grupo
          </li>
          <li class="flex items-center gap-2">
            <Icon name="check" :size="16" class="text-green-400 flex-shrink-0" />
            {{ formatLimit(plan.limits.ai_per_month) }} usos de IA / mês
          </li>
        </ul>

        <button
          class="mt-5 text-sm w-full"
          :class="buttonInfo(plan).disabled ? 'btn-secondary cursor-default' : 'btn-primary'"
          :disabled="busy || buttonInfo(plan).disabled"
          @click="onSelect(plan)"
        >
          {{ buttonInfo(plan).label }}
        </button>
      </div>
    </div>

    <!-- Modal de CPF/CNPJ (só na 1ª assinatura) -->
    <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="selected = null">
      <div class="card w-full max-w-sm space-y-4">
        <div>
          <h3 class="text-base font-bold text-white">Assinar {{ selected.name }}</h3>
          <p class="text-xs text-dark-400 mt-1">
            {{ formatPrice(priceFor(selected)) }}/{{ cycle === 'annual' ? 'ano' : 'mês' }}.
            Informe seu CPF ou CNPJ para gerar a cobrança.
          </p>
        </div>

        <div>
          <label class="text-xs text-dark-300">CPF ou CNPJ</label>
          <input
            v-model="cpf"
            type="text"
            inputmode="numeric"
            placeholder="000.000.000-00"
            class="input mt-1 w-full"
            @keyup.enter="confirmSubscribe"
          />
        </div>

        <div class="flex gap-2 justify-end">
          <button class="btn-secondary text-sm" :disabled="busy" @click="selected = null">Cancelar</button>
          <button class="btn-primary text-sm" :disabled="busy || cpf.length < 11" @click="confirmSubscribe">
            {{ busy ? 'Processando...' : 'Continuar para pagamento' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { billingApi, type PlansResponse } from '@/api/billing'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useAuthStore } from '@/stores/auth'
import Icon from '@/components/ui/Icon.vue'
import type { Plan, BillingCycle } from '@/types'

defineProps<{ embedded?: boolean }>()

const route = useRoute()
const toast = useToast()
const { confirm } = useConfirm()
const auth = useAuthStore()

const loading = ref(true)
const error = ref(false)
const busy = ref(false)
const plans = ref<Plan[]>([])
const current = ref<PlansResponse['current'] | null>(null)
const cycle = ref<BillingCycle>('monthly')
const selected = ref<Plan | null>(null)
const cpf = ref('')

const RANK: Record<string, number> = { free: 0, pro: 1, ultra: 2 }

const currentPlanName = computed(() => {
  const p = plans.value.find(pl => pl.key === current.value?.plan)
  return p?.name ?? 'Gratuito'
})

// "acesso até DD/MM" — para cancelado/atrasado o usuário continua usando até vencer
const statusNote = computed(() => {
  const c = current.value
  if (!c || c.plan === 'free' || !c.expires_at) return ''
  const date = formatDate(c.expires_at)
  if (c.status === 'canceled') return `Acesso garantido até ${date}.`
  if (c.status === 'overdue') return `Regularize o pagamento. Acesso até ${date}.`
  if (c.status === 'active') return `Renova automaticamente em ${date}.`
  return ''
})

const statusNoteClass = computed(() =>
  current.value?.status === 'overdue' ? 'text-red-400' : 'text-dark-400',
)

const pendingNote = computed(() => {
  const c = current.value
  if (!c?.pending_plan) return ''
  const p = plans.value.find(pl => pl.key === c.pending_plan)
  return `Mudança para ${p?.name ?? c.pending_plan} aguardando confirmação do pagamento.`
})

function formatPrice(value: number | null | undefined) {
  return (value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('pt-BR')
}

function formatLimit(value: number | null) {
  return value === null ? 'Ilimitados' : value
}

function priceFor(plan: Plan) {
  return plan.prices[cycle.value]
}

function annualSavings(plan: Plan) {
  return plan.prices.monthly * 12 - plan.prices.annual
}

/** Rótulo + estado do botão de cada card conforme o plano/ciclo atual do usuário. */
function buttonInfo(plan: Plan): { label: string; disabled: boolean } {
  const c = current.value
  if (plan.key === 'free') return { label: 'Gratuito', disabled: true }
  if (c?.pending_plan === plan.key) return { label: 'Aguardando pagamento', disabled: true }

  const isCurrentPlan = c?.plan === plan.key
  const sameCycle = c?.cycle === cycle.value

  if (isCurrentPlan) {
    if (c?.status === 'canceled') return { label: `Reativar ${plan.name}`, disabled: false }
    if (sameCycle) return { label: 'Plano atual', disabled: true }
    return { label: cycle.value === 'annual' ? 'Mudar para anual' : 'Mudar para mensal', disabled: false }
  }

  if (c && c.plan !== 'free') {
    return RANK[plan.key] > RANK[c.plan]
      ? { label: 'Fazer upgrade', disabled: false }
      : { label: `Mudar para ${plan.name}`, disabled: false }
  }

  return { label: `Assinar ${plan.name}`, disabled: false }
}

async function load() {
  loading.value = true
  error.value = false
  try {
    const { data } = await billingApi.plans()
    plans.value = data.plans ?? []
    current.value = data.current ?? null
    cycle.value = data.current?.cycle ?? 'monthly'
  } catch {
    error.value = true
    toast.error('Não foi possível carregar os planos.')
  } finally {
    loading.value = false
  }
}

function onSelect(plan: Plan) {
  if (plan.key === 'free' || buttonInfo(plan).disabled) return
  // Quem já tem cliente no ASAAS (qualquer assinatura anterior) não reinforma CPF.
  if (current.value && current.value.plan !== 'free') {
    submit(plan)
    return
  }
  selected.value = plan
  cpf.value = ''
}

async function confirmSubscribe() {
  if (!selected.value || cpf.value.length < 11) return
  await submit(selected.value, cpf.value)
}

async function submit(plan: Plan, cpfValue = '') {
  busy.value = true
  try {
    const { data } = await billingApi.subscribe(plan.key, cycle.value, cpfValue)
    if (data.invoice_url) {
      // Redireciona para a página de pagamento do ASAAS (Pix/cartão/boleto).
      window.location.href = data.invoice_url
    } else {
      toast.success('Assinatura criada! Verifique seu e-mail para pagar.')
      selected.value = null
      await load()
    }
  } catch {
    toast.error('Não foi possível iniciar a assinatura.')
  } finally {
    busy.value = false
  }
}

async function onCancel() {
  if (!await confirm({ title: 'Cancelar assinatura', message: 'Tem certeza que deseja cancelar a assinatura? Você mantém o acesso até o fim do período já pago.', confirmText: 'Cancelar assinatura', cancelText: 'Voltar', variant: 'danger' })) return
  busy.value = true
  try {
    await billingApi.cancel()
    toast.success('Assinatura cancelada. Você mantém o acesso até o vencimento.')
    await load()
    await auth.fetchMe()
  } catch {
    toast.error('Não foi possível cancelar.')
  } finally {
    busy.value = false
  }
}

onMounted(async () => {
  await load()
  // Voltou do pagamento no ASAAS (callback successUrl).
  if (route.query.pagamento === 'sucesso') {
    toast.success('Pagamento recebido! Seu plano será ativado em instantes.')
    await auth.fetchMe()
  }
})
</script>
