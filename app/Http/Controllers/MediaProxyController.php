<?php

namespace App\Http\Controllers;

use App\Support\MediaDisk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Proxies media files from the S3/R2 bucket through the app's own domain
 * so browsers never hit the pub-*.r2.dev URL (which Brave and some ad
 * blockers refuse to resolve).
 *
 * Responses are sent with aggressive cache headers so repeat visits are
 * served from the browser cache, not re-fetched from R2.
 */
class MediaProxyController extends Controller
{
    public function __invoke(Request $request, string $path)
    {
        $disk = Storage::disk(MediaDisk::name());

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $size = $disk->size($path);
        $lastModified = $disk->lastModified($path);

        $etag = '"'.md5($path.'-'.$lastModified).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        return new StreamedResponse(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'ETag' => $etag,
        ]);
    }
}
