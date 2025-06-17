<?php

use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\FollowerController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\UserController;

Route::group(['middleware' => 'cors'], function () {
    Route::prefix('v1/auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
        Route::post('/posts', [PostController::class, 'store']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
        Route::get('/posts', [PostController::class, 'index']);

        Route::post('/users/{username}/follow', [FollowController::class, 'follow']);
        Route::delete('/users/{username}/unfollow', [FollowController::class, 'unfollow']);
        Route::get('/following', [FollowController::class, 'following']);

        Route::put('/users/{username}/accept', [FollowerController::class, 'accept']);
        Route::put('/users/{username}/reject', [FollowerController::class, 'reject']);
        Route::get('/users/followers', [FollowerController::class, 'followers']);
        Route::get('/users/pending-followers', [FollowerController::class, 'pendingFollowers']);
        Route::get('/users/rejected-followers', [FollowerController::class, 'rejectedFollowers']);
        Route::get('/users/notifications', [FollowerController::class, 'notifications']);

        Route::get('/users/{username}', [UserController::class, 'show']);
        Route::get('/users', [UserController::class, 'index']);
    });
});
