<?php

namespace App\Support;

/**
 * Explicit branch authorization state (FAIL-CLOSED).
 *
 * - unrestricted(): super_admin/admin — may read all branches (incl. NULL).
 * - restricted(ids): non-admin — may read ONLY the given branch ids (never NULL).
 * - denied(): restricted with zero assignments — may read NOTHING (zero rows).
 *
 * EMPTY IDS !== UNRESTRICTED. Use ::restricted([]) / ::denied() for zero-assignment.
 */
final class BranchAccess
{
    private function __construct(
        public readonly bool $unrestricted,
        public readonly array $branchIds,
    ) {}

    public static function unrestricted(): self
    {
        return new self(true, []);
    }

    /**
     * @param  array<int>|iterable<int>  $branchIds
     */
    public static function restricted(iterable $branchIds): self
    {
        $ids = collect($branchIds)->map(fn ($id) => (int) $id)->unique()->values()->all();

        return new self(false, $ids);
    }

    public static function denied(): self
    {
        return new self(false, []);
    }

    public function isUnrestricted(): bool
    {
        return $this->unrestricted;
    }

    public function isDenied(): bool
    {
        return ! $this->unrestricted && $this->branchIds === [];
    }

    /**
     * May this state read the given branch? NULL branch rows are
     * global/legacy data: only unrestricted users may read them.
     */
    public function canAccess(?int $branchId): bool
    {
        if ($this->unrestricted) {
            return true;
        }

        if ($branchId === null) {
            return false;
        }

        return in_array($branchId, $this->branchIds, true);
    }
}
