<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the effective branch for the current request context.
 *
 * Order of precedence:
 *  1. Explicit session context (web tenant UI) — wins.
 *  2. Fallback for stateless contexts (Sanctum API): the authenticated
 *     user's branch when they have exactly one assignment, so transactional
 *     rows never silently fall through to branch_id = NULL.
 *  3. null when no unambiguous branch can be determined (kept as global).
 */
class BranchContext
{
    public function resolveCurrentBranchId(): ?int
    {
        if (session()->has('current_branch_id')) {
            $branchId = session('current_branch_id');

            return $branchId ? (int) $branchId : null;
        }

        $user = Auth::user();

        if ($user instanceof User) {
            $accessible = $user->accessibleBranchIds();

            // Only auto-assign when the user is scoped to exactly one branch;
            // a multi-branch user must choose explicitly per request.
            if ($accessible->count() === 1) {
                return (int) $accessible->first();
            }
        }

        return null;
    }
}
