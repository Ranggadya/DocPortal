<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocPage;
use App\Models\DocSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\DocRow;
use App\Models\DocRowSnippet;
use Illuminate\Validation\ValidationException;

use function Symfony\Component\Clock\now;

class DocsUserController extends Controller
{
    public function createSection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'unique:doc_sections,slug'],
            'description' => ['nullable', 'string'],
        ]);

        $maxPos = DB::table('docs_sections')->max('position') ?? 0;
        $data['position'] = $maxPos + 1;
        $section = DocSection::create($data);

        return response()->json($section, 201);
    }

    public function getListSections(): JsonResponse
    {
        $section = DocSection::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        return response()->json($section, 200);
    }

    public function updateSection(Request $request, int $id): JsonResponse
    {
        $section = DocSection::findOrFail($id);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('doc_sections', 'slug')->ignore($section->id),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $section->update($data);
        return response()->json($section, 200);
    }

    public function deleteSection(int $id): JsonResponse
    {
        $section = DocSection::findOrFail($id);

        $activePagesCount = $section->pages()->count();

        if (!$activePagesCount > 0) {
            return response()->json([
                'message' => 'Section masih memiliki page. Hapus/arsipkan page terlebih dahulu, atau gunakan endpoint archive section (cascade).',
                'active_pages' => $activePagesCount,
            ], 422);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section Berhasil di hapus'
        ], 200);
    }

    public function archiveSection(int $id): JsonResponse
    {
        $section = DocSection::findOrFail($id);

        DB::transaction(function () use ($section) {
            $section->pages()->delete();
            $section->delete();
        });

        return response()->json([
            'message' => 'Section dan seluruh page di dalamnya berhasil diarsipkan.',
        ], 200);
    }


    public function createPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'section_id'   => ['required', 'integer', 'exists:docs_sections,id'],
            'title'        => ['required', 'string', 'max:255'],
            'slug'         => ['required', 'string', 'max:255', 'unique:doc_pages,slug'],
            'description'  => ['nullable', 'string'],
            'content'      => ['nullable', 'string'],
            'content_type' => ['nullable', 'in:markdown,html'],
        ]);

        $data['content_type'] = $data['content_type'] ?? 'markdown';
        $data['status'] = $data['status'] ?? 'draft';

        $maxPos = DB::table('doc_pages')
            ->where('section_id', $data['section_id'])
            ->max('position') ?? 0;

        $data['position'] = $maxPos + 1;

        $page = DocPage::create($data);
        return response()->json($page, 201);
    }

    public function listPageBySection(int $sectionId): JsonResponse
    {
        $sectionExist = DB::table('docs_sections')->where('id', $sectionId)->exists();

        if (!$sectionExist) {
            return response()->json([
                'message' => 'Section tidak ditemukan'
            ], 404);
        }

        $pages = DocPage::query()
            ->where('section_id', $sectionId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        return response()->json($pages, 200);
    }

    public function deletePage(int $id): JsonResponse
    {
        $page = DocPage::findOrFail($id);

        $page->delete();
        return response()->json([
            'message' => 'Page berhasil di hapus',
        ], 200);
    }

    public function publishPage(int $id): JsonResponse
    {
        $page = DocPage::findOrFail($id);
        $page->update([
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        return response()->json($page, 200);
    }

    public function unpublishPage(int $id): JsonResponse
    {
        $page = DocPage::findOrFail($id);
        $page->update([
            'status' => 'unpublish',
            'published_at' => null,
        ]);

        return response()->json($page, 200);
    }

    public function reorderSections(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ordered_ids'] as $index => $id) {
                DB::table('docs_sections')
                    ->where('id', $id)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Section order updated.'], 200);
    }


    public function reorderPages(Request $request, int $sectionId): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        $sectionExists = DB::table('docs_sections')->where('id', $sectionId)->exists();
        if (! $sectionExists) {
            return response()->json(['message' => 'Section tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($data, $sectionId) {
            $count = DB::table('doc_pages')
                ->where('section_id', $sectionId)
                ->whereIn('id', $data['ordered_ids'])
                ->count();

            if ($count !== count($data['ordered_ids'])) {
                abort(422, 'Ada page yang tidak termasuk section ini.');
            }

            foreach ($data['ordered_ids'] as $index => $id) {
                DB::table('doc_pages')
                    ->where('id', $id)
                    ->where('section_id', $sectionId)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Page order updated.'], 200);
    }

    public function listRowsByPage(int $pageId): JsonResponse
    {
        $page = DocPage::query()->find($pageId);
        if (! $page) {
            return response()->json(['message' => 'Page tidak ditemukan.'], 404);
        }

        $rows = DocRow::query()
            ->where('page_id', $pageId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json([
            'page_id' => $pageId,
            'rows' => $rows,
        ], 200);
    }

    public function createRow(Request $request, int $pageId): JsonResponse
    {
        $page = DocPage::query()->find($pageId);
        if (! $page) {
            return response()->json(['message' => 'Page tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body'  => ['nullable', 'string'],
        ]);

        $maxPos = DB::table('doc_rows')
            ->where('page_id', $pageId)
            ->max('position') ?? 0;

        $row = DocRow::create([
            'page_id' => $pageId,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'position' => $maxPos + 1,
        ]);

        return response()->json($row, 201);
    }

    public function updateRow(Request $request, int $rowId): JsonResponse
    {
        $row = DocRow::query()->find($rowId);
        if (! $row) {
            return response()->json(['message' => 'Row tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body'  => ['nullable', 'string'],
        ]);

        $row->update([
            'title' => array_key_exists('title', $data) ? $data['title'] : $row->title,
            'body'  => array_key_exists('body', $data) ? $data['body'] : $row->body,
        ]);

        return response()->json($row, 200);
    }

    public function deleteRow(int $rowId): JsonResponse
    {
        $row = DocRow::query()->find($rowId);
        if (! $row) {
            return response()->json(['message' => 'Row tidak ditemukan.'], 404);
        }

        $row->delete();

        return response()->json(['message' => 'Row berhasil dihapus (archived).'], 200);
    }

    public function listSnippetsByRow(int $rowId): JsonResponse
    {
        $row = DocRow::query()->find($rowId);
        if (! $row) {
            return response()->json(['message' => 'Row tidak ditemukan.'], 404);
        }

        $snippets = DocRowSnippet::query()
            ->where('row_id', $rowId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json([
            'row_id' => $rowId,
            'snippets' => $snippets,
        ], 200);
    }

    public function createSnippet(Request $request, int $rowId): JsonResponse
    {
        $row = DocRow::query()->find($rowId);
        if (! $row) {
            return response()->json(['message' => 'Row tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'language' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string'],
        ]);

        // Cegah language tab ganda pada row yang sama
        $exists = DocRowSnippet::query()
            ->where('row_id', $rowId)
            ->where('language', $data['language'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'language' => ['Language tab sudah ada di row ini. Gunakan update jika ingin mengubah.'],
            ]);
        }

        $maxPos = DB::table('doc_row_snippets')
            ->where('row_id', $rowId)
            ->max('position') ?? 0;

        $snippet = DocRowSnippet::create([
            'row_id' => $rowId,
            'language' => $data['language'],
            'code' => $data['code'],
            'position' => $maxPos + 1,
        ]);

        return response()->json($snippet, 201);
    }

    public function updateSnippet(Request $request, int $snippetId): JsonResponse
    {
        $snippet = DocRowSnippet::query()->find($snippetId);
        if (! $snippet) {
            return response()->json(['message' => 'Snippet tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'language' => ['nullable', 'string', 'max:32'],
            'code' => ['nullable', 'string'],
        ]);

        // Jika user mengubah language, pastikan tidak bentrok pada row yang sama
        if (array_key_exists('language', $data) && $data['language'] !== null) {
            $exists = DocRowSnippet::query()
                ->where('row_id', $snippet->row_id)
                ->where('language', $data['language'])
                ->where('id', '!=', $snippet->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'language' => ['Language tab sudah ada di row ini. Pilih language lain.'],
                ]);
            }
        }

        $snippet->update([
            'language' => array_key_exists('language', $data) ? $data['language'] : $snippet->language,
            'code' => array_key_exists('code', $data) ? $data['code'] : $snippet->code,
        ]);

        return response()->json($snippet, 200);
    }

    public function deleteSnippet(int $snippetId): JsonResponse
    {
        $snippet = DocRowSnippet::query()->find($snippetId);
        if (! $snippet) {
            return response()->json(['message' => 'Snippet tidak ditemukan.'], 404);
        }

        $snippet->delete();

        return response()->json(['message' => 'Snippet berhasil dihapus (archived).'], 200);
    }

    public function reorderRows(Request $request, int $pageId): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        $pageExists = DB::table('doc_pages')->where('id', $pageId)->exists();
        if (! $pageExists) {
            return response()->json(['message' => 'Page tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($data, $pageId) {
            // Pastikan semua row memang milik page ini
            $count = DB::table('doc_rows')
                ->where('page_id', $pageId)
                ->whereIn('id', $data['ordered_ids'])
                ->count();

            if ($count !== count($data['ordered_ids'])) {
                abort(422, 'Ada row yang tidak termasuk page ini.');
            }

            foreach ($data['ordered_ids'] as $index => $id) {
                DB::table('doc_rows')
                    ->where('id', $id)
                    ->where('page_id', $pageId)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Row order updated.'], 200);
    }

    public function reorderSnippets(Request $request, int $rowId): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        $rowExists = DB::table('doc_rows')->where('id', $rowId)->exists();
        if (! $rowExists) {
            return response()->json(['message' => 'Row tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($data, $rowId) {
            $count = DB::table('doc_row_snippets')
                ->where('row_id', $rowId)
                ->whereIn('id', $data['ordered_ids'])
                ->count();

            if ($count !== count($data['ordered_ids'])) {
                abort(422, 'Ada snippet yang tidak termasuk row ini.');
            }

            foreach ($data['ordered_ids'] as $index => $id) {
                DB::table('doc_row_snippets')
                    ->where('id', $id)
                    ->where('row_id', $rowId)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Snippet order updated.'], 200);
    }
}
