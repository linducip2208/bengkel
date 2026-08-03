<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'name', 'api_format', 'base_url', 'merchant_id',
    'api_key_encrypted', 'secret_key_encrypted',
    'extra_headers', 'extra_config', 'callback_path',
    'supported_methods', 'is_active', 'is_default', 'sandbox_mode', 'description',
])]
class PaymentGateway extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'extra_headers' => 'array',
            'extra_config' => 'array',
            'supported_methods' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sandbox_mode' => 'boolean',
        ];
    }

    public function getApiKeyAttribute(): ?string
    {
        if (empty($this->api_key_encrypted)) return null;
        try { return Crypt::decryptString($this->api_key_encrypted); }
        catch (\Throwable) { return null; }
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSecretKeyAttribute(): ?string
    {
        if (empty($this->secret_key_encrypted)) return null;
        try { return Crypt::decryptString($this->secret_key_encrypted); }
        catch (\Throwable) { return null; }
    }

    public function setSecretKeyAttribute(?string $value): void
    {
        $this->attributes['secret_key_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    public static function default(): ?self
    {
        return self::where('is_active', true)->where('is_default', true)->first()
            ?? self::where('is_active', true)->first();
    }
}
