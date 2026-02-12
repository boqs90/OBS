<template>
  <div class="user-academic-performance">
    <a-card title="Rendimiento Académico" size="small">
      <!-- Filtros -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-select
              v-model="filters.period"
              placeholder="Período"
              style="width: 100%"
              @change="fetchAcademicPerformance"
            >
              <a-select-option value="1">Primer Período</a-select-option>
              <a-select-option value="2">Segundo Período</a-select-option>
              <a-select-option value="3">Tercer Período</a-select-option>
              <a-select-option value="4">Cuarto Período</a-select-option>
              <a-select-option value="year">Año Completo</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="8">
            <a-select
              v-model="filters.subject"
              placeholder="Asignatura (opcional)"
              style="width: 100%"
              @change="fetchAcademicPerformance"
              allowClear
            >
              <a-select-option value="math">Matemáticas</a-select-option>
              <a-select-option value="science">Ciencias</a-select-option>
              <a-select-option value="language">Lenguaje</a-select-option>
              <a-select-option value="history">Historia</a-select-option>
              <a-select-option value="english">Inglés</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="8">
            <a-button type="primary" @click="fetchAcademicPerformance" :loading="loading">
              <a-icon type="search" /> Actualizar
            </a-button>
            <a-button @click="exportReport" style="margin-left: 8px;" :disabled="!academicData.length">
              <a-icon type="download" /> Exportar
            </a-button>
          </a-col>
        </a-row>
      </div>

      <!-- Resumen General -->
      <div class="summary-section" v-if="academicData.length">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Promedio General"
              :value="summary.average"
              :precision="2"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Mejor Asignatura"
              :value="summary.bestSubject"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Asignatura a Mejorar"
              :value="summary.worstSubject"
              :value-style="{ color: '#f5222d' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Estado General"
              :value="summary.status"
              :value-style="{ color: summary.passed ? '#52c41a' : '#f5222d' }"
            />
          </a-col>
        </a-row>
      </div>

      <!-- Gráfico de Progreso -->
      <div class="chart-section" v-if="chartData.length">
        <a-card title="Progreso Académico" size="small">
          <div ref="progressChart" style="height: 300px;"></div>
        </a-card>
      </div>

      <!-- Tabla de Calificaciones -->
      <a-table
        :columns="columns"
        :data-source="academicData"
        :loading="loading"
        :pagination="false"
        size="small"
        row-key="id"
      >
        <!-- Columna de asignatura -->
        <template slot="subject" slot-scope="text, record">
          <div class="subject-info">
            <a-icon :type="getSubjectIcon(record.subject)" style="margin-right: 8px;" />
            {{ getSubjectName(record.subject) }}
          </div>
        </template>

        <!-- Columna de calificación -->
        <template slot="grade" slot-scope="text, record">
          <a-progress
            :percent="Math.round(record.grade * 10)"
            :status="getGradeStatus(record.grade)"
            :format="() => record.grade.toFixed(1)"
            size="small"
          />
        </template>

        <!-- Columna de estado -->
        <template slot="status" slot-scope="text, record">
          <a-tag :color="record.passed ? 'green' : 'red'">
            {{ record.passed ? 'Aprobado' : 'Reprobado' }}
          </a-tag>
        </template>

        <!-- Columna de tendencia -->
        <template slot="trend" slot-scope="text, record">
          <div class="trend-info">
            <a-icon 
              :type="record.trend > 0 ? 'arrow-up' : record.trend < 0 ? 'arrow-down' : 'minus'" 
              :style="{ color: record.trend > 0 ? '#52c41a' : record.trend < 0 ? '#f5222d' : '#666' }"
            />
            <span style="margin-left: 4px;">
              {{ record.trend > 0 ? '+' : '' }}{{ record.trend.toFixed(1) }}
            </span>
          </div>
        </template>

        <!-- Columna de acciones -->
        <template slot="actions" slot-scope="text, record">
          <a-button type="link" size="small" @click="viewSubjectDetails(record)">
            <a-icon type="eye" /> Detalles
          </a-button>
        </template>
      </a-table>
    </a-card>

    <!-- Modal de Detalles de Asignatura -->
    <a-modal
      title="Detalles de Asignatura"
      :visible="subjectModal.visible"
      @cancel="closeSubjectModal"
      :footer="null"
      width="600px"
    >
      <div v-if="subjectModal.record">
        <a-descriptions :column="2" bordered size="small">
          <a-descriptions-item label="Asignatura">
            {{ getSubjectName(subjectModal.record.subject) }}
          </a-descriptions-item>
          <a-descriptions-item label="Calificación">
            <a-progress
              :percent="Math.round(subjectModal.record.grade * 10)"
              :status="getGradeStatus(subjectModal.record.grade)"
              :format="() => subjectModal.record.grade.toFixed(1)"
              size="small"
            />
          </a-descriptions-item>
          <a-descriptions-item label="Estado">
            <a-tag :color="subjectModal.record.passed ? 'green' : 'red'">
              {{ subjectModal.record.passed ? 'Aprobado' : 'Reprobado' }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Tendencia">
            <div class="trend-info">
              <a-icon 
                :type="subjectModal.record.trend > 0 ? 'arrow-up' : subjectModal.record.trend < 0 ? 'arrow-down' : 'minus'" 
                :style="{ color: subjectModal.record.trend > 0 ? '#52c41a' : subjectModal.record.trend < 0 ? '#f5222d' : '#666' }"
              />
              <span style="margin-left: 4px;">
                {{ subjectModal.record.trend > 0 ? '+' : '' }}{{ subjectModal.record.trend.toFixed(1) }}
              </span>
            </div>
          </a-descriptions-item>
          <a-descriptions-item label="Maestro">
            {{ subjectModal.record.teacher || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Horario">
            {{ subjectModal.record.schedule || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="Asistencia" :span="2">
            <a-progress
              :percent="subjectModal.record.attendance || 0"
              :status="subjectModal.record.attendance >= 80 ? 'success' : 'exception'"
              size="small"
            />
          </a-descriptions-item>
          <a-descriptions-item label="Observaciones" :span="2">
            {{ subjectModal.record.observations || '-' }}
          </a-descriptions-item>
        </a-descriptions>

        <!-- Historial de calificaciones -->
        <div style="margin-top: 24px;">
          <h4>Historial de Calificaciones</h4>
          <a-table
            :columns="historyColumns"
            :data-source="subjectModal.record.history || []"
            :pagination="false"
            size="small"
            row-key="period"
          >
            <template slot="grade" slot-scope="text">
              <a-tag :color="getGradeColor(text)">
                {{ text.toFixed(1) }}
              </a-tag>
            </template>
          </a-table>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'UserAcademicPerformance',
  props: {
    userId: {
      type: [String, Number],
      required: true,
    },
    userType: {
      type: String,
      default: 'student', // student, teacher
    },
  },
  data() {
    return {
      loading: false,
      academicData: [],
      chartData: [],
      filters: {
        period: 'year',
        subject: '',
      },
      summary: {
        average: 0,
        bestSubject: '-',
        worstSubject: '-',
        status: '-',
        passed: false,
      },
      subjectModal: {
        visible: false,
        record: null,
      },
    };
  },
  computed: {
    columns() {
      return [
        {
          title: 'Asignatura',
          key: 'subject',
          scopedSlots: { customRender: 'subject' },
          width: 150,
        },
        {
          title: 'Calificación',
          key: 'grade',
          scopedSlots: { customRender: 'grade' },
          width: 120,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
        {
          title: 'Tendencia',
          key: 'trend',
          scopedSlots: { customRender: 'trend' },
          width: 100,
        },
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 80,
        },
      ];
    },
    historyColumns() {
      return [
        {
          title: 'Período',
          dataIndex: 'period',
          key: 'period',
          width: 100,
        },
        {
          title: 'Calificación',
          dataIndex: 'grade',
          key: 'grade',
          scopedSlots: { customRender: 'grade' },
          width: 100,
        },
        {
          title: 'Observaciones',
          dataIndex: 'observations',
          key: 'observations',
        },
      ];
    },
  },
  mounted() {
    this.fetchAcademicPerformance();
  },
  methods: {
    fetchAcademicPerformance() {
      this.loading = true;
      const params = {
        user_id: this.userId,
        user_type: this.userType,
        period: this.filters.period,
        subject: this.filters.subject || undefined,
      };

      axios.get('/api/user-academic-performance', { params })
        .then(response => {
          this.academicData = response.data.grades || [];
          this.chartData = response.data.chart || [];
          this.calculateSummary();
          this.renderProgressChart();
        })
        .catch(error => {
          this.$message.error('Error al cargar los datos académicos');
          console.error('Error fetching academic performance:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    calculateSummary() {
      if (!this.academicData.length) {
        this.summary = { average: 0, bestSubject: '-', worstSubject: '-', status: '-', passed: false };
        return;
      }

      const grades = this.academicData.map(item => item.grade);
      const average = grades.reduce((sum, grade) => sum + grade, 0) / grades.length;
      
      const bestGrade = Math.max(...grades);
      const worstGrade = Math.min(...grades);
      
      const bestSubject = this.academicData.find(item => item.grade === bestGrade);
      const worstSubject = this.academicData.find(item => item.grade === worstGrade);
      
      const passed = this.academicData.filter(item => item.passed).length;
      const totalPassed = passed === this.academicData.length;

      this.summary = {
        average: average,
        bestSubject: bestSubject ? this.getSubjectName(bestSubject.subject) : '-',
        worstSubject: worstSubject ? this.getSubjectName(worstSubject.subject) : '-',
        status: totalPassed ? 'Aprobado' : 'Reprobado',
        passed: totalPassed,
      };
    },
    renderProgressChart() {
      // Implementar gráfico de progreso académico
      // Mostrar evolución de calificaciones en el tiempo
      this.$nextTick(() => {
        // Código para renderizar gráfico con Chart.js o similar
      });
    },
    viewSubjectDetails(record) {
      this.subjectModal.record = record;
      this.subjectModal.visible = true;
    },
    closeSubjectModal() {
      this.subjectModal.visible = false;
      this.subjectModal.record = null;
    },
    exportReport() {
      const params = {
        user_id: this.userId,
        user_type: this.userType,
        period: this.filters.period,
        format: 'pdf',
      };

      axios.get('/api/user-academic-performance/export', { 
        params,
        responseType: 'blob',
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `rendimiento_${this.userId}_${moment().format('YYYY-MM-DD')}.pdf`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          this.$message.error('Error al exportar el reporte');
          console.error('Error exporting academic report:', error);
        });
    },
    getSubjectName(subject) {
      const subjects = {
        math: 'Matemáticas',
        science: 'Ciencias',
        language: 'Lenguaje',
        history: 'Historia',
        english: 'Inglés',
      };
      return subjects[subject] || subject;
    },
    getSubjectIcon(subject) {
      const icons = {
        math: 'calculator',
        science: 'experiment',
        language: 'book',
        history: 'clock-circle',
        english: 'global',
      };
      return icons[subject] || 'book';
    },
    getGradeStatus(grade) {
      if (grade >= 7) return 'success';
      if (grade >= 6) return 'normal';
      return 'exception';
    },
    getGradeColor(grade) {
      if (grade >= 9) return 'green';
      if (grade >= 7) return 'blue';
      if (grade >= 6) return 'orange';
      return 'red';
    },
  },
};
</script>

<style scoped>
.user-academic-performance {
  margin-top: 16px;
}

.filters-section {
  margin-bottom: 16px;
  padding: 12px;
  background: #fafafa;
  border-radius: 6px;
}

.summary-section {
  margin: 16px 0;
  padding: 12px;
  background: #f0f2f5;
  border-radius: 6px;
}

.chart-section {
  margin: 16px 0;
}

.subject-info {
  display: flex;
  align-items: center;
}

.trend-info {
  display: flex;
  align-items: center;
  font-size: 12px;
}
</style>
