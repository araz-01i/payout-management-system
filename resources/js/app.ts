import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, createSSRApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const app = (import.meta.env.SSR ? createSSRApp : createApp)({ render: () => h(App, props) });
        app.use(plugin).use(ZiggyVue).mount(el!);

        if (typeof window !== 'undefined') {
            initializeTheme();
            initializeFlashToast();
        }
    },
    progress: {
        color: '#4B5563',
    },
});
