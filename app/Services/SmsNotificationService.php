<?php

namespace App\Services;

class SmsNotificationService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url', '');
        $this->apiKey = config('services.sms.api_key', '');
    }

    public function send(string $phone, string $message): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return ['success' => false, 'error' => 'SMS gateway not configured'];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post($this->apiUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
                'json' => ['phone' => $this->normalizePhone($phone), 'message' => $message],
            ]);
            return ['success' => true, 'data' => json_decode($response->getBody(), true)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendReminder(string $phone, string $type, string $detail): array
    {
        $message = "Aplikasi Bengkel Terbaik Reminder\n{$type}: {$detail}\nHubungi kami untuk booking.";
        return $this->send($phone, $message);
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) return '62' . substr($phone, 1);
        return $phone;
    }
}
