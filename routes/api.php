<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Public — no auth required
Route::get('invite/{token}', [InviteController::class, 'info']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('profile', [AuthController::class, 'updateProfile']);
    });
});

// Broadcasting auth — lets Echo authenticate private channels using Bearer tokens
Route::middleware('auth:sanctum')->post('broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('my-tasks', [DashboardController::class, 'myTasks']);

    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('{project}', [ProjectController::class, 'show']);
        Route::put('{project}', [ProjectController::class, 'update']);
        Route::delete('{project}', [ProjectController::class, 'destroy']);
        Route::get('{project}/dashboard', [ProjectController::class, 'dashboard']);
        Route::get('{project}/members', [ProjectController::class, 'members']);
        Route::post('{project}/members', [ProjectController::class, 'addMember']);
        Route::delete('{project}/members/{userId}', [ProjectController::class, 'removeMember']);

        Route::get('{project}/tasks', [TaskController::class, 'index']);
        Route::post('{project}/tasks', [TaskController::class, 'store']);
        Route::get('{project}/tasks/{task}', [TaskController::class, 'show']);
        Route::put('{project}/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('{project}/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('{project}/tasks/reorder', [TaskController::class, 'reorder']);
        Route::post('{project}/tasks/{task}/comments', [TaskController::class, 'storeComment']);
        Route::post('{project}/tasks/{task}/time', [TaskController::class, 'logTime']);
        Route::post('{project}/tasks/{task}/submit-approval', [TaskController::class, 'submitApproval']);
        Route::post('{project}/tasks/{task}/approve', [TaskController::class, 'approveTask']);
        Route::post('{project}/tasks/{task}/reject', [TaskController::class, 'rejectTask']);
        Route::post('{project}/tasks/{task}/checklists', [TaskController::class, 'storeChecklist']);
        Route::put('{project}/tasks/{task}/checklists/{checklistId}', [TaskController::class, 'updateChecklist']);
        Route::delete('{project}/tasks/{task}/checklists/{checklistId}', [TaskController::class, 'destroyChecklist']);

        Route::post('{project}/invite', [InviteController::class, 'generate']);

        Route::get('{project}/attachments', [AttachmentController::class, 'index']);
        Route::post('{project}/attachments', [AttachmentController::class, 'store']);
        Route::get('{project}/attachments/{attachment}/view', [AttachmentController::class, 'view']);
        Route::get('{project}/attachments/{attachment}/download', [AttachmentController::class, 'download']);
        Route::delete('{project}/attachments/{attachment}', [AttachmentController::class, 'destroy']);

        // Meetings
        Route::get('{project}/meetings', [MeetingController::class, 'index']);
        Route::post('{project}/meetings', [MeetingController::class, 'store']);
        Route::put('{project}/meetings/{meeting}', [MeetingController::class, 'update']);
        Route::delete('{project}/meetings/{meeting}', [MeetingController::class, 'destroy']);

        // Notes
        Route::get('{project}/notes', [NoteController::class, 'index']);
        Route::post('{project}/notes', [NoteController::class, 'store']);
        Route::put('{project}/notes/{note}', [NoteController::class, 'update']);
        Route::delete('{project}/notes/{note}', [NoteController::class, 'destroy']);

        // Webhooks
        Route::get('{project}/webhooks', [WebhookController::class, 'index']);
        Route::post('{project}/webhooks', [WebhookController::class, 'store']);
        Route::put('{project}/webhooks/{webhook}', [WebhookController::class, 'update']);
        Route::delete('{project}/webhooks/{webhook}', [WebhookController::class, 'destroy']);
        Route::post('{project}/webhooks/{webhook}/test', [WebhookController::class, 'test']);
    });

    Route::post('invite/{token}/accept', [InviteController::class, 'accept']);

    Route::get('users/search', function (\Illuminate\Http\Request $request) {
        $users = \App\Models\User::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('email', 'like', '%' . $request->q . '%')
            ->take(10)
            ->get(['id', 'name', 'email', 'avatar']);
        return response()->json($users);
    });
});
