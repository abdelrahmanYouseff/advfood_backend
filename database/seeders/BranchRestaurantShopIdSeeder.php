<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchRestaurantShopId;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class BranchRestaurantShopIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get branches
        $mroujBranch = Branch::where('email', 'mrouj@advfood.com')->first();
        $labanBranch = Branch::where('email', 'laban@advfood.com')->first();

        if (!$mroujBranch || !$labanBranch) {
            $this->command->error('❌ Branches not found. Please run BranchSeeder first.');
            return;
        }

        // Get restaurants by name
        $gatherUs = Restaurant::where('name', 'Gather Us')->first();
        $delawa = Restaurant::where('name', 'Delawa')->first();
        $tantBakiza = Restaurant::where('name', 'Tant Bakiza')->first();

        if (!$gatherUs || !$delawa || !$tantBakiza) {
            $this->command->error('❌ Restaurants not found. Please ensure restaurants are seeded.');
            return;
        }

        // Mrouj branch shop_ids (existing values - need to get from restaurant table)
        $mroujShopIds = [
            $gatherUs->id => $gatherUs->shop_id ?? '210', // Gather Us
            $delawa->id => $delawa->shop_id ?? null, // Delawa - use current value
            $tantBakiza->id => $tantBakiza->shop_id ?? null, // Tant Bakiza - use current value
        ];

        // Laban branch shop_ids (new values)
        $labanShopIds = [
            $gatherUs->id => '218', // Gather Us
            $delawa->id => '219', // Delawa
            $tantBakiza->id => '220', // Tant Bakiza
        ];

        // Create mappings for Mrouj branch
        foreach ($mroujShopIds as $restaurantId => $shopId) {
            if ($shopId) {
                BranchRestaurantShopId::updateOrCreate(
                    [
                        'branch_id' => $mroujBranch->id,
                        'restaurant_id' => $restaurantId,
                    ],
                    [
                        'shop_id' => $shopId,
                    ]
                );
            }
        }

        // Create mappings for Laban branch
        foreach ($labanShopIds as $restaurantId => $shopId) {
            BranchRestaurantShopId::updateOrCreate(
                [
                    'branch_id' => $labanBranch->id,
                    'restaurant_id' => $restaurantId,
                ],
                [
                    'shop_id' => $shopId,
                ]
            );
        }

        $this->command->info('✅ Branch restaurant shop IDs seeded successfully!');
    }
}
