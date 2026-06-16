import api from './client'
import type { Plan, PlanKey } from '@/types'

export interface PlansResponse {
  current: {
    plan: PlanKey
    status: string | null
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
  subscribe: (plan: PlanKey, cpf_cnpj: string) =>
    api.post<SubscribeResponse>('/subscriptions', { plan, cpf_cnpj }),
  cancel: () => api.post('/subscriptions/cancel'),
}
