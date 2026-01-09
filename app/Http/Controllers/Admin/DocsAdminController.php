<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocPage;
use Illuminate\Http\JsonResponse;

use function Symfony\Component\Clock\now;

class DocsAdminController extends Controller
{
     /**
      * POST /api/admin/docs/pages
      *
      * Tujuan:
      * - Membuat page baru di sebuah section
      * - Konten disimpan sebagai markdown/html (default markdown)
      * - Status bisa draft/published/archived
      *
      * Catatan:
      * - Bila status published, set published_at otomatis.
      */

     public function createPage(Request $request): JsonResponse
     {
          $data = $request->validate([
               'section_id'   => ['required', 'exists:doc_sections,id'],
               'title'        => ['required', 'string', 'max:255'],
               'slug'         => ['required', 'string', 'max:255', 'unique:doc_pages,slug'],
               'description'  => ['nullable', 'string'],
               'content'      => ['nullable', 'string'],
               'content_type' => ['required', 'in:markdown,html'],
               'status'       => ['required', 'in:draft,published,archived'],
          ]);

          if ($data['status'] === 'published') {
               $data['published_at'] = now();
          }

          $page = DocPage::create($data);
          return response()->json($page, 201);
     }

     /**
      * PUT /api/admin/docs/pages/{id}
      *
      * Tujuan:
      * - Update page existing
      * - slug tetap unik
      * - jika publish pertama kali, set published_at
      */

     public function updatePage(Request $request, $id): JsonResponse
     {
          $page = DocPage::findOrFail($id);

          $data = $request->validate([
               'title'        => ['sometimes', 'string', 'max:255'],
               'slug'         => ['sometimes', 'string', 'max:255', 'unique:doc_pages,slug,' . $page->id],
               'description'  => ['sometimes', 'nullable', 'string'],
               'content'      => ['sometimes', 'nullable', 'string'],
               'content_type' => ['sometimes', 'in:markdown,html'],
               'status'       => ['sometimes', 'in:draft,published,archived'],
          ]);

          if (isset($data['status']) && $data['status'] === 'published' && !$page->published_at) {
               $data['published_at'] = now();
          }

          $page->update($data);
          return response()->json($page);
     }


     /**
      * POST /api/admin/docs/pages/{pageId}/snippets
      *
      * Tujuan:
      * - Menambahkan code snippet untuk page tertentu
      * - language dibatasi agar UI tab konsisten
      */

     public function addSnippet(Request $request, int $pageId): JsonResponse
     {
          $page = DocPage::findOrFail($pageId);

          $data = $request->validate([
               'language' => ['required', 'in:bash,javascript,php,python'],
               'title'    => ['nullable', 'string', 'max:255'],
               'code'     => ['required', 'string'],
               'position' => ['nullable', 'integer', 'min:0'],
          ]);

          $snippet = $page->snippets()->create($data);
          return response()->json($snippet, 201);
     }
}
