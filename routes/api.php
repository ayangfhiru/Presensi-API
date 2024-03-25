<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogbookController;
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
    Route::post('division', [DivisionController::class, 'addDivision']);
    Route::delete('division/{id}', [DivisionController::class, 'deleteDivision']);
    
    Route::post('presence', [PresenceController::class, 'addPresence']);
    Route::get('presence', [PresenceController::class, 'getPresence']);
    Route::put('presence/{id}', [PresenceController::class, 'updatePresence']);
    Route::delete('presence/{id}', [PresenceController::class, 'deletePresence']);
    
    Route::post('user/register', [UserController::class, 'userRegister']);
    Route::get('users/', [UserController::class, 'getUser']);
    Route::put('users/{id}', [UserController::class, 'updateUser']);
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

    Route::post('logbook', [LogbookController::class, 'addLogbook']);
    Route::get('logbook', [LogbookController::class, 'getLogbook']);
    Route::get('logbook/{id}', [LogbookController::class, 'detailLogbook']);
    Route::put('logbook/{id}', [LogbookController::class, 'updateLogbook']);
    Route::delete('logbook/{id}', [LogbookController::class, 'deleteLogbook']);
    
    Route::get('account/detail', [AuthController::class, 'detailAccount']);
    Route::put('account/update', [AuthController::class, 'updateAccount']);
    Route::delete('account/logout', [AuthController::class, 'logout']);
});

Route::post('account/login', [AuthController::class, 'login']);
Route::get('division', [DivisionController::class, 'getDivision']);