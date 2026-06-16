export type PlanKey = 'free' | 'pro' | 'ultra'
export type BillingCycle = 'monthly' | 'annual'

export interface User {
  id: number
  name: string
  display_name: string | null
  email: string
  role: 'admin' | 'leader' | 'member'
  avatar: string | null
  plan?: PlanKey
  plan_status?: string | null
  plan_expires_at?: string | null
  created_at: string
}

export interface Plan {
  key: PlanKey
  name: string
  prices: {
    monthly: number
    annual: number
  }
  description: string
  limits: {
    projects: number | null
    members: number | null
    ai_per_month: number | null
  }
}

export interface Project {
  id: number
  name: string
  description: string | null
  category: string | null
  status: 'planning' | 'active' | 'paused' | 'completed' | 'cancelled'
  deadline: string | null
  progress: number
  owner: Pick<User, 'id' | 'name' | 'avatar'> | null
  members: Array<Pick<User, 'id' | 'name' | 'email' | 'avatar'> & { role: string }>
  tasks_count: number
  is_owner: boolean
  created_at: string
  risk_level?: 'low' | 'medium' | 'high'
}

export type TaskStatus = string

export interface ProjectColumn {
  id: number
  key: string
  label: string
  color: string
  position: number
  is_default: boolean
}

/** Formato usado pelo board do Kanban (status = key da coluna). */
export interface KanbanColumnDef {
  status: string
  label: string
  color: string
}
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface Tag {
  id: number
  name: string
  color: string
}

export interface ChecklistItem {
  id: number
  title: string
  completed: boolean
  position: number
}

export interface Comment {
  id: number
  content: string
  user: Pick<User, 'id' | 'name' | 'avatar'>
  created_at: string
  status?: 'sent' | 'delivered' | 'read'
}

export interface Attachment {
  id: number
  name: string
  mime_type: string
  size: number
  url: string
  uploader: Pick<User, 'id' | 'name'> | null
  created_at: string
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  due_date: string | null
  position: number
  is_overdue: boolean
  assignee: Pick<User, 'id' | 'name' | 'avatar'> | null
  assignees: Array<Pick<User, 'id' | 'name' | 'avatar'>>
  tags: Tag[]
  checklists_total: number
  checklists_done: number
  time_seconds: number
  approval_status: 'pending' | 'approved' | 'rejected' | null
  rejection_note: string | null
  created_at: string
  updated_at: string
  // detail fields
  creator?: Pick<User, 'id' | 'name'> | null
  checklists?: ChecklistItem[]
  comments?: Comment[]
  attachments?: Attachment[]
}

export interface MemberStats {
  user: User
  total_tasks: number
  completed_tasks: number
  overdue_tasks: number
  participation: number
  score: number
  grade: 'A' | 'B' | 'C' | 'D'
}

export interface ActivityLog {
  id: number
  action: string
  user: Pick<User, 'name' | 'avatar'> | null
  project: { id: number; name: string } | null
  data: Record<string, unknown> | null
  created_at: string
}

export interface DashboardStats {
  total_projects: number
  total_tasks: number
  done_tasks: number
  overdue_tasks: number
  completion_rate: number
}

export interface Meeting {
  id: number
  title: string
  description: string | null
  scheduled_at: string
  location: string | null
  notes: string | null
  creator: Pick<User, 'id' | 'name' | 'avatar'> | null
  is_past: boolean
  created_at: string
}

export interface ProjectNote {
  id: number
  title: string
  content: string | null
  author: Pick<User, 'id' | 'name' | 'avatar'> | null
  created_at: string
  updated_at: string
}

export interface ProjectWebhook {
  id: number
  url: string
  events: string[]
  active: boolean
  created_at: string
}

export interface ProjectDashboard {
  project: Project
  tasks_total: number
  tasks_done: number
  tasks_overdue: number
  tasks_by_status: Record<TaskStatus, number>
  tasks_by_priority: Record<TaskPriority, number>
  recent_activity: ActivityLog[]
  member_stats: MemberStats[]
  risk_level: 'low' | 'medium' | 'high'
  weekly_completions: Array<{ date: string; count: number }>
}
