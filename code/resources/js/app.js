import './bootstrap';
import { createApp } from 'vue';

// Import components
import ExampleComponent from './components/ExampleComponent.vue';
import ScheduleDashboard from './pages/ScheduleDashboard.vue';
import OperatorDashboard from './pages/OperatorDashboard.vue';

function initializeApp() {
    // Check if the Vuetify library is available on the window object
    if (typeof window.Vuetify !== 'undefined') {
        // Vuetify is loaded, so we can create and mount the Vue app
        const app = createApp({});

        // Create and use the Vuetify instance
        const vuetify = window.Vuetify.createVuetify();
        app.use(vuetify);

        // Register the Vue components
        app.component('example-component', ExampleComponent);
        app.component('schedule-dashboard', ScheduleDashboard);
        app.component('operator-dashboard', OperatorDashboard);

        // Mount the application to the DOM
        app.mount('#app');
    } else {
        // If Vuetify is not loaded, wait 100 milliseconds and try again
        setTimeout(initializeApp, 100);
    }
}

// Start the initialization process
initializeApp();
