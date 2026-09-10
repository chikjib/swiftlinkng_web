<?php

namespace App\Mail;

use Carbon\Carbon;
use App\AppConstants;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $user;

    public function __construct($user)
    {

        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->user->email)
            ->from(config('app.noreplyEmail'))
            ->subject('Verify your email and unlock Swiftlink rewards')
            ->view('emails.users.verifyEmail')
            ->with([
                'firstname' => $this->user->firstname,
                'verificationUrl' => URL::temporarySignedRoute(
                    'email.verify',
                    now()->addHours(24),
                    ['user' => $this->user->id]
                ),
                'year' => Carbon::now()->year,
            ]);
    }
}
