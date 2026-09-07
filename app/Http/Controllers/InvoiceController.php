<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Invoices\InvoiceAuthorizer;
use App\Invoices\InvoiceFactory;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceFactory $factory,
        private InvoiceAuthorizer $authorizer,
    ) {}

    public function portal(InvoiceType $type, int $id): View
    {
        $source = $this->factory->find($type, $id);

        abort_unless($this->factory->canGenerate($source), 404);
        abort_unless($this->authorizer->canView(auth()->user(), $type, $source), 403);

        return view('invoices.show', [
            'invoice' => $this->factory->make($source),
        ]);
    }

    public function signed(InvoiceType $type, int $id): View
    {
        $source = $this->factory->find($type, $id);

        abort_unless($this->factory->canGenerate($source), 404);

        return view('invoices.show', [
            'invoice' => $this->factory->make($source),
        ]);
    }
}
