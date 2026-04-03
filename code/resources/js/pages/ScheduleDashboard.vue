<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center mb-4 flex-wrap gap-2">
      <div>
        <div class="text-h5 font-weight-medium">Dashboard Gestore</div>
        <div class="text-body-2 text-medium-emphasis mt-1">Pianifica i turni e gestisci il team</div>
      </div>
    </div>

    <!-- Tabs -->
    <v-tabs v-model="activeTab" color="primary" class="mb-5">
      <v-tab value="turni" prepend-icon="mdi-calendar-month">Turni</v-tab>
      <v-tab value="operatori" prepend-icon="mdi-account-group">Operatori</v-tab>
      <v-tab value="richieste" prepend-icon="mdi-bell-outline">
        Richieste
        <v-badge v-if="pendingCount > 0" :content="pendingCount" color="warning" inline class="ml-1"></v-badge>
      </v-tab>
    </v-tabs>

    <v-tabs-window v-model="activeTab">

      <!-- ==================== TURNI ==================== -->
      <v-tabs-window-item value="turni">

        <!-- Navigazione settimana -->
        <v-card class="mb-4" rounded="xl">
          <v-card-text class="d-flex align-center pa-3">
            <v-btn icon variant="text" @click="changeWeek(-1)">
              <v-icon>mdi-chevron-left</v-icon>
            </v-btn>
            <v-btn icon variant="text" @click="goToToday" title="Oggi" size="small" class="mx-1">
              <v-icon size="small">mdi-calendar-today</v-icon>
            </v-btn>
            <span class="text-subtitle-1 font-weight-medium flex-grow-1 text-center">{{ weekDisplay }}</span>
            <v-btn icon variant="text" @click="changeWeek(1)">
              <v-icon>mdi-chevron-right</v-icon>
            </v-btn>
            <v-btn variant="tonal" color="primary" size="small" prepend-icon="mdi-file-pdf-box" class="ml-2" @click="exportPdf">
              PDF
            </v-btn>
          </v-card-text>
        </v-card>

        <!-- Griglia turni -->
        <v-card class="mb-4" rounded="xl">
          <div class="schedule-wrapper">
            <v-table density="compact">
              <thead>
                <tr>
                  <th class="operator-col">Operatore</th>
                  <th
                    v-for="day in weekDays"
                    :key="day.date"
                    class="text-center day-col"
                    :class="{ 'today-col': day.isToday }"
                  >
                    <div class="text-caption text-medium-emphasis">{{ day.name }}</div>
                    <div
                      class="day-number"
                      :class="{ 'today-badge': day.isToday }"
                    >{{ day.dayNum }}</div>
                  </th>
                  <th class="text-center total-col">
                    <div class="text-caption text-medium-emphasis">TOT.</div>
                    <div class="day-number">sett.</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="op in operators" :key="op.id">
                  <td class="operator-col">
                    <div class="d-flex align-center">
                      <v-avatar :color="op.color || 'primary'" size="28" class="mr-2">
                        <span class="text-caption font-weight-bold" style="color:white">
                          {{ op.name.charAt(0).toUpperCase() }}
                        </span>
                      </v-avatar>
                      <span class="text-body-2">{{ op.name }}</span>
                    </div>
                  </td>
                  <td
                    v-for="day in weekDays"
                    :key="day.date"
                    class="text-center cell"
                    :class="{ 'today-col': day.isToday }"
                  >
                    <div
                      v-for="shift in getShifts(op.id, day.date)"
                      :key="shift.id"
                      class="shift-chip"
                      :style="{ backgroundColor: op.color || '#1A73E8' }"
                      @click="openShiftDialog(shift, op, day.date)"
                    >
                      {{ formatTime(shift.start_time) }}&thinsp;&ndash;&thinsp;{{ formatTime(shift.end_time) }}
                    </div>
                    <div
                      v-for="abs in getAbsences(op.id, day.date)"
                      :key="`abs-${abs.id}`"
                      class="shift-chip"
                      :style="{ backgroundColor: absenceTypeHex(abs.type), opacity: 0.85 }"
                      @click="openAbsenceChip(abs, op, day.date)"
                    >
                      <v-icon size="10" class="mr-1" style="vertical-align:middle">mdi-calendar-remove</v-icon>{{ absenceTypeLabel(abs.type) }}
                    </div>
                    <div class="request-icons">
                      <v-tooltip v-for="req in getRequests(op.id, day.date)" :key="`req-${req.id}`" location="top" max-width="220">
                        <template #activator="{ props }">
                          <v-icon v-bind="props" size="13" :color="requestStatusColor(req.status)" style="cursor:default;">mdi-alert-circle</v-icon>
                        </template>
                        <div class="text-caption">
                          <div><strong>Richiesta indisponibilità</strong></div>
                          <div>Preferenza: {{ req.preference }}</div>
                          <div>Stato: {{ req.status }}</div>
                        </div>
                      </v-tooltip>
                    </div>

                    <v-btn
                      v-if="getAbsences(op.id, day.date).length === 0"
                      icon
                      size="x-small"
                      variant="text"
                      color="medium-emphasis"
                      @click="openShiftDialog(null, op, day.date)"
                    >
                      <v-icon size="16">mdi-plus</v-icon>
                    </v-btn>
                  </td>
                  <td class="text-center total-col">
                    <span
                      class="text-caption font-weight-medium"
                      :class="getOperatorWeeklyHours(op.id) > op.weekly_hours ? 'text-error' : getOperatorWeeklyHours(op.id) > 0 ? 'text-success' : 'text-medium-emphasis'"
                    >{{ getOperatorWeeklyHours(op.id) }}h</span>
                    <span class="text-caption text-medium-emphasis"> / {{ op.weekly_hours }}h</span>
                  </td>
                </tr>
                <!-- Totali giornalieri -->
                <tr class="totals-row">
                  <td class="operator-col">
                    <span class="text-caption font-weight-bold text-medium-emphasis">ORE / GIORNO</span>
                  </td>
                  <td v-for="day in weekDays" :key="day.date" class="text-center">
                    <v-chip
                      size="x-small"
                      :color="getDailyTotal(day.date) > 0 ? 'primary' : ''"
                      variant="tonal"
                    >{{ getDailyTotal(day.date) }}h</v-chip>
                  </td>
                  <td></td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card>

      </v-tabs-window-item>

      <!-- ==================== OPERATORI ==================== -->
      <v-tabs-window-item value="operatori">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <div class="text-subtitle-1 font-weight-medium">Team operatori</div>
            <div class="text-caption text-medium-emphasis">{{ operators.length }} operatori registrati</div>
          </div>
          <v-btn color="primary" prepend-icon="mdi-plus" elevation="0" @click="openOperatorDialog()">
            Nuovo operatore
          </v-btn>
        </div>

        <v-row>
          <v-col v-for="op in operators" :key="op.id" cols="12" sm="6" md="4" lg="3">
            <v-card rounded="xl" elevation="1" class="operator-card">
              <v-card-text class="pa-4">
                <div class="d-flex align-center mb-3">
                  <v-avatar :color="op.color || 'primary'" size="44" class="mr-3">
                    <span class="text-h6 font-weight-bold" style="color:white">
                      {{ op.name.charAt(0).toUpperCase() }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="text-subtitle-2 font-weight-medium">{{ op.name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ op.email }}</div>
                  </div>
                </div>
                <v-chip size="small" variant="tonal" color="primary" class="mr-1">
                  <v-icon start size="14">mdi-clock-outline</v-icon>
                  {{ op.weekly_hours }}h/sett.
                </v-chip>
              </v-card-text>
              <v-divider></v-divider>
              <v-card-actions class="pa-2">
                <v-btn size="small" variant="text" prepend-icon="mdi-pencil" @click="openOperatorDialog(op)">
                  Modifica
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn size="small" variant="text" color="error" icon @click="confirmDeleteOperator(op)">
                  <v-icon size="18">mdi-delete-outline</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
          <v-col v-if="!operators.length" cols="12">
            <v-card rounded="xl" class="text-center pa-8">
              <v-icon size="48" color="medium-emphasis" class="mb-3">mdi-account-off-outline</v-icon>
              <div class="text-body-1 text-medium-emphasis">Nessun operatore registrato</div>
            </v-card>
          </v-col>
        </v-row>
      </v-tabs-window-item>

      <!-- ==================== RICHIESTE ==================== -->
      <v-tabs-window-item value="richieste">

        <div class="d-flex align-center mb-4">
          <div>
            <div class="text-subtitle-1 font-weight-medium">Richieste di indisponibilità</div>
            <div class="text-caption text-medium-emphasis">{{ requests.length }} richieste totali</div>
          </div>
          <v-spacer></v-spacer>
          <div class="d-flex gap-2">
            <v-chip
              size="small"
              :color="requestFilter === 'tutte' ? 'primary' : ''"
              :variant="requestFilter === 'tutte' ? 'tonal' : 'outlined'"
              @click="requestFilter = 'tutte'"
              style="cursor:pointer"
            >Tutte</v-chip>
            <v-chip
              size="small"
              :color="requestFilter === 'in attesa' ? 'warning' : ''"
              :variant="requestFilter === 'in attesa' ? 'tonal' : 'outlined'"
              @click="requestFilter = 'in attesa'"
              style="cursor:pointer"
            >In attesa <span v-if="pendingCount > 0" class="ml-1">({{ pendingCount }})</span></v-chip>
            <v-chip
              size="small"
              :color="requestFilter === 'approvata' ? 'success' : ''"
              :variant="requestFilter === 'approvata' ? 'tonal' : 'outlined'"
              @click="requestFilter = 'approvata'"
              style="cursor:pointer"
            >Approvate</v-chip>
            <v-chip
              size="small"
              :color="requestFilter === 'rifiutata' ? 'error' : ''"
              :variant="requestFilter === 'rifiutata' ? 'tonal' : 'outlined'"
              @click="requestFilter = 'rifiutata'"
              style="cursor:pointer"
            >Rifiutate</v-chip>
          </div>
        </div>

        <v-card rounded="xl">
          <v-table>
            <thead>
              <tr>
                <th>Operatore</th>
                <th>Data</th>
                <th>Preferenza</th>
                <th>Note</th>
                <th>Stato</th>
                <th class="text-center">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in filteredRequests" :key="req.id">
                <td>
                  <div class="d-flex align-center">
                    <v-avatar :color="getOperatorColor(req.user_id)" size="28" class="mr-2">
                      <span class="text-caption font-weight-bold" style="color:white">
                        {{ req.user?.name?.charAt(0)?.toUpperCase() }}
                      </span>
                    </v-avatar>
                    <span class="text-body-2">{{ req.user?.name }}</span>
                  </div>
                </td>
                <td class="text-body-2">{{ new Date(String(req.date).substring(0,10)).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' }) }}</td>
                <td class="text-body-2">{{ req.preference }}</td>
                <td class="text-body-2 text-medium-emphasis">{{ req.note || '—' }}</td>
                <td>
                  <v-chip
                    size="small"
                    :color="req.status === 'approvata' ? 'success' : req.status === 'rifiutata' ? 'error' : 'warning'"
                    variant="tonal"
                  >{{ req.status }}</v-chip>
                </td>
                <td class="text-center">
                  <template v-if="req.status === 'in attesa'">
                    <v-btn size="small" color="success" variant="tonal" rounded="lg" class="mr-1" @click="updateRequestStatus(req, 'approvata')">
                      <v-icon start size="16">mdi-check</v-icon>Approva
                    </v-btn>
                    <v-btn size="small" color="error" variant="tonal" rounded="lg" @click="updateRequestStatus(req, 'rifiutata')">
                      <v-icon start size="16">mdi-close</v-icon>Rifiuta
                    </v-btn>
                  </template>
                  <span v-else class="text-caption text-medium-emphasis">—</span>
                </td>
              </tr>
              <tr v-if="!filteredRequests.length">
                <td colspan="5" class="text-center text-medium-emphasis pa-6">
                  Nessuna richiesta
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>

      </v-tabs-window-item>

    </v-tabs-window>

    <!-- ==================== DIALOG TURNO ==================== -->
    <v-dialog v-model="shiftDialog" max-width="420" rounded="xl">
      <v-card rounded="xl">
        <v-card-title class="pa-5 pb-2">
          {{ editingShift.id || editingShift.absence_id ? (editingShift.mode === 'assenza' ? 'Modifica assenza' : 'Modifica turno') : 'Nuovo turno / assenza' }}
        </v-card-title>
        <v-card-text class="px-5 pb-2">
          <div class="d-flex align-center mb-4">
            <v-avatar :color="editingShiftOperator?.color || 'primary'" size="32" class="mr-2">
              <span class="text-body-2 font-weight-bold" style="color:white">
                {{ editingShiftOperator?.name?.charAt(0)?.toUpperCase() }}
              </span>
            </v-avatar>
            <div>
              <div class="text-subtitle-2">{{ editingShiftOperator?.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ editingShiftDate }}</div>
            </div>
          </div>
          <v-btn-toggle v-model="editingShift.mode" mandatory density="compact" rounded="lg" class="mb-4 w-100" color="primary">
            <v-btn value="turno" size="small" class="flex-grow-1">
              <v-icon start size="15">mdi-clock-outline</v-icon>Turno
            </v-btn>
            <v-btn value="assenza" size="small" class="flex-grow-1">
              <v-icon start size="15">mdi-calendar-remove</v-icon>Assenza
            </v-btn>
          </v-btn-toggle>

          <template v-if="editingShift.mode === 'turno'">
            <v-row dense>
              <v-col cols="6">
                <v-text-field label="Ora inizio" type="time" v-model="editingShift.start_time" min="06:00" max="22:00"></v-text-field>
              </v-col>
              <v-col cols="6">
                <v-text-field label="Ora fine" type="time" v-model="editingShift.end_time" :min="editingShift.start_time" :max="shiftEndMax"></v-text-field>
              </v-col>
            </v-row>
          </template>

          <template v-if="editingShift.mode === 'assenza'">
            <v-select
              label="Tipo assenza"
              :items="absenceTypes"
              item-title="label"
              item-value="value"
              v-model="editingShift.absence_type"
              prepend-inner-icon="mdi-tag-outline"
              class="mb-3"
            ></v-select>
            <v-text-field
              label="Note (opzionale)"
              v-model="editingShift.absence_note"
              prepend-inner-icon="mdi-note-text-outline"
            ></v-text-field>
          </template>

          <v-alert v-if="shiftError" type="error" density="compact" class="mt-2">{{ shiftError }}</v-alert>
        </v-card-text>
        <v-card-actions class="px-5 pb-4">
          <v-btn v-if="editingShift.id || editingShift.absence_id" color="error" variant="text" size="small" @click="deleteCurrentEntry">
            <v-icon start size="16">mdi-delete-outline</v-icon>Elimina
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="shiftDialog = false">Annulla</v-btn>
          <v-btn color="primary" elevation="0" @click="saveShift">Salva</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ==================== DIALOG OPERATORE ==================== -->
    <v-dialog v-model="operatorDialog" max-width="460" rounded="xl">
      <v-card rounded="xl">
        <v-card-title class="pa-5 pb-2">
          {{ editingOperator.id ? 'Modifica operatore' : 'Nuovo operatore' }}
        </v-card-title>
        <v-card-text class="px-5 pb-2">
          <v-text-field label="Nome" v-model="editingOperator.name" prepend-inner-icon="mdi-account-outline" class="mb-3"></v-text-field>
          <v-text-field label="Email" type="email" v-model="editingOperator.email" prepend-inner-icon="mdi-email-outline" class="mb-3"></v-text-field>
          <v-text-field
            :label="editingOperator.id ? 'Nuova password (lascia vuoto per non cambiare)' : 'Password'"
            type="password"
            v-model="editingOperator.password"
            prepend-inner-icon="mdi-lock-outline"
            class="mb-3"
          ></v-text-field>
          <v-text-field
            label="Ore settimanali"
            type="number"
            v-model.number="editingOperator.weekly_hours"
            prepend-inner-icon="mdi-clock-outline"
            class="mb-3"
          ></v-text-field>
          <div class="d-flex align-center">
            <span class="text-body-2 mr-3">Colore operatore</span>
            <input
              type="color"
              v-model="editingOperator.color"
              style="width:40px; height:36px; cursor:pointer; border:1px solid rgba(128,128,128,0.3); border-radius:8px; padding:2px; background:transparent;"
            >
            <v-chip size="small" :color="editingOperator.color" variant="tonal" class="ml-3">
              {{ editingOperator.name || 'Anteprima' }}
            </v-chip>
          </div>
          <v-alert v-if="operatorError" type="error" density="compact" class="mt-3">{{ operatorError }}</v-alert>
        </v-card-text>
        <v-card-actions class="px-5 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="operatorDialog = false">Annulla</v-btn>
          <v-btn color="primary" elevation="0" @click="saveOperator">Salva</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, watch, onMounted } from 'vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export default {
  setup() {
    const operators = ref([]);
    const shifts    = ref([]);
    const requests  = ref([]);
    const absences  = ref([]);
    const totals    = ref({ hours_per_operator: [], hours_per_day: [] });
    const currentDate = ref(new Date());
    const activeTab     = ref('turni');
    const requestFilter = ref('tutte');

    // ---- Settimana ----
    const weekDays = computed(() => {
      const today = new Date().toISOString().split('T')[0];
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
          isToday: date === today,
        };
      });
    });

    const weekDisplay = computed(() => {
      const s = weekDays.value[0];
      const e = weekDays.value[6];
      const ds = new Date(s.date);
      const de = new Date(e.date);
      const fmt = d => d.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' });
      return `${fmt(ds)} — ${fmt(de)}`;
    });

    const pendingCount = computed(() =>
      requests.value.filter(r => r.status === 'in attesa').length
    );

    const filteredRequests = computed(() =>
      requestFilter.value === 'tutte'
        ? requests.value
        : requests.value.filter(r => r.status === requestFilter.value)
    );

    const changeWeek = (dir) => {
      const d = new Date(currentDate.value);
      d.setDate(d.getDate() + 7 * dir);
      currentDate.value = d;
    };

    const goToToday = () => { currentDate.value = new Date(); };

    // ---- Fetch ----
    const fetchAll = async () => {
      const start = weekDays.value[0].date;
      const end   = weekDays.value[6].date;

      const [u, s, r, t, a] = await Promise.allSettled([
        axios.get('/api/users'),
        axios.get(`/api/shifts?start_date=${start}&end_date=${end}`),
        axios.get('/api/unavailability-requests'),
        axios.get(`/api/dashboard/totals?start_date=${start}&end_date=${end}`),
        axios.get(`/api/absences?start_date=${start}&end_date=${end}`),
      ]);

      if (u.status === 'fulfilled') operators.value = u.value.data;
      if (s.status === 'fulfilled') shifts.value    = s.value.data;
      if (r.status === 'fulfilled') requests.value  = r.value.data;
      if (t.status === 'fulfilled') totals.value    = t.value.data;
      if (a.status === 'fulfilled') absences.value  = a.value.data;

      [u, s, r, t, a].forEach((res, i) => {
        if (res.status === 'rejected') {
          console.error(`fetchAll [${i}] failed:`, res.reason?.response?.data ?? res.reason);
        }
      });
    };

    watch(currentDate, fetchAll);
    onMounted(fetchAll);

    // ---- Helper turni ----
    const getShifts = (opId, date) =>
      shifts.value.filter(s => s.user_id === opId && s.start_time.startsWith(date));

    // Estrae HH:MM direttamente dalla stringa senza conversione timezone
    const formatTime = (dt) => {
      const s = dt.replace('T', ' ').replace(/\.\d+Z?$/, '');
      return s.substring(11, 16);
    };

    const getAbsences = (opId, date) =>
      absences.value.filter(a => a.user_id === opId && String(a.date).startsWith(date));

    const getRequests = (opId, date) =>
      requests.value.filter(r => r.user_id === opId && String(r.date).startsWith(date));

    const requestStatusColor = (status) =>
      status === 'approvata' ? 'success' : status === 'rifiutata' ? 'error' : 'warning';

    const getDailyTotal = (date) =>
      totals.value.hours_per_day?.find(d => d.date === date)?.total_hours ?? 0;

    const getOperatorColor = (userId) =>
      operators.value.find(o => o.id === userId)?.color || 'primary';

    const getOperatorWeeklyHours = (userId) =>
      totals.value.hours_per_operator?.find(t => t.user_id === userId)?.total_hours ?? 0;

    // ---- Dialog turno ----
    const shiftDialog = ref(false);
    const shiftError  = ref('');
    const editingShift = ref({});
    const editingShiftOperator = ref(null);
    const editingShiftDate = ref('');

    const editingShiftAbsences = computed(() => {
      if (!editingShiftOperator.value || !editingShiftDate.value) return [];
      return getAbsences(editingShiftOperator.value.id, editingShiftDate.value);
    });

    const shiftEndMax = computed(() => {
      const t = editingShift.value.start_time;
      if (!t) return '22:00';
      const [h, m] = t.split(':').map(Number);
      const endH = Math.min(h + 6, 22);
      return `${String(endH).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    });

    const openShiftDialog = (shift, op, date) => {
      shiftError.value = '';
      editingShiftOperator.value = op;
      editingShiftDate.value = date;
      if (shift) {
        editingShift.value = {
          mode: 'turno',
          id: shift.id,
          start_time: formatTime(shift.start_time),
          end_time: formatTime(shift.end_time),
        };
      } else {
        const existingShifts = getShifts(op.id, date);
        let sh = 6, sm = 0;
        if (existingShifts.length > 0) {
          const lastEnd = formatTime(existingShifts[existingShifts.length - 1].end_time);
          [sh, sm] = lastEnd.split(':').map(Number);
          // +30 minuti di pausa dal turno precedente
          sm += 30;
          if (sm >= 60) { sh += 1; sm -= 60; }
        }
        // Clamp start al range 06:00–22:00
        if (sh < 6)  { sh = 6;  sm = 0; }
        if (sh >= 22) { sh = 22; sm = 0; }
        const defaultStart = `${String(sh).padStart(2, '0')}:${String(sm).padStart(2, '0')}`;
        // Fine = start + 6h, max 22:00
        const endH = Math.min(sh + 6, 22);
        const defaultEnd = `${String(endH).padStart(2, '0')}:${String(sm).padStart(2, '0')}`;
        editingShift.value = {
          mode: 'turno',
          start_time: defaultStart,
          end_time: defaultEnd,
          absence_type: 'ferie',
          absence_note: '',
        };
      }
      shiftDialog.value = true;
    };

    const openAbsenceChip = (abs, op, date) => {
      shiftError.value = '';
      editingShiftOperator.value = op;
      editingShiftDate.value = date;
      editingShift.value = {
        mode: 'assenza',
        absence_id: abs.id,
        absence_type: abs.type,
        absence_note: abs.note || '',
      };
      shiftDialog.value = true;
    };

    const saveShift = async () => {
      shiftError.value = '';
      try {
        if (editingShift.value.mode === 'assenza') {
          const p = {
            user_id: editingShiftOperator.value.id,
            date:    editingShiftDate.value,
            type:    editingShift.value.absence_type,
            note:    editingShift.value.absence_note || '',
          };
          editingShift.value.absence_id
            ? await axios.put(`/api/absences/${editingShift.value.absence_id}`, p)
            : await axios.post('/api/absences', p);
        } else {
          const p = {
            user_id:    editingShiftOperator.value.id,
            start_time: `${editingShiftDate.value} ${editingShift.value.start_time}`,
            end_time:   `${editingShiftDate.value} ${editingShift.value.end_time}`,
          };
          editingShift.value.id
            ? await axios.put(`/api/shifts/${editingShift.value.id}`, p)
            : await axios.post('/api/shifts', p);
        }
        shiftDialog.value = false;
        await fetchAll();
      } catch (e) {
        shiftError.value = e?.response?.data?.message || 'Errore durante il salvataggio.';
      }
    };

    const deleteCurrentEntry = async () => {
      if (!confirm('Eliminare questo elemento?')) return;
      try {
        if (editingShift.value.absence_id) {
          await axios.delete(`/api/absences/${editingShift.value.absence_id}`);
        } else {
          await axios.delete(`/api/shifts/${editingShift.value.id}`);
        }
        shiftDialog.value = false;
        await fetchAll();
      } catch (e) { console.error(e); }
    };

    // ---- Richieste indisponibilità ----
    const updateRequestStatus = async (req, status) => {
      try {
        await axios.post(`/api/unavailability-requests/${req.id}/update-status`, { status });
        await fetchAll();
      } catch (e) { console.error(e); }
    };

    // ---- Dialog operatore ----
    const operatorDialog = ref(false);
    const operatorError  = ref('');
    const editingOperator = ref({});

    const openOperatorDialog = (op = null) => {
      operatorError.value = '';
      editingOperator.value = op
        ? { ...op, password: '' }
        : { name: '', email: '', password: '', weekly_hours: 40, color: '#1A73E8' };
      operatorDialog.value = true;
    };

    const saveOperator = async () => {
      operatorError.value = '';
      try {
        editingOperator.value.id
          ? await axios.put(`/api/users/${editingOperator.value.id}`, editingOperator.value)
          : await axios.post('/api/users', editingOperator.value);
        operatorDialog.value = false;
        await fetchAll();
      } catch (e) {
        const errs = e?.response?.data?.errors;
        operatorError.value = errs
          ? Object.values(errs).flat().join(' ')
          : (e?.response?.data?.message || 'Errore durante il salvataggio.');
      }
    };

    const confirmDeleteOperator = async (op) => {
      if (!confirm(`Eliminare ${op.name}? Verranno eliminati anche turni e richieste.`)) return;
      try {
        await axios.delete(`/api/users/${op.id}`);
        await fetchAll();
      } catch (e) { console.error(e); }
    };

    // ---- Assenze ----
    const absenceTypes = [
      { label: 'Ferie',         value: 'ferie',          color: 'success', hex: '#43a047' },
      { label: 'Permesso',      value: 'permesso',        color: 'info',    hex: '#1e88e5' },
      { label: 'Compensativo',  value: 'compensativo',    color: 'warning', hex: '#fb8c00' },
      { label: 'Altra assenza', value: 'altra_assenza',   color: 'error',   hex: '#e53935' },
    ];

    const absenceTypeLabel = (t) => absenceTypes.find(a => a.value === t)?.label ?? t;
    const absenceTypeColor = (t) => absenceTypes.find(a => a.value === t)?.color ?? 'default';
    const absenceTypeHex   = (t) => absenceTypes.find(a => a.value === t)?.hex   ?? '#9e9e9e';

    // ---- Export PDF ----
    const hexToRgb = (hex) => {
      const clean = (hex || '#1A73E8').replace('#', '');
      return [
        parseInt(clean.substring(0, 2), 16),
        parseInt(clean.substring(2, 4), 16),
        parseInt(clean.substring(4, 6), 16),
      ];
    };

    const lightenRgb = ([r, g, b], amount = 0.75) => [
      Math.round(r + (255 - r) * amount),
      Math.round(g + (255 - g) * amount),
      Math.round(b + (255 - b) * amount),
    ];

    const exportPdf = () => {
      const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text('Turni settimanali', 14, 16);
      doc.setFontSize(10);
      doc.setFont('helvetica', 'normal');
      doc.text(weekDisplay.value, 14, 23);

      // Colonne: Operatore + 7 giorni + TOT.
      const head = [['Operatore', ...weekDays.value.map(d => `${d.name}\n${d.dayNum}`), 'TOT.']];

      const body = operators.value.map(op => {
        const row = [op.name];
        weekDays.value.forEach(day => {
          const dayShifts = getShifts(op.id, day.date);
          const absRow = getAbsences(op.id, day.date);
          let cell = dayShifts.length
            ? dayShifts.map(s => `${formatTime(s.start_time)}–${formatTime(s.end_time)}`).join('\n')
            : '';
          if (absRow.length) {
            const absLabel = absRow.map(a => absenceTypeLabel(a.type)).join(', ');
            cell = cell ? `${cell}\n(${absLabel})` : `(${absLabel})`;
          }
          row.push(cell);
        });
        const weeklyH = getOperatorWeeklyHours(op.id);
        const targetH = op.weekly_hours;
        row.push(`${weeklyH}h / ${targetH}h`);
        return row;
      });

      // Riga totali giornalieri
      const totalsRow = ['ORE / GIORNO', ...weekDays.value.map(d => {
        const h = getDailyTotal(d.date);
        return h > 0 ? `${h}h` : '';
      }), ''];

      autoTable(doc, {
        startY: 28,
        head,
        body: [...body, totalsRow],
        styles: { fontSize: 8, cellPadding: 3 },
        headStyles: { fillColor: [26, 115, 232], textColor: 255, fontStyle: 'bold' },
        bodyStyles: { valign: 'middle' },
        didParseCell(data) {
          if (data.section === 'body' && data.row.index < operators.value.length) {
            const op = operators.value[data.row.index];
            const rgb = hexToRgb(op.color || '#1A73E8');
            if (data.column.index === 0) {
              // Colonna operatore: colore pieno
              data.cell.styles.fillColor = rgb;
              data.cell.styles.textColor = 255;
            } else if (data.cell.raw) {
              // Celle con turni: tinta leggera del colore operatore
              data.cell.styles.fillColor = lightenRgb(rgb, 0.80);
            }
          }
          if (data.section === 'body' && data.row.index === operators.value.length) {
            data.cell.styles.fillColor = [230, 234, 241];
            data.cell.styles.fontStyle = 'bold';
          }
        },
        columnStyles: {
          0: { cellWidth: 34, fontStyle: 'bold' },
          [weekDays.value.length + 1]: { cellWidth: 22, fontStyle: 'bold' },
        },
      });

      const filename = `turni_${weekDays.value[0].date}_${weekDays.value[6].date}.pdf`;
      doc.save(filename);
    };

    return {
      activeTab,
      operators, shifts, requests, absences, totals,
      requestFilter, pendingCount, filteredRequests,
      weekDays, weekDisplay, changeWeek, goToToday,
      getShifts, getAbsences, getRequests, requestStatusColor, formatTime, getDailyTotal, getOperatorColor, getOperatorWeeklyHours,
      shiftDialog, shiftError, editingShift, editingShiftOperator, editingShiftDate, editingShiftAbsences, shiftEndMax,
      openShiftDialog, openAbsenceChip, saveShift, deleteCurrentEntry,
      updateRequestStatus,
      operatorDialog, operatorError, editingOperator,
      openOperatorDialog, saveOperator, confirmDeleteOperator,
      absenceTypes, absenceTypeLabel, absenceTypeColor, absenceTypeHex,
      exportPdf,
    };
  },
};
</script>

<style scoped>
.schedule-wrapper { overflow-x: auto; }

.operator-col { min-width: 160px; white-space: nowrap; }
.day-col      { min-width: 120px; text-align: center; padding: 8px 4px; }
.total-col    { min-width: 90px; text-align: center; white-space: nowrap; }
.cell         { padding: 4px 4px !important; height: 56px; position: relative; }

.request-icons {
  position: absolute;
  top: 3px;
  left: 4px;
  display: flex;
  gap: 2px;
}

.day-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 500;
  margin: 2px auto 0;
}

.today-badge {
  background-color: rgb(var(--v-theme-primary));
  color: white;
}

.today-col { background-color: rgba(var(--v-theme-primary), 0.05); }

.shift-chip {
  display: inline-block;
  color: white;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
  cursor: pointer;
  margin: 1px 0;
  white-space: nowrap;
  transition: filter 0.15s;
  user-select: none;
}
.shift-chip:hover { filter: brightness(0.85); }


.totals-row td { background-color: rgba(var(--v-theme-surface-variant), 0.5); }

.operator-card { transition: box-shadow 0.2s; }
.operator-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important; }
</style>
