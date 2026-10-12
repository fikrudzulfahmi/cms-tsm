<template>
  <div class="strip">
    <div v-for="item in items" :key="item.label">
      <div class="num">{{ item.value }}</div>
      <div class="label">{{ item.label }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ items: Array<{ label: string; value: string | number }> }>();
</script>

<style scoped>
.strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: var(--surface);
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  border-radius: var(--r-md);
  overflow: hidden;
}
.strip > div {
  padding: 24px;
  border-right: 1px solid var(--line);
}
.strip > div:last-child { border-right: none; }
.num {
  font-size: 2rem;
  font-weight: 800;
  color: var(--primary);
  font-family: var(--font-mono);
}
.label { font-size: 0.85rem; color: var(--muted); }
@media (max-width: 860px) {
  .strip { grid-template-columns: 1fr 1fr; }
  .strip > div:nth-child(2) { border-right: none; }
}
@media (max-width: 480px) {
  .strip { grid-template-columns: 1fr; }
  .strip > div { border-right: none; border-bottom: 1px solid var(--line); }
}
</style>
