<template>
  <form class="post-form" @submit.prevent="createPost">
    <h2>新規投稿</h2>

    <div class="form-group">
      <label for="title">タイトル</label>
      <input id="title" v-model="form.title" maxlength="200" />
      <p v-if="errors.title" class="error">{{ errors.title[0] }}</p>
    </div>

    <div class="form-group">
      <label for="content">本文</label>
      <textarea id="content" v-model="form.content" rows="5"></textarea>
      <p v-if="errors.content" class="error">{{ errors.content[0] }}</p>
    </div>

    <p v-if="message" class="error">{{ message }}</p>

    <!-- 送信中はボタンを押せないようにする（二重投稿の防止） -->
    <button type="submit" :disabled="submitting">
      {{ submitting ? '送信中...' : '投稿する' }}
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'

const emit = defineEmits(['created'])

// フォームの入力値（state）。v-model で入力欄と結びついている
const form = ref({ title: '', content: '' })
// Laravel から返ってきたバリデーションエラー（{ title: ['タイトルを入力してください'] } の形）
const errors = ref({})
// バリデーション以外のエラーメッセージ
const message = ref('')
// 送信中かどうか
const submitting = ref(false)

async function createPost() {
  submitting.value = true
  errors.value = {}
  message.value = ''

  try {
    const response = await api.post('/posts', form.value)
    emit('created', response.data.data) // 親コンポーネントに「作成した」と通知（親が一覧を再取得する）
    form.value = { title: '', content: '' } // フォームをリセット
  } catch (e) {
    if (e.response?.status === 422) {
      // バリデーションエラー：項目ごとのメッセージを表示する
      errors.value = e.response.data.errors
    } else {
      message.value = '投稿に失敗しました。時間をおいて再度お試しください。'
      console.error('登録失敗:', e.response?.data ?? e)
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.post-form {
  border: 1px solid #444;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}
.form-group {
  margin-bottom: 12px;
}
.form-group label {
  display: block;
  font-weight: bold;
  margin-bottom: 4px;
}
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}
.error {
  color: #dc2626;
  margin: 4px 0 0;
}
</style>
