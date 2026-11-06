<template>
  <article class="card">
    <h3>{{ post.title }}</h3>
    <p class="meta">投稿者：{{ post.user.name }} ／ {{ post.created_at }}</p>
    <p class="content">{{ post.content }}</p>
  </article>
</template>

<script setup>
// 投稿一件分の表示の担当：受け取った1件をどう見せるかだけを決める
defineProps({
  post: { type: Object, required: true },
})
</script>

<style scoped>
.card {
  border: 1px solid #444;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}
.meta {
  color: #888;
  font-size: 0.9em;
}
/* 本文の改行をそのまま表示する */
.content {
  white-space: pre-wrap;
}
</style>
