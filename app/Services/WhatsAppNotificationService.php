<?php

namespace App\Services;

class WhatsAppNotificationService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', '');
        $this->apiKey = config('services.whatsapp.api_key', '');
    }

    /** Send WhatsApp message via configured gateway */
    public function send(string $phone, string $message): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return ['success' => false, 'error' => 'WhatsApp gateway not configured'];
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

    /** Send estimation for customer approval */
    public function sendEstimation($service, string $phone): array
    {
        $url = url('/track/' . ($service->job_no ?? $service->id));
        $message = "Bengkel Paten\n\nEstimasi Servis:\n{$service->title}\nBiaya: Rp " . number_format($service->charge, 0, ',', '.') . "\n\nLihat & Approve: {$url}\n\nBalas YA untuk menyetujui.";

        return $this->send($phone, $message);
    }

    /** Send service completion notification */
    public function sendServiceComplete($service, string $phone): array
    {
        $message = "Bengkel Paten\n\nServis {$service->title} TELAH SELESAI.\nKendaraan siap diambil.\n\nTotal: Rp " . number_format($service->charge, 0, ',', '.') . "\n\nTerima kasih!";

        return $this->send($phone, $message);
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) return '62' . substr($phone, 1);
        if (str_starts_with($phone, '62')) return $phone;
        return '62' . $phone;
    }
}
