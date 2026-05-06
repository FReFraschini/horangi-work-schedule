import './bootstrap';
import { createApp } from 'vue';
import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';

const lightTheme = {
    dark: false,
    colors: {
        background:       '#F8F9FA',
        surface:          '#FFFFFF',
        'surface-variant':'#E8EAED',
        primary:          '#1A73E8',
        'primary-darken-1':'#1557B0',
        secondary:        '#34A853',
        error:            '#EA4335',
        info:             '#4285F4',
        success:          '#34A853',
        warning:          '#F9AB00',
        'on-background':  '#202124',
        'on-surface':     '#202124',
    },
};

const darkTheme = {
    dark: true,
    colors: {
        background:       '#1C1B1F',
        surface:          '#28272B',
        'surface-variant':'#3A3940',
        primary:          '#8AB4F8',
        'primary-darken-1':'#669DF6',
        secondary:        '#81C995',
        error:            '#F28B82',
        info:             '#8AB4F8',
        success:          '#81C995',
        warning:          '#FDD663',
        'on-background':  '#E3E3E3',
        'on-surface':     '#E3E3E3',
    },
};

const savedTheme = localStorage.getItem('app-theme') || 'light';

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: savedTheme,
        themes: { light: lightTheme, dark: darkTheme },
    },
    defaults: {
        VCard:      { rounded: 'xl', elevation: 1 },
        VTextField: { variant: 'outlined', density: 'compact', rounded: 'lg', hideDetails: 'auto' },
        VSelect:    { variant: 'outlined', density: 'compact', rounded: 'lg', hideDetails: 'auto' },
        VBtn:       { rounded: 'lg' },
        VChip:      { rounded: 'lg' },
        VAlert:     { rounded: 'lg' },
    },
});

import LoginForm        from './components/LoginForm.vue';
import ScheduleDashboard from './pages/ScheduleDashboard.vue';
import OperatorDashboard from './pages/OperatorDashboard.vue';

const app = createApp({
    data() {
        return { isDark: savedTheme === 'dark' };
    },
    methods: {
        toggleTheme() {
            this.isDark = !this.isDark;
            const t = this.isDark ? 'dark' : 'light';
            localStorage.setItem('app-theme', t);
            this.$vuetify.theme.global.name = t;
        },
    },
});

app.use(vuetify);
app.component('login-form', LoginForm);
app.component('schedule-dashboard', ScheduleDashboard);
app.component('operator-dashboard', OperatorDashboard);
app.mount('#app');
