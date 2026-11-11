<?php

namespace App\Mail;

use App\Data\Bookings\BookingMailData;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Booking|BookingMailData $booking)
    {
        $this->onQueue('emails');
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "حجز جديد في {$this->details()->centerName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.bookings.new-booking-received',
            with: ['details' => $this->details()],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function details(): BookingMailData
    {
        return $this->booking instanceof BookingMailData
            ? $this->booking
            : BookingMailData::fromBooking($this->booking);
    }
}
