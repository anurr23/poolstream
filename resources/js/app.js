import '../scss/app.scss';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'PoolStream';

let activeRequests = 0;
let showTimer = null;

const setBlockUi = (show) => {
    const el = document.getElementById('bb-block-ui');
    if (!el) return;
    if (show) {
        el.classList.add('is-active');
        el.setAttribute('aria-hidden', 'false');
    } else {
        el.classList.remove('is-active');
        el.setAttribute('aria-hidden', 'true');
    }
};

router.on('start', () => {
    activeRequests++;
    if (activeRequests === 1) {
        showTimer = setTimeout(() => setBlockUi(true), 60);
    }
});

router.on('finish', () => {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests === 0) {
        if (showTimer) {
            clearTimeout(showTimer);
            showTimer = null;
        }
        setBlockUi(false);
    }
});

window.addEventListener('pageshow', () => {
    activeRequests = 0;
    if (showTimer) {
        clearTimeout(showTimer);
        showTimer = null;
    }
    setBlockUi(false);
});

window.addEventListener('beforeunload', () => {
    setBlockUi(true);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
