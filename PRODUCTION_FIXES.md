# Production Fixes - AdvFood

## مشكلة رفع الصور في الإنتاج

### المشكلة
في بيئة الإنتاج، عند محاولة رفع الصور في صفحات إنشاء/تعديل المطاعم والمنتجات، تظهر الصفحة بيضاء مع الخطأ:
```
TypeError: Cannot read properties of undefined (reading 'createObjectURL')
```

### السبب
في بيئة الإنتاج، `URL.createObjectURL` غير متاح مباشرة ويحتاج إلى الوصول عبر `window.URL.createObjectURL`.

### الحل
تم إصلاح المشكلة باستخدام computed properties للتعامل مع معاينة الصور بطريقة آمنة:

1. **RestaurantCreate.vue**:
   ```vue
   <!-- إضافة computed property -->
   const logoPreviewUrl = computed(() => {
       if (form.logo && typeof window !== 'undefined' && window.URL) {
           try {
               return window.URL.createObjectURL(form.logo);
           } catch (error) {
               console.error('Error creating object URL:', error);
               return null;
           }
       }
       return null;
   });
   
   <!-- استخدام في template -->
   <div v-if="form.logo && form.logo !== null && logoPreviewUrl" class="relative">
       <img :src="logoPreviewUrl" alt="Logo Preview" />
   </div>
   ```

2. **MenuItemCreate.vue**:
   ```vue
   <!-- إضافة computed property -->
   const imagePreviewUrl = computed(() => {
       if (form.image && typeof window !== 'undefined' && window.URL) {
           try {
               return window.URL.createObjectURL(form.image);
           } catch (error) {
               console.error('Error creating object URL:', error);
               return null;
           }
       }
       return null;
   });
   
   <!-- استخدام في template -->
   <div v-if="form.image && imagePreviewUrl" class="relative">
       <img :src="imagePreviewUrl" alt="Preview" />
   </div>
   ```

3. **RestaurantEdit.vue** (كان صحيحاً بالفعل):
   ```vue
   :src="form.logo ? (window as any).URL.createObjectURL(form.logo) : `/storage/${restaurant.logo}`"
   ```

### ملاحظات مهمة
- استخدم computed properties للتعامل مع معاينة الصور بطريقة آمنة
- تحقق من وجود `window` و `window.URL` قبل استخدام `createObjectURL`
- استخدم try-catch للتعامل مع الأخطاء المحتملة
- هذا ضروري في بيئة الإنتاج حيث TypeScript أكثر صرامة
- في بيئة التطوير المحلية قد يعمل كلا الطريقتين

### اختبار الإصلاح
1. اذهب إلى: https://advfoodapp.clarastars.com/restaurants/create
2. حاول رفع صورة
3. يجب أن تعمل معاينة الصورة بدون أخطاء

### الملفات المحدثة
- `resources/js/pages/RestaurantCreate.vue`
- `resources/js/pages/MenuItemCreate.vue`
- `resources/js/pages/RestaurantEdit.vue` (كان صحيحاً)

### تاريخ الإصلاح
- تم الإصلاح في: 7 أغسطس 2025
- تم اختباره على: https://advfoodapp.clarastars.com/ 
