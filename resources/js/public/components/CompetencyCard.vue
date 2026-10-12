<template>
  <div class="card" :class="{ 'card-primary': highlight }">
    <div class="icon-box" :class="{ 'icon-light': highlight }" aria-hidden="true">
      <component :is="icon" v-if="icon" :size="20" />
    </div>
    <h3>{{ title }}</h3>
    <p>{{ description }}</p>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
  title: string;
  description?: string;
  icon?: Component;
  highlight?: boolean;
}>();
</script>

<style scoped>
.card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--r-md);
  padding: 24px;
  box-shadow: var(--shadow-sm);
}
.card-primary {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary-d);
}
.card-primary h3, .card-primary p { color: #fff; }
.icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: var(--bg);
  border-radius: 11px;
  color: var(--primary);
  margin-bottom: 16px;
}
.icon-light { background: rgba(255, 255, 255, 0.15); color: #fff; }
h3 { margin-bottom: 8px; }
p { color: var(--muted); font-size: 0.95rem; }
.card-primary p { color: rgba(255, 255, 255, 0.85); }
</style>
