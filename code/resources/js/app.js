import './bootstrap';
import { createApp } from 'vue';

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';

const vuetify = createVuetify({
  components,
  directives,
});

// Import components
import ExampleComponent from './components/ExampleComponent.vue';
import ScheduleDashboard from './pages/ScheduleDashboard.vue';
import OperatorDashboard from './pages/OperatorDashboard.vue';

const app = createApp({});

app.use(vuetify);

app.component('example-component', ExampleComponent);
app.component('schedule-dashboard', ScheduleDashboard);
app.component('operator-dashboard', OperatorDashboard);

app.mount('#app');
