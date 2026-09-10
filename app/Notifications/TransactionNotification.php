<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

class TransactionNotification extends Notification
{
    use Queueable;
    private $transactionData;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($transactionData)
    {
        $this->transactionData = $transactionData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        //return ['mail', 'database', 'telegram'];
        return ['database', 'telegram'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // return (new MailMessage)
        //             ->line('The introduction to the notification.')
        //             ->action('Notification Action', url('/'))
        //             ->line('Thank you for using our application!');

        return (new MailMessage)
            //->name($this->transactionData['name'])
            ->line($this->transactionData['body'])
            ->action($this->transactionData['transactionText'], $this->transactionData['transactionUrl'])
            ->line($this->transactionData['thanks']);
    }

    public function toTelegram($notifiable)
    {
        return TelegramMessage::create()
            ->to($this->transactionData['channelID'])
            ->content($this->transactionData['telegramText'])
            ->options(['parse_mode' => 'MarkdownV2']);


        // $user->notify(new SendNotification($invoice));

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'transaction_id' => $this->transactionData['transaction_id']
        ];
    }
}
