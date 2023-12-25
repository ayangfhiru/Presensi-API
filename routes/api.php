<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\MentoringController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Route::post('role', [RoleController::class, 'addRole']);
    // Route::get('role', [RoleController::class, 'getRole']);
    // Route::put('role/{id}', [RoleController::class, 'updateRole']);
    // Route::delete('role/{id}', [RoleController::class, 'deleteRole']);

    Route::post('division', [DivisionController::class, 'addDivision']);
    Route::delete('division/{id}', [DivisionController::class, 'deleteDivision']);
    
    Route::post('presence', [PresenceController::class, 'addPresence']);
    Route::get('presence', [PresenceController::class, 'getPresence']);
    Route::get('presence/{id}', [PresenceController::class, 'getDetailPresence']);
    
    Route::post('user/register', [UserController::class, 'register']);
    Route::get('users/', [UserController::class, 'getUser']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);

    Route::post('mentoring', [MentoringController::class, 'addMentoring']);
    Route::get('mentoring', [MentoringController::class, 'getMentoring']);
    Route::put('mentoring/{id}', [MentoringController::class, 'updateMentoring']);
    Route::delete('mentoring/{id}', [MentoringController::class, 'deleteMentoring']);

    Route::get('project/participant-mentor', [ProjectController::class, 'getParticipantMentor']);
    Route::post('project', [ProjectController::class, 'addProject']);
    Route::get('project', [ProjectController::class, 'getProject']);
    Route::put('project/{id}', [ProjectController::class, 'updateProject']);
    Route::delete('project/{id}', [ProjectController::class, 'deleteProject']);

    Route::post('task', [TaskController::class, 'addTask']);
    Route::get('task', [TaskController::class, 'getTask']);
    Route::get('task/{id}', [TaskController::class, 'detailTask']);
    Route::put('task/{id}', [TaskController::class, 'updateTask']);
    Route::delete('task/{id}', [TaskController::class, 'deleteTask']);
    
    Route::get('account/detail', [AuthController::class, 'detailAccount']);
    Route::post('account/update', [AuthController::class, 'updateAccount']);
    Route::delete('account/logout', [AuthController::class, 'logout']);
});

Route::post('account/login', [AuthController::class, 'login']);
Route::get('division', [DivisionController::class, 'getDivision']);