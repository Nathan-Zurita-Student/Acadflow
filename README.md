# AcadFlow

Sistema de gerenciamento de projetos e grupos acadêmicos, desenvolvido com **Laravel 13**, **Vue 3**, **TypeScript**, **Pinia** e **Inertia.js**.

---

## Sumário

1. [Visão Geral](#visão-geral)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Requisitos](#requisitos)
4. [Instalação e Configuração](#instalação-e-configuração)
5. [Estrutura do Projeto](#estrutura-do-projeto)
6. [Banco de Dados](#banco-de-dados)
7. [Backend — Rotas da API](#backend--rotas-da-api)
8. [Backend — Controllers](#backend--controllers)
9. [Backend — Models](#backend--models)
10. [Backend — Services e Policies](#backend--services-e-policies)
11. [Frontend — Páginas e Componentes](#frontend--páginas-e-componentes)
12. [Frontend — Stores Pinia](#frontend--stores-pinia)
13. [Frontend — Camada de API](#frontend--camada-de-api)
14. [Frontend — Composables](#frontend--composables)
15. [Sistema de Notificações em Tempo Real](#sistema-de-notificações-em-tempo-real)
16. [Funcionalidades Principais](#funcionalidades-principais)
17. [Scripts de Desenvolvimento](#scripts-de-desenvolvimento)

---

## Visão Geral

O **AcadFlow** é uma ferramenta de gerenciamento de projetos pensada para grupos acadêmicos. Ele permite criar projetos, organizar tarefas em um quadro Kanban, acompanhar o progresso da equipe, registrar reuniões, gerenciar arquivos e muito mais — tudo em tempo real graças ao Laravel Reverb.

---

## Stack Tecnológico

| Camada | Tecnologia |
|---|---|
| Framework backend | Laravel 13 |
| Autenticação | Laravel Sanctum (tokens) |
| WebSockets / Tempo real | Laravel Reverb |
| ORM | Eloquent |
| Frontend SPA | Vue 3 + TypeScript |
| Roteamento frontend | Vue Router 4 |
| Estado global | Pinia 3 |
| Requisições HTTP | Axios |
| Ponte backend/frontend | Inertia.js |
| Estilização | Tailwind CSS 3 |
| Gráficos | ApexCharts + vue3-apexcharts |
| Drag & Drop | vuedraggable |
| Build tool | Vite 8 |
| Banco de dados | MySQL |
| Testes | Pest PHP 4 |

---

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+
- npm
- MySQL 8+
- Extensões PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

---

## Instalação e Configuração

```bash
# 1. Clonar o repositório
git clone <url-do-repositório> acadflow
cd acadflow

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências Node
npm install

# 4. Copiar e editar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar banco de dados no .env
# DB_DATABASE=acadflow
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Rodar migrations e seeders
php artisan migrate --seed

# 7. Criar link de storage
php artisan storage:link

# 8. Iniciar todos os serviços de desenvolvimento
npm run dev
```

O comando `npm run dev` inicia em paralelo:
- Servidor Laravel (`php artisan serve`)
- Worker de filas (`php artisan queue:listen`)
- Logs em tempo real (`php artisan pail`)
- Vite (compilação do frontend com HMR)

---

## Estrutura do Projeto

```
acadflow/
├── app/
│   ├── Events/               # Eventos da aplicação
│   ├── Http/
│   │   ├── Controllers/Api/  # Controllers da API REST
│   │   ├── Middleware/       # Middlewares HTTP
│   │   ├── Requests/         # Validação de formulários (Form Requests)
│   │   └── Resources/        # Transformação de respostas da API
│   ├── Models/               # Models Eloquent
│   ├── Observers/            # Observers de models
│   ├── Policies/             # Autorização por política
│   ├── Providers/            # Service providers
│   └── Services/             # Lógica de negócio
├── config/                   # Configurações da aplicação
├── database/
│   ├── factories/            # Factories para testes
│   ├── migrations/           # Migrations do banco
│   └── seeders/              # Seeders
├── resources/
│   ├── css/                  # Estilos globais (Tailwind)
│   ├── js/                   # Frontend Vue 3 + TypeScript
│   │   ├── api/              # Módulos de chamada à API
│   │   ├── components/       # Componentes Vue reutilizáveis
│   │   ├── composables/      # Composables Vue
│   │   ├── layouts/          # Layouts de página
│   │   ├── Pages/            # Páginas (roteadas)
│   │   ├── router/           # Configuração do Vue Router
│   │   ├── stores/           # Stores Pinia
│   │   ├── types/            # Definições de tipos TypeScript
│   │   ├── app.ts            # Ponto de entrada da aplicação
│   │   └── echo.ts           # Configuração Laravel Echo
│   └── views/
│       └── app.blade.php     # Template Blade (ponto de montagem Inertia)
├── routes/
│   ├── api.php               # Rotas da API REST
│   └── web.php               # Rota SPA catch-all (Inertia)
├── storage/                  # Arquivos enviados e logs
├── tests/                    # Testes automatizados (Pest)
├── .env.example              # Modelo de variáveis de ambiente
├── composer.json
├── package.json
├── tailwind.config.js
├── tsconfig.json
└── vite.config.ts
```

---

## Banco de Dados

### Diagrama de Entidades

```
users
 └── projects (owner_id)
      ├── project_members  ←→ users (pivot: role)
      ├── tasks
      │    ├── task_assignees  ←→ users (pivot)
      │    ├── task_checklists
      │    ├── task_comments
      │    ├── task_time_logs
      │    ├── task_tags  ←→ tags
      │    └── attachments (morphable)
      ├── attachments (morphable)
      ├── meetings
      ├── project_notes
      ├── project_webhooks
      ├── project_invitations  ←→ users
      ├── project_invite_tokens
      ├── activity_logs
      └── tags
users
 └── notifications
```

### Tabelas

| Tabela | Descrição |
|---|---|
| `users` | Contas de usuário com nome, e-mail, avatar e role |
| `projects` | Projetos com status, deadline, categoria e progresso |
| `project_members` | Pivot entre usuários e projetos com role (admin, leader, member) |
| `tasks` | Tarefas com status, prioridade, posição no kanban e fluxo de aprovação |
| `task_assignees` | Pivot de múltiplos responsáveis por tarefa |
| `task_checklists` | Subtarefas com estado de conclusão |
| `task_comments` | Comentários de discussão em tarefas |
| `task_time_logs` | Registro de horas trabalhadas por tarefa |
| `tags` | Tags de organização vinculadas a projetos |
| `task_tags` | Pivot entre tarefas e tags |
| `attachments` | Arquivos anexados (polymórfico: task ou project) |
| `meetings` | Reuniões agendadas com local e notas |
| `project_notes` | Notas compartilhadas do projeto |
| `project_webhooks` | Webhooks de integração por projeto |
| `project_invitations` | Convites enviados a usuários específicos |
| `project_invite_tokens` | Links de convite com token e validade |
| `notifications` | Notificações de usuário |
| `activity_logs` | Trilha de auditoria de ações |
| `personal_access_tokens` | Tokens Sanctum |
| `jobs` / `cache` | Infraestrutura Laravel |

### Status e Prioridades de Tarefas

**Status (fluxo Kanban):**
`backlog` → `pending` → `in_progress` → `review` → `done`

**Prioridade:**
`low` | `medium` | `high` | `urgent`

**Aprovação:**
`pending_approval` → `approved` | `rejected`

---

## Backend — Rotas da API

Todas as rotas abaixo utilizam o prefixo `/api`. As rotas marcadas com 🔒 requerem autenticação via token Sanctum (`Authorization: Bearer <token>`).

### Autenticação

| Método | Rota | Descrição |
|---|---|---|
| POST | `/auth/register` | Registrar novo usuário |
| POST | `/auth/login` | Login (retorna token) |
| POST | `/auth/logout` | 🔒 Logout |
| GET | `/auth/me` | 🔒 Dados do usuário autenticado |
| POST | `/auth/profile` | 🔒 Atualizar perfil |

### Dashboard

| Método | Rota | Descrição |
|---|---|---|
| GET | `/dashboard` | 🔒 Estatísticas gerais do usuário |
| GET | `/my-tasks` | 🔒 Tarefas atribuídas ao usuário |
| GET | `/users/search` | 🔒 Buscar usuários por nome/e-mail |

### Projetos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects` | 🔒 Listar projetos do usuário |
| POST | `/projects` | 🔒 Criar projeto |
| GET | `/projects/{project}` | 🔒 Detalhes do projeto |
| PUT | `/projects/{project}` | 🔒 Atualizar projeto |
| DELETE | `/projects/{project}` | 🔒 Excluir projeto |
| GET | `/projects/{project}/dashboard` | 🔒 Estatísticas do projeto |
| GET | `/projects/{project}/members` | 🔒 Estatísticas de membros |
| POST | `/projects/{project}/members` | 🔒 Adicionar membro |
| DELETE | `/projects/{project}/members/{userId}` | 🔒 Remover membro |

### Tarefas

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects/{project}/tasks` | 🔒 Listar tarefas |
| POST | `/projects/{project}/tasks` | 🔒 Criar tarefa |
| GET | `/projects/{project}/tasks/{task}` | 🔒 Detalhes da tarefa |
| PUT | `/projects/{project}/tasks/{task}` | 🔒 Atualizar tarefa |
| DELETE | `/projects/{project}/tasks/{task}` | 🔒 Excluir tarefa |
| POST | `/projects/{project}/tasks/reorder` | 🔒 Reordenar tarefas (Kanban) |
| POST | `/projects/{project}/tasks/{task}/comments` | 🔒 Adicionar comentário |
| POST | `/projects/{project}/tasks/{task}/time` | 🔒 Registrar horas |
| POST | `/projects/{project}/tasks/{task}/submit-approval` | 🔒 Submeter para aprovação |
| POST | `/projects/{project}/tasks/{task}/approve` | 🔒 Aprovar tarefa |
| POST | `/projects/{project}/tasks/{task}/reject` | 🔒 Rejeitar tarefa |
| POST | `/projects/{project}/tasks/{task}/checklists` | 🔒 Adicionar checklist |
| PUT | `/projects/{project}/tasks/{task}/checklists/{id}` | 🔒 Atualizar checklist |
| DELETE | `/projects/{project}/tasks/{task}/checklists/{id}` | 🔒 Remover checklist |

### Arquivos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects/{project}/attachments` | 🔒 Listar arquivos |
| POST | `/projects/{project}/attachments` | 🔒 Upload de arquivo |
| GET | `/projects/{project}/attachments/{attachment}/view` | 🔒 Visualizar arquivo |
| GET | `/projects/{project}/attachments/{attachment}/download` | 🔒 Download de arquivo |
| DELETE | `/projects/{project}/attachments/{attachment}` | 🔒 Excluir arquivo |

### Reuniões

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects/{project}/meetings` | 🔒 Listar reuniões |
| POST | `/projects/{project}/meetings` | 🔒 Criar reunião |
| PUT | `/projects/{project}/meetings/{meeting}` | 🔒 Atualizar reunião |
| DELETE | `/projects/{project}/meetings/{meeting}` | 🔒 Excluir reunião |

### Notas

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects/{project}/notes` | 🔒 Listar notas |
| POST | `/projects/{project}/notes` | 🔒 Criar nota |
| PUT | `/projects/{project}/notes/{note}` | 🔒 Atualizar nota |
| DELETE | `/projects/{project}/notes/{note}` | 🔒 Excluir nota |

### Webhooks

| Método | Rota | Descrição |
|---|---|---|
| GET | `/projects/{project}/webhooks` | 🔒 Listar webhooks |
| POST | `/projects/{project}/webhooks` | 🔒 Criar webhook |
| PUT | `/projects/{project}/webhooks/{webhook}` | 🔒 Atualizar webhook |
| DELETE | `/projects/{project}/webhooks/{webhook}` | 🔒 Excluir webhook |
| POST | `/projects/{project}/webhooks/{webhook}/test` | 🔒 Testar webhook |

### Convites

| Método | Rota | Descrição |
|---|---|---|
| GET | `/invite/{token}` | Informações do convite (público) |
| POST | `/invite/{token}/accept` | 🔒 Aceitar convite por link |
| GET | `/invitations/pending` | 🔒 Listar convites pendentes |
| POST | `/invitations/{invitation}/respond` | 🔒 Aceitar ou recusar convite |

### Notificações

| Método | Rota | Descrição |
|---|---|---|
| GET | `/notifications` | 🔒 Listar notificações |
| POST | `/notifications/{notification}/read` | 🔒 Marcar como lida |
| POST | `/notifications/read-all` | 🔒 Marcar todas como lidas |

---

## Backend — Controllers

Todos os controllers estão em `app/Http/Controllers/Api/`.

| Controller | Responsabilidade |
|---|---|
| `AuthController` | Registro, login, logout, perfil do usuário |
| `ProjectController` | CRUD de projetos, membros, estatísticas |
| `TaskController` | CRUD de tarefas, reordenação, aprovação |
| `TaskCommentController` | Comentários em tarefas |
| `TaskChecklistController` | Checklists de tarefas |
| `TaskTimeLogController` | Registro de horas em tarefas |
| `AttachmentController` | Upload, visualização e download de arquivos |
| `MeetingController` | CRUD de reuniões |
| `NoteController` | CRUD de notas do projeto |
| `WebhookController` | CRUD e teste de webhooks |
| `NotificationController` | Listagem e leitura de notificações |
| `InviteController` | Links de convite com token |
| `ProjectInvitationController` | Convites individuais por usuário |
| `DashboardController` | Estatísticas gerais, minhas tarefas, busca de usuários |

---

## Backend — Models

Todos os models estão em `app/Models/`.

### `User`
- Campos: `name`, `display_name`, `email`, `password`, `avatar`, `role`
- Relações: `ownedProjects`, `projects`, `assignedTasks`, `notifications`

### `Project`
- Campos: `name`, `description`, `category`, `status`, `deadline`, `progress`, `owner_id`
- Relações: `owner`, `members`, `tasks`, `tags`, `attachments`, `meetings`, `notes`, `webhooks`

### `Task`
- Campos: `title`, `description`, `status`, `priority`, `due_date`, `position`, `approval_status`, `rejection_note`
- Relações: `project`, `assignees`, `creator`, `tags`, `checklists`, `comments`, `attachments`, `timeLogs`

### `Tag`
- Campos: `name`, `color`, `project_id`
- Relações: `project`, `tasks`

### `TaskChecklist`
- Campos: `title`, `completed`, `position`, `task_id`

### `TaskComment`
- Campos: `content`, `created_by`, `task_id`

### `Attachment`
- Campos: `name`, `mime_type`, `size`, `path`, `attachable_type`, `attachable_id`
- Polymórfico: pertence a `Project` ou `Task`

### `Meeting`
- Campos: `title`, `description`, `scheduled_at`, `location`, `notes`, `project_id`

### `ProjectNote`
- Campos: `title`, `content`, `project_id`, `created_by`

### `ProjectWebhook`
- Campos: `url`, `events` (array), `active`, `project_id`

### `ProjectInvitation`
- Campos: `status` (pending/accepted/rejected), `project_id`, `user_id`

### `ProjectInviteToken`
- Campos: `token`, `expires_at`, `project_id`, `created_by`

### `TaskTimeLog`
- Campos: `duration` (minutos), `task_id`, `logged_by`

### `Notification`
- Campos: `type`, `data` (JSON), `read_at`, `user_id`

### `ActivityLog`
- Campos: `action`, `user_id`, `project_id`, `data` (JSON)

---

## Backend — Services e Policies

### Services (`app/Services/`)

| Service | Responsabilidade |
|---|---|
| `ProjectService` | Criação, atualização, gerenciamento de membros e estatísticas de projetos |
| `NotificationService` | Criação e envio de notificações aos usuários |
| `WebhookService` | Disparo de requisições HTTP para webhooks cadastrados |

### Policies (`app/Policies/`)

| Policy | Descrição |
|---|---|
| `ProjectPolicy` | Controla quem pode visualizar, editar, excluir projetos e gerenciar membros |

### Observers (`app/Observers/`)

| Observer | Descrição |
|---|---|
| `TaskObserver` | Reage a eventos do ciclo de vida da tarefa (criação, atualização, exclusão) |

### Events (`app/Events/`)

| Event | Quando é disparado |
|---|---|
| `CommentPosted` | Quando um comentário é adicionado a uma tarefa |
| `NotificationSent` | Quando uma notificação é enviada a um usuário |

---

## Frontend — Páginas e Componentes

### Páginas (`resources/js/Pages/`)

| Arquivo | Rota | Descrição |
|---|---|---|
| `auth/LoginPage.vue` | `/login` | Tela de login |
| `auth/RegisterPage.vue` | `/register` | Tela de registro |
| `dashboard/DashboardPage.vue` | `/` | Dashboard principal |
| `MyTasksPage.vue` | `/my-tasks` | Tarefas do usuário logado |
| `projects/ProjectsPage.vue` | `/projects` | Lista de projetos |
| `projects/ProjectDetailPage.vue` | `/projects/:id` | Visão geral do projeto |
| `projects/KanbanPage.vue` | `/projects/:id/kanban` | Quadro Kanban |
| `projects/MembersPage.vue` | `/projects/:id/members` | Membros e estatísticas |
| `projects/FilesPage.vue` | `/projects/:id/files` | Arquivos e anexos |
| `projects/MeetingsPage.vue` | `/projects/:id/meetings` | Reuniões |
| `projects/NotesPage.vue` | `/projects/:id/notes` | Notas do projeto |
| `projects/SettingsPage.vue` | `/projects/:id/settings` | Configurações do projeto |
| `AcceptInvitePage.vue` | `/invite/:token` | Aceitar convite por link |

### Componentes UI (`resources/js/components/ui/`)

| Componente | Função |
|---|---|
| `ProjectCard.vue` | Card de projeto na lista |
| `ProjectModal.vue` | Modal de criar/editar projeto |
| `StatCard.vue` | Card de estatística (dashboard) |
| `StatusBadge.vue` | Badge de status da tarefa |
| `PriorityDot.vue` | Indicador de prioridade |
| `RiskBadge.vue` | Badge de nível de risco do projeto |
| `MeetingCard.vue` | Card de reunião |
| `MemberRow.vue` | Linha de membro na lista |
| `UserAvatar.vue` | Avatar do usuário |
| `NotificationBell.vue` | Sino de notificações |
| `NotificationPopup.vue` | Dropdown de notificações |
| `InviteLinkModal.vue` | Modal para gerar link de convite |
| `InviteMemberModal.vue` | Modal para convidar membro específico |
| `ProfileModal.vue` | Modal de perfil do usuário |
| `Toast.vue` | Notificação toast |
| `NavItem.vue` | Item de navegação lateral |
| `TimelineItem.vue` | Entrada de timeline de atividades |

### Componentes Kanban (`resources/js/components/kanban/`)

| Componente | Função |
|---|---|
| `KanbanColumn.vue` | Coluna de status no quadro Kanban |
| `KanbanCard.vue` | Card de tarefa no Kanban (drag & drop) |

### Componentes de Tarefa (`resources/js/components/tasks/`)

| Componente | Função |
|---|---|
| `TaskModal.vue` | Modal completo de criar/editar tarefa |
| `PomodoroTimer.vue` | Timer Pomodoro integrado à tarefa |

### Layout (`resources/js/layouts/`)

| Componente | Função |
|---|---|
| `AppLayout.vue` | Layout principal: sidebar, header, área de conteúdo |

---

## Frontend — Stores Pinia

Todas as stores estão em `resources/js/stores/`.

### `auth.ts`
Gerencia o estado de autenticação do usuário.

| Estado | Tipo | Descrição |
|---|---|---|
| `user` | `User \| null` | Usuário autenticado |
| `token` | `string \| null` | Token de acesso |
| `loading` | `boolean` | Indicador de carregamento |
| `isAuthenticated` | `boolean` (computed) | Se há usuário logado |

**Ações:** `login`, `register`, `logout`, `fetchMe`, `updateProfile`, `clearAuth`

### `projects.ts`
Gerencia projetos e o projeto ativo.

| Estado | Descrição |
|---|---|
| `projects` | Lista de projetos do usuário |
| `currentProject` | Projeto em visualização |
| `currentDashboard` | Dados do dashboard do projeto |
| `loading` | Indicador de carregamento |

**Ações:** `fetchProjects`, `fetchProject`, `fetchDashboard`, `createProject`, `updateProject`, `deleteProject`

### `tasks.ts`
Gerencia tarefas, filtros e criação.

### `notifications.ts`
Gerencia notificações do usuário.

| Estado | Descrição |
|---|---|
| `notifications` | Lista de notificações |
| `unreadCount` | Contagem de não lidas |

**Ações:** `fetch`, `markRead`, `markAllRead`

---

## Frontend — Camada de API

Todos os módulos estão em `resources/js/api/`.

### `client.ts`
Instância Axios configurada com:
- Injeção automática do `Bearer token` (lido da store de auth)
- Redirecionamento para `/login` em respostas 401
- Suporte a `FormData` para upload de arquivos

### `auth.ts`
Funções: `login()`, `register()`, `logout()`, `me()`, `updateProfile()`

### `projects.ts`
Agrupa todas as chamadas relacionadas a projetos:
- Projetos: `list`, `get`, `create`, `update`, `delete`, `dashboard`, `members`, `addMember`, `removeMember`
- Tarefas: `list`, `get`, `create`, `update`, `delete`, `reorder`, `approve`, `reject`, `addChecklist`, `updateChecklist`, `deleteChecklist`, `addComment`, `logTime`
- Arquivos: `list`, `upload`, `view`, `download`, `delete`
- Reuniões: `list`, `create`, `update`, `delete`
- Notas: `list`, `create`, `update`, `delete`
- Webhooks: `list`, `create`, `update`, `delete`, `test`

### `notifications.ts`
Funções: `list()`, `markRead(id)`, `markAllRead()`

---

## Frontend — Composables

Localizados em `resources/js/composables/`.

| Composable | Função |
|---|---|
| `useToast.ts` | Exibe mensagens toast de sucesso/erro |
| `useTimeAgo.ts` | Formata datas relativas ("há 2 horas") |
| `usePolling.ts` | Executa polling de dados em intervalo configurável |

---

## Sistema de Notificações em Tempo Real

O AcadFlow usa **Laravel Reverb** (WebSocket) + **Laravel Echo** (cliente JS) para entregar notificações em tempo real.

### Fluxo

```
Ação do usuário (criar tarefa, comentar, etc.)
        ↓
  Controller / Observer
        ↓
  NotificationService (salva no banco)
        ↓
  Broadcast via Reverb (canal privado do usuário)
        ↓
  Laravel Echo (frontend) recebe o evento
        ↓
  Store de notificações atualiza o estado
        ↓
  NotificationBell exibe o contador em tempo real
```

### Configuração (`.env`)

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Para iniciar o servidor Reverb:
```bash
php artisan reverb:start
```

---

## Funcionalidades Principais

### Gerenciamento de Projetos
- Criar projetos com nome, descrição, categoria, deadline e status
- Dashboard por projeto com gráficos de progresso, distribuição de tarefas e dados semanais
- Controle de risco automático baseado em progresso e prazo
- Convites por link ou por e-mail

### Quadro Kanban
- Colunas: Backlog → Pendente → Em andamento → Revisão → Concluído
- Drag & drop para mover e reordenar tarefas
- Filtros por prioridade, responsável e tag

### Gerenciamento de Tarefas
- Prioridades: baixa, média, alta, urgente
- Múltiplos responsáveis por tarefa
- Subtarefas com checklists
- Comentários com histórico
- Registro de horas trabalhadas
- Timer Pomodoro integrado
- Fluxo de aprovação (submeter → aprovar/rejeitar)
- Anexos de arquivos

### Gestão de Membros
- Roles: admin, líder, membro
- Estatísticas individuais: tarefas concluídas, participação, nota calculada
- Remoção de membros

### Arquivos
- Upload de arquivos com suporte a múltiplos tipos
- Visualização inline e download
- Associação a projetos ou tarefas específicas

### Reuniões
- Agendamento com data/hora, local e descrição
- Registro de notas pós-reunião

### Notas
- Documentação compartilhada por projeto

### Webhooks
- Configuração de endpoints externos
- Seleção de eventos para notificação
- Teste de webhook diretamente pela interface

### Notificações
- Notificações em tempo real via WebSocket
- Marcação individual ou em massa como lida
- Contador de não lidas no header

---

## Scripts de Desenvolvimento

```bash
# Desenvolvimento completo (todos os processos em paralelo)
npm run dev

# Build de produção
npm run build

# Testes PHP (Pest)
php artisan test

# Linting PHP (Pint)
./vendor/bin/pint

# Verificação de tipos TypeScript
npx vue-tsc --noEmit

# Iniciar servidor Reverb (WebSockets)
php artisan reverb:start

# Rodar filas manualmente
php artisan queue:work
```

---

## Variáveis de Ambiente Importantes

| Variável | Descrição |
|---|---|
| `APP_NAME` | Nome da aplicação |
| `APP_URL` | URL base |
| `DB_*` | Configurações do banco de dados |
| `BROADCAST_CONNECTION` | `reverb` para produção, `log` para dev sem WebSocket |
| `REVERB_*` | Configurações do servidor WebSocket |
| `VITE_REVERB_*` | Mesmas configurações expostas ao frontend via Vite |
| `QUEUE_CONNECTION` | `database` (padrão) ou `redis` para produção |
| `FILESYSTEM_DISK` | `local` ou `s3` para produção |
| `SESSION_DRIVER` | `database` (padrão) |
