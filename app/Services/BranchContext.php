<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the effective branch for the current request context.
 *
 * Order of precedence:
 *  1. Explicit session context (web tenant UI) — wins, but only when the
 *     authenticated user is authorized for it (otherwise null so callers
 *     fail closed instead of silently falling through to branch_id = NULL).
 *  2. Fallback for stateless contexts (Sanctum API): the authenticated
 *     user's branch when they are restricted to exactly one assignment.
 *  3. null when no unambiguous, authorized branch can be determined.
 *     Callers MUST treat null as "no branch" and fail closed for
 *     restricted users (HasBranchScope enforces this on create).
 */
class BranchContext
{
    public function resolveCurrentBranchId(): ?int
    {
        if (session()->has('current_branch_id')) {
            $branchId = session('current_branch_id');
            if (! $branchId) {
                return null;
            }
            $branchId = (int) $branchId;

            $user = Auth::user();
            if ($user instanceof User && ! $user->branchAccess()->canAccess($branchId)) {
                return null;
            }

            return $branchId;
        }

        $user = Auth::user();

        if ($user instanceof User) {
            $access = $user->branchAccess();

            // Only auto-assign when the user is restricted to exactly one
            // branch; multi-branch users must choose explicitly per request,
            // zero-assignment users must fail closed, unrestricted admins
            // keep explicit/global behaviour (null here).
            if (! $access->isUnrestricted() && count($access->branchIds) === 1) {
                return (int) $access->branchIds[0];
            }
        }

        return null;
    }
}
