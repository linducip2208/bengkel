<?php

namespace App\Support;

final class PseoPageData
{
    public function __construct(
        public readonly string $slug,
        public readonly string $intent,
        public readonly string $title,
        public readonly string $metaDescription,
        public readonly string $h1,
        public readonly string $intro,
        public readonly string $eyebrow,
        public readonly string $problem,
        public readonly string $solution,
        public readonly array $benefits,
        public readonly array $features,
        public readonly string $audience,
        public readonly array $faq,
        public readonly array $relatedLinks,
        public readonly string $canonical,
        public readonly bool $indexable,
        public readonly array $schema,
        public readonly string $generatedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
