<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public Organization $organization;
    public ?string $pdfPath;
    public string $customerName;
    public string $organizationName;
    public string $invoiceNumber;
    public string $invoiceDate;
    public string $dueDate;
    public string $totalAmount;
    public string $currency;
    public ?string $customMessage;
    public string $viewUrl;

    public function __construct(
        Invoice $invoice,
        Organization $organization,
        ?string $pdfPath = null,
        ?string $customMessage = null
    ) {
        $this->invoice = $invoice;
        $this->organization = $organization;
        $this->pdfPath = $pdfPath;
        $this->customMessage = $customMessage;

        // Pre-compute data for the view
        $this->customerName = $invoice->customer->name ?? 'Valued Customer';
        $this->organizationName = $organization->name;
        $this->invoiceNumber = $invoice->invoice_number;
        $this->invoiceDate = $invoice->invoice_date->format('M d, Y');
        $this->dueDate = $invoice->due_date->format('M d, Y');
        $this->currency = $organization->currency ?? 'ZMW';
        $this->totalAmount = number_format($invoice->total_amount, 2);
        $this->viewUrl = config('app.url') . '/invoices/' . $invoice->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoiceNumber} from {$this->organizationName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $attachments[] = Attachment::fromPath($this->pdfPath)
                ->as("Invoice-{$this->invoiceNumber}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
