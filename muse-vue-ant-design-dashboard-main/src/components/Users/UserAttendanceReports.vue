<template>
  <div class="user-attendance-reports">
    <a-card title="Reportes de Asistencia" size="small">
      <!-- Filtros -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-range-picker
              v-model="filters.dateRange"
              @change="fetchUserAttendance"
              style="width: 100%"
              :placeholder="['Fecha Inicio', 'Fecha Fin']"
            />
          </a-col>
          <a-col :span="8">
            <a-select
              v-model="filters.status"
              placeholder="Estado"
              style="width: 100%"
              @change="fetchUserAttendance"
              allowClear
            >
              <a-select-option value="present">Presente</a-select-option>
              <a-select-option value="absent">Ausente</a-select-option>
              <a-select-option value="late">Tardanza</a-select-option>
              <a-select-option value="excused">Justificado</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="8">
            <a-button type="primary" @click="fetchUserAttendance" :loading="loading">
              <a-icon type="search" /> Buscar
            </a-button>
            <a-button @click="exportAttendance" style="margin-left: 8px;" :disabled="!attendanceData.length">
              <a-icon type="download" /> Exportar
            </a-button>
          </a-col>
        </a-row>
      </div>

      <!-- Estadísticas -->
      <div class="stats-section" v-if="attendanceData.length">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Días Asistidos"
              :value="statistics.present"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Ausencias"
              :value="statistics.absent"
              :value-style="{ color: '#f5222d' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Tardanzas"
              :value="statistics.late"
              :value-style="{ color: '#fa8c16' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Asistencia %"
              :value="attendancePercentage"
              suffix="%"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de Asistencia -->
      <a-table
        :columns="columns"
        :data-source="attendanceData"
        :loading="loading"
        :pagination="pagination"
        @change="handleTableChange"
        size="small"
        row-key="id"
      >
        <!-- Columna de estado -->
        <template slot="status" slot-scope="text, record">
          <a-tag :color="getStatusColor(record.status)">
            {{ getStatusText(record.status) }}
          </a-tag>
        </template>

        <!-- Columna de fecha -->
        <template slot="date" slot-scope="text, record">
          {{ formatDate(record.date) }}
        </template>

        <!-- Columna de horario -->
        <template slot="schedule" slot-scope="text, record">
          <div v-if="record.check_in">
            <div><strong>Entrada:</strong> {{ formatTime(record.check_in) }}</div>
            <div v-if="record.check_out"><strong>Salida:</strong> {{ formatTime(record.check_out) }}</div>
          </div>
          <span v-else>-</span>
        </template>

        <!-- Columna de acciones -->
        <template slot="actions" slot-scope="text, record">
          <a-button type="link" size="small" @click="viewDetails(record)">
            <a-icon type="eye" /> Ver
          </a-button>
        </template>
      </a-table>
    </a-card>

    <!-- Modal de Detalles -->
    <a-modal
      title="Detalles de Asistencia"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="500px"
    >
      <div v-if="detailsModal.record">
        <a-descriptions :column="2" bordered size="small">
          <a-descriptions-item label="Fecha">
            {{ formatDate(detailsModal.record.date) }}
          </a-descriptions-item>
          <a-descriptions-item label="Estado">
            <a-tag :color="getStatusColor(detailsModal.record.status)">
              {{ getStatusText(detailsModal.record.status) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Entrada">
            {{ detailsModal.record.check_in ? formatTime(detailsModal.record.check_in) : '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Salida">
            {{ detailsModal.record.check_out ? formatTime(detailsModal.record.check_out) : '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Horas Trabajadas" :span="2">
            {{ detailsModal.record.worked_hours || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Observaciones" :span="2">
            {{ detailsModal.record.notes || '-' }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'UserAttendanceReports',
  props: {
    userId: {
      type: [String, Number],
      required: true,
    },
    userType: {
      type: String,
      default: 'student', // student, teacher, employee
    },
  },
  data() {
    return {
      loading: false,
      attendanceData: [],
      filters: {
        dateRange: null,
        status: '',
      },
      pagination: {
        current: 1,
        pageSize: 10,
        total: 0,
      },
      statistics: {
        present: 0,
        absent: 0,
        late: 0,
        excused: 0,
      },
      detailsModal: {
        visible: false,
        record: null,
      },
    };
  },
  computed: {
    columns() {
      return [
        {
          title: 'Fecha',
          key: 'date',
          scopedSlots: { customRender: 'date' },
          width: 100,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
        {
          title: 'Horario',
          key: 'schedule',
          scopedSlots: { customRender: 'schedule' },
          width: 150,
        },
        {
          title: 'Departamento/Grado',
          dataIndex: 'department',
          key: 'department',
          width: 120,
        },
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 80,
        },
      ];
    },
    attendancePercentage() {
      if (!this.attendanceData.length) return 0;
      const present = this.statistics.present + this.statistics.late;
      return Math.round((present / this.attendanceData.length) * 100);
    },
  },
  mounted() {
    // Establecer rango de fechas por defecto (últimos 30 días)
    this.filters.dateRange = [
      moment().subtract(30, 'days'),
      moment(),
    ];
    this.fetchUserAttendance();
  },
  methods: {
    fetchUserAttendance() {
      this.loading = true;
      const params = {
        user_id: this.userId,
        user_type: this.userType,
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        status: this.filters.status || undefined,
        date_from: this.filters.dateRange?.[0]?.format('YYYY-MM-DD'),
        date_to: this.filters.dateRange?.[1]?.format('YYYY-MM-DD'),
      };

      axios.get('/api/user-attendance', { params })
        .then(response => {
          this.attendanceData = response.data.data || [];
          this.pagination.total = response.data.total || 0;
          this.calculateStatistics();
        })
        .catch(error => {
          this.$message.error('Error al cargar los datos de asistencia');
          console.error('Error fetching user attendance:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchUserAttendance();
    },
    calculateStatistics() {
      this.statistics = {
        present: this.attendanceData.filter(item => item.status === 'present').length,
        absent: this.attendanceData.filter(item => item.status === 'absent').length,
        late: this.attendanceData.filter(item => item.status === 'late').length,
        excused: this.attendanceData.filter(item => item.status === 'excused').length,
      };
    },
    viewDetails(record) {
      this.detailsModal.record = record;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.record = null;
    },
    exportAttendance() {
      const params = {
        user_id: this.userId,
        user_type: this.userType,
        date_from: this.filters.dateRange?.[0]?.format('YYYY-MM-DD'),
        date_to: this.filters.dateRange?.[1]?.format('YYYY-MM-DD'),
        format: 'excel',
      };

      axios.get('/api/user-attendance/export', { 
        params,
        responseType: 'blob',
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `asistencia_${this.userId}_${moment().format('YYYY-MM-DD')}.xlsx`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          this.$message.error('Error al exportar los datos');
          console.error('Error exporting attendance:', error);
        });
    },
    getStatusText(status) {
      const texts = {
        present: 'Presente',
        absent: 'Ausente',
        late: 'Tardanza',
        excused: 'Justificado',
      };
      return texts[status] || status;
    },
    getStatusColor(status) {
      const colors = {
        present: 'green',
        absent: 'red',
        late: 'orange',
        excused: 'blue',
      };
      return colors[status] || 'default';
    },
    formatDate(dateString) {
      return moment(dateString).format('DD/MM/YYYY');
    },
    formatTime(timeString) {
      return moment(timeString, 'HH:mm:ss').format('HH:mm');
    },
  },
};
</script>

<style scoped>
.user-attendance-reports {
  margin-top: 16px;
}

.filters-section {
  margin-bottom: 16px;
  padding: 12px;
  background: #fafafa;
  border-radius: 6px;
}

.stats-section {
  margin: 16px 0;
  padding: 12px;
  background: #f0f2f5;
  border-radius: 6px;
}
</style>
