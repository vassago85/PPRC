<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Invoices\InvoiceAuthorizer;
use App\Invoices\InvoiceFactory;
use App\Invoices\InvoicePdfRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceFactory $factory,
        private InvoiceAuthorizer $authorizer,
        private InvoicePdfRenderer $pdfRenderer,
    ) {}

    /**
     * Portal: a signed-in member downloading their own (or household's) invoice.
     * Authorised through InvoiceAuthorizer: members see their household, and
     * anyone with payments.view sees everything.
     */
    public function portal(Request $request, InvoiceType $type, int $id): Response
    {
        $source = $this->factory->find($type, $id);

        abort_unless($this->factory->canGenerate($source), 404);
        abort_unless($this->authorizer->canView($request->user(), $type, $source), 403);

        return $this->respondWithPdf($request, $source);
    }

    /**
     * Signed URL path, used by email links and the admin invoice preview.
     * Authorisation is the signature itself — the link only works for people
     * who received it from a trusted source.
     */
    public function signed(Request $request, InvoiceType $type, int $id): Response
    {
        $source = $this->factory->find($type, $id);

        abort_unless($this->factory->canGenerate($source), 404);

        return $this->respondWithPdf($request, $source);
    }

    /**
     * Build the PDF and return it as an inline download by default (so the
     * browser's built-in PDF viewer opens it in a new tab, matching how
     * every other business sends invoices). Append `?download=1` to force an
     * attachment download — handy for members on devices without an inline
     * PDF viewer.
     */
    private function respondWithPdf(Request $request, mixed $source): Response
    {
        $invoice = $this->factory->make($source);
        $pdf = $this->pdfRenderer->render($invoice);
        $filename = $this->pdfRenderer->filename($invoice);

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                // No caching — invoices change (paid status, totals) and the
                // member must always see the current state of their account.
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }
}
