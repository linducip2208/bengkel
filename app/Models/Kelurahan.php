<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelurahan extends Model
{
    protected $table = 'kelurahan';

    protected $fillable = ['code', 'name', 'kecamatan', 'kabupaten', 'provinsi', 'slug', 'is_active'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->name}, {$this->kecamatan}, {$this->kabupaten}, {$this->provinsi}";
    }

    public function getCitySlugAttribute(): string
    {
        return str_replace(' ', '-', strtolower($this->kabupaten));
    }
}
