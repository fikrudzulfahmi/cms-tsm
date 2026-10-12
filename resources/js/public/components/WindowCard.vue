<template>
  <div class="window">
    <div class="window-bar">
      <span class="dot" aria-hidden="true"></span>
      <span class="dot" aria-hidden="true"></span>
      <span class="dot" aria-hidden="true"></span>
      <span class="name">{{ name }}</span>
    </div>
    <div class="window-body">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ name?: string }>();
</script>

<style scoped>
.window {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
}
.window-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--soft);
  padding: 10px 16px;
  border-bottom: 1px solid var(--line);
}
.window-bar .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #B5CDE8;
}
.window-bar .name {
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--muted);
  margin-left: 8px;
}
.window-body { padding: 20px; }
</style>
