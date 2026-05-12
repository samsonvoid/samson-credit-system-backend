<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepaymentReminderNotification extends Notification
{
    use Queueable;

    protected $customer;
    protected $balance;

    /**
     * Create a new notification instance.
     */
    public function __construct($customer, $balance)
    {
        $this->customer = $customer;
        $this->balance = $balance;
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Friendly Repayment Reminder - ' . config('app.name'))
            ->greeting('Hello ' . $this->customer->name . ',')
            ->line('This is a friendly reminder regarding your outstanding balance of ' . number_format($this->balance) . ' TZS.')
            ->line('To maintain your Trust Score and ensure continued access to credit, please visit your portal to view payment details.')
            ->action('View My Portal', url('/portal/dashboard'))
            ->line('If you have already made a payment, please disregard this message.')
            ->line('Thank you for your continued business!');
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
