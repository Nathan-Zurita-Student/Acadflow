<template>
  <div class="space-y-6 animate-fade-in" :class="embedded ? '' : 'max-w-5xl mx-auto'">
    <!-- Header (oculto quando embutido nas Configurações) -->
    <div v-if="!embedded">
      <h1 class="text-xl font-bold text-white">Planos & Assinatura</h1>
      <p class="text-dark-400 text-sm mt-0.5">
        Escolha o plano ideal para o seu grupo. Pague com Pix, cartão ou boleto.
      </p>
    </div>

    <!-- Plano atual -->
    <div v-if="current" class="card flex items-center justify-between">
      <div>
        <p class="text-xs text-dark-400">Seu plano atual</p>
        <p class="text-lg font-bold text-white capitalize">
          {{ currentPlanName }}
          <span v-if="current.status === 'overdue'" class="text-xs text-red-400 font-medium ml-2">pagamento atrasado</span>
          <span v-else-if="current.status === 'canceled'" class="text-xs text-amber-400 font-medium ml-2">cancelado</span>
        </p>
      </div>
      <button
        v-if="current.plan !== 'free' && current.status !== 'canceled'"
        class="btn-danger text-sm"
        :disabled="busy"
        @click="onCancel"
      >
        Cancelar assinatura
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="card animate-pulse h-72" />
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
            {{ plan.price === 0 ? 'Grátis' : formatPrice(plan.price) }}
          </span>
          <span v-if="plan.price > 0" class="text-xs text-dark-400">/mês</span>
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
          :class="current?.plan === plan.key ? 'btn-secondary cursor-default' : 'btn-primary'"
          :disabled="busy || current?.plan === plan.key || plan.key === 'free'"
          @click="onSelect(plan)"
        >
          <template v-if="current?.plan === plan.key">Plano atual</template>
          <template v-else-if="plan.key === 'free'">Gratuito</template>
          <template v-else>Assinar {{ plan.name }}</template>
        </button>
      </div>
    </div>

    <!-- Modal de CPF/CNPJ -->
    <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="selected = null">
      <div class="card w-full max-w-sm space-y-4">
        <div>
          <h3 class="text-base font-bold text-white">Assinar {{ selected.name }}</h3>
          <p class="text-xs text-dark-400 mt-1">
            {{ formatPrice(selected.price) }}/mês. Informe seu CPF ou CNPJ para gerar a cobrança.
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
import { useAuthStore } from '@/stores/auth'
import Icon from '@/components/ui/Icon.vue'
import type { Plan } from '@/types'

defineProps<{ embedded?: boolean }>()

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()

const loading = ref(true)
const busy = ref(false)
const plans = ref<Plan[]>([])
const current = ref<PlansResponse['current'] | null>(null)
const selected = ref<Plan | null>(null)
const cpf = ref('')

const currentPlanName = computed(() => {
  const p = plans.value.find(pl => pl.key === current.value?.plan)
  return p?.name ?? 'Gratuito'
})

function formatPrice(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatLimit(value: number | null) {
  return value === null ? 'Ilimitados' : value
}

async function load() {
  loading.value = true
  try {
    const { data } = await billingApi.plans()
    plans.value = data.plans
    current.value = data.current
  } catch {
    toast.error('Não foi possível carregar os planos.')
  } finally {
    loading.value = false
  }
}

function onSelect(plan: Plan) {
  if (plan.key === 'free' || current.value?.plan === plan.key) return
  selected.value = plan
  cpf.value = ''
}

async function confirmSubscribe() {
  if (!selected.value || cpf.value.length < 11) return
  busy.value = true
  try {
    const { data } = await billingApi.subscribe(selected.value.key, cpf.value)
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
  if (!confirm('Tem certeza que deseja cancelar a assinatura?')) return
  busy.value = true
  try {
    await billingApi.cancel()
    toast.success('Assinatura cancelada.')
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
