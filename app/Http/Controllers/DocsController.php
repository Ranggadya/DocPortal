<?php

namespace App\Http\Controllers;

use App\Models\DocPage;
use App\Models\DocSection;
use Illuminate\Http\JsonResponse;

class DocsController extends Controller
{
    public function getSections(): JsonResponse
    {
        $sections = DocSection::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->with([
                // Load pages yang published dalam section root
                'pages' => function ($query) {
                    $query->where('status', 'published')
                        ->orderBy('title');
                },

                // Load children level 1 (dan pagesnya)
                'children' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('position')
                        ->with([
                            'pages' => function ($qq) {
                                $qq->where('status', 'published')
                                    ->orderBy('title');
                            },

                            'children' => function ($qqq) {
                                $qqq->where('is_active', true)
                                    ->orderBy('position')
                                    ->with([
                                        'pages' => function ($q4) {
                                            $q4->where('status', 'published')
                                                ->orderBy('title');
                                        }
                                    ]);
                            },
                        ]);
                }
            ])
            ->get();

        return response()->json($sections);
    }

    /**
     * GET /api/docs/pages/{slug}
     *
     * Tujuan:
     * - Mengambil 1 page berdasarkan slug
     * - Mengikutsertakan code snippets (untuk panel kanan)
     * - Hanya menampilkan page yang published
     */

    public function getPageBySlug(string $slug): JsonResponse
    {
        $page = DocPage::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with('snippets')
            ->firstOrFail();

        return response()->json($page);   
    }
}
