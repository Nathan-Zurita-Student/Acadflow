<?php

use App\Http\Controllers\Api\AiPlanController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectColumnController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectInvitationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TaskChecklistController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskTimeLogController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Public — no auth required
Route::get('invite/{token}', [InviteController::class, 'info'])->name('invite.info');

// Webhook do ASAAS — público (autenticado pelo token no header), chamado pelo gateway
Route::post('webhooks/asaas', [SubscriptionController::class, 'webhook'])->name('webhooks.asaas');

// As rotas de autenticação por sessão vivem em routes/web.php (grupo `web`).

// Broadcasting auth — autentica canais privados do Echo via sessão/cookie.
Route::middleware('auth:sanctum')->post('broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->name('broadcasting.auth');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear');

    // Planos & assinaturas (ASAAS)
    Route::get('plans', [SubscriptionController::class, 'index'])->name('plans.index');
    Route::post('subscriptions', [SubscriptionController::class, 'subscribe'])->name('subscriptions.store');
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('my-tasks', [DashboardController::class, 'myTasks'])->name('dashboard.my-tasks');
    Route::get('calendar', [DashboardController::class, 'calendar'])->name('calendar.index');
    Route::get('users/search', [DashboardController::class, 'searchUsers'])->name('users.search');
    Route::get('search', \App\Http\Controllers\Api\SearchController::class)->name('search');

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('{project}/dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');
        Route::get('{project}/members', [ProjectController::class, 'members'])->name('members.index');
        Route::post('{project}/members', [ProjectController::class, 'addMember'])->name('members.store');
        Route::delete('{project}/members/{userId}', [ProjectController::class, 'removeMember'])->name('members.destroy');
        Route::delete('{project}/leave', [ProjectController::class, 'leave'])->name('leave');

        // Colunas do Kanban (configuráveis por projeto)
        Route::get('{project}/columns', [ProjectColumnController::class, 'index'])->name('columns.index');
        Route::post('{project}/columns', [ProjectColumnController::class, 'store'])->name('columns.store');
        Route::post('{project}/columns/reorder', [ProjectColumnController::class, 'reorder'])->name('columns.reorder');
        Route::put('{project}/columns/{column}', [ProjectColumnController::class, 'update'])->name('columns.update');
        Route::delete('{project}/columns/{column}', [ProjectColumnController::class, 'destroy'])->name('columns.destroy');

        Route::get('{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('{project}/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::put('{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('{project}/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');

        // IA — gerar plano de trabalho a partir do enunciado
        Route::post('{project}/ai/generate-plan', [AiPlanController::class, 'generate'])->name('ai.generate');
        Route::post('{project}/ai/apply-plan', [AiPlanController::class, 'apply'])->name('ai.apply');
        Route::post('{project}/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::post('{project}/tasks/{task}/comments/delivered', [TaskCommentController::class, 'markDelivered'])->name('tasks.comments.delivered');
        Route::post('{project}/tasks/{task}/comments/read', [TaskCommentController::class, 'markRead'])->name('tasks.comments.read');
        Route::post('{project}/tasks/{task}/time', [TaskTimeLogController::class, 'store'])->name('tasks.time.store');
        Route::post('{project}/tasks/{task}/submit-approval', [TaskController::class, 'submitApproval'])->name('tasks.approval.submit');
        Route::post('{project}/tasks/{task}/approve', [TaskController::class, 'approveTask'])->name('tasks.approval.approve');
        Route::post('{project}/tasks/{task}/reject', [TaskController::class, 'rejectTask'])->name('tasks.approval.reject');
        Route::post('{project}/tasks/{task}/checklists', [TaskChecklistController::class, 'store'])->name('tasks.checklists.store');
        Route::put('{project}/tasks/{task}/checklists/{checklistId}', [TaskChecklistController::class, 'update'])->name('tasks.checklists.update');
        Route::delete('{project}/tasks/{task}/checklists/{checklistId}', [TaskChecklistController::class, 'destroy'])->name('tasks.checklists.destroy');

        Route::post('{project}/invite', [InviteController::class, 'generate'])->name('invite.generate');
        Route::post('{project}/invitations', [ProjectInvitationController::class, 'store'])->name('invitations.store');

        Route::get('{project}/attachments', [AttachmentController::class, 'index'])->name('attachments.index');
        Route::post('{project}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('{project}/attachments/{attachment}/view', [AttachmentController::class, 'view'])->name('attachments.view');
        Route::get('{project}/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('{project}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

        // Meetings
        Route::get('{project}/meetings', [MeetingController::class, 'index'])->name('meetings.index');
        Route::post('{project}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::put('{project}/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('{project}/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

        // Notes
        Route::get('{project}/notes', [NoteController::class, 'index'])->name('notes.index');
        Route::post('{project}/notes', [NoteController::class, 'store'])->name('notes.store');
        Route::put('{project}/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('{project}/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

        // Webhooks
        Route::get('{project}/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('{project}/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::put('{project}/webhooks/{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
        Route::delete('{project}/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
        Route::post('{project}/webhooks/{webhook}/test', [WebhookController::class, 'test'])->name('webhooks.test');
    });

    Route::post('invite/{token}/accept', [InviteController::class, 'accept'])->name('invite.accept');

    Route::get('invitations/pending', [ProjectInvitationController::class, 'pending'])->name('invitations.pending');
    Route::post('invitations/{invitation}/respond', [ProjectInvitationController::class, 'respond'])->name('invitations.respond');
});
