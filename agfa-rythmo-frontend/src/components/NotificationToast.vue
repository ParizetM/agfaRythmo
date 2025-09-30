<template>
  <div class="fixed top-4 right-4 z-[9999] space-y-2">
    <div
      v-for="notification in notifications"
      :key="notification.id"
      :class="[
        'min-w-80 max-w-lg w-full bg-white dark:bg-gray-800 shadow-2xl rounded-lg pointer-events-auto flex ring-2 ring-white dark:ring-gray-600 transform transition-all duration-300 ease-in-out',
        getNotificationStyles(notification.type)
      ]"
    >
      <div class="flex-1 p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <CheckCircleIcon v-if="notification.type === 'success'" class="h-6 w-6 text-green-400" />
            <XCircleIcon v-else-if="notification.type === 'error'" class="h-6 w-6 text-red-400" />
            <ExclamationTriangleIcon v-else-if="notification.type === 'warning'" class="h-6 w-6 text-yellow-400" />
            <InformationCircleIcon v-else class="h-6 w-6 text-blue-400" />
          </div>
          <div class="ml-3 flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ notification.title }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300 break-words">
              {{ notification.message }}
            </p>
          </div>
        </div>
      </div>
      <div class="flex border-l border-gray-200 dark:border-gray-600">
        <button
          @click="removeNotification(notification.id)"
          class="w-full border border-transparent rounded-none rounded-r-lg p-4 flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import {
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';
import { notificationService, type Notification } from '../services/notifications';

const notifications = ref<Notification[]>([]);
let unsubscribe: (() => void) | null = null;

onMounted(() => {
  unsubscribe = notificationService.subscribe((newNotifications) => {
    notifications.value = newNotifications;
  });
});

onUnmounted(() => {
  if (unsubscribe) {
    unsubscribe();
  }
});

function removeNotification(id: string) {
  notificationService.remove(id);
}

function getNotificationStyles(type: string): string {
  switch (type) {
    case 'success':
      return 'border-l-4 border-green-400';
    case 'error':
      return 'border-l-4 border-red-400';
    case 'warning':
      return 'border-l-4 border-yellow-400';
    case 'info':
    default:
      return 'border-l-4 border-blue-400';
  }
}
</script>
