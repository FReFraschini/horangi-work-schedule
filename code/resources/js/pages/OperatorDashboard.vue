<template>
  <v-container>
    <v-row>
      <v-col>
        <h1 class="text-h4">La Mia Dashboard</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <h2 class="text-h5">I Miei Prossimi Turni</h2>
        <v-list>
          <v-list-item v-for="shift in myShifts" :key="shift.id" :title="`${formatDate(shift.start_time)}: ${formatTime(shift.start_time)} - ${formatTime(shift.end_time)}`"></v-list-item>
        </v-list>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <h2 class="text-h5">Le Mie Richieste di Indisponibilità</h2>

        <v-card class="mt-4">
          <v-card-title>Nuova Richiesta</v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field type="date" label="Data" v-model="newRequest.date"></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  label="Preferenza"
                  :items="['mattina', 'pomeriggio', 'tutto il giorno']"
                  v-model="newRequest.preference"
                ></v-select>
              </v-col>
            </v-row>
            <v-alert v-if="errorMessage" type="error" dense>{{ errorMessage }}</v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="submitRequest">Invia Richiesta</v-btn>
          </v-card-actions>
        </v-card>

        <v-list class="mt-4">
          <v-list-item v-for="request in myRequests" :key="request.id" :title="`${request.date} (${request.preference}) - Stato: ${request.status}`"></v-list-item>
        </v-list>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from 'axios';
import { ref, onMounted } from 'vue';

export default {
  setup() {
    const myShifts = ref([]);
    const myRequests = ref([]);
    const newRequest = ref({
      date: new Date().toISOString().split('T')[0],
      preference: 'tutto il giorno',
    });
    const errorMessage = ref('');

    const fetchData = async () => {
      try {
        const [shiftsRes, requestsRes] = await Promise.all([
          axios.get('/api/operator/shifts'),
          axios.get('/api/operator/unavailability-requests'),
        ]);
        myShifts.value = shiftsRes.data;
        myRequests.value = requestsRes.data;
      } catch (error) {
        console.error("Error fetching operator data:", error);
      }
    };

    onMounted(fetchData);

    const submitRequest = async () => {
      errorMessage.value = '';
      try {
        await axios.post('/api/operator/unavailability-requests', newRequest.value);
        await fetchData(); // Refresh list after submitting
      } catch (error) {
        errorMessage.value = "Errore durante l'invio della richiesta.";
        console.error("Error submitting request:", error.response.data);
      }
    };

    const formatDate = (datetime) => new Date(datetime).toLocaleDateString('it-IT');
    const formatTime = (datetime) => new Date(datetime).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

    return {
      myShifts,
      myRequests,
      newRequest,
      errorMessage,
      submitRequest,
      formatDate,
      formatTime,
    };
  },
};
</script>
