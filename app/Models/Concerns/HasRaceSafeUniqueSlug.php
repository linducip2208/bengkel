<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait HasRaceSafeUniqueSlug
{
    private const SLUG_WRITE_ATTEMPTS = 20;

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $counter = 2;

        while (static::withoutGlobalScopes()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public static function createWithUniqueSlug(array $attributes): self
    {
        $source = (string) ($attributes['slug'] ?? $attributes[static::UNIQUE_SLUG_SOURCE] ?? '');

        for ($attempt = 1; $attempt <= self::SLUG_WRITE_ATTEMPTS; $attempt++) {
            $attributes['slug'] = static::generateUniqueSlug($source);

            try {
                return static::create($attributes);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::SLUG_WRITE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Tidak dapat membuat slug unik.');
    }

    public function updateWithUniqueSlug(array $attributes): bool
    {
        $source = (string) ($attributes['slug'] ?? $attributes[static::UNIQUE_SLUG_SOURCE] ?? $this->getAttribute(static::UNIQUE_SLUG_SOURCE));

        for ($attempt = 1; $attempt <= self::SLUG_WRITE_ATTEMPTS; $attempt++) {
            $attributes['slug'] = static::generateUniqueSlug($source, $this->getKey());

            try {
                return $this->update($attributes);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::SLUG_WRITE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Tidak dapat memperbarui slug unik.');
    }
}
