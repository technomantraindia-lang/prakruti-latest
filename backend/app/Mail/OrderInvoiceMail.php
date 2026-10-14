<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public ?string $pdfPath;

    public function __construct(Order $order, ?string $pdfPath = null)
    {
        $this->order = $order;
        $this->pdfPath = $pdfPath ?: $order->invoice_pdf_path;
    }

    public function envelope(): Envelope
    {
        $invNum = $this->order->invoice_number ?: ('ORD-' . $this->order->id);
        return new Envelope(
            subject: "Your Prakruti Ayurvedic Tax Invoice [{$invNum}] - Order Confirmation",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_invoice',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfPath && Storage::disk('public')->exists($this->pdfPath)) {
            $fullPath = Storage::disk('public')->path($this->pdfPath);
            $attachments[] = Attachment::fromPath($fullPath)
                ->as("Invoice-{$this->order->invoice_number}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
