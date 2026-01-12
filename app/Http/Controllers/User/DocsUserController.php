<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocPage;
use App\Models\DocSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
            // soft delete semua pages aktif dalam section
            $section->pages()->delete();

            // soft delete section
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
}
