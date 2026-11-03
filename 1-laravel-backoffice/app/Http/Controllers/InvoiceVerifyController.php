<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

/**
 * Fatura doğrulama (QR kod): verification_code ile faturanın
 * meşruiyetini herkese açık biçimde teyit eder.
 */
class InvoiceVerifyController extends Controller
{
    public function show(string $code)
    {
        $invoice = Invoice::with(['contact', 'company', 'items'])
            ->where('verification_code', $code)
            ->first();

        if (! $invoice) {
            return view('invoices.verify', ['invoice' => null, 'code' => $code]);
        }

        return view('invoices.verify', ['invoice' => $invoice, 'code' => $code]);
    }
}
