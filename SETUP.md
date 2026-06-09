# AcadFlow — Setup

## Pré-requisitos

- PHP 8.3+
- Composer 2+
- Node 18+ / npm 10+
- MySQL 8 (via Laragon, XAMPP ou similar)

---

## 1. Banco de Dados

Abra o **Laragon** (ou seu servidor MySQL) e inicie o MySQL.

Crie o banco:
```sql
CREATE DATABASE acadflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 2. Backend (Laravel)

```bash
cd backend

# Copiar .env (já configurado para MySQL local)
# Edite DB_PASSWORD se necessário

# Rodar migrations + seeders
php artisan migrate:fresh --seed

# Iniciar servidor
php artisan serve
```

O backend ficará em: **http://localhost:8000**

**Contas criadas pelo seeder:**

| Email                  | Senha    | Role   |
|------------------------|----------|--------|
| admin@acadflow.com     | password | admin  |
| nathan@acadflow.com    | password | leader |
| maria@acadflow.com     | password | member |
| joao@acadflow.com      | password | member |
| ana@acadflow.com       | password | member |

---

## 3. Frontend (Vue 3)

```bash
cd frontend

npm install   # se ainda não fez

npm run dev
```

O frontend ficará em: **http://localhost:5173**

---

## Estrutura do Projeto

```
wanderson/
├── backend/               # Laravel 12 API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # AuthController, ProjectController, TaskController...
│   │   │   ├── Requests/          # Form Requests validados
│   │   │   └── Middleware/
│   │   ├── Models/               # User, Project, Task, Tag...
│   │   ├── Policies/             # ProjectPolicy
│   │   └── Services/             # ProjectService (regras de negócio)
│   ├── database/
│   │   ├── migrations/           # 10 migrations
│   │   └── seeders/              # DatabaseSeeder com dados demo
│   └── routes/api.php            # 28 endpoints REST
│
└── frontend/              # Vue 3 + TypeScript + Vite
    └── src/
        ├── api/           # client.ts, auth.ts, projects.ts
        ├── stores/        # Pinia: auth, projects, tasks
        ├── router/        # Vue Router com guards
        ├── layouts/       # AppLayout (sidebar + navbar)
        ├── pages/
        │   ├── auth/      # Login, Register
        │   ├── dashboard/ # Dashboard global
        │   └── projects/  # ProjectDetail, Kanban, Members, Files
        ├── components/
        │   ├── ui/        # StatCard, StatusBadge, ProjectCard, MemberRow...
        │   ├── kanban/    # KanbanColumn, KanbanCard
        │   └── tasks/     # TaskModal (com checklist + comentários)
        └── types/         # TypeScript interfaces
```

---

## Funcionalidades Implementadas

- **Auth**: login, registro, logout, tokens Sanctum
- **Projetos**: CRUD completo, membros, dashboard por projeto
- **Kanban**: drag & drop por colunas (backlog → done)
- **Tarefas**: criação, edição, prioridades, prazos, checklist, comentários
- **Membros**: score de produtividade, grade A/B/C/D, gráficos
- **Arquivos**: upload de PDF/imagens/docs, download, exclusão
- **Dashboard**: métricas globais, gráficos ApexCharts, atividade recente
- **Timeline**: progresso visual por fase do projeto
- **Risco**: cálculo automático (baixo/médio/alto) por prazo e atrasos
- **Dark mode**: interface 100% dark, design inspirado no Linear/Vercel
