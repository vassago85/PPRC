<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Proxies media files from the R2 public URL through the app's own domain
 * so browsers never hit the pub-*.r2.dev URL (which Brave and some ad
 * blockers refuse to resolve).
 *
 * Fetches from the public R2 URL server-side (no S3 API credentials needed)
 * and streams back with aggressive cache headers.
 */
class MediaProxyController extends Controller
{
    public function __invoke(Request $request, string $path)
    {
        $baseUrl = rtrim((string) config('filesystems.disks.s3.url'), '/');

        if (blank($baseUrl)) {
            abort(404);
        }

        $sourceUrl = $baseUrl.'/'.ltrim($path, '/');

        $etag = '"'.md5($path).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        $upstream = Http::withOptions(['stream' => true])
            ->timeout(30)
            ->get($sourceUrl);

        if ($upstream->failed()) {
            abort($upstream->status() === 404 ? 404 : 502);
        }

        $mime = $upstream->header('Content-Type') ?: 'application/octet-stream';
        $size = $upstream->header('Content-Length');

        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'ETag' => $etag,
        ];

        if ($size) {
            $headers['Content-Length'] = $size;
        }

        return new StreamedResponse(function () use ($upstream) {
            echo $upstream->body();
        }, 200, $headers);
    }
}
