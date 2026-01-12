<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\DocsUserController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'user']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('/docs/sections', [DocsController::class, 'getSections']);
    Route::get('/docs/pages/{slug}', [DocsController::class, 'getPageBySlug']);
    Route::get('/docs/sidebar', [DocsController::class, 'getSidebar']);

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
    Route::post('/sections/{id}/archive', [DocsUserController::class, 'archiveSection']);
    Route::patch('/sections/reorder', [DocsUserController::class, 'reorderSections']);
    Route::patch('/sections/{sectionId}/pages/reorder', [DocsUserController::class, 'reorderPages']);

    Route::get('/pages/{pageId}/rows', [DocsUserController::class, 'listRowsByPage']);
    Route::post('/pages/{pageId}/rows', [DocsUserController::class, 'createRow']);
    Route::put('/rows/{rowId}', [DocsUserController::class, 'updateRow']);
    Route::delete('/rows/{rowId}', [DocsUserController::class, 'deleteRow']);

    // SNIPPETS (code tabs per row)
    Route::get('/rows/{rowId}/snippets', [DocsUserController::class, 'listSnippetsByRow']);
    Route::post('/rows/{rowId}/snippets', [DocsUserController::class, 'createSnippet']);
    Route::put('/snippets/{snippetId}', [DocsUserController::class, 'updateSnippet']);
    Route::delete('/snippets/{snippetId}', [DocsUserController::class, 'deleteSnippet']);

    Route::patch('/pages/{pageId}/rows/reorder', [DocsUserController::class, 'reorderRows']);
    Route::patch('/rows/{rowId}/snippets/reorder', [DocsUserController::class, 'reorderSnippets']);


});
