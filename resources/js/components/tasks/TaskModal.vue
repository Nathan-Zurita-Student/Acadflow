<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')">
      <div class="w-full max-w-2xl bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700 flex-shrink-0">
          <h2 class="font-semibold text-white">{{ isEdit ? 'Editar Tarefa' : 'Nova Tarefa' }}</h2>
          <button @click="$emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div v-if="isEdit" class="flex items-center gap-1 px-4 sm:px-6 pt-3 flex-shrink-0 overflow-x-auto">
          <button v-for="tab in tabs" :key="tab.id"
            @click="activeTab = tab.id"
            :class="['px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center gap-1.5 flex-shrink-0 whitespace-nowrap',
              activeTab === tab.id ? 'bg-dark-700 text-white font-medium' : 'text-dark-400 hover:text-dark-200']">
            {{ tab.label }}
            <span v-if="tab.id === 'files' && attachments.length"
              class="text-xs bg-accent-600/30 text-accent-400 px-1.5 py-0.5 rounded-full">
              {{ attachments.length }}
            </span>
            <span v-if="tab.id === 'comments' && (detail?.comments?.length ?? 0) > 0"
              class="text-xs bg-dark-600 text-dark-400 px-1.5 py-0.5 rounded-full">
              {{ detail?.comments?.length }}
            </span>
            <span v-if="tab.id === 'checklist' && completedChecklistRatio"
              class="text-xs bg-dark-600 text-dark-400 px-1.5 py-0.5 rounded-full">
              {{ completedChecklistRatio }}
            </span>
          </button>
          <div class="flex-1" />
          <button v-if="isLeader && props.task"
            @click="duplicateTask"
            :disabled="duplicating"
            class="ml-auto text-xs flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-dark-700 text-dark-400 hover:text-dark-200 hover:border-dark-600 transition-colors disabled:opacity-50 flex-shrink-0"
            title="Duplicar tarefa">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Duplicar
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">

          <!-- ── Form tab ─────────────────────────────────── -->
          <div v-if="activeTab === 'form'" class="p-6 space-y-4">
            <div>
              <label class="label">Título *</label>
              <input v-model="form.title" class="input" placeholder="Título da tarefa" required />
            </div>
            <div>
              <label class="label">Descrição</label>
              <textarea v-model="form.description" class="input resize-none" rows="3" placeholder="Descreva a tarefa..." />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Status</label>
                <select v-model="form.status" class="input">
                  <option value="backlog">Backlog</option>
                  <option value="pending">Pendente</option>
                  <option value="in_progress">Em andamento</option>
                  <option value="review">Revisão</option>
                  <option value="done">Concluída</option>
                </select>
              </div>
              <div>
                <label class="label">Prioridade</label>
                <select v-model="form.priority" class="input">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>
            <div>
              <label class="label">Prazo</label>
              <input v-model="form.due_date" type="date" class="input" />
            </div>

            <!-- ── Alocação de membros ─────────────────────── -->
            <div v-if="projectMembers.length">
              <label class="label">
                Membros alocados
                <span v-if="!isLeader" class="ml-1.5 text-xs text-dark-600 font-normal normal-case">(somente o líder pode alterar)</span>
              </label>

              <!-- Selected chips -->
              <div v-if="selectedIds.length" class="flex flex-wrap gap-2 mb-2">
                <div
                  v-for="m in selectedMembers" :key="m.id"
                  class="flex items-center gap-1.5 pl-1.5 pr-2 py-1 rounded-full text-xs font-medium bg-accent-600/15 border border-accent-500/25 text-accent-300"
                >
                  <UserAvatar :user="m" class="w-5 h-5 rounded-full bg-accent-600/40 text-xs font-bold" />
                  {{ m.name.split(' ')[0] }}
                  <button
                    v-if="isLeader"
                    @click="toggleMember(m.id)"
                    class="ml-0.5 hover:text-white transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Picker (leader only) -->
              <div v-if="isLeader" class="relative" ref="pickerRef">
                <button
                  type="button"
                  @click="pickerOpen = !pickerOpen"
                  class="w-full flex items-center justify-between px-3 py-2 rounded-xl border border-dark-700 bg-dark-900 hover:border-dark-600 text-sm text-dark-400 hover:text-dark-200 transition-colors"
                >
                  <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    {{ selectedIds.length ? 'Adicionar / remover membros' : 'Alocar membros para esta tarefa' }}
                  </span>
                  <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': pickerOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown -->
                <div
                  v-if="pickerOpen"
                  class="absolute z-10 top-full left-0 right-0 mt-1 bg-dark-800 border border-dark-700 rounded-xl shadow-2xl overflow-hidden"
                >
                  <!-- Search -->
                  <div class="p-2 border-b border-dark-700">
                    <input
                      v-model="memberSearch"
                      placeholder="Buscar membro..."
                      class="w-full text-sm bg-dark-900 border border-dark-700 rounded-lg px-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500"
                      @click.stop
                    />
                  </div>

                  <!-- Member list -->
                  <div class="max-h-52 overflow-y-auto">
                    <div
                      v-for="m in filteredMembers" :key="m.id"
                      @click="toggleMember(m.id)"
                      class="flex items-center gap-3 px-3 py-2.5 hover:bg-dark-700 cursor-pointer transition-colors"
                    >
                      <!-- Checkbox -->
                      <div
                        class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                        :class="isSelected(m.id)
                          ? 'bg-accent-600 border-accent-600'
                          : 'border-dark-600 bg-dark-800'"
                      >
                        <svg v-if="isSelected(m.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                      </div>

                      <!-- Avatar -->
                      <UserAvatar :user="m" class="w-7 h-7 rounded-full bg-accent-600/30 text-xs font-bold text-accent-300 flex-shrink-0" />

                      <!-- Info -->
                      <div class="flex-1 min-w-0">
                        <p class="text-sm text-dark-200 font-medium truncate">{{ m.name }}</p>
                        <p class="text-xs text-dark-500 truncate">{{ m.email }}</p>
                      </div>

                      <!-- Role badge -->
                      <span
                        class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                        :class="m.role === 'leader' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-dark-700 text-dark-500'"
                      >{{ m.role === 'leader' ? 'Líder' : 'Membro' }}</span>
                    </div>

                    <p v-if="!filteredMembers.length" class="text-sm text-dark-600 text-center py-4">
                      Nenhum membro encontrado
                    </p>
                  </div>

                  <!-- Footer -->
                  <div class="p-2 border-t border-dark-700 flex items-center justify-between">
                    <span class="text-xs text-dark-500">{{ selectedIds.length }} selecionado{{ selectedIds.length !== 1 ? 's' : '' }}</span>
                    <button
                      @click="pickerOpen = false"
                      class="text-xs text-accent-400 hover:text-accent-300 font-medium px-2 py-1 rounded-lg hover:bg-accent-500/10 transition-colors"
                    >Confirmar</button>
                  </div>
                </div>
              </div>

              <!-- Read-only for non-leaders -->
              <p v-else-if="!selectedIds.length" class="text-sm text-dark-600 italic">
                Nenhum membro alocado
              </p>
            </div>

            <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="$emit('close')" class="btn-secondary flex-1 justify-center">Cancelar</button>
              <button @click="submit" :disabled="saving" class="btn-primary flex-1 justify-center">
                <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span v-else>{{ isEdit ? 'Salvar' : 'Criar' }}</span>
              </button>
            </div>
          </div>

          <!-- ── Checklist tab ───────────────────────────── -->
          <div v-if="activeTab === 'checklist'" class="p-6">
            <div class="flex gap-2 mb-4">
              <input v-model="newCheckItem" class="input flex-1" placeholder="Novo item..." @keyup.enter="addCheckItem" />
              <button @click="addCheckItem" class="btn-primary">Adicionar</button>
            </div>
            <div class="space-y-2">
              <div v-for="item in detail?.checklists ?? []" :key="item.id"
                class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-dark-700/50 group">
                <input type="checkbox" :checked="item.completed"
                  @change="toggleChecklist(item.id, !item.completed)"
                  class="w-4 h-4 rounded border-dark-600 bg-dark-700 text-accent-500 focus:ring-accent-500" />
                <span :class="['flex-1 text-sm', item.completed ? 'line-through text-dark-500' : 'text-dark-200']">
                  {{ item.title }}
                </span>
                <button @click="deleteChecklist(item.id)"
                  class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-600/20 text-dark-500 hover:text-red-400">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <p v-if="!detail?.checklists?.length" class="text-sm text-dark-500 text-center py-4">
                Nenhum item na checklist
              </p>
            </div>
          </div>

          <!-- ── Comments tab (WhatsApp-style) ───────────── -->
          <div v-if="activeTab === 'comments'" class="flex flex-col h-[60vh] sm:h-[460px]">

            <!-- Presence / typing bar -->
            <div class="flex items-center justify-between px-3 py-1.5 border-b border-dark-700/50 bg-dark-800/40 text-[11px] min-h-[28px] flex-shrink-0">
              <span class="text-dark-500 flex items-center gap-1.5 min-w-0 truncate">
                <template v-if="onlineOthers.length">
                  <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0" />
                  <span class="truncate">{{ onlineOthers.map(u => u.name.split(' ')[0]).join(', ') }} {{ onlineOthers.length === 1 ? 'está vendo' : 'estão vendo' }}</span>
                </template>
                <template v-else>
                  <span class="inline-block w-1.5 h-1.5 rounded-full bg-dark-600 flex-shrink-0" />
                  Ninguém mais vendo agora
                </template>
              </span>
              <span v-if="typingText" class="text-accent-400 italic flex-shrink-0 ml-2">{{ typingText }}</span>
            </div>

            <!-- Messages -->
            <div ref="commentsContainer" class="flex-1 overflow-y-auto min-h-0 px-3 py-3 space-y-0.5 bg-dark-900/30">

              <div v-if="!groupedComments.length" class="flex flex-col items-center gap-2 py-14 text-dark-600">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="text-sm">Nenhuma mensagem ainda.</p>
              </div>

              <template v-for="item in groupedComments" :key="item.comment.id">

                <!-- Date separator -->
                <div v-if="item.dateSeparator" class="flex items-center gap-2 py-2 my-1">
                  <div class="flex-1 h-px bg-dark-700/50" />
                  <span class="text-[10px] text-dark-500 bg-dark-800 border border-dark-700 px-2.5 py-0.5 rounded-full">
                    {{ item.dateSeparator }}
                  </span>
                  <div class="flex-1 h-px bg-dark-700/50" />
                </div>

                <!-- Own message (right) -->
                <div v-if="item.isOwn" class="flex justify-end items-end gap-1.5"
                  :class="item.isFirstInGroup ? 'mt-3' : 'mt-0.5'">
                  <div class="max-w-[78%]">
                    <div class="px-3.5 py-2 shadow-sm"
                      :class="[
                        'bg-accent-600 text-white',
                        item.isFirstInGroup ? 'rounded-2xl rounded-br-sm' : 'rounded-2xl',
                        item.pending ? 'opacity-70' : '',
                      ]">
                      <p class="text-sm leading-relaxed break-words" v-html="renderMd(item.comment.content)" />
                      <div class="flex items-center justify-end gap-1 mt-0.5">
                        <time class="text-[10px] text-white/55">{{ msgTime(item.comment.created_at) }}</time>
                        <!-- Enviando -->
                        <svg v-if="item.pending" class="w-3 h-3 text-white/35" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                        </svg>
                        <!-- Enviada: 1 check -->
                        <svg v-else-if="statusOf(item.comment) === 'sent'" class="w-3.5 h-3.5 text-white/55" viewBox="0 0 12 11" fill="currentColor">
                          <path d="M11.071.653a.75.75 0 0 1 .025 1.06l-6.5 7a.75.75 0 0 1-1.085.008l-3-3.2a.75.75 0 0 1 1.078-1.042l2.445 2.608 5.977-6.409a.75.75 0 0 1 1.06-.025z"/>
                        </svg>
                        <!-- Entregue (cinza) / Lida por todos (azul): 2 checks -->
                        <svg v-else class="w-3.5 h-3.5" :class="statusOf(item.comment) === 'read' ? 'text-sky-300' : 'text-white/55'" viewBox="0 0 16 11" fill="currentColor">
                          <path d="M11.071.653a.75.75 0 0 1 .025 1.06l-6.5 7a.75.75 0 0 1-1.085.008l-3-3.2a.75.75 0 0 1 1.078-1.042l2.445 2.608 5.977-6.409a.75.75 0 0 1 1.06-.025zM14.621.653a.75.75 0 0 1 .025 1.06l-6.5 7a.75.75 0 0 1-1.083.01L5.96 7.19a.75.75 0 1 1 1.08-1.04l.606.628 5.915-6.1a.75.75 0 0 1 1.06-.025z"/>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Other user message (left) -->
                <div v-else class="flex items-end gap-1.5"
                  :class="item.isFirstInGroup ? 'mt-3' : 'mt-0.5'">
                  <!-- Avatar placeholder to keep alignment -->
                  <div class="w-7 flex-shrink-0">
                    <UserAvatar
                      v-if="item.isLastInGroup"
                      :user="item.comment.user"
                      class="w-7 h-7 rounded-full text-xs font-bold"
                      :style="{ background: userBubbleBg(item.comment.user.id), color: userNameColor(item.comment.user.id) }"
                    />
                  </div>
                  <div class="max-w-[78%]">
                    <p v-if="item.isFirstInGroup" class="text-[11px] font-semibold mb-0.5 pl-1"
                      :style="{ color: userNameColor(item.comment.user.id) }">
                      {{ item.comment.user.name.split(' ')[0] }}
                    </p>
                    <div class="px-3.5 py-2 shadow-sm border border-dark-600/20"
                      :class="[
                        'bg-dark-700 text-dark-100',
                        item.isFirstInGroup ? 'rounded-2xl rounded-bl-sm' : 'rounded-2xl',
                      ]">
                      <p class="text-sm leading-relaxed break-words" v-html="renderMd(item.comment.content)" />
                      <time class="text-[10px] text-dark-500 mt-0.5 block text-right">{{ msgTime(item.comment.created_at) }}</time>
                    </div>
                  </div>
                </div>

              </template>
            </div>

            <!-- Mention dropdown (above input) -->
            <div v-if="mentionOpen && filteredMentions.length"
              class="border-t border-dark-700 bg-dark-800 max-h-36 overflow-y-auto">
              <button v-for="m in filteredMentions" :key="m.id"
                type="button"
                @mousedown.prevent="selectMention(m)"
                class="w-full flex items-center gap-2.5 px-4 py-2.5 hover:bg-dark-700 transition-colors text-left">
                <UserAvatar
                  :user="m"
                  class="w-7 h-7 rounded-full text-xs font-bold flex-shrink-0"
                  :style="{ background: userBubbleBg(m.id), color: userNameColor(m.id) }"
                />
                <div>
                  <p class="text-sm font-medium text-dark-200">{{ m.name }}</p>
                  <p class="text-[11px] text-dark-500">{{ m.email }}</p>
                </div>
              </button>
            </div>

            <!-- Input bar -->
            <div class="border-t border-dark-700 bg-dark-800 flex-shrink-0">
              <div class="flex items-end gap-2 px-3 py-2.5">
                <textarea
                  ref="commentInputRef"
                  v-model="newComment"
                  :rows="commentRows"
                  class="input resize-none flex-1 py-2 text-sm min-h-0"
                  placeholder="Mensagem... (@mencionar, **negrito**, *itálico*, `código`)"
                  @keydown.enter.exact.prevent="addComment"
                  @keydown.shift.enter.exact="appendNewline"
                  @keydown.escape="mentionOpen = false"
                  @input="onCommentInput"
                />
                <button @click="addComment"
                  :disabled="!newComment.trim() || sending"
                  class="btn-primary flex-shrink-0 w-9 h-9 p-0 flex items-center justify-center rounded-full self-end disabled:opacity-40">
                  <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                  <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                  </svg>
                </button>
              </div>
              <p class="text-[10px] text-dark-600 px-3 pb-2">Enter envia · Shift+Enter nova linha · @ para mencionar</p>
            </div>
          </div>

          <!-- ── Files tab ───────────────────────────────── -->
          <div v-if="activeTab === 'files'" class="p-6 space-y-4">
            <div
              class="border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
              :class="isDraggingFile ? 'border-accent-500 bg-accent-500/5' : 'border-dark-600 hover:border-dark-500 hover:bg-dark-700/30'"
              @dragover.prevent="isDraggingFile = true"
              @dragleave.self="isDraggingFile = false"
              @drop.prevent="onFileDrop"
              @click="fileInput?.click()"
            >
              <input ref="fileInput" type="file" class="hidden" multiple @change="onFileSelect" />
              <div v-if="!uploading">
                <svg class="w-8 h-8 text-dark-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm text-dark-400">Arraste arquivos aqui ou <span class="text-accent-400 font-medium">clique para selecionar</span></p>
                <p class="text-xs text-dark-600 mt-1">Imagens, PDFs, documentos — qualquer formato, até 50MB</p>
              </div>
              <div v-else class="flex flex-col items-center gap-2">
                <div class="w-6 h-6 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin" />
                <p class="text-sm text-dark-400">Enviando {{ uploadQueue.length > 1 ? uploadQueue.length + ' arquivos' : 'arquivo' }}...</p>
                <div class="w-48 h-1.5 bg-dark-700 rounded-full overflow-hidden">
                  <div class="h-full bg-accent-500 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
                </div>
              </div>
            </div>

            <div v-if="attachments.length" class="space-y-2">
              <div v-for="file in attachments" :key="file.id"
                class="flex items-center gap-3 p-3 rounded-xl bg-dark-700/40 border border-dark-700 hover:border-dark-600 group transition-colors">
                <!-- Miniatura para imagens, ícone para o resto -->
                <a v-if="file.mime_type.startsWith('image/')" :href="file.url" target="_blank" rel="noopener"
                  class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 bg-dark-900 block" @click.stop>
                  <img :src="file.url" :alt="file.name" loading="lazy" class="w-full h-full object-cover" />
                </a>
                <div v-else class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" :class="fileIconBg(file.mime_type)">
                  <svg class="w-4 h-4" :class="fileIconColor(file.mime_type)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="fileIconPath(file.mime_type)" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-dark-200 truncate">{{ file.name }}</p>
                  <p class="text-xs text-dark-500">{{ formatSize(file.size) }} · {{ timeAgo(file.created_at) }}</p>
                </div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <a :href="file.url" target="_blank" rel="noopener"
                    class="p-1.5 rounded-lg hover:bg-dark-600 text-dark-400 hover:text-white transition-colors" title="Abrir" @click.stop>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                  </a>
                  <a :href="file.url" :download="file.name"
                    class="p-1.5 rounded-lg hover:bg-dark-600 text-dark-400 hover:text-white transition-colors" title="Baixar" @click.stop>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                  </a>
                  <button @click="deleteAttachment(file.id)"
                    class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-400 hover:text-red-400 transition-colors" title="Remover">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-else-if="!uploading" class="text-sm text-dark-600 text-center py-2">Nenhum arquivo anexado ainda</p>
          </div>

          <!-- ── Pomodoro tab ────────────────────────────── -->
          <div v-if="activeTab === 'pomodoro'">
            <PomodoroTimer
              v-if="props.task"
              :project-id="props.projectId"
              :task-id="props.task.id"
            />
            <div v-else class="p-6 text-center text-dark-500 text-sm">
              Salve a tarefa primeiro para usar o Pomodoro Timer.
            </div>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { useTasksStore } from '@/stores/tasks'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useRealtime } from '@/composables/useRealtime'
import { tasksApi, attachmentsApi } from '@/api/projects'
import PomodoroTimer from '@/components/tasks/PomodoroTimer.vue'
import type { Task, TaskStatus, Attachment, Comment, ChecklistItem } from '@/types'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import { useTimeAgo } from '@/composables/useTimeAgo'

const props = defineProps<{
  projectId: number
  task?: Task
  defaultStatus?: TaskStatus
  isLeader?: boolean
}>()
const emit = defineEmits(['close', 'saved'])

const store         = useTasksStore()
const projectsStore = useProjectsStore()
const authStore     = useAuthStore()
const toast         = useToast()
const { currentProject } = storeToRefs(projectsStore)

const currentUserId = computed(() => authStore.user?.id)

// ── tempo real (chat instantâneo, presença, digitação, recibos) ──
const realtime = useRealtime()
let presenceCh: any = null
const onlineUsers  = ref<Array<{ id: number; name: string; avatar?: string | null }>>([])
const typingUsers  = ref<Record<number, string>>({})
const typingTimers: Record<number, ReturnType<typeof setTimeout>> = {}
let lastTypingSent = 0

const onlineOthers = computed(() => onlineUsers.value.filter(u => u.id !== currentUserId.value))
const typingText = computed(() => {
  const names = Object.values(typingUsers.value).map(n => n.split(' ')[0])
  if (!names.length) return ''
  if (names.length === 1) return `${names[0]} está digitando...`
  if (names.length === 2) return `${names[0]} e ${names[1]} estão digitando...`
  return 'Várias pessoas estão digitando...'
})
function statusOf(c: any): string { return c.status ?? 'sent' }

// Per-user chat bubble colors (hashed by user id)
const CHAT_COLORS = [
  { name: '#6366f1', bg: 'rgba(99,102,241,0.18)' },
  { name: '#10b981', bg: 'rgba(16,185,129,0.18)' },
  { name: '#f59e0b', bg: 'rgba(245,158,11,0.18)' },
  { name: '#3b82f6', bg: 'rgba(59,130,246,0.18)' },
  { name: '#ec4899', bg: 'rgba(236,72,153,0.18)' },
  { name: '#8b5cf6', bg: 'rgba(139,92,246,0.18)' },
  { name: '#14b8a6', bg: 'rgba(20,184,166,0.18)' },
  { name: '#f97316', bg: 'rgba(249,115,22,0.18)' },
]
function userNameColor(id: number) { return CHAT_COLORS[id % CHAT_COLORS.length].name }
function userBubbleBg(id: number)  { return CHAT_COLORS[id % CHAT_COLORS.length].bg }

const isEdit       = !!props.task
const saving       = ref(false)
const duplicating  = ref(false)
const error        = ref('')
const activeTab    = ref('form')
const detail       = ref<Task | null>(null)
const newComment        = ref('')
const commentRows       = ref(1)
const sending           = ref(false)
const newCheckItem      = ref('')
const commentsContainer = ref<HTMLElement | null>(null)
const commentInputRef   = ref<HTMLTextAreaElement | null>(null)
const mentionOpen       = ref(false)
const mentionQuery      = ref('')
let commentsPollInterval: ReturnType<typeof setInterval> | null = null

// ── member picker ─────────────────────────────────────
const pickerOpen   = ref(false)
const memberSearch = ref('')
const pickerRef    = ref<HTMLElement | null>(null)
const selectedIds  = ref<number[]>(
  props.task?.assignees?.map(a => a.id) ?? []
)

const projectMembers = computed(() => currentProject.value?.members ?? [])

const filteredMembers = computed(() => {
  const q = memberSearch.value.toLowerCase()
  if (!q) return projectMembers.value
  return projectMembers.value.filter(m =>
    m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q)
  )
})

const selectedMembers = computed(() =>
  projectMembers.value.filter(m => selectedIds.value.includes(m.id))
)

function isSelected(id: number) { return selectedIds.value.includes(id) }

function toggleMember(id: number) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

function onOutsideClick(e: MouseEvent) {
  if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) {
    pickerOpen.value = false
  }
}
onMounted(() => document.addEventListener('mousedown', onOutsideClick))
onUnmounted(() => {
  document.removeEventListener('mousedown', onOutsideClick)
  window.removeEventListener('focus', markReadIfViewing)
  stopCommentsPolling()
  Object.values(typingTimers).forEach(t => clearTimeout(t))
})

// ── files ─────────────────────────────────────────────
const fileInput    = ref<HTMLInputElement | null>(null)
const attachments  = ref<Attachment[]>([])
const isDraggingFile = ref(false)
const uploading    = ref(false)
const uploadProgress = ref(0)
const uploadQueue  = ref<File[]>([])

const tabs = [
  { id: 'form',      label: 'Detalhes' },
  { id: 'checklist', label: 'Checklist' },
  { id: 'comments',  label: 'Comentários' },
  { id: 'files',     label: 'Arquivos' },
  { id: 'pomodoro',  label: 'Pomodoro' },
]

// ── chat helpers ──────────────────────────────────────
function renderMd(text: string): string {
  const e = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
  return e
    .replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>')
    .replace(/\*([^*\n]+?)\*/g, '<em>$1</em>')
    .replace(/`([^`\n]+?)`/g, '<code class="bg-black/25 px-1 rounded text-xs font-mono">$1</code>')
    .replace(/~~(.+?)~~/gs, '<del class="opacity-60">$1</del>')
    .replace(/@([\wÀ-ſ]+)/g, '<span class="font-semibold text-accent-300">@$1</span>')
    .replace(/\n/g, '<br>')
}

function msgTime(dateStr: string): string {
  return new Date(dateStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function formatDateSep(dateStr: string): string {
  const d = new Date(dateStr)
  const today = new Date(); today.setHours(0,0,0,0)
  const yest  = new Date(today); yest.setDate(yest.getDate()-1)
  d.setHours(0,0,0,0)
  if (d.getTime() === today.getTime()) return 'Hoje'
  if (d.getTime() === yest.getTime())  return 'Ontem'
  return new Date(dateStr).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long' })
}

type LocalComment = Omit<Comment, 'id'> & { id: number | string; pending?: boolean }

const groupedComments = computed(() => {
  const comments: LocalComment[] = detail.value?.comments ?? []
  return comments.map((c, i) => {
    const prev = comments[i - 1]
    const next = comments[i + 1]
    const isOwn = c.user?.id === currentUserId.value
    const prevDateStr = prev ? new Date(prev.created_at).toDateString() : null
    const currDateStr = new Date(c.created_at).toDateString()
    const dateSeparator = prevDateStr !== currDateStr ? formatDateSep(c.created_at) : null
    const closeEnough = (a: LocalComment | undefined, b: LocalComment | undefined) =>
      !!a && !!b &&
      a.user?.id === b.user?.id &&
      Math.abs(new Date(b.created_at).getTime() - new Date(a.created_at).getTime()) < 5 * 60 * 1000
    const isFirstInGroup = !prev || !!dateSeparator || !closeEnough(prev, c)
    const isLastInGroup  = !next || new Date(next.created_at).toDateString() !== currDateStr || !closeEnough(c, next)
    return { comment: c, isOwn, dateSeparator, isFirstInGroup, isLastInGroup, pending: !!c.pending }
  })
})

const filteredMentions = computed(() => {
  if (!mentionOpen.value) return []
  const q = mentionQuery.value.toLowerCase()
  return projectMembers.value
    .filter(m => !q || m.name.toLowerCase().includes(q))
    .slice(0, 5)
})

function onCommentInput() {
  adjustCommentRows()
  sendTyping()
  const match = newComment.value.match(/@([\wÀ-ſ]*)$/)
  if (match) {
    mentionQuery.value = match[1].toLowerCase()
    mentionOpen.value = true
  } else {
    mentionOpen.value = false
  }
}

function selectMention(member: { id: number; name: string }) {
  const first = member.name.split(' ')[0]
  newComment.value = newComment.value.replace(/@[\wÀ-ſ]*$/, `@${first} `)
  mentionOpen.value = false
  nextTick(() => commentInputRef.value?.focus())
}

const completedChecklistRatio = computed(() => {
  const items = detail.value?.checklists ?? []
  if (!items.length) return ''
  const done = items.filter((i: ChecklistItem) => i.completed).length
  return `${done}/${items.length}`
})

const form = ref({
  title:       props.task?.title       ?? '',
  description: props.task?.description ?? '',
  status:      props.task?.status      ?? props.defaultStatus ?? 'backlog',
  priority:    props.task?.priority    ?? 'medium',
  due_date:    props.task?.due_date    ?? '',
})

async function refreshComments() {
  if (!props.task || sending.value) return
  try {
    const res = await tasksApi.get(props.projectId, props.task.id)
    if (detail.value) detail.value.comments = res.data.comments ?? []
  } catch {}
}

function startCommentsPolling() {
  if (commentsPollInterval) return
  commentsPollInterval = setInterval(refreshComments, 5000)
}

function stopCommentsPolling() {
  if (commentsPollInterval) { clearInterval(commentsPollInterval); commentsPollInterval = null }
}

// ── handlers de tempo real ──
function onCommentPosted(e: any) {
  if (!detail.value) return
  const list = (detail.value.comments ??= []) as LocalComment[]
  if (list.some(c => c.id === e.id)) return
  list.push({ ...e })
  nextTick(scrollCommentsToBottom)
  if (e.user?.id !== currentUserId.value && props.task) {
    tasksApi.markCommentsDelivered(props.projectId, props.task.id).catch(() => {})
    markReadIfViewing()
  }
}

function onCommentStatus(e: { comment_id: number; status: string }) {
  const list = detail.value?.comments as LocalComment[] | undefined
  const c = list?.find(x => x.id === e.comment_id)
  if (c) (c as any).status = e.status
}

function onTyping(e: { id: number; name: string }) {
  if (e.id === currentUserId.value) return
  typingUsers.value = { ...typingUsers.value, [e.id]: e.name }
  if (typingTimers[e.id]) clearTimeout(typingTimers[e.id])
  typingTimers[e.id] = setTimeout(() => {
    const copy = { ...typingUsers.value }; delete copy[e.id]; typingUsers.value = copy
  }, 3000)
}

function sendTyping() {
  if (!realtime.enabled || !presenceCh || !props.task) return
  const now = Date.now()
  if (now - lastTypingSent < 1500) return
  lastTypingSent = now
  presenceCh.whisper('typing', { id: currentUserId.value, name: authStore.user?.name ?? '' })
}

function markReadIfViewing() {
  if (!props.task) return
  if (activeTab.value === 'comments' && !document.hidden) {
    tasksApi.markCommentsRead(props.projectId, props.task.id).catch(() => {})
  }
}

function setupRealtime() {
  if (!realtime.enabled || !props.task) return
  const taskId = props.task.id

  realtime.privateChannel(`task.${taskId}`)
    ?.listen('.comment.posted', onCommentPosted)
    ?.listen('.comment.status', onCommentStatus)

  presenceCh = realtime.presence(`task-presence.${taskId}`)
  presenceCh?.here((users: any[]) => { onlineUsers.value = users })
  presenceCh?.joining((u: any) => { if (!onlineUsers.value.some(x => x.id === u.id)) onlineUsers.value.push(u) })
  presenceCh?.leaving((u: any) => { onlineUsers.value = onlineUsers.value.filter(x => x.id !== u.id) })
  presenceCh?.listenForWhisper('typing', onTyping)
}

watch(activeTab, (tab) => {
  if (tab === 'comments') {
    if (!realtime.enabled) startCommentsPolling()
    nextTick(() => scrollCommentsToBottom())
    markReadIfViewing()
  } else {
    stopCommentsPolling()
  }
})

function scrollCommentsToBottom() {
  if (commentsContainer.value) {
    commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight
  }
}

onMounted(async () => {
  if (isEdit && props.task) {
    const res = await tasksApi.get(props.projectId, props.task.id)
    detail.value = res.data
    attachments.value = res.data.attachments ?? []
    setupRealtime()
  }
  if (!currentProject.value || currentProject.value.id !== props.projectId) {
    await projectsStore.fetchProject(props.projectId)
  }
  window.addEventListener('focus', markReadIfViewing)
})

async function submit() {
  if (!form.value.title.trim()) return
  error.value = ''
  saving.value = true
  try {
    const payload = {
      ...form.value,
      ...(props.isLeader ? { assignee_ids: selectedIds.value } : {}),
    }
    if (isEdit && props.task) {
      await store.updateTask(props.projectId, props.task.id, payload)
      toast.success('Tarefa atualizada.')
    } else {
      await store.createTask(props.projectId, payload)
      toast.success('Tarefa criada.')
    }
    emit('saved')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}

async function duplicateTask() {
  if (!props.task) return
  duplicating.value = true
  try {
    await store.createTask(props.projectId, {
      title: form.value.title + ' (cópia)',
      description: form.value.description,
      status: form.value.status,
      priority: form.value.priority,
      due_date: form.value.due_date,
      assignee_ids: selectedIds.value,
    })
    toast.success('Tarefa duplicada.')
    emit('saved')
  } catch {
    toast.error('Erro ao duplicar tarefa.')
  } finally {
    duplicating.value = false
  }
}

// ── checklist ─────────────────────────────────────────
async function addCheckItem() {
  if (!newCheckItem.value.trim() || !props.task) return
  const res = await tasksApi.addChecklist(props.projectId, props.task.id, newCheckItem.value)
  detail.value?.checklists?.push(res.data)
  newCheckItem.value = ''
}

async function toggleChecklist(id: number, completed: boolean) {
  if (!props.task) return
  await tasksApi.updateChecklist(props.projectId, props.task.id, id, completed)
  const item = detail.value?.checklists?.find((c: { id: number }) => c.id === id)
  if (item) item.completed = completed
}

async function deleteChecklist(id: number) {
  if (!props.task) return
  await tasksApi.deleteChecklist(props.projectId, props.task.id, id)
  if (detail.value?.checklists)
    detail.value.checklists = detail.value.checklists.filter((c: { id: number }) => c.id !== id)
}

// ── comments ──────────────────────────────────────────
async function addComment() {
  const text = newComment.value.trim()
  if (!text || !props.task || sending.value) return
  mentionOpen.value = false
  newComment.value = ''
  commentRows.value = 1
  sending.value = true

  // Optimistic update — message appears instantly
  const tempId = `pending-${Date.now()}`
  const optimistic: LocalComment = {
    id: tempId,
    content: text,
    user: { id: currentUserId.value!, name: authStore.user?.name ?? 'Você', avatar: null },
    created_at: new Date().toISOString(),
    pending: true,
  }
  if (!detail.value!.comments) detail.value!.comments = []
  const localComments = detail.value!.comments as LocalComment[]
  localComments.push(optimistic)
  nextTick(() => scrollCommentsToBottom())

  try {
    const res = await tasksApi.addComment(props.projectId, props.task.id, text)
    const real = res.data as LocalComment
    const idx = localComments.findIndex(c => c.id === tempId)
    const alreadyReal = localComments.some(c => c.id === real.id) // broadcast pode ter chegado antes
    if (idx !== -1) {
      if (alreadyReal) localComments.splice(idx, 1)
      else localComments.splice(idx, 1, real)
    } else if (!alreadyReal) {
      localComments.push(real)
    }
  } catch {
    detail.value!.comments = localComments.filter(c => c.id !== tempId) as Comment[]
  } finally {
    sending.value = false
  }
}

function appendNewline() {
  newComment.value += '\n'
  nextTick(adjustCommentRows)
}

function adjustCommentRows() {
  const lines = newComment.value.split('\n').length
  commentRows.value = Math.min(Math.max(lines, 1), 4)
}

// ── file upload ───────────────────────────────────────
function onFileSelect(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  if (files.length) uploadFiles(files)
}
function onFileDrop(e: DragEvent) {
  isDraggingFile.value = false
  const files = Array.from(e.dataTransfer?.files ?? [])
  if (files.length) uploadFiles(files)
}
async function uploadFiles(files: File[]) {
  if (!props.task) return
  uploadQueue.value = files
  uploading.value = true
  uploadProgress.value = 0
  for (let i = 0; i < files.length; i++) {
    try {
      const { data } = await attachmentsApi.upload(props.projectId, files[i], props.task.id)
      attachments.value.unshift(data)
    } catch {}
    uploadProgress.value = Math.round(((i + 1) / files.length) * 100)
  }
  uploading.value = false
  uploadQueue.value = []
  if (fileInput.value) fileInput.value.value = ''
}
async function deleteAttachment(id: number) {
  await attachmentsApi.delete(props.projectId, id)
  attachments.value = attachments.value.filter(a => a.id !== id)
}

// ── file helpers ──────────────────────────────────────
function fileIconPath(mime: string) {
  if (mime.startsWith('image/')) return 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'
  if (mime === 'application/pdf') return 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
  if (mime.includes('video')) return 'M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'
  return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
}
function fileIconBg(mime: string) {
  if (mime.startsWith('image/')) return 'bg-purple-500/15'
  if (mime === 'application/pdf') return 'bg-red-500/15'
  if (mime.includes('video')) return 'bg-blue-500/15'
  return 'bg-emerald-500/15'
}
function fileIconColor(mime: string) {
  if (mime.startsWith('image/')) return 'text-purple-400'
  if (mime === 'application/pdf') return 'text-red-400'
  if (mime.includes('video')) return 'text-blue-400'
  return 'text-emerald-400'
}
function formatSize(bytes: number) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
const { timeAgo } = useTimeAgo()
</script>
