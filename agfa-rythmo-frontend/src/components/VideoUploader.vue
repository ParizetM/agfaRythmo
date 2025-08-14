<template>
	<div class="video-uploader">
		<form @submit.prevent="uploadVideo">
			<label for="video">Choisir une vidéo :</label>
			<input
				id="video"
				type="file"
				accept="video/mp4,video/quicktime"
				@change="onFileChange"
				required
			/>
			<button type="submit" :disabled="loading || !selectedFile">Uploader</button>
		</form>

		<div v-if="previewUrl" class="preview">
			<h3>Preview :</h3>
			<video :src="previewUrl" controls width="400"></video>
		</div>
		<div v-if="error" class="error">{{ error }}</div>
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

<style scoped>
.video-uploader {
	max-width: 500px;
	margin: 2rem auto;
	padding: 2rem;
	border: 1px solid #ccc;
	border-radius: 8px;
	background: #fafafa;
}
.preview {
	margin-top: 1.5rem;
}
.error {
	color: red;
	margin-top: 1rem;
}
</style>
