<?php

use App\Services\MailService;

if (!function_exists('sendMail')) {
    /**
     * Función helper global para enviar correos usando Resend
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
    function sendMail(
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
        $mailService = app(MailService::class);
        return $mailService->send($to, $subject, $view, $data, $from, $fromName, $cc, $bcc, $attachments);
    }
}

if (!function_exists('sendMailHtml')) {
    /**
     * Función helper global para enviar correos HTML usando Resend
     *
     * @param string|array $to Dirección(es) de correo destinatario(s)
     * @param string $subject Asunto del correo
     * @param string $html Contenido HTML del correo
     * @param string|null $from Dirección de correo remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool
     */
    function sendMailHtml(
        string|array $to,
        string $subject,
        string $html,
        ?string $from = null,
        ?string $fromName = null
    ): bool {
        $mailService = app(MailService::class);
        return $mailService->sendHtml($to, $subject, $html, $from, $fromName);
    }
}

if (!function_exists('sendMailText')) {
    /**
     * Función helper global para enviar correos de texto plano usando Resend
     *
     * @param string|array $to Dirección(es) de correo destinatario(s)
     * @param string $subject Asunto del correo
     * @param string $text Contenido de texto plano del correo
     * @param string|null $from Dirección de correo remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool
     */
    function sendMailText(
        string|array $to,
        string $subject,
        string $text,
        ?string $from = null,
        ?string $fromName = null
    ): bool {
        $mailService = app(MailService::class);
        return $mailService->sendText($to, $subject, $text, $from, $fromName);
    }
}

