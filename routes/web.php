<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/user/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/user/login', [LoginController::class, 'login']);
Route::post('/user/logout', [LoginController::class, 'logout']);
Route::get('/user/auth/google', [GoogleController::class, 'redirect']);
Route::get('/user/auth/google/callback', [GoogleController::class, 'callback']);

Route::middleware('auth.if_enabled')->group(function () {

    Route::get('/', [HomeController::class, 'index']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('permission:admin.users')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::patch('/admin/users/{user}', [AdminUserController::class, 'update']);
        Route::post('/admin/users/{user}/review', [AdminUserController::class, 'review']);
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
    });

    Route::middleware('permission:admin.roles')->group(function () {
        Route::get('/admin/roles', [AdminRoleController::class, 'index']);
        Route::post('/admin/roles', [AdminRoleController::class, 'store']);
        Route::patch('/admin/roles/{role}', [AdminRoleController::class, 'update']);
        Route::delete('/admin/roles/{role}', [AdminRoleController::class, 'destroy']);
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/admin/audit', [AuditController::class, 'index']);
    });
});
