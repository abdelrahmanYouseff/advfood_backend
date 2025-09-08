<template>
  <AppLayout>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-6">
              <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ads</h2>
              <Link
                :href="route('ads.create')"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Add New Ad
              </Link>
            </div>

            <!-- Success Message -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
              {{ $page.props.flash.success }}
            </div>

            <!-- Ads Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Image
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Position
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Views
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Clicks
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="ad in ads" :key="ad.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <img
                        v-if="ad.image"
                        :src="`/storage/${ad.image}`"
                        :alt="ad.title"
                        class="h-12 w-12 rounded-lg object-cover"
                      />
                      <div v-else class="h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-400 text-xs">No Image</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ ad.title }}</div>
                      <div v-if="ad.description" class="text-sm text-gray-500 truncate max-w-xs">
                        {{ ad.description }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getTypeBadgeClass(ad.type)">
                        {{ getTypeLabel(ad.type) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ getPositionLabel(ad.position) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="ad.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ ad.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ ad.views_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ ad.clicks_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2 space-x-reverse">
                        <Link
                          :href="route('ads.show', ad.id)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          View
                        </Link>
                        <Link
                          :href="route('ads.edit', ad.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Edit
                        </Link>
                        <button
                          @click="toggleStatus(ad)"
                          class="text-yellow-600 hover:text-yellow-900"
                        >
                          {{ ad.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                          @click="deleteAd(ad)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-if="ads.length === 0" class="text-center py-12">
              <div class="text-gray-500 text-lg">No ads found</div>
              <Link
                :href="route('ads.create')"
                class="mt-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Add First Ad
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  ads: Array
})

const getTypeLabel = (type) => {
  const types = {
    banner: 'Banner',
    popup: 'Popup',
    sidebar: 'Sidebar'
  }
  return types[type] || type
}

const getTypeBadgeClass = (type) => {
  const classes = {
    banner: 'bg-blue-100 text-blue-800',
    popup: 'bg-purple-100 text-purple-800',
    sidebar: 'bg-green-100 text-green-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getPositionLabel = (position) => {
  const positions = {
    top: 'Top',
    bottom: 'Bottom',
    left: 'Left',
    right: 'Right',
    center: 'Center'
  }
  return positions[position] || position
}

const toggleStatus = (ad) => {
  router.post(route('ads.toggle-status', ad.id), {}, {
    preserveState: true,
    preserveScroll: true
  })
}

const deleteAd = (ad) => {
  if (confirm('Are you sure you want to delete this ad?')) {
    router.delete(route('ads.destroy', ad.id))
  }
}
</script>
