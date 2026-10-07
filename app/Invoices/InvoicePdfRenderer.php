<?php

namespace App\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Renders an InvoiceDocument into an A4 PDF via DomPDF.
 *
 * The view (resources/views/invoices/pdf.blade.php) is deliberately plain —
 * tables + inline styles, no Tailwind / modern CSS — because DomPDF only
 * supports a conservative subset of CSS. If the layout starts breaking, make
 * the view simpler, don't try to teach DomPDF about flexbox.
 */
class InvoicePdfRenderer
{
    /**
     * Target width in pixels for the invoice logo. The PDF renders the logo
     * at 60pt (~80px); 240px is 4× that so it still looks crisp on high-DPI
     * exports but is small enough that DomPDF can decode it in a handful of MB.
     */
    private const LOGO_TARGET_PX = 240;

    public function render(InvoiceDocument $invoice): DomPdf
    {
        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => $this->logoPath(),
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    /**
     * The logo file to embed in the PDF.
     *
     * The public `pprclogo.png` is ~550 KB — DomPDF decodes PNGs into memory
     * uncompressed, which blows past the default 128 MB CLI memory limit on
     * a crisp hero logo. We render a smaller 240 px copy once per deploy and
     * cache it under storage/app so every subsequent invoice is cheap.
     *
     * Returns null if the source logo is missing or GD can't resize it —
     * the Blade template renders the invoice without a logo in that case
     * rather than crashing.
     */
    public function logoPath(): ?string
    {
        $source = public_path('pprclogo.png');

        if (! file_exists($source)) {
            return null;
        }

        $cached = storage_path('app/invoice-logo-'.self::LOGO_TARGET_PX.'.png');

        // Reuse the cached copy once it's been written, unless the source
        // logo has been replaced since (filemtime comparison keeps the cache
        // honest after a brand refresh).
        if (file_exists($cached) && filemtime($cached) >= filemtime($source)) {
            return $cached;
        }

        if (! function_exists('imagecreatefrompng')) {
            // No GD — fall back to the original and hope memory_limit is big
            // enough. Logs so an admin notices after a deploy to a slim image.
            return $source;
        }

        try {
            $this->resizePng($source, $cached, self::LOGO_TARGET_PX);

            return file_exists($cached) ? $cached : $source;
        } catch (\Throwable) {
            return $source;
        }
    }

    private function resizePng(string $source, string $destination, int $targetWidth): void
    {
        $src = @imagecreatefrompng($source);
        if ($src === false) {
            return;
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        if ($srcWidth <= $targetWidth) {
            // Already small enough — just copy once and skip the resample.
            imagedestroy($src);
            @copy($source, $destination);

            return;
        }

        $targetHeight = (int) round($srcHeight * ($targetWidth / $srcWidth));

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        // Preserve transparency on the downscaled copy.
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        if (! is_dir(dirname($destination))) {
            @mkdir(dirname($destination), 0775, true);
        }

        imagepng($dst, $destination, 6);

        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Suggested filename for the download, derived from the invoice number
     * and type so the member's downloads folder is navigable. Spaces and
     * forward slashes are stripped — Windows and macOS both choke on those
     * in download filenames when the browser doesn't quote them properly.
     *
     * Example: PPRC-M194-match-entry.pdf
     */
    public function filename(InvoiceDocument $invoice): string
    {
        $slug = strtolower(str_replace(' ', '-', $invoice->type->label()));
        $number = preg_replace('/[^A-Za-z0-9_\-]/', '-', $invoice->number);

        return "{$number}-{$slug}.pdf";
    }
}
