<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function student()
    {
        $manifest = [
            'name'             => 'Palomatika — Ученик',
            'short_name'       => 'Palomatika',
            'description'      => 'Подготовка к ОГЭ по математике',
            'start_url'        => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#111318',
            'theme_color'      => '#111318',
            'lang'             => 'ru',
            'icons'            => [
                ['src' => '/icons/student-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/student-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'screenshots'      => [],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function teacher()
    {
        $manifest = [
            'name'             => 'Palomatika — Репетитор',
            'short_name'       => 'Palomatika Pro',
            'description'      => 'Управление учениками и уроками',
            'start_url'        => '/dashboard',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#111318',
            'theme_color'      => '#4f8ef7',
            'lang'             => 'ru',
            'icons'            => [
                ['src' => '/icons/teacher-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/teacher-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker()
    {
        $content = file_get_contents(public_path('sw.js'));
        return response($content, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/');
    }
}
