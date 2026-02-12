<template>
  <div class="academic-performance-reports">
    <a-card title="Reportes de Rendimiento Académico" :loading="loading">
      <!-- Filtros -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-select
              v-model="filters.reportType"
              placeholder="Tipo de Reporte"
              style="width: 100%"
              @change="fetchPerformanceData"
            >
              <a-select-option value="students">Por Alumnos</a-select-option>
              <a-select-option value="teachers">Por Maestros</a-select-option>
              <a-select-option value="subjects">Por Asignaturas</a-select-option>
              <a-select-option value="grades">Por Grados</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.grade"
              placeholder="Grado"
              style="width: 100%"
              @change="fetchPerformanceData"
              allowClear
            >
              <a-select-option value="1">Primero</a-select-option>
              <a-select-option value="2">Segundo</a-select-option>
              <a-select-option value="3">Tercero</a-select-option>
              <a-select-option value="4">Cuarto</a-select-option>
              <a-select-option value="5">Quinto</a-select-option>
              <a-select-option value="6">Sexto</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.subject"
              placeholder="Asignatura"
              style="width: 100%"
              @change="fetchPerformanceData"
              allowClear
            >
              <a-select-option value="math">Matemáticas</a-select-option>
              <a-select-option value="science">Ciencias</a-select-option>
              <a-select-option value="language">Lenguaje</a-select-option>
              <a-select-option value="history">Historia</a-select-option>
              <a-select-option value="english">Inglés</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.period"
              placeholder="Período"
              style="width: 100%"
              @change="fetchPerformanceData"
            >
              <a-select-option value="1">Primer Período</a-select-option>
              <a-select-option value="2">Segundo Período</a-select-option>
              <a-select-option value="3">Tercer Período</a-select-option>
              <a-select-option value="4">Cuarto Período</a-select-option>
              <a-select-option value="year">Año Completo</a-select-option>
            </a-select>
          </a-col>
        </a-row>
        <a-row style="margin-top: 16px;">
          <a-col :span="12">
            <a-input-search
              v-model="filters.search"
              placeholder="Buscar por nombre o documento"
              @search="fetchPerformanceData"
              style="width: 100%"
            />
          </a-col>
          <a-col :span="12">
            <a-space style="float: right;">
              <a-button type="primary" @click="fetchPerformanceData" :loading="loading">
                <a-icon type="search" /> Buscar
              </a-button>
              <a-button @click="exportToExcel" :disabled="!performanceData.length">
                <a-icon type="file-excel" /> Exportar Excel
              </a-button>
              <a-button @click="exportToPDF" :disabled="!performanceData.length">
                <a-icon type="file-pdf" /> Exportar PDF
              </a-button>
              <a-button @click="resetFilters">
                <a-icon type="reload" /> Limpiar
              </a-button>
            </a-space>
          </a-col>
        </a-row>
      </div>

      <!-- Estadísticas Generales -->
      <div class="stats-section" v-if="performanceData.length">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Promedio General"
              :value="statistics.average"
              :precision="2"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Aprobados"
              :value="statistics.passed"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Reprobados"
              :value="statistics.failed"
              :value-style="{ color: '#f5222d' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Tasa de Aprobación"
              :value="statistics.passRate"
              suffix="%"
              :value-style="{ color: '#722ed1' }"
            />
          </a-col>
        </a-row>
      </div>

      <!-- Gráficos -->
      <div class="charts-section" v-if="performanceData.length">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-card title="Distribución de Calificaciones" size="small">
              <div ref="gradeDistributionChart" style="height: 300px;"></div>
            </a-card>
          </a-col>
          <a-col :span="12">
            <a-card title="Rendimiento por Asignatura" size="small">
              <div ref="subjectPerformanceChart" style="height: 300px;"></div>
            </a-card>
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de Datos -->
      <div class="table-section">
        <a-table
          :columns="columns"
          :data-source="performanceData"
          :loading="loading"
          :pagination="pagination"
          @change="handleTableChange"
          row-key="id"
          :scroll="{ x: 1400 }"
        >
          <!-- Columna de estudiante/maestro -->
          <template slot="person" slot-scope="text, record">
            <div class="person-info">
              <a-avatar :src="record.person.avatar" :size="small">
                {{ record.person.name.charAt(0) }}
              </a-avatar>
              <div class="person-details">
                <div class="person-name">{{ record.person.name }}</div>
                <div class="person-type">{{ getPersonTypeText(record.person.type) }}</div>
              </div>
            </div>
          </template>

          <!-- Columna de promedio -->
          <template slot="average" slot-scope="text, record">
            <a-progress
              :percent="Math.round(record.average * 10)"
              :status="getGradeStatus(record.average)"
              :format="() => record.average.toFixed(1)"
              size="small"
            />
          </template>

          <!-- Columna de estado -->
          <template slot="status" slot-scope="text, record">
            <a-tag :color="record.passed ? 'green' : 'red'">
              {{ record.passed ? 'Aprobado' : 'Reprobado' }}
            </a-tag>
          </template>

          <!-- Columna de calificaciones -->
          <template slot="grades" slot-scope="text, record">
            <div class="grades-list">
              <a-tag 
                v-for="grade in record.grades" 
                :key="grade.subject"
                :color="getGradeColor(grade.value)"
                size="small"
              >
                {{ grade.subject }}: {{ grade.value }}
              </a-tag>
            </div>
          </template>

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="viewDetails(record)">
                <a-icon type="eye" /> Detalles
              </a-button>
              <a-button type="link" size="small" @click="viewProgress(record)">
                <a-icon type="line-chart" /> Progreso
              </a-button>
              <a-button type="link" size="small" @click="generateReport(record)">
                <a-icon type="file-text" /> Reporte
              </a-button>
            </a-space>
          </template>
        </a-table>
      </div>
    </a-card>

    <!-- Modal de Detalles -->
    <a-modal
      title="Detalles de Rendimiento Académico"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="800px"
    >
      <div v-if="detailsModal.record">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-descriptions title="Información General" :column="1" bordered size="small">
              <a-descriptions-item label="Nombre">
                {{ detailsModal.record.person.name }}
              </a-descriptions-item>
              <a-descriptions-item label="Tipo">
                {{ getPersonTypeText(detailsModal.record.person.type) }}
              </a-descriptions-item>
              <a-descriptions-item label="Grado">
                {{ detailsModal.record.grade }}
              </a-descriptions-item>
              <a-descriptions-item label="Período">
                {{ getPeriodText(detailsModal.record.period) }}
              </a-descriptions-item>
              <a-descriptions-item label="Promedio">
                <a-progress
                  :percent="Math.round(detailsModal.record.average * 10)"
                  :status="getGradeStatus(detailsModal.record.average)"
                  :format="() => detailsModal.record.average.toFixed(1)"
                  size="small"
                />
              </a-descriptions-item>
              <a-descriptions-item label="Estado">
                <a-tag :color="detailsModal.record.passed ? 'green' : 'red'">
                  {{ detailsModal.record.passed ? 'Aprobado' : 'Reprobado' }}
                </a-tag>
              </a-descriptions-item>
            </a-descriptions>
          </a-col>
          <a-col :span="12">
            <a-descriptions title="Calificaciones por Asignatura" :column="1" bordered size="small">
              <a-descriptions-item 
                v-for="grade in detailsModal.record.grades" 
                :key="grade.subject"
                :label="grade.subject"
              >
                <a-tag :color="getGradeColor(grade.value)">
                  {{ grade.value }}
                </a-tag>
                <span style="margin-left: 8px; font-size: 12px;">
                  {{ getGradeText(grade.value) }}
                </span>
              </a-descriptions-item>
            </a-descriptions>
          </a-col>
        </a-row>
      </div>
    </a-modal>

    <!-- Modal de Progreso -->
    <a-modal
      title="Progreso Académico"
      :visible="progressModal.visible"
      @cancel="closeProgressModal"
      :footer="null"
      width="800px"
    >
      <div v-if="progressModal.record">
        <div ref="progressChart" style="height: 400px;"></div>
      </div>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'AcademicPerformanceReports',
  data() {
    return {
      loading: false,
      performanceData: [],
      filters: {
        reportType: 'students',
        grade: '',
        subject: '',
        period: 'year',
        search: '',
      },
      pagination: {
        current: 1,
        pageSize: 20,
        total: 0,
      },
      statistics: {
        average: 0,
        passed: 0,
        failed: 0,
        passRate: 0,
      },
      detailsModal: {
        visible: false,
        record: null,
      },
      progressModal: {
        visible: false,
        record: null,
      },
    };
  },
  computed: {
    columns() {
      const baseColumns = [
        {
          title: 'Persona',
          key: 'person',
          scopedSlots: { customRender: 'person' },
          width: 200,
          fixed: 'left',
        },
        {
          title: 'Grado',
          dataIndex: 'grade',
          key: 'grade',
          width: 80,
        },
        {
          title: 'Promedio',
          key: 'average',
          scopedSlots: { customRender: 'average' },
          width: 120,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
      ];

      if (this.filters.reportType === 'students') {
        baseColumns.push({
          title: 'Calificaciones',
          key: 'grades',
          scopedSlots: { customRender: 'grades' },
          width: 300,
        });
      }

      if (this.filters.reportType === 'teachers') {
        baseColumns.push({
          title: 'Asignaturas',
          dataIndex: 'subjects',
          key: 'subjects',
          width: 200,
        });
        baseColumns.push({
          title: 'Alumnos',
          dataIndex: 'studentCount',
          key: 'studentCount',
          width: 80,
        });
      }

      baseColumns.push({
        title: 'Acciones',
        key: 'actions',
        scopedSlots: { customRender: 'actions' },
        width: 180,
        fixed: 'right',
      });

      return baseColumns;
    },
  },
  mounted() {
    this.fetchPerformanceData();
    this.initCharts();
  },
  methods: {
    fetchPerformanceData() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        report_type: this.filters.reportType,
        grade: this.filters.grade || undefined,
        subject: this.filters.subject || undefined,
        period: this.filters.period,
        search: this.filters.search || undefined,
      };

      axios.get('/api/academic-performance', { params })
        .then(response => {
          this.performanceData = response.data.data || [];
          this.pagination.total = response.data.total || 0;
          this.calculateStatistics();
          this.updateCharts();
        })
        .catch(error => {
          this.$message.error('Error al cargar los datos de rendimiento académico');
          console.error('Error fetching academic performance:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchPerformanceData();
    },
    calculateStatistics() {
      if (!this.performanceData.length) {
        this.statistics = { average: 0, passed: 0, failed: 0, passRate: 0 };
        return;
      }

      const passed = this.performanceData.filter(item => item.passed).length;
      const failed = this.performanceData.length - passed;
      const average = this.performanceData.reduce((sum, item) => sum + item.average, 0) / this.performanceData.length;

      this.statistics = {
        average: average,
        passed: passed,
        failed: failed,
        passRate: Math.round((passed / this.performanceData.length) * 100),
      };
    },
    initCharts() {
      // Inicialización de gráficos
      this.$nextTick(() => {
        this.updateCharts();
      });
    },
    updateCharts() {
      // Implementación de gráficos con Chart.js o similar
      // Gráfico de distribución de calificaciones
      // Gráfico de rendimiento por asignatura
    },
    viewDetails(record) {
      this.detailsModal.record = record;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.record = null;
    },
    viewProgress(record) {
      this.progressModal.record = record;
      this.progressModal.visible = true;
      
      this.$nextTick(() => {
        this.renderProgressChart(record);
      });
    },
    closeProgressModal() {
      this.progressModal.visible = false;
      this.progressModal.record = null;
    },
    renderProgressChart(record) {
      // Implementar gráfico de progreso académico
      // Mostrar evolución de calificaciones en el tiempo
    },
    generateReport(record) {
      // Generar reporte individual
      this.$message.info('Generando reporte individual...');
    },
    exportToExcel() {
      const params = {
        report_type: this.filters.reportType,
        grade: this.filters.grade,
        subject: this.filters.subject,
        period: this.filters.period,
        format: 'excel',
      };

      axios.get('/api/academic-performance/export', { 
        params,
        responseType: 'blob',
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `rendimiento_academico_${moment().format('YYYY-MM-DD')}.xlsx`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          this.$message.error('Error al exportar los datos');
          console.error('Error exporting academic performance:', error);
        });
    },
    exportToPDF() {
      // Implementar exportación a PDF
      this.$message.info('Exportación a PDF en desarrollo');
    },
    resetFilters() {
      this.filters = {
        reportType: 'students',
        grade: '',
        subject: '',
        period: 'year',
        search: '',
      };
      this.pagination.current = 1;
      this.fetchPerformanceData();
    },
    getPersonTypeText(type) {
      const types = {
        student: 'Alumno',
        teacher: 'Maestro',
      };
      return types[type] || type;
    },
    getPeriodText(period) {
      const periods = {
        '1': 'Primer Período',
        '2': 'Segundo Período',
        '3': 'Tercer Período',
        '4': 'Cuarto Período',
        'year': 'Año Completo',
      };
      return periods[period] || period;
    },
    getGradeStatus(average) {
      if (average >= 7) return 'success';
      if (average >= 6) return 'normal';
      return 'exception';
    },
    getGradeColor(grade) {
      if (grade >= 9) return 'green';
      if (grade >= 7) return 'blue';
      if (grade >= 6) return 'orange';
      return 'red';
    },
    getGradeText(grade) {
      if (grade >= 9) return 'Excelente';
      if (grade >= 7) return 'Bueno';
      if (grade >= 6) return 'Regular';
      return 'Deficiente';
    },
  },
};
</script>

<style scoped>
.academic-performance-reports {
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

.person-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.person-details {
  display: flex;
  flex-direction: column;
}

.person-name {
  font-weight: 500;
  font-size: 14px;
}

.person-type {
  font-size: 12px;
  color: #666;
}

.grades-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
</style>
