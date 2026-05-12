<?php

namespace App\Services;

use App\Models\Branch;
use InvalidArgumentException;

class BranchPickupBranchResolver
{
    /**
     * @param  int  $branchCode  Client branch code: 1 = Mrouj, 2 = Laban
     */
    public static function resolveBranchId(int $branchCode): int
    {
        $map = config('branch_pickup', []);
        $email = $map[$branchCode] ?? null;

        if (empty($email) || !is_string($email)) {
            throw new InvalidArgumentException('Invalid branch_code: no mapping configured.');
        }

        $branch = Branch::where('email', $email)->first();

        if (!$branch) {
            throw new InvalidArgumentException(
                "Branch for code {$branchCode} is not configured in the system (email: {$email})."
            );
        }

        return (int) $branch->id;
    }
}
