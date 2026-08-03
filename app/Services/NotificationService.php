<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(string $templateSlug, Model $notifiable, array $data = []): void
    {
        $template = $this->getTemplate($templateSlug);

        if (!$template || !$template->is_active) {
            return;
        }

        $to = $notifiable->email ?? $notifiable->phone ?? null;
        if (!$to) {
            return;
        }

        if ($template->channel === 'email') {
            $this->sendEmail($templateSlug, $to, $data);
        } elseif ($template->channel === 'whatsapp') {
            $this->sendWhatsApp($templateSlug, $to, $data);
        }
    }

    public function sendEmail(string $templateSlug, string $to, array $data): void
    {
        $template = $this->getTemplate($templateSlug);
        if (!$template || !$template->is_active) {
            return;
        }

        try {
            $subject = $this->parseTemplate($template->subject, $data);
            $body = $this->parseTemplate($template->body, $data);

            Mail::html($body, function ($message) use ($to, $subject) {
                $fromAddress = app(SettingsService::class)->get('mail_from_address', config('mail.from.address'));
                $fromName = app(SettingsService::class)->get('mail_from_name', config('mail.from.name'));
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

            $this->logSent($to, $subject, $body, 'sent');
        } catch (\Exception $e) {
            $this->logSent($to, $template->subject ?? '', $template->body ?? '', 'failed', $e->getMessage());
        }
    }

    public function sendWhatsApp(string $templateSlug, string $to, array $data): void
    {
        $template = $this->getTemplate($templateSlug);
        if (!$template || !$template->is_active) {
            return;
        }

        $provider = app(SettingsService::class)->get('whatsapp_provider', 'none');
        if ($provider === 'none') {
            return;
        }

        $body = $this->parseTemplate($template->body, $data);

        try {
            $apiUrl = app(SettingsService::class)->get('whatsapp_api_url', '');
            $apiKey = app(SettingsService::class)->get('whatsapp_api_key', '');
            $senderId = app(SettingsService::class)->get('whatsapp_sender_id', '');

            if ($provider === 'fonnte') {
                $this->sendViaFonnte($to, $body, $apiUrl, $apiKey);
            } elseif ($provider === 'wablas') {
                $this->sendViaWablas($to, $body, $apiUrl, $apiKey);
            } elseif ($provider === 'wa_business') {
                $this->sendViaWABusiness($to, $body, $apiUrl, $apiKey, $senderId);
            }

            $this->logSent($to, 'WhatsApp Notification', $body, 'sent');
        } catch (\Exception $e) {
            $this->logSent($to, 'WhatsApp Notification', $body, 'failed', $e->getMessage());
        }
    }

    private function sendViaFonnte(string $to, string $message, string $apiUrl, string $apiKey): void
    {
        \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post(rtrim($apiUrl, '/') . '/send', [
                'target' => $to,
                'message' => $message,
            ]);
    }

    private function sendViaWablas(string $to, string $message, string $apiUrl, string $apiKey): void
    {
        \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post(rtrim($apiUrl, '/') . '/send-message', [
                'phone' => $to,
                'message' => $message,
            ]);
    }

    private function sendViaWABusiness(string $to, string $message, string $apiUrl, string $apiKey, string $senderId): void
    {
        \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post(rtrim($apiUrl, '/') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
                'recipient_type' => 'individual',
            ]);
    }

    public function getTemplate(string $slug): ?NotificationTemplate
    {
        return NotificationTemplate::where('slug', $slug)->first();
    }

    public function logSent(string $to, string $subject, string $body, string $status, ?string $error = null): EmailLog
    {
        return EmailLog::create([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    private function parseTemplate(string $template, array $data): string
    {
        $search = array_map(fn($k) => '{' . $k . '}', array_keys($data));
        $replace = array_values($data);

        return str_replace($search, $replace, $template);
    }
}
