<template>
  <main class="container">
    <!-- フォーム：投稿を作成したら created を通知 → 一覧を取得し直す -->
    <PostForm @created="fetchPosts" />

    <!-- 一覧表示：表示する投稿・状態を props で渡すだけ -->
    <PostList :posts="posts" :loading="loading" :error="error" />
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from './api'
import PostForm from './components/PostForm.vue'
import PostList from './components/PostList.vue'

// 投稿一覧のデータ（state）は親が持つ
// フォームと一覧は兄弟なので、共通の親がデータを持つと「作成 → 一覧を更新」をつなげられる
const posts = ref([])
const loading = ref(false)
const error = ref(null)

// 一覧を取得する（最初の表示時と、投稿の作成後に呼ぶ）
async function fetchPosts() {
  loading.value = true
  try {
    const response = await api.get('/posts')
    posts.value = response.data.data
    error.value = null
  } catch (e) {
    error.value = '投稿を取得できませんでした'
  } finally {
    loading.value = false
  }
}

onMounted(fetchPosts)
</script>

<style scoped>
.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 16px;
}
</style>
