<template>
  <div>
    <h1>Dashboard Pianificazione Turni</h1>
    
    <div class="date-picker">
      <button @click="changeWeek(-1)">&lt; Settimana Precedente</button>
      <span>{{ weekDisplay }}</span>
      <button @click="changeWeek(1)">Settimana Successiva &gt;</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>Operatore</th>
          <th v-for="day in weekDays" :key="day.date">{{ day.name }} <br> {{ day.date }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="operator in operators" :key="operator.id">
          <td>{{ operator.name }}</td>
          <td v-for="day in weekDays" :key="day.date" @click="openShiftModal(null, operator, day.date)">
            <div v-for="shift in getShiftsForOperatorAndDay(operator.id, day.date)" :key="shift.id" class="shift" @click.stop="openShiftModal(shift, operator, day.date)">
              {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="totals">
      <h2>Totalizzatori ({{ weekDisplay }})</h2>
      <div v-for="total in totals.hours_per_operator" :key="total.user_id">
        <strong>{{ total.name }}:</strong> {{ total.total_hours }} / {{ total.weekly_hours }} ore
      </div>
    </div>

    <div class="requests">
      <h2>Richieste di Indisponibilità</h2>
      <ul>
        <li v-for="request in requests" :key="request.id">
          {{ request.user.name }} - {{ request.date }} ({{ request.preference }}) - Stato: {{ request.status }}
          <button v-if="request.status === 'in attesa'" @click="updateRequestStatus(request, 'approvata')">Approva</button>
          <button v-if="request.status === 'in attesa'" @click="updateRequestStatus(request, 'rifiutata')">Rifiuta</button>
        </li>
      </ul>
    </div>

    <!-- Shift Modal -->
    <div v-if="showModal" class="modal">
      <div class="modal-content">
        <span class="close" @click="closeShiftModal">&times;</span>
        <h2>{{ modalShift.id ? 'Modifica Turno' : 'Nuovo Turno' }}</h2>
        <p><strong>Operatore:</strong> {{ selectedOperator.name }}</p>
        <p><strong>Data:</strong> {{ selectedDate }}</p>
        <div>
          <label>Ora Inizio:</label>
          <input type="time" v-model="modalShift.start_time">
        </div>
        <div>
          <label>Ora Fine:</label>
          <input type="time" v-model="modalShift.end_time">
        </div>
        <button @click="saveShift">Salva</button>
        <button v-if="modalShift.id" @click="deleteShift" class="delete">Elimina</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted, computed, watch } from 'vue';

export default {
  setup() {
    const operators = ref([]);
    const shifts = ref([]);
    const requests = ref([]);
    const totals = ref({ hours_per_operator: [], hours_per_day: [] });
    const currentDate = ref(new Date());

    const showModal = ref(false);
    const modalShift = ref({});
    const selectedOperator = ref(null);
    const selectedDate = ref('');

    // Fetching data
    const fetchData = async () => {
      const start = weekDays.value[0].date;
      const end = weekDays.value[6].date;
      try {
        const [usersRes, shiftsRes, requestsRes, totalsRes] = await Promise.all([
          axios.get('/api/users'),
          axios.get(`/api/shifts?start_date=${start}&end_date=${end}`),
          axios.get('/api/unavailability-requests'),
          axios.get(`/api/dashboard/totals?start_date=${start}&end_date=${end}`),
        ]);
        operators.value = usersRes.data;
        shifts.value = shiftsRes.data;
        requests.value = requestsRes.data;
        totals.value = totalsRes.data;
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    };

    onMounted(fetchData);

    // Week navigation
    const weekDays = computed(() => {
        const startOfWeek = new Date(currentDate.value);
        startOfWeek.setDate(startOfWeek.getDate() - (startOfWeek.getDay() + 6) % 7); // Monday as start of week
        return Array.from({ length: 7 }).map((_, i) => {
            const day = new Date(startOfWeek);
            day.setDate(day.getDate() + i);
            return {
                date: day.toISOString().split('T')[0],
                name: day.toLocaleDateString('it-IT', { weekday: 'long' })
            };
        });
    });

    const weekDisplay = computed(() => `${weekDays.value[0].date} - ${weekDays.value[6].date}`);
    
    const changeWeek = (direction) => {
      currentDate.value.setDate(currentDate.value.getDate() + 7 * direction);
      fetchData();
    };
    
    watch(currentDate, fetchData, { immediate: true });

    // Shift logic
    const getShiftsForOperatorAndDay = (operatorId, date) => {
      return shifts.value.filter(s => s.user_id === operatorId && s.start_time.startsWith(date));
    };

    const formatTime = (datetime) => new Date(datetime).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

    // Modal logic
    const openShiftModal = (shift, operator, date) => {
      selectedOperator.value = operator;
      selectedDate.value = date;
      if (shift) {
        modalShift.value = { 
          id: shift.id,
          start_time: formatTime(shift.start_time),
          end_time: formatTime(shift.end_time),
        };
      } else {
        modalShift.value = { id: null, start_time: '06:00', end_time: '14:00' };
      }
      showModal.value = true;
    };

    const closeShiftModal = () => showModal.value = false;

    const saveShift = async () => {
      const payload = {
        user_id: selectedOperator.value.id,
        start_time: `${selectedDate.value} ${modalShift.value.start_time}`,
        end_time: `${selectedDate.value} ${modalShift.value.end_time}`,
      };

      try {
        if (modalShift.value.id) {
          await axios.put(`/api/shifts/${modalShift.value.id}`, payload);
        } else {
          await axios.post('/api/shifts', payload);
        }
        await fetchData();
        closeShiftModal();
      } catch (error) {
        console.error("Error saving shift:", error.response.data);
      }
    };
    
    const deleteShift = async () => {
        if (!confirm("Sei sicuro di voler eliminare questo turno?")) return;
        try {
            await axios.delete(`/api/shifts/${modalShift.value.id}`);
            await fetchData();
            closeShiftModal();
        } catch (error) {
            console.error("Error deleting shift:", error);
        }
    };

    // Request logic
    const updateRequestStatus = async (request, status) => {
        try {
            await axios.post(`/api/unavailability-requests/${request.id}/update-status`, { status });
            await fetchData();
        } catch (error) {
            console.error("Error updating request status:", error);
        }
    };

    return { 
        operators, shifts, requests, totals, weekDays, weekDisplay, changeWeek,
        getShiftsForOperatorAndDay, formatTime, 
        showModal, modalShift, selectedOperator, selectedDate,
        openShiftModal, closeShiftModal, saveShift, deleteShift,
        updateRequestStatus,
    };
  },
};
</script>

<style scoped>
.modal { display: block; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
.modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.shift { background-color: #4caf50; color: white; padding: 5px; margin-top: 5px; border-radius: 3px; cursor: pointer; }
.delete { background-color: #f44336; color: white; }
td { cursor: pointer; }
</style>
