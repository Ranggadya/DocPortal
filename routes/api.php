<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\Admin\DocsAdminController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/docs/sections', [DocsController::class, 'getSections']);
    Route::get('/docs/pages/{slug}', [DocsController::class, 'getPageBySlug']);
});

Route::middleware('auth:sanctum')->prefix('admin/docs')->group(function () {
    Route::post('/pages', [DocsAdminController::class, 'createPage']);
    Route::put('/pages/{id}', [DocsAdminController::class, 'updatePage']);
    Route::post('/pages/{pageId}/snippets', [DocsAdminController::class, 'addSnippet']);
});
