<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsService
{
    public function get(string $key, $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public function set(string $key, $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $this->guessGroup($key)]
        );
    }

    public function getAllByGroup(string $group): array
    {
        return Setting::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public function getInvoiceSections(): array
    {
        $default = ['company', 'customer', 'items', 'totals', 'payments', 'notes', 'footer'];

        $raw = $this->get('invoice_sections', '');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($section) => in_array($section, $default, true)));
            }
        }

        return $default;
    }

    public function getCompanyInfo(): array
    {
        return [
            'name' => $this->get('company_name', config('app.name')),
            'address' => $this->get('company_address', ''),
            'phone' => $this->get('company_phone', ''),
            'email' => $this->get('company_email', ''),
            'logo' => $this->get('company_logo', ''),
            'tax_id' => $this->get('company_tax_id', ''),
        ];
    }

    public function index(): array
    {
        return Setting::all()->groupBy('group')->toArray();
    }

    public function update(Request $request): void
    {
        foreach ($request->all() as $key => $value) {
            if ($key === '_token' || $key === '_method') {
                continue;
            }
            $this->set($key, $value);
        }
    }

    private function guessGroup(string $key): string
    {
        $groups = [
            'company' => 'general',
            'timezone' => 'general',
            'date_format' => 'general',
            'currency' => 'general',
            'language' => 'general',
            'smtp' => 'email',
            'mail_from' => 'email',
            'whatsapp' => 'whatsapp',
            'invoice' => 'invoice',
            'notify' => 'notification',
        ];

        foreach ($groups as $prefix => $group) {
            if (str_starts_with($key, $prefix)) {
                return $group;
            }
        }

        return 'general';
    }
}
