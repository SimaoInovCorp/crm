<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Deal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(private ActivityLogService $activityLogService) {}

    /**
     * Generate a signed PDF invoice for the deal linked to a calendar event
     * and send it to the person associated with the event or deal.
     *
     * @throws \RuntimeException
     */
    public function sendInvoice(CalendarEvent $event): string
    {
        // Load required relationships
        $event->load(['deal.products', 'deal.entity', 'person', 'deal.owner']);

        $deal   = $event->deal;
        $person = $event->person ?? $deal?->person;

        if (! $deal) {
            throw new \RuntimeException('No deal is linked to this calendar event.');
        }

        if (! $person || ! $person->email) {
            throw new \RuntimeException('No person with an email address is linked to this event or deal.');
        }

        // Load deal products via pivot
        $products = $deal->products()->withPivot(['quantity', 'price'])->get();

        $subtotal = $products->sum(fn ($p) => $p->pivot->quantity * $p->pivot->price)
            ?: (float) $deal->value;

        // Generate a cryptographic verification ID (HMAC-SHA256)
        $payload        = implode('|', [$deal->id, $event->id, $person->id, now()->timestamp]);
        $verificationId = hash_hmac('sha256', $payload, config('app.key'));

        $invoiceNumber = 'INV-' . strtoupper(Str::random(8));
        $generatedAt   = now()->toIso8601String();
        $tenantName    = $deal->tenant?->name ?? config('app.name');

        // Render PDF
        $pdf = Pdf::loadView('invoices.invoice', [
            'deal'           => $deal,
            'event'          => $event,
            'person'         => $person,
            'entity'         => $deal->entity,
            'products'       => $products,
            'subtotal'       => $subtotal,
            'invoiceNumber'  => $invoiceNumber,
            'verificationId' => $verificationId,
            'generatedAt'    => $generatedAt,
            'tenantName'     => $tenantName,
        ])->setPaper('a4', 'portrait');

        $pdfContent  = $pdf->output();
        $pdfFilename = "invoice-{$invoiceNumber}.pdf";
        $tmpPath     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdfFilename;

        file_put_contents($tmpPath, $pdfContent);

        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name');

        Mail::send([], [], function ($message) use ($person, $deal, $tmpPath, $pdfFilename, $invoiceNumber, $fromAddress, $fromName) { // intentional: closure — cannot be queued
            $message->to($person->email, $person->name)
                ->from($fromAddress, $fromName)
                ->subject("Invoice {$invoiceNumber} — {$deal->title}")
                ->html(
                    "<p>Dear {$person->name},</p>" .
                    "<p>Please find attached your invoice for <strong>{$deal->title}</strong>.</p>" .
                    "<p>Invoice number: <strong>{$invoiceNumber}</strong></p>" .
                    '<p>Thank you for your business.</p>'
                )
                ->attach($tmpPath, [
                    'as'   => $pdfFilename,
                    'mime' => 'application/pdf',
                ]);
        });

        // Clean up tmp file
        @unlink($tmpPath);

        // Log the action on the deal
        $this->activityLogService->log(
            $deal,
            'email',
            "Invoice {$invoiceNumber} sent to {$person->email} (event: {$event->title})",
            ['invoice_number' => $invoiceNumber, 'recipient' => $person->email, 'event_id' => $event->id]
        );

        return $invoiceNumber;
    }
}
