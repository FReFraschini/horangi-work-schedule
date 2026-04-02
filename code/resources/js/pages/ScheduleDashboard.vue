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
      <v-tab value="assenze" prepend-icon="mdi-calendar-remove">Assenze</v-tab>
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
                    <v-btn
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

      <!-- ==================== ASSENZE ==================== -->
      <v-tabs-window-item value="assenze">
        <div class="d-flex align-center flex-wrap gap-2 mb-4">
          <v-btn icon variant="text" @click="changeWeek(-1)"><v-icon>mdi-chevron-left</v-icon></v-btn>
          <span class="text-subtitle-1 font-weight-medium">{{ weekDisplay }}</span>
          <v-btn icon variant="text" @click="changeWeek(1)"><v-icon>mdi-chevron-right</v-icon></v-btn>
          <v-spacer></v-spacer>
          <v-btn color="primary" prepend-icon="mdi-plus" elevation="0" @click="openAbsenceDialog()">
            Nuova assenza
          </v-btn>
        </div>

        <v-card rounded="xl">
          <v-table>
            <thead>
              <tr>
                <th>Operatore</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Note</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="abs in absences" :key="abs.id">
                <td>
                  <div class="d-flex align-center">
                    <v-avatar :color="getOperatorColor(abs.user_id)" size="24" class="mr-2">
                      <span class="text-caption" style="color:white; font-size:10px;">{{ abs.user?.name?.charAt(0)?.toUpperCase() }}</span>
                    </v-avatar>
                    {{ abs.user?.name }}
                  </div>
                </td>
                <td class="text-body-2">{{ abs.date }}</td>
                <td>
                  <v-chip size="small" :color="absenceTypeColor(abs.type)" variant="tonal">
                    {{ absenceTypeLabel(abs.type) }}
                  </v-chip>
                </td>
                <td class="text-body-2 text-medium-emphasis">{{ abs.note || '—' }}</td>
                <td>
                  <v-btn icon size="small" variant="text" color="error" @click="confirmDeleteAbsence(abs)">
                    <v-icon size="18">mdi-delete-outline</v-icon>
                  </v-btn>
                </td>
              </tr>
              <tr v-if="!absences.length">
                <td colspan="5" class="text-center text-medium-emphasis pa-6">
                  Nessuna assenza registrata per questa settimana
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
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
                <td class="text-body-2">{{ req.date }}</td>
                <td class="text-body-2">{{ req.preference }}</td>
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
          {{ editingShift.id ? 'Modifica turno' : 'Nuovo turno' }}
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
          <v-row dense>
            <v-col cols="6">
              <v-text-field label="Ora inizio" type="time" v-model="editingShift.start_time"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field label="Ora fine" type="time" v-model="editingShift.end_time"></v-text-field>
            </v-col>
          </v-row>
          <v-alert v-if="shiftError" type="error" density="compact" class="mt-2">{{ shiftError }}</v-alert>
        </v-card-text>
        <v-card-actions class="px-5 pb-4">
          <v-btn v-if="editingShift.id" color="error" variant="text" size="small" @click="deleteShift">
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

    <!-- ==================== DIALOG ASSENZA ==================== -->
    <v-dialog v-model="absenceDialog" max-width="420" rounded="xl">
      <v-card rounded="xl">
        <v-card-title class="pa-5 pb-2">Nuova assenza</v-card-title>
        <v-card-text class="px-5 pb-2">
          <v-select
            label="Operatore"
            :items="operators"
            item-title="name"
            item-value="id"
            v-model="editingAbsence.user_id"
            prepend-inner-icon="mdi-account-outline"
            class="mb-3"
          ></v-select>
          <v-text-field label="Data" type="date" v-model="editingAbsence.date" prepend-inner-icon="mdi-calendar" class="mb-3"></v-text-field>
          <v-select
            label="Tipo assenza"
            :items="absenceTypes"
            item-title="label"
            item-value="value"
            v-model="editingAbsence.type"
            prepend-inner-icon="mdi-tag-outline"
            class="mb-3"
          ></v-select>
          <v-text-field label="Note (opzionale)" v-model="editingAbsence.note" prepend-inner-icon="mdi-note-text-outline"></v-text-field>
          <v-alert v-if="absenceError" type="error" density="compact" class="mt-3">{{ absenceError }}</v-alert>
        </v-card-text>
        <v-card-actions class="px-5 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="absenceDialog = false">Annulla</v-btn>
          <v-btn color="primary" elevation="0" @click="saveAbsence">Salva</v-btn>
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

    const formatTime = (dt) =>
      new Date(dt).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

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

    const openShiftDialog = (shift, op, date) => {
      shiftError.value = '';
      editingShiftOperator.value = op;
      editingShiftDate.value = date;
      editingShift.value = shift
        ? { id: shift.id, start_time: formatTime(shift.start_time), end_time: formatTime(shift.end_time) }
        : { start_time: '08:00', end_time: '16:00' };
      shiftDialog.value = true;
    };

    const saveShift = async () => {
      shiftError.value = '';
      const p = {
        user_id:    editingShiftOperator.value.id,
        start_time: `${editingShiftDate.value} ${editingShift.value.start_time}`,
        end_time:   `${editingShiftDate.value} ${editingShift.value.end_time}`,
      };
      try {
        editingShift.value.id
          ? await axios.put(`/api/shifts/${editingShift.value.id}`, p)
          : await axios.post('/api/shifts', p);
        shiftDialog.value = false;
        await fetchAll();
      } catch (e) {
        shiftError.value = e?.response?.data?.message || 'Errore durante il salvataggio.';
      }
    };

    const deleteShift = async () => {
      if (!confirm('Eliminare questo turno?')) return;
      try {
        await axios.delete(`/api/shifts/${editingShift.value.id}`);
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
    const absenceDialog = ref(false);
    const absenceError  = ref('');
    const editingAbsence = ref({});

    const absenceTypes = [
      { label: 'Ferie',         value: 'ferie',          color: 'success' },
      { label: 'Permesso',      value: 'permesso',        color: 'info'    },
      { label: 'Compensativo',  value: 'compensativo',    color: 'warning' },
      { label: 'Altra assenza', value: 'altra_assenza',   color: 'error'   },
    ];

    const absenceTypeLabel = (t) => absenceTypes.find(a => a.value === t)?.label ?? t;
    const absenceTypeColor = (t) => absenceTypes.find(a => a.value === t)?.color ?? 'default';

    const openAbsenceDialog = () => {
      absenceError.value = '';
      editingAbsence.value = {
        user_id: operators.value[0]?.id ?? null,
        date:    weekDays.value[0].date,
        type:    'ferie',
        note:    '',
      };
      absenceDialog.value = true;
    };

    const saveAbsence = async () => {
      absenceError.value = '';
      try {
        await axios.post('/api/absences', editingAbsence.value);
        absenceDialog.value = false;
        await fetchAll();
      } catch (e) {
        const errs = e?.response?.data?.errors;
        absenceError.value = errs
          ? Object.values(errs).flat().join(' ')
          : (e?.response?.data?.message || 'Errore durante il salvataggio.');
      }
    };

    // ---- Export PDF ----
    const exportPdf = () => {
      const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

      // Intestazione
      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text('Turni settimanali', 14, 16);
      doc.setFontSize(10);
      doc.setFont('helvetica', 'normal');
      doc.text(weekDisplay.value, 14, 23);

      // Colonne: Operatore + 7 giorni
      const head = [['Operatore', ...weekDays.value.map(d => `${d.name}\n${d.dayNum}`)]];

      const body = operators.value.map(op => {
        const row = [op.name];
        weekDays.value.forEach(day => {
          const dayShifts = getShifts(op.id, day.date);
          row.push(dayShifts.length
            ? dayShifts.map(s => `${formatTime(s.start_time)}–${formatTime(s.end_time)}`).join('\n')
            : ''
          );
        });
        return row;
      });

      // Riga totali
      const totalsRow = ['ORE / GIORNO', ...weekDays.value.map(d => {
        const h = getDailyTotal(d.date);
        return h > 0 ? `${h}h` : '';
      })];

      autoTable(doc, {
        startY: 28,
        head,
        body: [...body, totalsRow],
        styles: { fontSize: 8, cellPadding: 3 },
        headStyles: { fillColor: [26, 115, 232], textColor: 255, fontStyle: 'bold' },
        bodyStyles: { valign: 'middle' },
        didParseCell(data) {
          if (data.row.index === body.length) {
            data.cell.styles.fillColor = [230, 234, 241];
            data.cell.styles.fontStyle = 'bold';
          }
        },
        columnStyles: { 0: { cellWidth: 36, fontStyle: 'bold' } },
      });

      const filename = `turni_${weekDays.value[0].date}_${weekDays.value[6].date}.pdf`;
      doc.save(filename);
    };

    const confirmDeleteAbsence = async (abs) => {
      if (!confirm('Eliminare questa assenza?')) return;
      try {
        await axios.delete(`/api/absences/${abs.id}`);
        await fetchAll();
      } catch (e) { console.error(e); }
    };

    return {
      activeTab,
      operators, shifts, requests, absences, totals,
      requestFilter, pendingCount, filteredRequests,
      weekDays, weekDisplay, changeWeek, goToToday,
      getShifts, formatTime, getDailyTotal, getOperatorColor, getOperatorWeeklyHours,
      shiftDialog, shiftError, editingShift, editingShiftOperator, editingShiftDate,
      openShiftDialog, saveShift, deleteShift,
      updateRequestStatus,
      operatorDialog, operatorError, editingOperator,
      openOperatorDialog, saveOperator, confirmDeleteOperator,
      absenceDialog, absenceError, editingAbsence, absenceTypes,
      absenceTypeLabel, absenceTypeColor, openAbsenceDialog, saveAbsence, confirmDeleteAbsence,
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
.cell         { padding: 4px 4px !important; }

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
