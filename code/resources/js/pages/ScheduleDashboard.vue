<template>
  <v-container fluid>
    <v-row>
      <v-col>
        <h1 class="text-h4">Dashboard Pianificazione Turni</h1>
      </v-col>
    </v-row>

    <v-row align="center" justify="center">
      <v-col cols="auto">
        <v-btn icon @click="changeWeek(-1)">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
      </v-col>
      <v-col cols="auto">
        <span class="text-h6">{{ weekDisplay }}</span>
      </v-col>
      <v-col cols="auto">
        <v-btn icon @click="changeWeek(1)">
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>
      </v-col>
    </v-row>

    <v-table>
      <thead>
        <tr>
          <th class="text-left">Operatore</th>
          <th v-for="day in weekDays" :key="day.date" class="text-center">
            {{ day.name }} <br> {{ day.date }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="operator in operators" :key="operator.id">
          <td>{{ operator.name }}</td>
          <td v-for="day in weekDays" :key="day.date" class="text-center">
            <div v-for="shift in getShiftsForOperatorAndDay(operator.id, day.date)" :key="shift.id" class="shift" @click="openShiftModal(shift, operator, day.date)">
              {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
            </div>
            <v-btn icon size="x-small" @click="openShiftModal(null, operator, day.date)">
              <v-icon>mdi-plus</v-icon>
            </v-btn>
          </td>
        </tr>
      </tbody>
    </v-table>

    <v-row>
      <v-col>
        <h2 class="text-h5 mt-4">Totalizzatori ({{ weekDisplay }})</h2>
        <v-list>
          <v-list-item v-for="total in totals.hours_per_operator" :key="total.user_id" :title="`${total.name}: ${total.total_hours} / ${total.weekly_hours} ore`"></v-list-item>
        </v-list>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <h2 class="text-h5 mt-4">Richieste di Indisponibilità</h2>
        <v-list>
          <v-list-item v-for="request in requests" :key="request.id" :title="`${request.user.name} - ${request.date} (${request.preference}) - Stato: ${request.status}`">
            <template v-slot:append>
              <div v-if="request.status === 'in attesa'">
                <v-btn size="small" @click="updateRequestStatus(request, 'approvata')">Approva</v-btn>
                <v-btn size="small" @click="updateRequestStatus(request, 'rifiutata')">Rifiuta</v-btn>
              </div>
            </template>
          </v-list-item>
        </v-list>
      </v-col>
    </v-row>

    <v-dialog v-model="showModal" max-width="500px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ modalShift.id ? 'Modifica Turno' : 'Nuovo Turno' }}</span>
        </v-card-title>
        <v-card-text>
          <p><strong>Operatore:</strong> {{ selectedOperator.name }}</p>
          <p><strong>Data:</strong> {{ selectedDate }}</p>
          <v-text-field label="Ora Inizio" type="time" v-model="modalShift.start_time"></v-text-field>
          <v-text-field label="Ora Fine" type="time" v-model="modalShift.end_time"></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeShiftModal">Annulla</v-btn>
          <v-btn color="blue darken-1" text @click="saveShift">Salva</v-btn>
          <v-btn v-if="modalShift.id" color="red darken-1" text @click="deleteShift">Elimina</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
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
      const newDate = new Date(currentDate.value);
      newDate.setDate(newDate.getDate() + 7 * direction);
      currentDate.value = newDate;
    };
    
    watch(currentDate, fetchData, { immediate: true });

    const getShiftsForOperatorAndDay = (operatorId, date) => {
      return shifts.value.filter(s => s.user_id === operatorId && s.start_time.startsWith(date));
    };

    const formatTime = (datetime) => new Date(datetime).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

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
.shift {
  background-color: #4caf50;
  color: white;
  padding: 5px;
  margin-top: 5px;
  border-radius: 3px;
  cursor: pointer;
}
</style>
