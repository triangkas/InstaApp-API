<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user', [AuthController::class, 'user']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/list', [PostController::class, 'list']);
    Route::post('/comment/list', [PostController::class, 'commentList']);
    Route::post('/comment/posts', [PostController::class, 'storeComment']);
    Route::post('/likes', [PostController::class, 'likes']);
    Route::post('/cek-status', [PostController::class, 'cekStatus']);
});
