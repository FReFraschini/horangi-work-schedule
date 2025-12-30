import './bootstrap';
import { createApp } from 'vue';

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';

// Custom Theme
const softBlueTheme = {
  dark: false,
  colors: {
    background: '#E3F2FD',
    surface: '#FFFFFF',
    primary: '#1976D2',
    secondary: '#42A5F5',
    error: '#B00020',
    info: '#2196F3',
    success: '#4CAF50',
    warning: '#FB8C00',
  },
};

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'softBlueTheme',
    themes: {
      softBlueTheme,
    },
  },
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
