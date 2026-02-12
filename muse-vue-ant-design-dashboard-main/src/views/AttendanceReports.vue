<template>
  <div class="attendance-reports">
    <a-card title="Reportes de Asistencia" :loading="loading">
      <!-- Filtros -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-select
              v-model="filters.userType"
              placeholder="Tipo de Usuario"
              style="width: 100%"
              @change="fetchAttendanceData"
            >
              <a-select-option value="">Todos</a-select-option>
              <a-select-option value="employee">Empleados</a-select-option>
              <a-select-option value="teacher">Docentes</a-select-option>
              <a-select-option value="student">Alumnos</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-range-picker
              v-model="filters.dateRange"
              @change="fetchAttendanceData"
              style="width: 100%"
              :placeholder="['Fecha Inicio', 'Fecha Fin']"
            />
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.status"
              placeholder="Estado de Asistencia"
              style="width: 100%"
              @change="fetchAttendanceData"
            >
              <a-select-option value="">Todos</a-select-option>
              <a-select-option value="present">Presente</a-select-option>
              <a-select-option value="absent">Ausente</a-select-option>
              <a-select-option value="late">Tardanza</a-select-option>
              <a-select-option value="excused">Justificado</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-input-search
              v-model="filters.search"
              placeholder="Buscar por nombre o documento"
              @search="fetchAttendanceData"
              style="width: 100%"
            />
          </a-col>
        </a-row>
        <a-row style="margin-top: 16px;">
          <a-col :span="24">
            <a-space>
              <a-button type="primary" @click="fetchAttendanceData" :loading="loading">
                <a-icon type="search" /> Buscar
              </a-button>
              <a-button @click="exportToExcel" :disabled="!attendanceData.length">
                <a-icon type="file-excel" /> Exportar Excel
              </a-button>
              <a-button @click="exportToPDF" :disabled="!attendanceData.length">
                <a-icon type="file-pdf" /> Exportar PDF
              </a-button>
              <a-button @click="resetFilters">
                <a-icon type="reload" /> Limpiar Filtros
              </a-button>
            </a-space>
          </a-col>
        </a-row>
      </div>

      <!-- Estadísticas Generales -->
      <div class="stats-section" v-if="attendanceData.length">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Total Registros"
              :value="statistics.total"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Presentes"
              :value="statistics.present"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Ausentes"
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
        </a-row>
      </div>

      <!-- Gráficos -->
      <div class="charts-section" v-if="attendanceData.length">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-card title="Asistencia por Tipo de Usuario" size="small">
              <div ref="userTypeChart" style="height: 300px;"></div>
            </a-card>
          </a-col>
          <a-col :span="12">
            <a-card title="Tendencia de Asistencia" size="small">
              <div ref="trendChart" style="height: 300px;"></div>
            </a-card>
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de Datos -->
      <div class="table-section">
        <a-table
          :columns="columns"
          :data-source="attendanceData"
          :loading="loading"
          :pagination="pagination"
          @change="handleTableChange"
          row-key="id"
          :scroll="{ x: 1200 }"
        >
          <!-- Columna de usuario -->
          <template slot="user" slot-scope="text, record">
            <div class="user-info">
              <a-avatar :src="record.user.avatar" :size="small">
                {{ record.user.name.charAt(0) }}
              </a-avatar>
              <div class="user-details">
                <div class="user-name">{{ record.user.name }}</div>
                <div class="user-type">{{ getUserTypeText(record.user.type) }}</div>
              </div>
            </div>
          </template>

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

          <!-- Columna de hora -->
          <template slot="time" slot-scope="text, record">
            <div v-if="record.check_in">
              <div><strong>Entrada:</strong> {{ formatTime(record.check_in) }}</div>
              <div v-if="record.check_out"><strong>Salida:</strong> {{ formatTime(record.check_out) }}</div>
            </div>
            <span v-else>-</span>
          </template>

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="viewDetails(record)">
                <a-icon type="eye" /> Detalles
              </a-button>
              <a-button 
                type="link" 
                size="small" 
                @click="editAttendance(record)"
                v-if="canEdit(record)"
              >
                <a-icon type="edit" /> Editar
              </a-button>
            </a-space>
          </template>
        </a-table>
      </div>
    </a-card>

    <!-- Modal de Detalles -->
    <a-modal
      title="Detalles de Asistencia"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="600px"
    >
      <div v-if="detailsModal.record">
        <a-descriptions :column="2" bordered>
          <a-descriptions-item label="Usuario">
            {{ detailsModal.record.user.name }}
          </a-descriptions-item>
          <a-descriptions-item label="Tipo">
            {{ getUserTypeText(detailsModal.record.user.type) }}
          </a-descriptions-item>
          <a-descriptions-item label="Fecha">
            {{ formatDate(detailsModal.record.date) }}
          </a-descriptions-item>
          <a-descriptions-item label="Estado">
            <a-tag :color="getStatusColor(detailsModal.record.status)">
              {{ getStatusText(detailsModal.record.status) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Hora Entrada">
            {{ detailsModal.record.check_in ? formatTime(detailsModal.record.check_in) : '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Hora Salida">
            {{ detailsModal.record.check_out ? formatTime(detailsModal.record.check_out) : '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Observaciones" :span="2">
            {{ detailsModal.record.notes || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Registrado por" :span="2">
            {{ detailsModal.record.registered_by ? detailsModal.record.registered_by.name : '-' }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </a-modal>

    <!-- Modal de Edición -->
    <a-modal
      title="Editar Asistencia"
      :visible="editModal.visible"
      @ok="handleEditAttendance"
      @cancel="closeEditModal"
      :confirmLoading="editModal.loading"
    >
      <a-form :form="editForm">
        <a-form-item label="Estado de Asistencia">
          <a-select
            v-decorator="['status', { 
              rules: [{ required: true, message: 'Selecciona el estado de asistencia.' }] 
            }]"
            placeholder="Selecciona estado"
          >
            <a-select-option value="present">Presente</a-select-option>
            <a-select-option value="absent">Ausente</a-select-option>
            <a-select-option value="late">Tardanza</a-select-option>
            <a-select-option value="excused">Justificado</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="Hora Entrada">
          <a-time-picker
            v-decorator="['check_in']"
            placeholder="Hora de entrada"
            format="HH:mm"
            style="width: 100%"
          />
        </a-form-item>
        <a-form-item label="Hora Salida">
          <a-time-picker
            v-decorator="['check_out']"
            placeholder="Hora de salida"
            format="HH:mm"
            style="width: 100%"
          />
        </a-form-item>
        <a-form-item label="Observaciones">
          <a-textarea
            v-decorator="['notes']"
            placeholder="Observaciones adicionales"
            :rows="3"
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'AttendanceReports',
  data() {
    return {
      loading: false,
      attendanceData: [],
      filters: {
        userType: '',
        dateRange: null,
        status: '',
        search: '',
      },
      pagination: {
        current: 1,
        pageSize: 20,
        total: 0,
      },
      statistics: {
        total: 0,
        present: 0,
        absent: 0,
        late: 0,
        excused: 0,
      },
      detailsModal: {
        visible: false,
        record: null,
      },
      editModal: {
        visible: false,
        loading: false,
        record: null,
      },
    };
  },
  computed: {
    columns() {
      return [
        {
          title: 'Usuario',
          key: 'user',
          scopedSlots: { customRender: 'user' },
          width: 200,
        },
        {
          title: 'Documento',
          dataIndex: ['user', 'document'],
          key: 'document',
          width: 120,
        },
        {
          title: 'Fecha',
          key: 'date',
          scopedSlots: { customRender: 'date' },
          width: 120,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
        {
          title: 'Horario',
          key: 'time',
          scopedSlots: { customRender: 'time' },
          width: 150,
        },
        {
          title: 'Departamento/Grado',
          dataIndex: 'department',
          key: 'department',
          width: 150,
        },
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 150,
          fixed: 'right',
        },
      ];
    },
  },
  beforeCreate() {
    this.editForm = this.$form.createForm(this, { name: 'edit_attendance_form' });
  },
  mounted() {
    this.fetchAttendanceData();
    this.initCharts();
  },
  methods: {
    fetchAttendanceData() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        user_type: this.filters.userType || undefined,
        status: this.filters.status || undefined,
        search: this.filters.search || undefined,
        date_from: this.filters.dateRange?.[0]?.format('YYYY-MM-DD'),
        date_to: this.filters.dateRange?.[1]?.format('YYYY-MM-DD'),
      };

      axios.get('/api/attendance-reports', { params })
        .then(response => {
          this.attendanceData = response.data.data || [];
          this.pagination.total = response.data.total || 0;
          this.calculateStatistics();
          this.updateCharts();
        })
        .catch(error => {
          this.$message.error('Error al cargar los datos de asistencia');
          console.error('Error fetching attendance data:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchAttendanceData();
    },
    calculateStatistics() {
      this.statistics = {
        total: this.attendanceData.length,
        present: this.attendanceData.filter(item => item.status === 'present').length,
        absent: this.attendanceData.filter(item => item.status === 'absent').length,
        late: this.attendanceData.filter(item => item.status === 'late').length,
        excused: this.attendanceData.filter(item => item.status === 'excused').length,
      };
    },
    initCharts() {
      // Inicialización de gráficos (requiere chart.js o similar)
      this.$nextTick(() => {
        this.updateCharts();
      });
    },
    updateCharts() {
      // Implementación de gráficos
      // Se puede usar Chart.js, ECharts, o similar
    },
    viewDetails(record) {
      this.detailsModal.record = record;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.record = null;
    },
    editAttendance(record) {
      this.editModal.record = record;
      this.editModal.visible = true;
      
      this.$nextTick(() => {
        this.editForm.setFieldsValue({
          status: record.status,
          check_in: record.check_in ? moment(record.check_in, 'HH:mm') : null,
          check_out: record.check_out ? moment(record.check_out, 'HH:mm') : null,
          notes: record.notes,
        });
      });
    },
    closeEditModal() {
      this.editModal.visible = false;
      this.editModal.record = null;
      this.editModal.loading = false;
      this.editForm.resetFields();
    },
    handleEditAttendance() {
      this.editForm.validateFields((err, values) => {
        if (!err) {
          this.editModal.loading = true;
          
          const data = {
            status: values.status,
            check_in: values.check_in ? values.check_in.format('HH:mm') : null,
            check_out: values.check_out ? values.check_out.format('HH:mm') : null,
            notes: values.notes,
          };

          axios.put(`/api/attendance/${this.editModal.record.id}`, data)
            .then(() => {
              this.$message.success('Asistencia actualizada correctamente');
              this.closeEditModal();
              this.fetchAttendanceData();
            })
            .catch(error => {
              this.$message.error('Error al actualizar la asistencia');
              console.error('Error updating attendance:', error);
            })
            .finally(() => {
              this.editModal.loading = false;
            });
        }
      });
    },
    canEdit(record) {
      // Lógica de permisos para editar
      return true; // Implementar según roles
    },
    exportToExcel() {
      // Implementación de exportación a Excel
      this.$message.info('Exportación a Excel en desarrollo');
    },
    exportToPDF() {
      // Implementación de exportación a PDF
      this.$message.info('Exportación a PDF en desarrollo');
    },
    resetFilters() {
      this.filters = {
        userType: '',
        dateRange: null,
        status: '',
        search: '',
      };
      this.pagination.current = 1;
      this.fetchAttendanceData();
    },
    getUserTypeText(type) {
      const types = {
        employee: 'Empleado',
        teacher: 'Docente',
        student: 'Alumno',
      };
      return types[type] || type;
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
.attendance-reports {
  padding: 24px;
}

.filters-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.stats-section {
  margin: 24px 0;
  padding: 16px;
  background: #f0f2f5;
  border-radius: 8px;
}

.charts-section {
  margin: 24px 0;
}

.table-section {
  margin-top: 24px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 500;
  font-size: 14px;
}

.user-type {
  font-size: 12px;
  color: #666;
}
</style>
