<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DelawaProductsSeeder extends Seeder
{
    /** Delawa restaurant ID in advfood_backend (as provided). */
    public const DELAWA_RESTAURANT_ID = 821017371;

    /**
     * Products copied from http://adv-line.sa/api/delawa/products (no API call at runtime).
     */
    protected static function products(): array
    {
        return [
            ['id' => 42, 'name' => 'Delawa Winter Package', 'price' => 289, 'image' => 'https://adv-line.sa/storage/products/images/EknIkcbjV7grXhQxjqHCmKXsZdPBHYUAoQi5abou.jpg'],
            ['id' => 28, 'name' => 'Hanini Cheesecake', 'price' => 94, 'image' => 'https://adv-line.sa/storage/products/images/KjI4N45No657Os7VUSrQpaXNQojjH6ZFNOLv8ozd.png'],
            ['id' => 29, 'name' => 'Hanini Cheesecake Big Size', 'price' => 164, 'image' => 'https://adv-line.sa/storage/products/images/4dvn5ekIjqEl79Aju5JuYVnZBDuOljuPlah0S8iq.png'],
            ['id' => 30, 'name' => 'Dolce Brownies', 'price' => 109, 'image' => 'https://adv-line.sa/storage/products/images/DYolQ38E1foNQudDBYjLOoGcvRd8nQjvqJxJDZVJ.png'],
            ['id' => 31, 'name' => 'Dolce Brownies big size', 'price' => 179, 'image' => 'https://adv-line.sa/storage/products/images/zcx138pnrTVlDc7BEcDgjJdyKnBIgY5MuaotBnMr.png'],
            ['id' => 32, 'name' => 'Croquant Pistachio', 'price' => 109, 'image' => 'https://adv-line.sa/storage/products/images/Sevo0ppAn6Yu6okrITG1uHVvSitTvlE2hew1W7Wq.png'],
            ['id' => 33, 'name' => 'Croquant Pistachio big size', 'price' => 179, 'image' => 'https://adv-line.sa/storage/products/images/E0k7JFOuJfFO5TymADvh3YGksWfH3MfC38Lg7vR2.png'],
            ['id' => 34, 'name' => 'Delawa Swiss Roll', 'price' => 164, 'image' => 'https://adv-line.sa/storage/products/images/ZewMsCcWOqzX559nkEc6MB4HR3LMAGUNns9keNDD.png'],
            ['id' => 35, 'name' => 'Midnight Brûlée Cake', 'price' => 195, 'image' => 'https://adv-line.sa/storage/products/images/LYoQqHg8UY85jATr8y5VABeINwKTzs96njPLRZvf.png'],
            ['id' => 36, 'name' => 'Honey Cake', 'price' => 164, 'image' => 'https://adv-line.sa/storage/products/images/VBxZiOSdGQipQ59TkQfA4G7sdJbKisvf13SBRqmQ.png'],
            ['id' => 37, 'name' => 'Berry Nutella Bites', 'price' => 149, 'image' => 'https://adv-line.sa/storage/products/images/Pg006gglzHE48A0YcXF0CLXRL9waTNDXigvxfnaC.png'],
            ['id' => 38, 'name' => 'Pistachio Raspberry Bites', 'price' => 169, 'image' => 'https://adv-line.sa/storage/products/images/tAfMvIpo6PIYKPzhKZxqEc6U221TmK9I9GcUMrjy.png'],
            ['id' => 39, 'name' => 'Kunafa Brownies Bites', 'price' => 169, 'image' => 'https://adv-line.sa/storage/products/images/73Ojh4RDRfip0INc4XPj2WntCvTLeatxvMHQxGVb.png'],
            ['id' => 40, 'name' => 'Dulce Ganache with Dates', 'price' => 179, 'image' => 'https://adv-line.sa/storage/products/images/SP061r9LxPDTnhs3ZRr9YpTtsLZRgbCvh2gwSlx6.png'],
            ['id' => 41, 'name' => 'Delawa Chocorush', 'price' => 235, 'image' => 'https://adv-line.sa/storage/products/images/yQphZbWZsoWkPoLljqOL4GASFaoJn8paAajScoru.jpg'],
            ['id' => 17, 'name' => 'Wild Berry Cake', 'price' => 349, 'image' => 'https://adv-line.sa/menu/delawa/17.webp'],
            ['id' => 18, 'name' => 'Delawa Millefeuille', 'price' => 189, 'image' => 'https://adv-line.sa/menu/delawa/18.webp'],
            ['id' => 1, 'name' => 'Tutti Frutti - Totii Froti Smart Size', 'price' => 94, 'image' => 'https://adv-line.sa/menu/delawa/1.webp'],
            ['id' => 16, 'name' => 'Crunchy Pistachio Cake', 'price' => 235, 'image' => 'https://adv-line.sa/menu/delawa/16.webp'],
            ['id' => 15, 'name' => 'Monamour Chocolate Cake', 'price' => 195, 'image' => 'https://adv-line.sa/menu/delawa/15.webp'],
            ['id' => 14, 'name' => 'Cheese Cake Madrid Mix Berry', 'price' => 235, 'image' => 'https://adv-line.sa/menu/delawa/14.webp'],
            ['id' => 13, 'name' => 'Gatherings Box', 'price' => 189, 'image' => 'https://adv-line.sa/menu/delawa/13.webp'],
            ['id' => 12, 'name' => 'Mango Pavlova - Mango Pavlova Big Size', 'price' => 179, 'image' => 'https://adv-line.sa/menu/delawa/12.webp'],
            ['id' => 11, 'name' => 'Verry Berry - Verry Berry Big Size', 'price' => 179, 'image' => 'https://adv-line.sa/menu/delawa/11.webp'],
            ['id' => 10, 'name' => 'Dulce De Leche - Dulce De Leche Big Size', 'price' => 194, 'image' => 'https://adv-line.sa/menu/delawa/10.webp'],
            ['id' => 9, 'name' => 'Cheesy Lotus Cherry - Cheesy Lotus Cherry Big Size', 'price' => 179, 'image' => 'https://adv-line.sa/menu/delawa/9.webp'],
            ['id' => 8, 'name' => 'Tiramisu Delawa - Delawa Tramisu Big Size', 'price' => 194, 'image' => 'https://adv-line.sa/menu/delawa/8.webp'],
            ['id' => 7, 'name' => 'Tutti Frutti - Totii Frotii Big Size', 'price' => 194, 'image' => 'https://adv-line.sa/menu/delawa/7.webp'],
            ['id' => 6, 'name' => 'Dulce De Leche - Dulce De Leche Smart Size', 'price' => 94, 'image' => 'https://adv-line.sa/menu/delawa/6.webp'],
            ['id' => 5, 'name' => 'Mango Pavlova - Mango Pavlova Smart Size', 'price' => 79, 'image' => 'https://adv-line.sa/menu/delawa/5.webp'],
            ['id' => 4, 'name' => 'Verry Berry - Verry Berry Smart Size', 'price' => 79, 'image' => 'https://adv-line.sa/menu/delawa/4.webp'],
            ['id' => 3, 'name' => 'Cheesy Lotus Cherry - Cheesy Lotus Cherry Smart Size', 'price' => 79, 'image' => 'https://adv-line.sa/menu/delawa/3.webp'],
            ['id' => 2, 'name' => 'Tiramisu Delawa - Delawa Tramisu Smart Size', 'price' => 94, 'image' => 'https://adv-line.sa/menu/delawa/2.webp'],
        ];
    }

    /**
     * Ensure Delawa restaurant exists (id = 821017371). Creates it if missing.
     */
    protected function ensureDelawaRestaurant(): void
    {
        if (Restaurant::find(self::DELAWA_RESTAURANT_ID)) {
            return;
        }

        DB::table('restaurants')->insert([
            'id' => self::DELAWA_RESTAURANT_ID,
            'name' => 'Delawa',
            'description' => null,
            'address' => '—',
            'phone' => '—',
            'email' => null,
            'logo' => null,
            'cover_image' => null,
            'is_active' => true,
            'opening_time' => null,
            'closing_time' => null,
            'delivery_fee' => 0,
            'delivery_time' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function run(): void
    {
        $this->ensureDelawaRestaurant();

        $sortOrder = 0;
        foreach (self::products() as $product) {
            MenuItem::updateOrCreate(
                [
                    'restaurant_id' => self::DELAWA_RESTAURANT_ID,
                    'name' => $product['name'],
                ],
                [
                    'price' => (float) $product['price'],
                    'image' => $product['image'] ?? null,
                    'description' => null,
                    'is_available' => true,
                    'is_featured' => false,
                    'sort_order' => $sortOrder++,
                ]
            );
        }
    }
}
