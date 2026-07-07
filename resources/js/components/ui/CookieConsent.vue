<template>
  <Teleport to="body">
    <Transition name="cc">
      <div v-if="visible" class="fixed inset-x-0 bottom-0 z-[120] flex justify-center px-3 pb-3 sm:pb-5">
        <div class="glass border-gradient w-full max-w-3xl rounded-2xl p-4 shadow-card-float sm:p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex items-start gap-3">
              <span class="cc-icon" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2a10 10 0 1 0 10 10 3.5 3.5 0 0 1-4.5-4.5A3.5 3.5 0 0 1 12 3z" />
                  <circle cx="9.5" cy="11" r="1" fill="currentColor" stroke="none" />
                  <circle cx="14" cy="14.5" r="1" fill="currentColor" stroke="none" />
                  <circle cx="10" cy="15.5" r="1" fill="currentColor" stroke="none" />
                </svg>
              </span>
              <p class="text-[13px] leading-relaxed text-dark-300">
                Usamos cookies essenciais para manter você conectado e melhorar sua experiência.
                Saiba mais na
                <RouterLink to="/privacidade" target="_blank" class="font-medium text-accent-400 hover:text-accent-300">Política de Privacidade</RouterLink>.
              </p>
            </div>
            <div class="flex flex-shrink-0 gap-2 sm:ml-auto">
              <button class="cc-ghost" @click="decide('essential')">Somente essenciais</button>
              <button class="cc-primary" @click="decide('all')">Aceitar</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const KEY = 'acadflow:cookie-consent'
const visible = ref(false)

onMounted(() => {
  try {
    visible.value = !localStorage.getItem(KEY)
  } catch {
    visible.value = false
  }
})

function decide(choice: 'all' | 'essential') {
  try {
    localStorage.setItem(KEY, JSON.stringify({ choice, at: new Date().toISOString() }))
  } catch { /* modo privado etc. */ }
  visible.value = false
}
</script>

<style scoped>
.cc-icon {
  display: inline-flex;
  height: 2.25rem;
  width: 2.25rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.7rem;
  color: rgb(var(--accent-300));
  background: rgb(var(--accent-500) / 0.14);
  border: 1px solid rgb(var(--accent-500) / 0.22);
}

.cc-primary {
  border-radius: 0.6rem;
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(180deg, rgb(var(--accent-500)), rgb(var(--accent-600)));
  box-shadow: 0 6px 18px -8px rgb(var(--accent-600) / 0.6);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.2s;
  white-space: nowrap;
}
.cc-primary:hover { filter: brightness(1.07); }
.cc-primary:active { transform: scale(0.97); }

.cc-ghost {
  border-radius: 0.6rem;
  padding: 0.5rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 500;
  color: rgb(var(--d-300));
  background: rgb(var(--d-800) / 0.6);
  border: 1px solid rgb(255 255 255 / 0.08);
  transition: background 0.2s, color 0.2s;
  white-space: nowrap;
}
.cc-ghost:hover { background: rgb(var(--d-700) / 0.7); color: rgb(var(--d-100)); }

.cc-enter-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
.cc-leave-active { transition: transform 0.3s ease, opacity 0.3s ease; }
.cc-enter-from, .cc-leave-to { opacity: 0; transform: translateY(120%); }
</style>
