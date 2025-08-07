<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing data
        $users = User::all();
        $restaurants = Restaurant::all();

        if ($users->isEmpty() || $restaurants->isEmpty()) {
            return;
        }

        // Create sample orders first
        $orders = [];
        foreach ($restaurants as $restaurant) {
            foreach ($users as $user) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'restaurant_id' => $restaurant->id,
                    'status' => 'delivered',
                    'subtotal' => rand(5625, 18750) / 100, // Random amount between 56.25-187.50 SAR
                    'delivery_fee' => $restaurant->delivery_fee,
                    'tax' => rand(375, 1125) / 100, // Random tax between 3.75-11.25 SAR
                    'total' => 0, // Will be calculated
                    'delivery_address' => 'شارع الملك فهد، الرياض',
                    'delivery_phone' => '+966-50-123-4567',
                    'delivery_name' => $user->name,
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                ]);

                // Calculate total
                $order->total = $order->subtotal + $order->delivery_fee + $order->tax;
                $order->save();

                $orders[] = $order;
            }
        }

        // Create invoices for the orders
        foreach ($orders as $order) {
            Invoice::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'restaurant_id' => $order->restaurant_id,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'tax' => $order->tax,
                'total' => $order->total,
                'status' => 'paid',
                'due_date' => now()->addDays(30),
                'paid_at' => now(),
                'notes' => 'Sample invoice for testing',
            ]);
        }

        // Create some pending invoices
        foreach ($restaurants as $restaurant) {
            $user = $users->random();
            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'status' => 'delivered',
                'subtotal' => rand(7500, 30000) / 100,
                'delivery_fee' => $restaurant->delivery_fee,
                'tax' => rand(563, 1500) / 100,
                'total' => 0,
                'delivery_address' => 'شارع التحلية، جدة',
                'delivery_phone' => '+966-50-987-6543',
                'delivery_name' => $user->name,
                'payment_method' => 'card',
                'payment_status' => 'pending',
            ]);

            $order->total = $order->subtotal + $order->delivery_fee + $order->tax;
            $order->save();

            Invoice::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'restaurant_id' => $order->restaurant_id,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'tax' => $order->tax,
                'total' => $order->total,
                'status' => 'pending',
                'due_date' => now()->addDays(7),
                'notes' => 'Pending payment',
            ]);
        }
    }
}
