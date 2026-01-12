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
            ->with([
                'rows' => function ($q) {
                    $q->orderBy('position')->orderBy('id');
                },
                'rows.snippets' => function ($q) {
                    $q->orderBy('position')->orderBy('id');
                },
            ])
            ->first();

        if (! $page) {
            return response()->json([
                'message' => 'Page tidak ditemukan atau belum dipublish.',
            ], 404);
        }

        return response()->json([
            'page' => [
                'id' => $page->id,
                'section_id' => $page->section_id,
                'title' => $page->title,
                'slug' => $page->slug,
                'description' => $page->description,
                'content_type' => $page->content_type,
                'status' => $page->status,
                'published_at' => $page->published_at,
            ],
            'rows' => $page->rows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'body' => $row->body,
                    'position' => $row->position,
                    'snippets' => $row->snippets->map(function ($snip) {
                        return [
                            'id' => $snip->id,
                            'language' => $snip->language,
                            'code' => $snip->code,
                            'position' => $snip->position,
                        ];
                    })->values(),
                ];
            })->values(),
        ], 200);
    }

    public function getSidebar(): JsonResponse
    {
        $sections = \App\Models\DocSection::query()
            ->orderBy('position')->orderBy('id')
            ->get()
            ->map(function ($section) {
                $pages = \App\Models\DocPage::query()
                    ->where('section_id', $section->id)
                    ->where('status', 'published')
                    ->orderBy('position')->orderBy('id')
                    ->get(['id', 'section_id', 'title', 'slug', 'position']);

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'slug' => $section->slug,
                    'description' => $section->description,
                    'position' => $section->position,
                    'pages' => $pages,
                ];
            })
            ->values();

        return response()->json([
            'sections' => $sections,
        ], 200);
    }
}
