<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = ['entry_number', 'entry_date', 'description', 'reference_type', 'reference_id', 'created_by'];
    protected $casts = ['entry_date' => 'date'];
    public function lines(): HasMany { return $this->hasMany(JournalEntryLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
