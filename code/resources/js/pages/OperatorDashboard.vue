<template>
  <div>
    <!-- Header -->
    <div class="mb-5">
      <div class="text-h5 font-weight-medium">La mia dashboard</div>
      <div class="text-body-2 text-medium-emphasis mt-1">I tuoi turni e le tue richieste</div>
    </div>

    <v-row>
      <!-- Turni -->
      <v-col cols="12" md="6">
        <v-card rounded="xl" height="100%">
          <v-card-title class="pa-4 pb-2 d-flex align-center">
            <v-icon start color="primary" size="20">mdi-calendar-check</v-icon>
            <span class="text-subtitle-1 font-weight-medium">I miei turni</span>
            <v-chip size="small" class="ml-auto" color="primary" variant="tonal">
              {{ myShifts.length }}
            </v-chip>
          </v-card-title>
          <v-divider></v-divider>
          <v-list density="compact" class="pa-2">
            <v-list-item
              v-for="shift in myShifts"
              :key="shift.id"
              rounded="lg"
              class="mb-1"
            >
              <template v-slot:prepend>
                <v-avatar color="primary" size="36" class="mr-1">
                  <v-icon size="18" color="white">mdi-clock-outline</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="text-body-2 font-weight-medium">
                {{ formatDate(shift.start_time) }}
              </v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ formatTime(shift.start_time) }} &ndash; {{ formatTime(shift.end_time) }}
                &bull; {{ shiftDuration(shift) }}h
              </v-list-item-subtitle>
            </v-list-item>
            <div v-if="!myShifts.length" class="text-center pa-6">
              <v-icon size="40" color="medium-emphasis" class="mb-2">mdi-calendar-blank-outline</v-icon>
              <div class="text-body-2 text-medium-emphasis">Nessun turno in programma</div>
            </div>
          </v-list>
        </v-card>
      </v-col>

      <!-- Richieste -->
      <v-col cols="12" md="6">
        <v-card rounded="xl">
          <!-- Lista richieste -->
          <v-card-title class="pa-4 pb-2 d-flex align-center">
            <v-icon start color="warning" size="20">mdi-bell-outline</v-icon>
            <span class="text-subtitle-1 font-weight-medium">Le mie richieste</span>
            <v-chip size="small" class="ml-auto" color="warning" variant="tonal">
              {{ myRequests.length }}
            </v-chip>
          </v-card-title>
          <v-divider></v-divider>
          <v-list density="compact" class="pa-2">
            <v-list-item
              v-for="req in myRequests"
              :key="req.id"
              rounded="lg"
              class="mb-1"
            >
              <v-list-item-title class="text-body-2 font-weight-medium">{{ req.date }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">{{ req.preference }}</v-list-item-subtitle>
              <template v-slot:append>
                <v-chip size="x-small" :color="statusColor(req.status)" variant="tonal">
                  {{ req.status }}
                </v-chip>
              </template>
            </v-list-item>
            <div v-if="!myRequests.length" class="text-center pa-4">
              <div class="text-body-2 text-medium-emphasis">Nessuna richiesta inviata</div>
            </div>
          </v-list>

          <!-- Form nuova richiesta -->
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <div class="text-subtitle-2 font-weight-medium mb-3">
              <v-icon start size="16" color="primary">mdi-plus-circle-outline</v-icon>
              Nuova richiesta
            </div>
            <v-row dense>
              <v-col cols="12" sm="6">
                <v-text-field
                  type="date"
                  label="Data"
                  v-model="newRequest.date"
                  prepend-inner-icon="mdi-calendar"
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  label="Preferenza"
                  :items="preferenceOptions"
                  v-model="newRequest.preference"
                  prepend-inner-icon="mdi-clock-outline"
                ></v-select>
              </v-col>
            </v-row>
            <v-alert v-if="errorMessage" type="error" density="compact" class="mb-3">{{ errorMessage }}</v-alert>
            <v-alert v-if="successMessage" type="success" density="compact" class="mb-3">{{ successMessage }}</v-alert>
            <v-btn color="primary" elevation="0" block @click="submitRequest">
              <v-icon start>mdi-send-outline</v-icon>
              Invia richiesta
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted } from 'vue';

export default {
  setup() {
    const myShifts   = ref([]);
    const myRequests = ref([]);
    const newRequest = ref({
      date:       new Date().toISOString().split('T')[0],
      preference: 'tutto il giorno',
    });
    const errorMessage   = ref('');
    const successMessage = ref('');

    const preferenceOptions = ['mattina', 'pomeriggio', 'tutto il giorno'];

    const fetchData = async () => {
      try {
        const [s, r] = await Promise.all([
          axios.get('/api/operator/shifts'),
          axios.get('/api/operator/unavailability-requests'),
        ]);
        myShifts.value   = s.data;
        myRequests.value = r.data;
      } catch (e) {
        console.error('fetchData:', e);
      }
    };

    onMounted(fetchData);

    const submitRequest = async () => {
      errorMessage.value   = '';
      successMessage.value = '';
      try {
        await axios.post('/api/operator/unavailability-requests', newRequest.value);
        successMessage.value = 'Richiesta inviata con successo!';
        newRequest.value.preference = 'tutto il giorno';
        await fetchData();
      } catch (e) {
        errorMessage.value = e?.response?.data?.message || "Errore durante l'invio.";
      }
    };

    const statusColor = (s) => s === 'approvata' ? 'success' : s === 'rifiutata' ? 'error' : 'warning';

    const formatDate = (dt) =>
      new Date(dt).toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' });
    const formatTime = (dt) =>
      new Date(dt).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

    const shiftDuration = (shift) => {
      const ms = new Date(shift.end_time) - new Date(shift.start_time);
      return (ms / 3600000).toFixed(1);
    };

    return {
      myShifts, myRequests, newRequest,
      errorMessage, successMessage,
      preferenceOptions,
      submitRequest, statusColor,
      formatDate, formatTime, shiftDuration,
    };
  },
};
</script>
