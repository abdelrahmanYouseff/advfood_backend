<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('categories')->get();

        if ($restaurants->isEmpty()) {
            return;
        }

        // Pizza Palace Menu Items
        $pizzaPalace = $restaurants->where('name', 'Pizza Palace')->first();
        if ($pizzaPalace) {
            $pizzaCategory = $pizzaPalace->categories->where('name', 'Pizza')->first();
            $drinksCategory = $pizzaPalace->categories->where('name', 'Drinks')->first();
            $dessertsCategory = $pizzaPalace->categories->where('name', 'Desserts')->first();

            // Pizza Items
            if ($pizzaCategory) {
                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $pizzaCategory->id,
                    'name' => 'بيتزا مارجريتا',
                    'description' => 'بيتزا إيطالية تقليدية مع صلصة الطماطم والموزاريلا والريحان',
                    'price' => 48.75,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 20,
                    'ingredients' => 'عجينة البيتزا، صلصة الطماطم، جبنة الموزاريلا، الريحان الطازج، زيت الزيتون',
                    'allergens' => 'يحتوي على الجلوتين ومنتجات الألبان',
                ]);

                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $pizzaCategory->id,
                    'name' => 'بيتزا بيبروني',
                    'description' => 'بيتزا مع بيبروني وجبنة الموزاريلا',
                    'price' => 59.95,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 25,
                    'ingredients' => 'عجينة البيتزا، صلصة الطماطم، جبنة الموزاريلا، بيبروني، فلفل أسود',
                    'allergens' => 'يحتوي على الجلوتين ومنتجات الألبان واللحوم',
                ]);

                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $pizzaCategory->id,
                    'name' => 'بيتزا الفطر',
                    'description' => 'بيتزا نباتية مع الفطر الطازج والجبنة',
                    'price' => 52.45,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 22,
                    'ingredients' => 'عجينة البيتزا، صلصة الطماطم، جبنة الموزاريلا، فطر طازج، بصل، زيتون',
                    'allergens' => 'يحتوي على الجلوتين ومنتجات الألبان',
                ]);
            }

            // Drinks
            if ($drinksCategory) {
                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $drinksCategory->id,
                    'name' => 'كوكا كولا',
                    'description' => 'مشروب غازي منعش',
                    'price' => 9.38,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 2,
                    'ingredients' => 'كوكا كولا، ثلج',
                    'allergens' => 'لا يحتوي على مسببات حساسية معروفة',
                ]);

                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $drinksCategory->id,
                    'name' => 'عصير برتقال طازج',
                    'description' => 'عصير برتقال طبيعي 100%',
                    'price' => 14.95,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 5,
                    'ingredients' => 'برتقال طازج، سكر طبيعي',
                    'allergens' => 'لا يحتوي على مسببات حساسية معروفة',
                ]);
            }

            // Desserts
            if ($dessertsCategory) {
                MenuItem::create([
                    'restaurant_id' => $pizzaPalace->id,
                    'category_id' => $dessertsCategory->id,
                    'name' => 'تيراميسو',
                    'description' => 'حلوى إيطالية تقليدية مع القهوة والكريمة',
                    'price' => 26.20,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 10,
                    'ingredients' => 'بسكويت ليدي فينجر، قهوة إيطالية، كريمة الماسكاربوني، كاكاو',
                    'allergens' => 'يحتوي على البيض ومنتجات الألبان والجلوتين',
                ]);
            }
        }

        // Burger House Menu Items
        $burgerHouse = $restaurants->where('name', 'Burger House')->first();
        if ($burgerHouse) {
            $burgersCategory = $burgerHouse->categories->where('name', 'Burgers')->first();
            $sidesCategory = $burgerHouse->categories->where('name', 'Sides')->first();
            $drinksCategory = $burgerHouse->categories->where('name', 'Drinks')->first();

            // Burgers
            if ($burgersCategory) {
                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $burgersCategory->id,
                    'name' => 'برجر كلاسيك',
                    'description' => 'برجر لحم بقري مع خس وطماطم وبصل',
                    'price' => 33.70,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 15,
                    'ingredients' => 'لحم بقري، خبز البرجر، خس، طماطم، بصل، صلصة خاصة',
                    'allergens' => 'يحتوي على الجلوتين واللحوم',
                ]);

                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $burgersCategory->id,
                    'name' => 'برجر الجبنة',
                    'description' => 'برجر مع جبنة شيدر ذائبة',
                    'price' => 41.20,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 18,
                    'ingredients' => 'لحم بقري، جبنة شيدر، خبز البرجر، خس، طماطم، بصل',
                    'allergens' => 'يحتوي على الجلوتين واللحوم ومنتجات الألبان',
                ]);

                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $burgersCategory->id,
                    'name' => 'برجر الدجاج',
                    'description' => 'برجر دجاج مشوي مع صلصة خاصة',
                    'price' => 37.45,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 20,
                    'ingredients' => 'صدر دجاج مشوي، خبز البرجر، خس، طماطم، صلصة خاصة',
                    'allergens' => 'يحتوي على الجلوتين والدجاج',
                ]);
            }

            // Sides
            if ($sidesCategory) {
                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $sidesCategory->id,
                    'name' => 'بطاطس مقلية',
                    'description' => 'بطاطس مقلية مقرمشة مع ملح',
                    'price' => 14.95,
                    'is_available' => true,
                    'is_featured' => false,
                    'preparation_time' => 8,
                    'ingredients' => 'بطاطس، زيت نباتي، ملح',
                    'allergens' => 'لا يحتوي على مسببات حساسية معروفة',
                ]);

                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $sidesCategory->id,
                    'name' => 'حلقات البصل',
                    'description' => 'حلقات بصل مقلية مقرمشة',
                    'price' => 18.70,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 10,
                    'ingredients' => 'بصل، دقيق، بيض، فتات الخبز، زيت نباتي',
                    'allergens' => 'يحتوي على الجلوتين والبيض',
                ]);
            }

            // Drinks
            if ($drinksCategory) {
                MenuItem::create([
                    'restaurant_id' => $burgerHouse->id,
                    'category_id' => $drinksCategory->id,
                    'name' => 'ميلك شيك الشوكولاتة',
                    'description' => 'ميلك شيك غني بالشوكولاتة',
                    'price' => 18.70,
                    'is_available' => true,
                    'is_featured' => true,
                    'preparation_time' => 5,
                    'ingredients' => 'حليب، آيس كريم، شوكولاتة، كريمة مخفوقة',
                    'allergens' => 'يحتوي على منتجات الألبان',
                ]);
            }
        }
    }
}
