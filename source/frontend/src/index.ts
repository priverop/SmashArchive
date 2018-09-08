import Vue from 'vue';
import VueRouter from 'vue-router';
import * as components from './components';
import { UserStore } from './store';

Vue.use(VueRouter);

const routes = [
    { path: '/', component: components.home },
    { path: '/players', component: components.players },
    { path: '/tournaments', component: components.tournaments },
];

const router = new VueRouter({
    routes,
    mode: 'history',
});

new Vue({
    router,
    el: '#app',
});

(window as any).fbAsyncInit = async function () {
    FB.init({
        appId: '1878227255734015', // TODO Make this dynamic.
        xfbml: false,
        version: 'v3.0',
    });

    await UserStore.init();
};