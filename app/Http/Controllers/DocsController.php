<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the Starlight user guide built into public/docs. Web servers normally serve these files directly; this
 * covers directory URLs (/docs, /docs/planning) and setups where every request reaches Laravel.
 */
class DocsController extends Controller
{
    public const MIME_TYPES = [
        'css' => 'text/css', 'js' => 'text/javascript', 'mjs' => 'text/javascript', 'json' => 'application/json',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'txt' => 'text/plain', 'xml' => 'application/xml', 'html' => 'text/html; charset=UTF-8',
    ];

    public function __invoke(Request $request, ?string $path = null)
    {
        $root = realpath(config('app.docs_path') ?? public_path('docs'));
        if ($root === false) {
            abort(404, 'The user guide has not been built. Run: npm run docs:build');
        }

        $candidate = realpath($root . '/' . ltrim((string) $path, '/'));
        // Starlight links are absolute, so /docs/planning and /docs/planning/ can both serve planning/index.html.
        if ($candidate !== false && is_dir($candidate)) {
            $candidate = realpath($candidate . '/index.html');
        }
        // Never serve anything outside public/docs.
        if ($candidate === false || !str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) || !is_file($candidate)) {
            $notFound = $root . '/404.html';
            return is_file($notFound)
                ? response(file_get_contents($notFound), 404, ['Content-Type' => self::MIME_TYPES['html']])
                : abort(404);
        }

        $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        if ($extension === 'html') {
            return response(file_get_contents($candidate), 200, ['Content-Type' => self::MIME_TYPES['html']]);
        }
        $response = new BinaryFileResponse($candidate, 200, [
            'Content-Type' => self::MIME_TYPES[$extension] ?? 'application/octet-stream',
        ]);
        if (str_contains($candidate, DIRECTORY_SEPARATOR . '_astro' . DIRECTORY_SEPARATOR)) {
            $response->setPublic()->setMaxAge(31536000)->headers->addCacheControlDirective('immutable');
        }
        return $response;
    }
}
