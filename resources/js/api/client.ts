import axios from 'axios'

// Autenticação por SESSÃO/cookie (Sanctum SPA): sem token em localStorage.
// O axios envia o cookie de sessão (withCredentials) e o header X-XSRF-TOKEN
// automaticamente a partir do cookie XSRF-TOKEN.
const api = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

api.interceptors.request.use((config) => {
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const url: string = err.config?.url ?? ''
    const status = err.response?.status
    // 401 → sessão expirou/inexistente. Não redireciona no /auth/me (checagem
    // silenciosa) nem se já estivermos na tela de login.
    if (status === 401 && !url.includes('/auth/me') && window.location.pathname !== '/login') {
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

/** Obtém o cookie XSRF-TOKEN antes de requisições que alteram estado. */
export const csrf = () => axios.get('/sanctum/csrf-cookie', { withCredentials: true })

export default api
