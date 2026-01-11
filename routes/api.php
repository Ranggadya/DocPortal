<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\Admin\DocsAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\DocsUserController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'user']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('/docs/sections', [DocsController::class, 'getSections']);
    Route::get('/docs/pages/{slug}', [DocsController::class, 'getPageBySlug']);
});
Route::middleware('auth:sanctum')->prefix('user/docs')->group(function () {
    Route::get('/sections', [DocsUserController::class, 'getListSections']);
    Route::post('/sections', [DocsUserController::class, 'createSection']);
    Route::put('/sections/{id}', [DocsUserController::class, 'updateSection']);

    Route::post('/pages', [DocsUserController::class, 'createPage']);
    Route::get('/sections/{sectionId}/pages', [DocsUserController::class, 'listPagesBySection']);
    Route::put('/pages/{id}', [DocsUserController::class, 'updatePage']);
    Route::delete('/pages/{id}', [DocsUserController::class, 'deletePage']);
    Route::post('/pages/{id}/publish', [DocsUserController::class, 'publishPage']);
    Route::post('/pages/{id}/unpublish', [DocsUserController::class, 'unpublishPage']);
});
Route::middleware('auth:sanctum')->prefix('admin/docs')->group(function () {
    Route::post('/pages', [DocsAdminController::class, 'createPage']);
    Route::put('/pages/{id}', [DocsAdminController::class, 'updatePage']);
    Route::post('/pages/{pageId}/snippets', [DocsAdminController::class, 'addSnippet']);
});
