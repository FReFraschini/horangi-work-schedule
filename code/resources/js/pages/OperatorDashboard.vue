<template>
  <div>
    <h1>La Mia Dashboard</h1>

    <!-- My Shifts -->
    <div class="shifts-section">
      <h2>I Miei Prossimi Turni</h2>
      <ul>
        <li v-for="shift in myShifts" :key="shift.id">
          <strong>{{ formatDate(shift.start_time) }}:</strong> {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
        </li>
      </ul>
    </div>

    <!-- My Unavailability Requests -->
    <div class="requests-section">
      <h2>Le Mie Richieste di Indisponibilità</h2>
      
      <!-- Form to create a new request -->
      <div class="new-request-form">
        <h3>Nuova Richiesta</h3>
        <input type="date" v-model="newRequest.date">
        <select v-model="newRequest.preference">
          <option value="mattina">Mattina</option>
          <option value="pomeriggio">Pomeriggio</option>
          <option value="tutto il giorno">Tutto il giorno</option>
        </select>
        <button @click="submitRequest">Invia Richiesta</button>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
      </div>

      <!-- List of existing requests -->
      <ul>
        <li v-for="request in myRequests" :key="request.id">
          {{ request.date }} ({{ request.preference }}) - <strong>Stato: {{ request.status }}</strong>
        </li>
      </ul>
    </div>
  </div>
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

<style scoped>
.shifts-section, .requests-section {
  margin-bottom: 30px;
}
.new-request-form {
  margin-bottom: 20px;
  padding: 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.error {
  color: red;
}
</style>
