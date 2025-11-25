<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Envía un correo electrónico usando Resend
     *
     * @param string|array $to Dirección(es) de correo destinatario(s)
     * @param string $subject Asunto del correo
     * @param string $view Vista del correo (Blade template)
     * @param array $data Datos para la vista
     * @param string|null $from Dirección de correo remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @param array $cc Direcciones CC (opcional)
     * @param array $bcc Direcciones BCC (opcional)
     * @param array $attachments Archivos adjuntos (opcional)
     * @return bool
     */
    public function send(
        string|array $to,
        string $subject,
        string $view,
        array $data = [],
        ?string $from = null,
        ?string $fromName = null,
        array $cc = [],
        array $bcc = [],
        array $attachments = []
    ): bool {
        try {
            $fromAddress = $from ?? config('mail.from.address');
            $fromNameValue = $fromName ?? config('mail.from.name');

            $mailable = new class($to, $subject, $view, $data, $fromAddress, $fromNameValue, $cc, $bcc, $attachments) extends Mailable {
                public function __construct(
                    public $to,
                    public $subject,
                    public $view,
                    public $data,
                    public $fromAddress,
                    public $fromNameValue,
                    public $cc,
                    public $bcc,
                    public $attachments
                ) {
                    $this->subject = $subject;
                }

                public function build()
                {
                    $mail = $this->view($this->view, $this->data)
                        ->from($this->fromAddress, $this->fromNameValue)
                        ->subject($this->subject);

                    // Agregar destinatarios
                    if (is_array($this->to)) {
                        $mail->to($this->to);
                    } else {
                        $mail->to($this->to);
                    }

                    // Agregar CC
                    if (!empty($this->cc)) {
                        $mail->cc($this->cc);
                    }

                    // Agregar BCC
                    if (!empty($this->bcc)) {
                        $mail->bcc($this->bcc);
                    }

                    // Agregar adjuntos
                    foreach ($this->attachments as $attachment) {
                        if (is_string($attachment)) {
                            $mail->attach($attachment);
                        } elseif (is_array($attachment)) {
                            $mail->attach(
                                $attachment['path'],
                                $attachment['options'] ?? []
                            );
                        }
                    }

                    return $mail;
                }
            };

            Mail::mailer('resend')->send($mailable);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar correo: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Envía un correo HTML simple
     *
     * @param string|array $to Dirección(es) de correo destinatario(s)
     * @param string $subject Asunto del correo
     * @param string $html Contenido HTML del correo
     * @param string|null $from Dirección de correo remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool
     */
    public function sendHtml(
        string|array $to,
        string $subject,
        string $html,
        ?string $from = null,
        ?string $fromName = null
    ): bool {
        try {
            $fromAddress = $from ?? config('mail.from.address');
            $fromNameValue = $fromName ?? config('mail.from.name');

            $mailable = new class($to, $subject, $html, $fromAddress, $fromNameValue) extends Mailable {
                public function __construct(
                    public $to,
                    public $subject,
                    public $html,
                    public $fromAddress,
                    public $fromNameValue
                ) {
                    $this->subject = $subject;
                }

                public function build()
                {
                    return $this->html($this->html)
                        ->from($this->fromAddress, $this->fromNameValue)
                        ->subject($this->subject)
                        ->to($this->to);
                }
            };

            Mail::mailer('resend')->send($mailable);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar correo HTML: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envía un correo de texto plano
     *
     * @param string|array $to Dirección(es) de correo destinatario(s)
     * @param string $subject Asunto del correo
     * @param string $text Contenido de texto plano del correo
     * @param string|null $from Dirección de correo remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool
     */
    public function sendText(
        string|array $to,
        string $subject,
        string $text,
        ?string $from = null,
        ?string $fromName = null
    ): bool {
        try {
            $fromAddress = $from ?? config('mail.from.address');
            $fromNameValue = $fromName ?? config('mail.from.name');

            $mailable = new class($to, $subject, $text, $fromAddress, $fromNameValue) extends Mailable {
                public function __construct(
                    public $to,
                    public $subject,
                    public $text,
                    public $fromAddress,
                    public $fromNameValue
                ) {
                    $this->subject = $subject;
                }

                public function build()
                {
                    return $this->text('mail.plain')
                        ->from($this->fromAddress, $this->fromNameValue)
                        ->subject($this->subject)
                        ->to($this->to)
                        ->with(['content' => $this->text]);
                }
            };

            Mail::mailer('resend')->send($mailable);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de texto: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

