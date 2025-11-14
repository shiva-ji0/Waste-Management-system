<?php
namespace App\Mail;

use App\Models\Waste;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WasteStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public Waste $waste;

    public function __construct(Waste $waste)
    {
        $this->waste = $waste;
    }

    public function build()
    {
        return $this->subject('Waste Pickup Status Update')
                    ->view('emails.waste-status');
    }
}
