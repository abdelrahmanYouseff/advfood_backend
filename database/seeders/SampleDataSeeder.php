<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Create restaurants
        $restaurant1 = Restaurant::create([
            'name' => 'Pizza Palace',
            'description' => 'أفضل البيتزا الإيطالية التقليدية',
            'address' => 'شارع الملك فهد، الرياض',
            'phone' => '+966-11-234-5678',
            'email' => 'info@pizzapalace.com',
            'is_active' => true,
            'opening_time' => '10:00:00',
            'closing_time' => '22:00:00',
            'delivery_fee' => 18.75,
            'delivery_time' => 30,
        ]);

        $restaurant2 = Restaurant::create([
            'name' => 'Burger House',
            'description' => 'برجر طازج ولذيذ',
            'address' => 'شارع التحلية، جدة',
            'phone' => '+966-12-345-6789',
            'email' => 'info@burgerhouse.com',
            'is_active' => true,
            'opening_time' => '11:00:00',
            'closing_time' => '23:00:00',
            'delivery_fee' => 13.13,
            'delivery_time' => 25,
        ]);

        // Create categories for Pizza Palace
        $pizzaCategory = Category::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Pizzas',
            'description' => 'Fresh baked pizzas with various toppings',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $pastaCategory = Category::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Pasta',
            'description' => 'Italian pasta dishes',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create categories for Burger House
        $burgerCategory = Category::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Burgers',
            'description' => 'Delicious beef and chicken burgers',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $sidesCategory = Category::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Sides',
            'description' => 'French fries, onion rings, and more',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create menu items for Pizza Palace
        MenuItem::create([
            'restaurant_id' => $restaurant1->id,
            'category_id' => $pizzaCategory->id,
            'name' => 'Margherita Pizza',
            'description' => 'Classic pizza with tomato sauce, mozzarella, and basil',
            'price' => 12.99,
            'is_available' => true,
            'is_featured' => true,
            'sort_order' => 1,
            'ingredients' => 'Tomato sauce, mozzarella cheese, fresh basil',
            'preparation_time' => 15,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant1->id,
            'category_id' => $pizzaCategory->id,
            'name' => 'Pepperoni Pizza',
            'description' => 'Pizza topped with pepperoni and cheese',
            'price' => 14.99,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 2,
            'ingredients' => 'Tomato sauce, mozzarella cheese, pepperoni',
            'preparation_time' => 15,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant1->id,
            'category_id' => $pastaCategory->id,
            'name' => 'Spaghetti Carbonara',
            'description' => 'Classic Italian pasta with eggs, cheese, and pancetta',
            'price' => 11.99,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'ingredients' => 'Spaghetti, eggs, parmesan cheese, pancetta, black pepper',
            'preparation_time' => 12,
        ]);

        // Create menu items for Burger House
        MenuItem::create([
            'restaurant_id' => $restaurant2->id,
            'category_id' => $burgerCategory->id,
            'name' => 'Classic Cheeseburger',
            'description' => 'Beef patty with cheese, lettuce, tomato, and special sauce',
            'price' => 8.99,
            'is_available' => true,
            'is_featured' => true,
            'sort_order' => 1,
            'ingredients' => 'Beef patty, cheese, lettuce, tomato, onion, special sauce',
            'preparation_time' => 10,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant2->id,
            'category_id' => $burgerCategory->id,
            'name' => 'Bacon Deluxe Burger',
            'description' => 'Premium burger with bacon, cheese, and all the fixings',
            'price' => 11.99,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 2,
            'ingredients' => 'Beef patty, bacon, cheese, lettuce, tomato, onion, special sauce',
            'preparation_time' => 12,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant2->id,
            'category_id' => $sidesCategory->id,
            'name' => 'French Fries',
            'description' => 'Crispy golden fries served with ketchup',
            'price' => 3.99,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'ingredients' => 'Potatoes, salt, vegetable oil',
            'preparation_time' => 8,
        ]);
    }
}
