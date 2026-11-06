<template>
  <section>
    <h1>投稿一覧</h1>

    <p v-if="error" class="error">{{ error }}</p>
    <!-- 初回の読み込み中だけ表示（再取得中は今の一覧を出したままにする） -->
    <p v-else-if="loading && posts.length === 0">読み込み中...</p>
    <p v-else-if="posts.length === 0">投稿がありません</p>
    <PostCard v-for="post in posts" :key="post.id" :post="post" />
  </section>
</template>

<script setup>
import PostCard from './PostCard.vue'

// 一覧表示の担当：受け取った投稿を並べるだけ（APIの呼び出しはしない）
defineProps({
  posts: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
})
</script>

<style scoped>
.error {
  color: #dc2626;
}
</style>
