<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'event', 'subject_type', 'subject_id', 'description', 'changes', 'ip', 'user_agent'])]
class ActivityLog extends Model
{
    protected $casts = ['changes' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subject(): MorphTo { return $this->morphTo(); }

    public static function record(string $event, $subject = null, string $description = '', array $changes = []): self
    {
        $request = request();
        return self::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description ?: $event,
            'changes' => $changes ?: null,
            'ip' => $request?->ip(),
            'user_agent' => substr($request?->userAgent() ?? '', 0, 255),
        ]);
    }
}
