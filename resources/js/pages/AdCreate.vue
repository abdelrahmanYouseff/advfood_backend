<template>
  <AppLayout>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">Add New Ad</h2>
            <form @submit.prevent="submit" enctype="multipart/form-data">
              <!-- Image Upload -->
              <div class="mb-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                  Ad Image *
                </label>
                <input
                  id="image"
                  type="file"
                  accept="image/*"
                  @change="handleImageChange"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="{ 'border-red-500': errors.image }"
                  required
                />
                <div v-if="errors.image" class="mt-1 text-sm text-red-600">
                  {{ errors.image }}
                </div>
                <div v-if="imagePreview" class="mt-4">
                  <img :src="imagePreview" alt="Preview" class="h-64 w-full object-cover rounded-lg border" />
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="flex justify-end space-x-4 space-x-reverse">
                <Link
                  :href="route('ads.index')"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="processing"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                  {{ processing ? 'Saving...' : 'Save Ad' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'

const props = defineProps({
  errors: Object
})

const imagePreview = ref(null)

const form = useForm({
  image: null
})

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submit = () => {
  form.post(route('ads.store'))
}
</script>
