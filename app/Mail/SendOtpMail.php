<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;

    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Reset Password MotoCare',
        );
    }

    public function content(): Content
    {
        // Kita akan buat view sederhana menggunakan string langsung agar cepat
        return new Content(
            htmlString: "<h1>Reset Password</h1><p>Kode OTP kamu adalah: <strong>{$this->otpCode}</strong></p><p>Kode ini berlaku selama 5 menit.</p>"
        );
    }
}
