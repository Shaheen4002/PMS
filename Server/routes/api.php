<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::post('/reset_password', 'reset_password')->middleware('auth:sanctum');
    Route::post('/register-face', 'registerFace')->middleware('auth:sanctum');
    Route::post('/login-face', 'faceLogin');
    Route::get('/check-face-registered',  'checkFaceRegistered')->middleware('auth:sanctum');
});

Route::controller(ProjectController::class)->group(function () {
    Route::post('/add_project', 'addProject')->middleware(['auth:sanctum']);
    Route::delete('/delete_project/{project_id}', 'deleteProject')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::post('/edit_project/{project_id}', 'editProject')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::get('/project/{project_id}', 'getSingleProject')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::get('/user/{user_id}/projects', 'getUserProjects')->middleware(['auth:sanctum', 'isAdmin']);
    Route::get('/projects', 'getYourProjects')->middleware(['auth:sanctum']);
    Route::get('/project/{project_id}/users', 'getProjectMembers')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::get('/project/{project_id}/tasks', 'getProjectTasks')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::patch('/project/status/{project_id}', 'editProjectStatus')->middleware(['auth:sanctum', 'isProjectManager']);
});

Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'getAllUsers')->middleware(['auth:sanctum', 'isAdmin']);
    Route::delete('/delete_user/{user_id}', 'deleteUser')->middleware(['auth:sanctum', 'isAdmin']);
    Route::delete('/remove_member/{user_id}/project/{project_id}', 'removeUserFromProject')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::post('/add_member/{user_id}/project/{project_id}', 'addUserToProject')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::post('/add_lead/{user_id}/project/{project_id}', 'addLeaderToProject')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::get('/notifications', 'getMyNotifications')->middleware('auth:sanctum');
    Route::get('/notifications/unread-count','getUnreadCount')->middleware('auth:sanctum');
    Route::post('/notifications/mark-as-read', 'markAsRead')->middleware('auth:sanctum');
    Route::post('/notifications/mark-as-read/{notificationId}','markAsRead')->middleware('auth:sanctum');
});

Route::controller(TaskController::class)->group(function () {
    Route::post('/add_task/project/{project_id}', 'addTask')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::delete('/delete_task/{task_id}/project/{project_id}', 'deleteTask')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::post('/edit_task/{task_id}/project/{project_id}', 'editTask')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::get('/user/{user_id}/tasks/project/{project_id}', 'getUserTasks')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::get('/task/{task_id}/project/{project_id}', 'getSingleTask')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::patch('/task/{task_id}/project/{project_id}', 'editTaskStatus')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::patch('/task/{task_id}/progress/project/{project_id}', 'editTaskProgress')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::patch('/task/{task_id}/info/project/{project_id}', 'editTaskInfo')->middleware(['auth:sanctum', 'isProjectMember']);
});

Route::controller(FileController::class)->group(function () {
    Route::post('/add_file/project/{project_id}', 'addFile')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::get('/files/{file_id}/project/{project_id}', 'getSingleFile')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::get('/task/{task_id}/files/project/{project_id}', 'getTaskFiles')->middleware(['auth:sanctum', 'isProjectMember']);
    Route::delete('/delete_file/{file_id}/project/{project_id}', 'deleteFile')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::post('/edit_file/{file_id}/project/{project_id}', 'editFile')->middleware(['auth:sanctum', 'isProjectMember']);
});

Route::controller(CommentController::class)->group(function () {
    Route::post('/add_comment/project/{project_id}', 'addComment')->middleware(['auth:sanctum', 'isProjectMember']);;
    Route::get('/task/{task_id}/comments/project/{project_id}', 'getTaskComments')->middleware(['auth:sanctum', 'isProjectMember']);;
    Route::delete('/delete_comment/{comment_id}/project/{project_id}', 'deleteComment')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
});

Route::controller(ReportController::class)->group(function () {
    Route::post('/add_report/project/{project_id}', 'addReport')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::get('/report/{report_id}/project/{project_id}', 'getSingleReport')->middleware(['auth:sanctum', 'isProjectLeaderOrHigher']);
    Route::get('/project/{project_id}/reports', 'getProjectReports')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::get('/user/{user_id}/reports/project/{project_id}', 'getUserReports')->middleware(['auth:sanctum', 'isProjectManager']);
    Route::delete('/delete_report/{report_id}/project/{project_id}', 'deleteReport')->middleware(['auth:sanctum', 'isProjectManager']);
});
