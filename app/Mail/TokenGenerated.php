<?php

namespace App\Mail;

use App\AppConstants;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TokenGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    private $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->user->email, $this->user->firstname)
            ->from(config('app.noreplyEmail'))
            ->subject(config('app.siteName') . ': Reset Password')
            ->view('emails.users.token')
            ->with([
                'firstname' => $this->user->firstname,
                'year' => Carbon::now()->year,
            ]);
    }
}
