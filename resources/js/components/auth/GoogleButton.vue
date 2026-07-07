<template>
  <a :href="href" class="google-btn group">
    <span class="sheen" aria-hidden="true" />
    <svg class="h-[18px] w-[18px] transition-transform duration-500 group-hover:rotate-[8deg]" viewBox="0 0 48 48" aria-hidden="true">
      <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
      <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
      <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
      <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
    </svg>
    <span>Continuar com Google</span>
  </a>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ href?: string }>(), { href: '/api/auth/google/redirect' })
</script>

<style scoped>
.google-btn {
  position: relative;
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  overflow: hidden;
  border-radius: 0.75rem;
  padding: 0.7rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(var(--d-100));
  background: rgb(var(--d-800) / 0.6);
  backdrop-filter: blur(8px);
  border: 1px solid rgb(255 255 255 / 0.08);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s, border-color 0.2s;
}
.google-btn:hover {
  background: rgb(var(--d-700) / 0.7);
  border-color: rgb(255 255 255 / 0.14);
}
.google-btn:active { transform: scale(0.98); }

.sheen {
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, transparent 35%, rgb(255 255 255 / 0.08) 50%, transparent 65%);
  transform: translateX(-130%);
  transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.google-btn:hover .sheen { transform: translateX(130%); }
</style>
