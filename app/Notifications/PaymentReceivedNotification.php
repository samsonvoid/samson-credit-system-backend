<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public $payment;
    public $customerName;

    /**
     * Create a new notification instance.
     */
    public function __construct($payment, $customerName)
    {
        $this->payment = $payment;
        $this->customerName = $customerName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Electronic Receipt - Credit System')
            ->greeting("Hello {$this->customerName},")
            ->line("We have successfully received your payment of " . number_format($this->payment->amount_paid) . " TZS.")
            ->line("Payment Method: " . ucfirst($this->payment->method))
            ->line("Date: " . $this->payment->created_at->format('M d, Y H:i'))
            ->action('View My Dashboard', url('/portal/dashboard'))
            ->line('Thank you for your business and for staying committed to your repayment plan.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
