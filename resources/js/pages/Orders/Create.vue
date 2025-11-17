<template>
    <Head title="إنشاء طلب" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 space-y-8">
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-semibold text-gray-900">إنشاء طلب جديد</h1>
                <p class="text-muted-foreground">
                    اختر المستخدم والمطعم، أضف الأصناف المطلوبة، ثم راجع تفاصيل التكلفة قبل حفظ الطلب.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Section: General Information -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-800">المعلومات العامة</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            اختر المستخدم والمطعم، وسيتم ضبط حالة الطلب والدفع تلقائياً.
                        </p>
                    </div>

                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                        <div>
                            <Label for="user_id">المستخدم</Label>
                            <select
                                id="user_id"
                                v-model="form.user_id"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">اختر المستخدم</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <InputError :message="form.errors.user_id" class="mt-2" />
                        </div>

                        <div>
                            <Label for="restaurant_id">المطعم</Label>
                            <select
                                id="restaurant_id"
                                v-model="form.restaurant_id"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">اختر المطعم</option>
                                <option v-for="restaurant in restaurants" :key="restaurant.id" :value="restaurant.id">
                                    {{ restaurant.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.restaurant_id" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 px-6 pb-6 md:grid-cols-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs text-muted-foreground">حالة الطلب</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ statusLabel }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground">القيمة ثابتة ولا يمكن تعديلها</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs text-muted-foreground">حالة الدفع</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ paymentStatusLabel }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground">القيمة ثابتة ولا يمكن تعديلها</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs text-muted-foreground">طريقة الدفع</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ paymentMethodLabel }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground">القيمة ثابتة ولا يمكن تعديلها</p>
                        </div>
                    </div>
                </section>

                <!-- Section: Customer Information -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-800">بيانات العميل</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            أدخل بيانات التواصل وعنوان التوصيل والملاحظات الخاصة.
                        </p>
                    </div>

                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                        <div>
                            <Label for="delivery_name">اسم العميل</Label>
                            <Input id="delivery_name" v-model="form.delivery_name" type="text" placeholder="اسم العميل" />
                            <InputError :message="form.errors.delivery_name" class="mt-2" />
                        </div>

                        <div>
                            <Label for="delivery_phone">رقم الهاتف</Label>
                            <Input id="delivery_phone" v-model="form.delivery_phone" type="text" placeholder="05XXXXXXXX" />
                            <InputError :message="form.errors.delivery_phone" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <Label for="delivery_address">عنوان التوصيل</Label>
                            <textarea
                                id="delivery_address"
                                v-model="form.delivery_address"
                                rows="3"
                                placeholder="اكتب العنوان بالتفصيل"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <InputError :message="form.errors.delivery_address" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <Label for="special_instructions">ملاحظات خاصة</Label>
                            <textarea
                                id="special_instructions"
                                v-model="form.special_instructions"
                                rows="3"
                                placeholder="أي تعليمات إضافية"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <InputError :message="form.errors.special_instructions" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 md:col-span-2">
                            <Checkbox id="sound" v-model="form.sound" />
                            <Label for="sound" class="mb-0 text-sm font-medium text-gray-800">
                                تفعيل صوت الإشعارات لهذا الطلب
                            </Label>
                        </div>
                    </div>
                </section>

                <!-- Section: Items and Summary -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4 flex flex-col gap-1">
                        <h2 class="text-lg font-semibold text-gray-800">أصناف الطلب</h2>
                        <p class="text-sm text-muted-foreground">
                            اختر الأصناف التابعة للمطعم واضبط الكمية والسعر لكل صنف، وسيتم تحديث التكلفة تلقائياً.
                        </p>
                    </div>

                    <div class="px-6 py-6 space-y-6">
                        <div class="grid gap-6 lg:grid-cols-[2fr,1.1fr]">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex flex-1 items-center gap-2">
                                        <Label for="menu-search" class="mb-0 text-sm font-medium text-gray-700">بحث في الأصناف</Label>
                                        <Input
                                            id="menu-search"
                                            v-model="menuSearch"
                                            type="text"
                                            placeholder="ابحث باسم الصنف..."
                                            class="flex-1"
                                            :disabled="menuItemsLoading || !form.restaurant_id"
                                        />
                                    </div>
                                    <span v-if="form.restaurant_id && !menuItemsLoading" class="text-xs text-muted-foreground whitespace-nowrap">
                                        {{ filteredMenuItems.length }} صنف متاح
                                    </span>
                                </div>

                                <div class="rounded-lg border border-gray-200 min-h-[200px] bg-gray-50/60">
                                    <div
                                        v-if="!form.restaurant_id"
                                        class="flex h-full items-center justify-center p-6 text-sm text-muted-foreground"
                                    >
                                        اختر المطعم لعرض قائمة الأصناف المتاحة.
                                    </div>
                                    <div
                                        v-else-if="menuItemsLoading"
                                        class="flex h-full items-center justify-center p-6 text-sm text-muted-foreground"
                                    >
                                        جاري تحميل الأصناف...
                                    </div>
                                    <div v-else-if="menuItemsError" class="p-6 text-sm text-red-600">
                                        {{ menuItemsError }}
                                    </div>
                                    <div v-else class="max-h-[340px] overflow-y-auto divide-y divide-gray-200 bg-white">
                                        <div
                                            v-if="filteredMenuItems.length === 0"
                                            class="p-6 text-sm text-muted-foreground"
                                        >
                                            لا توجد أصناف مطابقة لبحثك في هذا المطعم.
                                        </div>
                                        <div
                                            v-else
                                            v-for="item in filteredMenuItems"
                                            :key="item.id"
                                            class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50"
                                        >
                                            <div>
                                                <div class="font-medium text-gray-800">{{ item.name }}</div>
                                                <div class="text-xs text-muted-foreground mt-1">
                                                    السعر: {{ formatCurrency(item.price) }}
                                                </div>
                                                <p v-if="item.description" class="text-xs text-muted-foreground mt-2 line-clamp-2">
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                            <Button type="button" size="sm" @click="addMenuItem(item)">
                                                إضافة للطلب
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <aside class="space-y-4">
                                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                                    <div class="border-b border-gray-100 px-5 py-4">
                                        <h3 class="text-sm font-semibold text-gray-800">ملخص التكلفة</h3>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            يتم تحديث الأرقام تلقائياً عند تعديل الكميات أو الأسعار.
                                        </p>
                                    </div>
                                    <div class="px-5 py-4 space-y-3 text-sm text-gray-700">
                                        <div class="flex items-center justify-between">
                                            <span>عدد الأصناف</span>
                                            <span>{{ selectedItems.length }} صنف</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>المجموع الفرعي</span>
                                            <span>{{ formatCurrency(form.subtotal) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>رسوم التوصيل</span>
                                            <Input v-model.number="form.delivery_fee" type="number" min="0" step="0.01" class="w-28 text-left" />
                                        </div>
                                        <InputError :message="form.errors.delivery_fee" class="text-xs text-red-600" />
                                        <div class="flex items-center justify-between">
                                            <span>الضريبة</span>
                                            <Input v-model.number="form.tax" type="number" min="0" step="0.01" class="w-28 text-left" />
                                        </div>
                                        <InputError :message="form.errors.tax" class="text-xs text-red-600" />
                                        <div class="flex items-center justify-between pt-3 border-t border-dashed">
                                            <span class="font-semibold text-gray-900">الإجمالي</span>
                                            <span class="font-semibold text-gray-900 text-base">{{ formatCurrency(form.total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-900">
                                    <p class="font-semibold mb-1">إرشادات:</p>
                                    <ul class="space-y-1">
                                        <li>• يمكن تعديل الكمية والسعر بعد إضافة الصنف.</li>
                                        <li>• يتم تحديث التكلفة النهائية تلقائياً.</li>
                                        <li>• تأكد من إدخال رسوم التوصيل والضرائب إن وجدت.</li>
                                    </ul>
                                </div>
                            </aside>
                        </div>

                        <div class="rounded-lg border border-gray-200">
                            <div v-if="selectedItems.length === 0" class="p-6 text-sm text-muted-foreground">
                                لم يتم اختيار أصناف بعد. استخدم قائمة الأصناف لإضافة عناصر إلى الطلب.
                            </div>
                            <div v-else class="overflow-x-auto">
                                <table class="w-full min-w-[720px] text-right text-sm">
                                    <thead class="bg-gray-50">
                                        <tr class="text-gray-600">
                                            <th class="px-4 py-2 font-medium">الصنف</th>
                                            <th class="px-4 py-2 font-medium w-28">الكمية</th>
                                            <th class="px-4 py-2 font-medium w-32">السعر</th>
                                            <th class="px-4 py-2 font-medium w-32">الإجمالي</th>
                                            <th class="px-4 py-2 font-medium w-20"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in selectedItems" :key="`${item.menu_item_id}-${index}`" class="border-t">
                                            <td class="px-4 py-3 text-gray-800 font-medium">{{ item.item_name }}</td>
                                            <td class="px-4 py-3">
                                                <Input v-model.number="item.quantity" type="number" min="1" class="w-full" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <Input v-model.number="item.price" type="number" min="0" step="0.01" class="w-full" />
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 font-medium">{{ formatCurrency(item.subtotal) }}</td>
                                            <td class="px-4 py-3 text-left">
                                                <button
                                                    type="button"
                                                    @click="removeSelectedItem(index)"
                                                    class="rounded-lg border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                                >
                                                    حذف
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p v-if="itemsError" class="text-sm text-red-600">
                            {{ itemsError }}
                        </p>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('orders.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    >
                        إلغاء
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ الطلب' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { computed, ref, watch } from 'vue';

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface RestaurantOption {
    id: number;
    name: string;
    shop_id?: string | null;
}

interface MenuItemOption {
    id: number;
    name: string;
    price: number;
    description?: string | null;
}

interface SelectedItemPayload {
    menu_item_id: number;
    item_name: string;
    quantity: number;
    price: number;
}

interface SelectedItemRow extends SelectedItemPayload {
    subtotal: number;
}

interface Props {
    users: UserOption[];
    restaurants: RestaurantOption[];
    statusOptions: Record<string, string>;
    paymentStatusOptions: Record<string, string>;
    paymentMethodOptions: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'الطلبات', href: '/orders' },
    { title: 'إنشاء طلب', href: '/orders/create' },
];

const form = useForm({
    user_id: '',
    restaurant_id: '',
    delivery_name: '',
    delivery_phone: '',
    delivery_address: '',
    status: 'pending',
    payment_status: 'paid',
    payment_method: 'online',
    subtotal: 0,
    delivery_fee: 0,
    tax: 0,
    total: 0,
    special_instructions: '',
    sound: false,
    items: [] as SelectedItemPayload[],
});

const menuItems = ref<MenuItemOption[]>([]);
const menuItemsLoading = ref(false);
const menuItemsError = ref<string | null>(null);
const selectedItems = ref<SelectedItemRow[]>([]);
const itemsError = ref<string | null>(null);
const menuSearch = ref('');

const filteredMenuItems = computed(() => {
    if (!menuSearch.value.trim()) {
        return menuItems.value;
    }
    const term = menuSearch.value.trim().toLowerCase();
    return menuItems.value.filter((item) =>
        item.name.toLowerCase().includes(term) ||
        (item.description ?? '').toLowerCase().includes(term)
    );
});

const mapLabel = (map: Record<string, string> | undefined, key: string, fallback: string) =>
    map?.[key] ?? fallback;
const statusLabel = computed(() => mapLabel(props.statusOptions, form.status, 'قيد الانتظار'));
const paymentStatusLabel = computed(() => mapLabel(props.paymentStatusOptions, form.payment_status, 'مدفوع'));
const paymentMethodLabel = computed(() => mapLabel(props.paymentMethodOptions, form.payment_method, 'دفع إلكتروني'));

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(value) ? value : 0);
};

const recalculateTotals = () => {
    let subtotal = 0;

    selectedItems.value.forEach((item) => {
        const quantity = Number(item.quantity) || 0;
        const price = Number(item.price) || 0;
        item.quantity = quantity;
        item.price = price;
        item.subtotal = Number((quantity * price).toFixed(2));
        subtotal += item.subtotal;
    });

    form.items = selectedItems.value.map((item) => ({
        menu_item_id: item.menu_item_id,
        item_name: item.item_name,
        quantity: item.quantity,
        price: item.price,
    }));

    form.subtotal = Number(subtotal.toFixed(2));
    const deliveryFee = Number(form.delivery_fee ?? 0);
    const tax = Number(form.tax ?? 0);
    form.total = Number((form.subtotal + deliveryFee + tax).toFixed(2));
};

const fetchMenuItems = async (restaurantId: number) => {
    menuItemsLoading.value = true;
    menuItemsError.value = null;
    menuItems.value = [];
    menuSearch.value = '';
    selectedItems.value = [];
    recalculateTotals();

    try {
        const response = await fetch(`/api/restaurants/${restaurantId}/menu-items?per_page=200&is_available=1`);
        if (!response.ok) {
            throw new Error('فشل تحميل الأصناف، حاول مرة أخرى.');
        }
        const data = await response.json();
        const itemsPayload = data?.data?.data ?? data?.data ?? [];
        menuItems.value = itemsPayload.map((item: any) => ({
            id: item.id,
            name: item.name,
            price: Number(item.price ?? 0),
            description: item.description ?? '',
        }));
    } catch (error: any) {
        menuItemsError.value = error?.message ?? 'حدث خطأ أثناء تحميل الأصناف.';
    } finally {
        menuItemsLoading.value = false;
    }
};

const addMenuItem = (item: MenuItemOption) => {
    const existing = selectedItems.value.find((selected) => selected.menu_item_id === item.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        selectedItems.value.push({
            menu_item_id: item.id,
            item_name: item.name,
            quantity: 1,
            price: Number(item.price ?? 0),
            subtotal: 0,
        });
    }
    itemsError.value = null;
    recalculateTotals();
};

const removeSelectedItem = (index: number) => {
    selectedItems.value.splice(index, 1);
    recalculateTotals();
};

watch(
    () => form.restaurant_id,
    (value) => {
        selectedItems.value = [];
        recalculateTotals();

        if (!value) {
            menuItems.value = [];
            return;
        }

        const restaurantId = Number(value);
        if (Number.isNaN(restaurantId) || restaurantId <= 0) {
            menuItems.value = [];
            return;
        }

        fetchMenuItems(restaurantId);
    }
);

watch(
    selectedItems,
    () => {
        recalculateTotals();
    },
    { deep: true }
);

watch(
    () => [form.delivery_fee, form.tax],
    () => {
        recalculateTotals();
    }
);

const submit = () => {
    if (!selectedItems.value.length) {
        itemsError.value = 'يرجى اختيار صنف واحد على الأقل قبل حفظ الطلب.';
        return;
    }

    itemsError.value = null;

    form
        .transform((data) => ({
            ...data,
            user_id: data.user_id ? Number(data.user_id) : null,
            restaurant_id: data.restaurant_id ? Number(data.restaurant_id) : null,
            delivery_fee: Number(data.delivery_fee ?? 0),
            tax: Number(data.tax ?? 0),
            subtotal: Number(data.subtotal ?? 0),
            total: Number(data.total ?? 0),
            status: 'pending',
            payment_status: 'paid',
            payment_method: 'online',
            items: data.items.map((item) => ({
                ...item,
                menu_item_id: Number(item.menu_item_id),
                quantity: Number(item.quantity),
                price: Number(item.price),
            })),
        }))
        .post(route('orders.store'), {
            onError: () => {
                if (form.errors.items) {
                    itemsError.value = Array.isArray(form.errors.items)
                        ? form.errors.items.join(' ')
                        : form.errors.items;
                }
            },
        });
};
</script>
