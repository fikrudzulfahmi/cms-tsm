import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { createPinia } from 'pinia';
import { createHead } from '@unhead/vue/legacy';
import App from './App.vue';
import { router } from './router';
import '../../css/tokens.css';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(VueQueryPlugin, {
  queryClientConfig: {
    defaultOptions: {
      queries: {
        staleTime: 60_000, // 1 menit default (spec 5.3)
        retry: 1,
        refetchOnWindowFocus: false,
      },
    },
  },
});
app.use(createHead());

router.isReady().then(() => {
  app.mount('#app');
});
