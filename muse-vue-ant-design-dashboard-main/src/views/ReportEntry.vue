<template>
  <div class="report-entry">
    <a-card title="Ingreso de Reportes" :loading="loading">
      <!-- Formulario de Ingreso -->
      <div class="entry-form-section">
        <a-form :form="reportForm" @submit.prevent="handleSubmitReport">
          <a-row :gutter="16">
            <a-col :span="8">
              <a-form-item label="Tipo de Reporte">
                <a-select
                  v-decorator="['reportType', { 
                    rules: [{ required: true, message: 'Selecciona el tipo de reporte.' }] 
                  }]"
                  placeholder="Selecciona tipo"
                  @change="handleTypeChange"
                >
                  <a-select-option value="attendance">Asistencia</a-select-option>
                  <a-select-option value="academic">Rendimiento Académico</a-select-option>
                  <a-select-option value="behavior">Comportamiento</a-select-option>
                  <a-select-option value="incident">Incidente</a-select-option>
                  <a-select-option value="other">Otro</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Persona">
                <a-select
                  v-decorator="['personId', { 
                    rules: [{ required: true, message: 'Selecciona la persona.' }] 
                  }]"
                  placeholder="Selecciona persona"
                  show-search
                  :filter-option="filterOption"
                >
                  <a-select-option 
                    v-for="person in peopleList" 
                    :key="person.id"
                    :value="person.id"
                  >
                    {{ person.name }} - {{ getPersonTypeText(person.type) }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Fecha del Reporte">
                <a-date-picker
                  v-decorator="['reportDate', { 
                    rules: [{ required: true, message: 'Selecciona la fecha.' }] 
                  }]"
                  placeholder="Selecciona fecha"
                  style="width: 100%"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <!-- Campos dinámicos según tipo de reporte -->
          <div v-if="reportType === 'attendance'">
            <a-row :gutter="16">
              <a-col :span="6">
                <a-form-item label="Estado">
                  <a-select
                    v-decorator="['attendance.status', { 
                      rules: [{ required: true, message: 'Selecciona el estado.' }] 
                    }]"
                    placeholder="Estado"
                  >
                    <a-select-option value="present">Presente</a-select-option>
                    <a-select-option value="absent">Ausente</a-select-option>
                    <a-select-option value="late">Tardanza</a-select-option>
                    <a-select-option value="excused">Justificado</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item label="Hora Entrada">
                  <a-time-picker
                    v-decorator="['attendance.checkIn']"
                    placeholder="Hora entrada"
                    format="HH:mm"
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item label="Hora Salida">
                  <a-time-picker
                    v-decorator="['attendance.checkOut']"
                    placeholder="Hora salida"
                    format="HH:mm"
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item label="Departamento/Grado">
                  <a-input
                    v-decorator="['attendance.department']"
                    placeholder="Ej: 1er Grado"
                  />
                </a-form-item>
              </a-col>
            </a-row>
          </div>

          <div v-if="reportType === 'academic'">
            <a-row :gutter="16">
              <a-col :span="8">
                <a-form-item label="Asignatura">
                  <a-select
                    v-decorator="['academic.subject', { 
                      rules: [{ required: true, message: 'Selecciona la asignatura.' }] 
                    }]"
                    placeholder="Asignatura"
                  >
                    <a-select-option value="math">Matemáticas</a-select-option>
                    <a-select-option value="science">Ciencias</a-select-option>
                    <a-select-option value="language">Lenguaje</a-select-option>
                    <a-select-option value="history">Historia</a-select-option>
                    <a-select-option value="english">Inglés</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="Calificación">
                  <a-input-number
                    v-decorator="['academic.grade', { 
                      rules: [{ required: true, message: 'Ingresa la calificación.' }] 
                    }]"
                    placeholder="0-10"
                    :min="0"
                    :max="10"
                    :precision="1"
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="Período">
                  <a-select
                    v-decorator="['academic.period']"
                    placeholder="Período"
                  >
                    <a-select-option value="1">Primer Período</a-select-option>
                    <a-select-option value="2">Segundo Período</a-select-option>
                    <a-select-option value="3">Tercer Período</a-select-option>
                    <a-select-option value="4">Cuarto Período</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
            </a-row>
          </div>

          <div v-if="reportType === 'behavior'">
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="Tipo de Comportamiento">
                  <a-select
                    v-decorator="['behavior.type', { 
                      rules: [{ required: true, message: 'Selecciona el tipo.' }] 
                    }]"
                    placeholder="Tipo"
                  >
                    <a-select-option value="positive">Positivo</a-select-option>
                    <a-select-option value="negative">Negativo</a-select-option>
                    <a-select-option value="neutral">Neutral</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="Gravedad">
                  <a-select
                    v-decorator="['behavior.severity']"
                    placeholder="Gravedad"
                  >
                    <a-select-option value="low">Baja</a-select-option>
                    <a-select-option value="medium">Media</a-select-option>
                    <a-select-option value="high">Alta</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
            </a-row>
          </div>

          <div v-if="reportType === 'incident'">
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="Tipo de Incidente">
                  <a-select
                    v-decorator="['incident.type', { 
                      rules: [{ required: true, message: 'Selecciona el tipo.' }] 
                    }]"
                    placeholder="Tipo"
                  >
                    <a-select-option value="accident">Accidente</a-select-option>
                    <a-select-option value="conflict">Conflicto</a-select-option>
                    <a-select-option value="violation">Violación de reglas</a-select-option>
                    <a-select-option value="other">Otro</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="Lugar">
                  <a-input
                    v-decorator="['incident.location']"
                    placeholder="Lugar del incidente"
                  />
                </a-form-item>
              </a-col>
            </a-row>
          </div>

          <!-- Campos comunes -->
          <a-row :gutter="16">
            <a-col :span="24">
              <a-form-item label="Descripción / Observaciones">
                <a-textarea
                  v-decorator="['description', { 
                    rules: [{ required: true, message: 'Ingresa una descripción.' }] 
                  }]"
                  placeholder="Describe los detalles del reporte..."
                  :rows="4"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :span="12">
              <a-form-item label="Reportado por">
                <a-input
                  v-decorator="['reportedBy']"
                  placeholder="Nombre del reportador"
                  :disabled="true"
                  :value="currentUser.name"
                />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Archivos Adjuntos">
                <a-upload
                  v-decorator="['attachments']"
                  name="files"
                  :multiple="true"
                  :before-upload="beforeUpload"
                  :file-list="fileList"
                  @change="handleFileChange"
                >
                  <a-button>
                    <a-icon type="upload" /> Adjuntar archivos
                  </a-button>
                </a-upload>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row>
            <a-col :span="24">
              <a-form-item>
                <a-space>
                  <a-button type="primary" html-type="submit" :loading="submitting">
                    <a-icon type="save" /> Guardar Reporte
                  </a-button>
                  <a-button @click="resetForm">
                    <a-icon type="redo" /> Limpiar Formulario
                  </a-button>
                </a-space>
              </a-form-item>
            </a-col>
          </a-row>
        </a-form>
      </div>

      <!-- Reportes Recientes -->
      <div class="recent-reports-section" style="margin-top: 32px;">
        <h3>Reportes Recientes</h3>
        <a-table
          :columns="recentColumns"
          :data-source="recentReports"
          :loading="loadingRecent"
          :pagination="false"
          size="small"
          row-key="id"
        >
          <!-- Columna de tipo -->
          <template slot="type" slot-scope="text, record">
            <a-tag :color="getReportTypeColor(record.type)">
              {{ getReportTypeText(record.type) }}
            </a-tag>
          </template>

          <!-- Columna de persona -->
          <template slot="person" slot-scope="text, record">
            {{ record.person.name }}
          </template>

          <!-- Columna de fecha -->
          <template slot="date" slot-scope="text, record">
            {{ formatDate(record.date) }}
          </template>

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="viewReport(record)">
                <a-icon type="eye" /> Ver
              </a-button>
              <a-button type="link" size="small" @click="editReport(record)">
                <a-icon type="edit" /> Editar
              </a-button>
              <a-popconfirm
                title="¿Estás seguro de eliminar este reporte?"
                @confirm="deleteReport(record)"
                ok-text="Sí"
                cancel-text="No"
              >
                <a-button type="link" size="small" style="color: #f5222d;">
                  <a-icon type="delete" /> Eliminar
                </a-button>
              </a-popconfirm>
            </a-space>
          </template>
        </a-table>
      </div>
    </a-card>

    <!-- Modal de Detalles -->
    <a-modal
      title="Detalles del Reporte"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="600px"
    >
      <div v-if="detailsModal.record">
        <a-descriptions :column="2" bordered size="small">
          <a-descriptions-item label="Tipo">
            <a-tag :color="getReportTypeColor(detailsModal.record.type)">
              {{ getReportTypeText(detailsModal.record.type) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Persona">
            {{ detailsModal.record.person.name }}
          </a-descriptions-item>
          <a-descriptions-item label="Fecha">
            {{ formatDate(detailsModal.record.date) }}
          </a-descriptions-item>
          <a-descriptions-item label="Reportado por">
            {{ detailsModal.record.reportedBy }}
          </a-descriptions-item>
          <a-descriptions-item label="Descripción" :span="2">
            {{ detailsModal.record.description }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import { getUser } from '@/utils/auth';

export default {
  name: 'ReportEntry',
  data() {
    return {
      loading: false,
      submitting: false,
      loadingRecent: false,
      reportType: '',
      peopleList: [],
      recentReports: [],
      fileList: [],
      currentUser: getUser() || {},
      detailsModal: {
        visible: false,
        record: null,
      },
    };
  },
  beforeCreate() {
    this.reportForm = this.$form.createForm(this, { name: 'report_entry_form' });
  },
  mounted() {
    this.fetchPeopleList();
    this.fetchRecentReports();
  },
  methods: {
    fetchPeopleList() {
      this.loading = true;
      axios.get('/api/people')
        .then(response => {
          this.peopleList = response.data || [];
        })
        .catch(error => {
          this.$message.error('Error al cargar la lista de personas');
          console.error('Error fetching people:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchRecentReports() {
      this.loadingRecent = true;
      axios.get('/api/reports/recent')
        .then(response => {
          this.recentReports = response.data || [];
        })
        .catch(error => {
          this.$message.error('Error al cargar los reportes recientes');
          console.error('Error fetching recent reports:', error);
        })
        .finally(() => {
          this.loadingRecent = false;
        });
    },
    handleTypeChange(value) {
      this.reportType = value;
    },
    handleSubmitReport() {
      this.reportForm.validateFields((err, values) => {
        if (!err) {
          this.submitting = true;
          
          const formData = new FormData();
          formData.append('reportType', values.reportType);
          formData.append('personId', values.personId);
          formData.append('reportDate', values.reportDate.format('YYYY-MM-DD'));
          formData.append('description', values.description);
          formData.append('reportedBy', this.currentUser.name || 'Anónimo');
          
          // Agregar campos específicos según tipo
          if (values.reportType === 'attendance') {
            formData.append('data', JSON.stringify(values.attendance || {}));
          } else if (values.reportType === 'academic') {
            formData.append('data', JSON.stringify(values.academic || {}));
          } else if (values.reportType === 'behavior') {
            formData.append('data', JSON.stringify(values.behavior || {}));
          } else if (values.reportType === 'incident') {
            formData.append('data', JSON.stringify(values.incident || {}));
          }
          
          // Agregar archivos
          if (this.fileList.length > 0) {
            this.fileList.forEach(file => {
              formData.append('files[]', file.originFileObj);
            });
          }

          axios.post('/api/reports', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          })
            .then(() => {
              this.$message.success('Reporte guardado correctamente');
              this.resetForm();
              this.fetchRecentReports();
            })
            .catch(error => {
              this.$message.error('Error al guardar el reporte');
              console.error('Error saving report:', error);
            })
            .finally(() => {
              this.submitting = false;
            });
        }
      });
    },
    resetForm() {
      this.reportForm.resetFields();
      this.fileList = [];
      this.reportType = '';
    },
    viewReport(record) {
      this.detailsModal.record = record;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.record = null;
    },
    editReport(record) {
      // Implementar edición de reporte
      this.$message.info('Función de edición en desarrollo');
    },
    deleteReport(record) {
      axios.delete(`/api/reports/${record.id}`)
        .then(() => {
          this.$message.success('Reporte eliminado correctamente');
          this.fetchRecentReports();
        })
        .catch(error => {
          this.$message.error('Error al eliminar el reporte');
          console.error('Error deleting report:', error);
        });
    },
    filterOption(input, option) {
      return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
    },
    beforeUpload(file) {
      // Validar archivo
      const isLt10M = file.size / 1024 / 1024 < 10;
      if (!isLt10M) {
        this.$message.error('El archivo debe ser menor a 10MB');
        return false;
      }
      return false; // Prevenir upload automático
    },
    handleFileChange({ fileList }) {
      this.fileList = fileList;
    },
    getReportTypeText(type) {
      const types = {
        attendance: 'Asistencia',
        academic: 'Académico',
        behavior: 'Comportamiento',
        incident: 'Incidente',
        other: 'Otro',
      };
      return types[type] || type;
    },
    getReportTypeColor(type) {
      const colors = {
        attendance: 'blue',
        academic: 'green',
        behavior: 'orange',
        incident: 'red',
        other: 'default',
      };
      return colors[type] || 'default';
    },
    getPersonTypeText(type) {
      const types = {
        student: 'Alumno',
        teacher: 'Maestro',
        employee: 'Empleado',
      };
      return types[type] || type;
    },
    formatDate(dateString) {
      return moment(dateString).format('DD/MM/YYYY HH:mm');
    },
  },
  computed: {
    recentColumns() {
      return [
        {
          title: 'Tipo',
          key: 'type',
          scopedSlots: { customRender: 'type' },
          width: 120,
        },
        {
          title: 'Persona',
          key: 'person',
          scopedSlots: { customRender: 'person' },
          width: 150,
        },
        {
          title: 'Fecha',
          key: 'date',
          scopedSlots: { customRender: 'date' },
          width: 150,
        },
        {
          title: 'Descripción',
          dataIndex: 'description',
          key: 'description',
          ellipsis: true,
        },
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 150,
        },
      ];
    },
  },
};
</script>

<style scoped>
.report-entry {
  padding: 24px;
}

.entry-form-section {
  padding: 20px;
  background: #fafafa;
  border-radius: 8px;
  margin-bottom: 24px;
}

.recent-reports-section h3 {
  margin-bottom: 16px;
  color: #1890ff;
}
</style>
