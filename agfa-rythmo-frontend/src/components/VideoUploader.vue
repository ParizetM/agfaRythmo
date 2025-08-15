<template>
  <div class="max-w-2xl mx-auto my-8 p-8 bg-white rounded-2xl shadow-xl">
    <form @submit.prevent="uploadVideo" class="space-y-6">
      <div>
        <label for="video" class="block text-lg font-semibold text-agfa-dark mb-3">
          Choisir une vidéo :
        </label>
        <input
          id="video"
          type="file"
          accept="video/mp4,video/quicktime"
          @change="onFileChange"
          required
          class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-agfa-blue file:text-white hover:file:bg-agfa-blue-hover"
        />
      </div>

      <button
        type="submit"
        :disabled="loading || !selectedFile"
        class="w-full py-3 px-6 bg-agfa-blue hover:bg-agfa-blue-hover disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-105 disabled:transform-none"
      >
        {{ loading ? 'Upload en cours...' : 'Uploader' }}
      </button>
    </form>

    <div v-if="previewUrl" class="mt-8 animate-fade-in">
      <h3 class="text-xl font-bold text-agfa-dark mb-4">Aperçu :</h3>
      <div class="bg-gray-800 rounded-xl overflow-hidden">
        <video
          :src="previewUrl"
          controls
          class="w-full max-w-md mx-auto block rounded-xl"
        ></video>
      </div>
    </div>

    <div v-if="error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg animate-fade-in">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const loading = ref(false)
const error = ref('')

function onFileChange(e: Event) {
	const files = (e.target as HTMLInputElement).files
	if (files && files[0]) {
		selectedFile.value = files[0]
		previewUrl.value = URL.createObjectURL(files[0])
		error.value = ''
	} else {
		selectedFile.value = null
		previewUrl.value = null
	}
}

async function uploadVideo() {
	if (!selectedFile.value) return
	loading.value = true
	error.value = ''
	try {
		const formData = new FormData()
		formData.append('video', selectedFile.value)
		const res = await fetch('http://agfa-rythmo-backend.test/api/videos/upload', {
			method: 'POST',
			body: formData,
		})
		if (!res.ok) throw new Error('Erreur lors de l\'upload')
		const data = await res.json()
		previewUrl.value = data.url.startsWith('http') ? data.url : `http://agfa-rythmo-backend.test${data.url}`
		} catch (e) {
			if (e instanceof Error) {
				error.value = e.message
			} else {
				error.value = 'Erreur inconnue'
			}
		} finally {
		loading.value = false
	}
}
</script>
