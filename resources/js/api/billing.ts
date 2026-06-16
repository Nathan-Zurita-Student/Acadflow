import api from './client'
import type { Plan, PlanKey, BillingCycle } from '@/types'

export interface PlansResponse {
  current: {
    plan: PlanKey
    status: string | null
    cycle: BillingCycle
    pending_plan: PlanKey | null
    expires_at: string | null
  }
  plans: Plan[]
}

export interface SubscribeResponse {
  message: string
  invoice_url: string | null
  subscription_id: string
}

export const billingApi = {
  plans: () => api.get<PlansResponse>('/plans'),
  subscribe: (plan: PlanKey, cycle: BillingCycle, cpf_cnpj: string) =>
    api.post<SubscribeResponse>('/subscriptions', { plan, cycle, cpf_cnpj }),
  cancel: () => api.post('/subscriptions/cancel'),
}
