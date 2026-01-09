<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocSection;
use App\Models\DocPage;

class DocsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Kita buat struktur seperti portal docs:
         * - Introduction
         * - Authenticating requests
         * - Countries (section) -> punya page Countries
         */

        // 1) Root sections (sidebar level 1)
        $intro = DocSection::updateOrCreate(
            ['slug' => 'introduction'],
            [
                'parent_id' => null,
                'title' => 'Introduction',
                'position' => 0,
                'is_active' => true,
            ]
        );

        $auth = DocSection::updateOrCreate(
            ['slug' => 'authenticating-requests'],
            [
                'parent_id' => null,
                'title' => 'Authenticating requests',
                'position' => 1,
                'is_active' => true,
            ]
        );

        $countriesSection = DocSection::updateOrCreate(
            ['slug' => 'countries'],
            [
                'parent_id' => null,
                'title' => 'Countries',
                'position' => 2,
                'is_active' => true,
            ]
        );

        // 2) Pages (konten utama)
        $introPage = DocPage::updateOrCreate(
            ['slug' => 'intro'],
            [
                'section_id' => $intro->id,
                'title' => 'Intro',
                'description' => 'Dokumentasi internal untuk API perusahaan.',
                'content_type' => 'markdown',
                'status' => 'published',
                'published_at' => now(),
                'content' => <<<MD
## Base URL

\`http://127.0.0.1:8000/api\`

## Tujuan

Portal ini digunakan oleh tim internal untuk memahami endpoint API dan contoh request.

## Konvensi

- Semua request menggunakan JSON.
- Gunakan token pada header bila endpoint membutuhkan autentikasi.
MD,
            ]
        );

        $countriesPage = DocPage::updateOrCreate(
            ['slug' => 'countries-list'],
            [
                'section_id' => $countriesSection->id,
                'title' => 'Get Countries',
                'description' => 'Mengambil daftar negara.',
                'content_type' => 'markdown',
                'status' => 'published',
                'published_at' => now(),
                'content' => <<<MD
## Endpoint

**GET** \`/countries\`

## Response (contoh)

\`\`\`json
{
  "data": [
    { "id": 1, "name": "Indonesia" },
    { "id": 2, "name": "Singapore" }
  ]
}
\`\`\`
MD,
            ]
        );

        // 3) Code snippets (panel kanan, tabs)
        // Agar tidak dobel, kita bersihkan dulu snippet milik page itu
        $countriesPage->snippets()->delete();

        $countriesPage->snippets()->createMany([
            [
                'language' => 'bash',
                'title' => null,
                'position' => 0,
                'code' => "curl -X GET \"http://127.0.0.1:8000/api/countries\" \\\n  -H \"Accept: application/json\"",
            ],
            [
                'language' => 'javascript',
                'title' => null,
                'position' => 1,
                'code' => "fetch('http://127.0.0.1:8000/api/countries', {\n  method: 'GET',\n  headers: { 'Accept': 'application/json' }\n}).then(r => r.json()).then(console.log);",
            ],
            [
                'language' => 'php',
                'title' => null,
                'position' => 2,
                'code' => "<?php\n\n\$client = new \\GuzzleHttp\\Client();\n\$res = \$client->get('http://127.0.0.1:8000/api/countries', [\n  'headers' => ['Accept' => 'application/json']\n]);\n\necho \$res->getBody();\n",
            ],
            [
                'language' => 'python',
                'title' => null,
                'position' => 3,
                'code' => "import requests\n\nr = requests.get('http://127.0.0.1:8000/api/countries', headers={'Accept':'application/json'})\nprint(r.json())",
            ],
        ]);

        // (Opsional) Snippet untuk intro bisa kosong, karena biasanya hanya teks.
        // $introPage->snippets() tidak wajib.
    }
}
