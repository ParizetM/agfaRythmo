<template>
  <BaseModal
    :show="show"
    :title="title"
    :hide-close="false"
    :close-on-backdrop="true"
    size="md"
    @update:show="$emit('update:show', $event)"
  >
    <template v-slot:icon v-if="type === 'danger'">
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
      </svg>
    </template>

    <template v-slot:icon v-else-if="type === 'warning'">
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
      </svg>
    </template>

    <template v-slot:icon v-else-if="type === 'info'">
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </template>

    <template v-slot:default>
      <div class="text-center py-4">
        <p class="text-gray-300 text-base leading-relaxed">{{ message }}</p>
        <p v-if="details" class="text-gray-400 text-sm mt-2">{{ details }}</p>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          @click="handleCancel"
          class="px-5 py-2.5 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors font-medium"
        >
          {{ cancelText }}
        </button>
        <button
          @click="handleConfirm"
          :class="[
            'px-5 py-2.5 text-white rounded-lg transition-colors font-medium shadow-lg hover:shadow-xl',
            type === 'danger' ? 'bg-red-600 hover:bg-red-700' :
            type === 'warning' ? 'bg-yellow-600 hover:bg-yellow-700' :
            'bg-purple-600 hover:bg-purple-700'
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

interface Props {
  show: boolean
  title: string
  message: string
  details?: string
  confirmText?: string
  cancelText?: string
  type?: 'danger' | 'warning' | 'info'
}

withDefaults(defineProps<Props>(), {
  confirmText: 'Confirmer',
  cancelText: 'Annuler',
  type: 'warning'
})

const emit = defineEmits<{
  'update:show': [value: boolean]
  confirm: []
  cancel: []
}>()

const handleConfirm = () => {
  emit('confirm')
  emit('update:show', false)
}

const handleCancel = () => {
  emit('cancel')
  emit('update:show', false)
}
</script>
