import { createApp } from 'vue';

import './app.css';
import App from '@root/App.vue';

import { update as tweenUpdate } from '@tweenjs/tween.js';

createApp(App, {
  websocketsServer: import.meta.env.VITE_WEBSOCKETS_SERVER,
  apiServer: import.meta.env.VITE_API_SERVER,
}).mount('#app');

const animate = (time: number) => {
  requestAnimationFrame(animate);
  tweenUpdate(time);
};
requestAnimationFrame(animate);
