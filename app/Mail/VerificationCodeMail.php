<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $type;
    public $userName;

    public function __construct(string $code, string $type, string $userName)
    {
        $this->code     = $code;
        $this->type     = $type;
        $this->userName = $userName;
    }

    public function build(): self
    {
        $subject = $this->type === 'email_verification'
            ? 'Verificação de E-mail - LocafyFest'
            : 'Redefinição de Senha - LocafyFest';

        return $this->subject($subject)->view('emails.verification_code');
    }
}
