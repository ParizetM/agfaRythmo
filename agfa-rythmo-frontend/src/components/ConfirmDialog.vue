<template>
  <BaseModal
    :show="show"
    :title="title"
    size="md"
    @update:show="$emit('update:show', $event)"
  >
    <template v-slot:icon>
      <svg
        class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-500"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <p class="text-gray-300 text-base leading-relaxed">{{ message }}</p>
    </template>

    <template v-slot:footer>
      <div class="flex justify-end gap-3 w-full">
        <button
          @click="$emit('cancel')"
          class="px-5 py-2.5 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200"
        >
          {{ cancelText }}
        </button>
        <button
          @click="$emit('confirm')"
          :class="[
            'px-5 py-2.5 font-medium rounded-lg transition-colors duration-200',
            danger
              ? 'bg-red-600 hover:bg-red-700 text-white'
              : 'bg-agfa-blue hover:bg-blue-600 text-white'
          ]"
        >
          {{ confirmText }}
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from './BaseModal.vue'

defineProps<{
  show: boolean
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  danger?: boolean
}>()

defineEmits<{
  'update:show': [value: boolean]
  confirm: []
  cancel: []
}>()
</script>
