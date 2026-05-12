<?php

/**
 * Branch pickup API: maps branch_code from the client (1 or 2) to dashboard branches.
 * Resolved at runtime by email so IDs stay correct even if rows are re-seeded.
 */
return [
    1 => env('BRANCH_PICKUP_CODE_1_EMAIL', 'mrouj@advfood.com'),
    2 => env('BRANCH_PICKUP_CODE_2_EMAIL', 'laban@advfood.com'),
];
