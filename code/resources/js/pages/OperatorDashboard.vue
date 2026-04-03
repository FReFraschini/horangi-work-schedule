<template>
  <div>
    <!-- Header -->
    <div class="mb-5">
      <div class="text-h5 font-weight-medium">La mia dashboard</div>
      <div class="text-body-2 text-medium-emphasis mt-1">I tuoi turni e le tue richieste</div>
    </div>

    <v-row>
      <!-- Turno di oggi -->
      <v-col cols="12" md="6">
        <v-card rounded="xl" height="100%">
          <v-card-title class="pa-4 pb-2 d-flex align-center">
            <v-icon start color="primary" size="20">mdi-calendar-today</v-icon>
            <span class="text-subtitle-1 font-weight-medium">Il mio turno di oggi</span>
            <span class="text-caption text-medium-emphasis ml-auto">{{ todayLabel }}</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <!-- Assenza -->
            <div
              v-if="todayAbsence"
              class="d-flex align-center pa-3 rounded-lg mb-3"
              :style="`background: rgba(var(--v-theme-${absenceTypes[todayAbsence.type]?.color ?? 'warning'}), 0.10);`"
            >
              <v-avatar :color="absenceTypes[todayAbsence.type]?.color ?? 'warning'" size="44" class="mr-3">
                <v-icon size="22" color="white">{{ absenceTypes[todayAbsence.type]?.icon ?? 'mdi-calendar-remove' }}</v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">{{ absenceTypes[todayAbsence.type]?.label ?? todayAbsence.type }}</div>
                <div v-if="todayAbsence.note" class="text-caption text-medium-emphasis">{{ todayAbsence.note }}</div>
              </div>
            </div>

            <!-- Turni -->
            <template v-if="todayShifts.length">
              <div
                v-for="shift in todayShifts"
                :key="shift.id"
                class="d-flex align-center pa-3 rounded-lg mb-2"
                style="background: rgba(var(--v-theme-primary), 0.08);"
              >
                <v-avatar color="primary" size="44" class="mr-3">
                  <v-icon size="22" color="white">mdi-clock-outline</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h6 font-weight-bold">
                    {{ formatTime(shift.start_time) }}&ensp;&ndash;&ensp;{{ formatTime(shift.end_time) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">Durata: {{ shiftDuration(shift) }}h</div>
                </div>
              </div>
            </template>
            <div v-else-if="!todayAbsence" class="text-center pa-6">
              <v-icon size="48" color="medium-emphasis" class="mb-3">mdi-calendar-blank-outline</v-icon>
              <div class="text-body-1 text-medium-emphasis">Nessun turno programmato per oggi</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Richieste -->
      <v-col cols="12" md="6">
        <v-card rounded="xl">
          <!-- Lista richieste -->
          <v-card-title class="pa-4 pb-2 d-flex align-center">
            <v-icon start color="warning" size="20">mdi-bell-outline</v-icon>
            <span class="text-subtitle-1 font-weight-medium">Le mie richieste</span>
            <v-btn
              size="x-small"
              :variant="showArchived ? 'tonal' : 'text'"
              :color="showArchived ? 'primary' : 'medium-emphasis'"
              class="ml-auto mr-1"
              prepend-icon="mdi-archive-outline"
              @click="toggleArchived"
            >Archiviate</v-btn>
            <v-chip size="small" :color="showArchived ? 'primary' : 'warning'" variant="tonal">
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
              :class="{ 'opacity-60': showArchived }"
            >
              <v-list-item-title class="text-body-2 font-weight-medium">
                {{ formatReqDate(req.date) }}
              </v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ req.preference }}<span v-if="req.note"> · {{ req.note }}</span>
              </v-list-item-subtitle>
              <template v-slot:append>
                <div class="d-flex align-center gap-1">
                  <v-chip size="x-small" :color="statusColor(req.status)" variant="tonal">
                    {{ req.status }}
                  </v-chip>
                  <template v-if="!showArchived">
                    <v-tooltip location="top" text="Archivia">
                      <template #activator="{ props }">
                        <v-btn v-bind="props" icon size="x-small" variant="text" color="medium-emphasis" @click="archiveRequest(req)">
                          <v-icon size="15">mdi-archive-arrow-down-outline</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>
                    <v-btn
                      v-if="req.status === 'in attesa'"
                      icon size="x-small" variant="text" color="error"
                      @click="deleteRequest(req)"
                    >
                      <v-icon size="15">mdi-delete-outline</v-icon>
                    </v-btn>
                  </template>
                  <v-btn
                    v-else
                    icon size="x-small" variant="text" color="error"
                    @click="deleteRequest(req)"
                  >
                    <v-icon size="15">mdi-delete-outline</v-icon>
                  </v-btn>
                </div>
              </template>
            </v-list-item>
            <div v-if="!myRequests.length" class="text-center pa-4">
              <div class="text-body-2 text-medium-emphasis">
                {{ showArchived ? 'Nessuna richiesta archiviata' : 'Nessuna richiesta inviata' }}
              </div>
            </div>
          </v-list>

          <!-- Form nuova richiesta -->
          <v-divider></v-divider>
          <v-card-text class="pa-4 pb-5">
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
              <v-col cols="12">
                <v-text-field
                  label="Note (opzionale)"
                  v-model="newRequest.note"
                  prepend-inner-icon="mdi-note-text-outline"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-alert v-if="errorMessage" type="error" density="compact" class="mb-3">{{ errorMessage }}</v-alert>
            <v-alert v-if="successMessage" type="success" density="compact" class="mb-3">{{ successMessage }}</v-alert>
            <v-btn color="primary" elevation="0" block class="mt-1" @click="submitRequest">
              <v-icon start>mdi-send-outline</v-icon>
              Invia richiesta
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Griglia turni settimanale -->
    <v-card rounded="xl" class="mt-5">
      <v-card-text class="d-flex align-center pa-3">
        <v-btn icon variant="text" @click="changeWeek(-1)"><v-icon>mdi-chevron-left</v-icon></v-btn>
        <v-btn icon variant="text" @click="goToToday" size="small" class="mx-1">
          <v-icon size="small">mdi-calendar-today</v-icon>
        </v-btn>
        <span class="text-subtitle-1 font-weight-medium flex-grow-1 text-center">{{ weekDisplay }}</span>
        <v-btn icon variant="text" @click="changeWeek(1)"><v-icon>mdi-chevron-right</v-icon></v-btn>
      </v-card-text>
    </v-card>

    <v-card rounded="xl" class="mt-2">
      <div style="overflow-x: auto;">
        <v-table density="compact">
          <thead>
            <tr>
              <th style="min-width:160px; white-space:nowrap;">Operatore</th>
              <th
                v-for="day in weekDays"
                :key="day.date"
                class="text-center"
                style="min-width:110px; padding: 8px 4px;"
                :style="day.isToday ? 'background:rgba(var(--v-theme-primary),0.05)' : ''"
              >
                <div class="text-caption text-medium-emphasis">{{ day.name }}</div>
                <div
                  class="day-number"
                  :class="{ 'today-badge': day.isToday }"
                  style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-size:.875rem;font-weight:500;margin:2px auto 0;"
                >{{ day.dayNum }}</div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="op in scheduleTeam" :key="op.id">
              <td style="min-width:160px; white-space:nowrap;">
                <div class="d-flex align-center">
                  <v-avatar :color="op.color || 'primary'" size="28" class="mr-2">
                    <span class="text-caption font-weight-bold" style="color:white">{{ op.name.charAt(0).toUpperCase() }}</span>
                  </v-avatar>
                  <span class="text-body-2" :class="op.id === currentUserId ? 'font-weight-bold' : ''">{{ op.name }}</span>
                </div>
              </td>
              <td
                v-for="day in weekDays"
                :key="day.date"
                class="text-center"
                style="padding: 4px; height:56px; position:relative;"
                :style="day.isToday ? 'background:rgba(var(--v-theme-primary),0.05)' : ''"
              >
                <div
                  v-for="shift in getScheduleShifts(op.id, day.date)"
                  :key="shift.id"
                  style="display:inline-block;color:white;padding:2px 7px;border-radius:20px;font-size:.7rem;font-weight:500;margin:1px 0;white-space:nowrap;"
                  :style="{ backgroundColor: op.color || '#1A73E8' }"
                >
                  {{ formatTime(shift.start_time) }}&thinsp;&ndash;&thinsp;{{ formatTime(shift.end_time) }}
                </div>
                <div
                  v-for="abs in getScheduleAbsences(op.id, day.date)"
                  :key="`abs-${abs.id}`"
                  style="display:inline-block;color:white;padding:2px 7px;border-radius:20px;font-size:.7rem;font-weight:500;margin:1px 0;white-space:nowrap;opacity:.85;"
                  :style="{ backgroundColor: absenceTypeHex(abs.type) }"
                >
                  <v-icon size="10" style="vertical-align:middle" class="mr-1">mdi-calendar-remove</v-icon>{{ absenceTypeLabel(abs.type) }}
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, watch, onMounted } from 'vue';

export default {
  props: { currentUserId: { type: Number, default: null } },
  setup(props) {
    const today = new Date().toISOString().split('T')[0];

    // ---- Griglia settimanale ----
    const currentDate   = ref(new Date());
    const scheduleShifts    = ref([]);
    const scheduleAbsences  = ref([]);
    const scheduleTeam      = ref([]);

    const weekDays = computed(() => {
      const todayStr = new Date().toISOString().split('T')[0];
      const start = new Date(currentDate.value);
      start.setDate(start.getDate() - (start.getDay() + 6) % 7);
      return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        const date = d.toISOString().split('T')[0];
        return {
          date,
          name: d.toLocaleDateString('it-IT', { weekday: 'short' }).toUpperCase(),
          dayNum: d.getDate(),
          isToday: date === todayStr,
        };
      });
    });

    const weekDisplay = computed(() => {
      const fmt = d => new Date(d.date).toLocaleDateString('it-IT', { day: 'numeric', month: 'short' });
      return `${fmt(weekDays.value[0])} — ${fmt(weekDays.value[6])}`;
    });

    const changeWeek = (dir) => {
      const d = new Date(currentDate.value);
      d.setDate(d.getDate() + 7 * dir);
      currentDate.value = d;
    };
    const goToToday = () => { currentDate.value = new Date(); };

    const fetchSchedule = async () => {
      const start = weekDays.value[0].date;
      const end   = weekDays.value[6].date;
      const [sh, ab, te] = await Promise.allSettled([
        axios.get(`/api/operator/schedule/shifts?start_date=${start}&end_date=${end}`),
        axios.get(`/api/operator/schedule/absences?start_date=${start}&end_date=${end}`),
        axios.get('/api/operator/schedule/team'),
      ]);
      if (sh.status === 'fulfilled') scheduleShifts.value   = sh.value.data;
      if (ab.status === 'fulfilled') scheduleAbsences.value = ab.value.data;
      if (te.status === 'fulfilled') scheduleTeam.value     = te.value.data;
    };

    watch(currentDate, fetchSchedule);

    const getScheduleShifts = (opId, date) =>
      scheduleShifts.value.filter(s => s.user_id === opId && s.start_time.startsWith(date));

    const getScheduleAbsences = (opId, date) =>
      scheduleAbsences.value.filter(a => a.user_id === opId && String(a.date).startsWith(date));

    const myShifts     = ref([]);
    const myRequests   = ref([]);
    const todayAbsence = ref(null);
    const showArchived = ref(false);
    const newRequest   = ref({
      date:       today,
      preference: 'tutto il giorno',
      note:       '',
    });
    const errorMessage   = ref('');
    const successMessage = ref('');

    const preferenceOptions = ['mattina', 'pomeriggio', 'tutto il giorno'];

    const fetchRequests = async () => {
      const res = await axios.get(`/api/operator/unavailability-requests?archived=${showArchived.value}`);
      myRequests.value = res.data;
    };

    const fetchData = async () => {
      const [s, r, a] = await Promise.allSettled([
        axios.get('/api/operator/shifts'),
        axios.get(`/api/operator/unavailability-requests?archived=false`),
        axios.get(`/api/operator/absences?date=${today}`),
      ]);
      if (s.status === 'fulfilled') myShifts.value   = s.value.data;
      if (r.status === 'fulfilled') myRequests.value = r.value.data;
      if (a.status === 'fulfilled') todayAbsence.value = a.value.data.length ? a.value.data[0] : null;
      [s, r, a].forEach((res, i) => {
        if (res.status === 'rejected') console.error(`fetchData [${i}]:`, res.reason?.response?.data ?? res.reason);
      });
    };

    const toggleArchived = async () => {
      showArchived.value = !showArchived.value;
      await fetchRequests();
    };

    onMounted(() => { fetchData(); fetchSchedule(); });

    const submitRequest = async () => {
      errorMessage.value   = '';
      successMessage.value = '';
      try {
        await axios.post('/api/operator/unavailability-requests', newRequest.value);
        successMessage.value = 'Richiesta inviata con successo!';
        newRequest.value.preference = 'tutto il giorno';
        newRequest.value.note = '';
        if (showArchived.value) showArchived.value = false;
        await fetchRequests();
      } catch (e) {
        errorMessage.value = e?.response?.data?.message || "Errore durante l'invio.";
      }
    };

    const archiveRequest = async (req) => {
      try {
        await axios.patch(`/api/operator/unavailability-requests/${req.id}/archive`);
        await fetchRequests();
      } catch (e) {
        errorMessage.value = e?.response?.data?.message || "Errore durante l'archiviazione.";
      }
    };

    const deleteRequest = async (req) => {
      if (!confirm('Eliminare questa richiesta?')) return;
      try {
        await axios.delete(`/api/operator/unavailability-requests/${req.id}`);
        await fetchRequests();
      } catch (e) {
        errorMessage.value = e?.response?.data?.message || "Errore durante l'eliminazione.";
      }
    };

    const formatReqDate = (dt) =>
      new Date(String(dt).substring(0, 10)).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });

    const todayShifts = computed(() =>
      myShifts.value.filter(s => s.start_time.startsWith(today))
    );

    const todayLabel = new Date().toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' });

    const absenceTypes = {
      ferie:          { label: 'Ferie',         color: 'success', icon: 'mdi-beach',               hex: '#43a047' },
      permesso:       { label: 'Permesso',       color: 'info',    icon: 'mdi-clock-minus-outline',  hex: '#1e88e5' },
      compensativo:   { label: 'Compensativo',   color: 'warning', icon: 'mdi-calendar-refresh',    hex: '#fb8c00' },
      altra_assenza:  { label: 'Altra assenza',  color: 'error',   icon: 'mdi-calendar-remove',     hex: '#e53935' },
    };

    const absenceTypeLabel = (t) => absenceTypes[t]?.label ?? t;
    const absenceTypeHex   = (t) => absenceTypes[t]?.hex   ?? '#9e9e9e';

    const statusColor = (s) => s === 'approvata' ? 'success' : s === 'rifiutata' ? 'error' : 'warning';

    const formatTime = (dt) => {
      const s = dt.replace('T', ' ').replace(/\.\d+Z?$/, '');
      return s.substring(11, 16);
    };

    const shiftDuration = (shift) => {
      const [sh, sm] = formatTime(shift.start_time).split(':').map(Number);
      const [eh, em] = formatTime(shift.end_time).split(':').map(Number);
      return ((eh * 60 + em - sh * 60 - sm) / 60).toFixed(1);
    };

    return {
      myShifts, myRequests, todayAbsence, newRequest,
      todayShifts, todayLabel,
      errorMessage, successMessage,
      preferenceOptions, absenceTypes, absenceTypeHex, absenceTypeLabel,
      showArchived, toggleArchived, archiveRequest,
      submitRequest, deleteRequest, statusColor,
      formatTime, shiftDuration, formatReqDate,
      weekDays, weekDisplay, changeWeek, goToToday,
      scheduleTeam, getScheduleShifts, getScheduleAbsences,
    };
  },
};
</script>
