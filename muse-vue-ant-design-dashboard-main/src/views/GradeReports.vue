<template>
  <div class="grade-reports">
    <a-card title="Boletas de Calificaciones" :loading="loading">
      <!-- Filtros y Acciones -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-select
              v-model="filters.grade"
              placeholder="Seleccionar Grado"
              style="width: 100%"
              @change="handleGradeChange"
            >
              <a-select-option value="1">Primer Grado</a-select-option>
              <a-select-option value="2">Segundo Grado</a-select-option>
              <a-select-option value="3">Tercer Grado</a-select-option>
              <a-select-option value="4">Cuarto Grado</a-select-option>
              <a-select-option value="5">Quinto Grado</a-select-option>
              <a-select-option value="6">Sexto Grado</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.period"
              placeholder="Período"
              style="width: 100%"
              @change="fetchGradeReports"
            >
              <a-select-option value="1">Primer Período</a-select-option>
              <a-select-option value="2">Segundo Período</a-select-option>
              <a-select-option value="3">Tercer Período</a-select-option>
              <a-select-option value="4">Cuarto Período</a-select-option>
              <a-select-option value="year">Año Completo</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.section"
              placeholder="Sección (opcional)"
              style="width: 100%"
              @change="fetchGradeReports"
              allowClear
            >
              <a-select-option value="A">Sección A</a-select-option>
              <a-select-option value="B">Sección B</a-select-option>
              <a-select-option value="C">Sección C</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-space>
              <a-button type="primary" @click="showConfigModal" :disabled="!filters.grade">
                <a-icon type="setting" /> Configurar Clases
              </a-button>
              <a-button type="primary" @click="showGradeEntryModal" :disabled="!filters.grade || !gradeConfig.subjects.length">
                <a-icon type="edit" /> Ingresar Notas
              </a-button>
            </a-space>
          </a-col>
        </a-row>
      </div>

      <!-- Información del Grado -->
      <div class="grade-info-section" v-if="filters.grade && gradeConfig.subjects.length">
        <a-alert
          :message="`Configuración para ${getGradeText(filters.grade)}`"
          :description="`${gradeConfig.subjects.length} asignaturas configuradas`
          type="info"
          show-icon
          style="margin-bottom: 16px;"
        />
        <div class="subjects-preview">
          <h4>Asignaturas Configuradas:</h4>
          <a-tag v-for="subject in gradeConfig.subjects" :key="subject.id" color="blue" style="margin: 4px;">
            {{ subject.name }}
          </a-tag>
        </div>
      </div>

      <!-- Tabla de Calificaciones -->
      <div class="grades-table-section" v-if="gradeReports.length">
        <h3>Calificaciones Ingresadas</h3>
        <a-table
          :columns="gradeColumns"
          :data-source="gradeReports"
          :loading="loadingReports"
          :pagination="pagination"
          @change="handleTableChange"
          :scroll="{ x: 1200 }"
          row-key="id"
        >
          <!-- Columna de alumno -->
          <template slot="student" slot-scope="text, record">
            <div class="student-info">
              <a-avatar :src="record.student.avatar" :size="small">
                {{ record.student.name.charAt(0) }}
              </a-avatar>
              <div class="student-details">
                <div class="student-name">{{ record.student.name }}</div>
                <div class="student-id">{{ record.student.document }}</div>
              </div>
            </div>
          </template>

          <!-- Columnas de asignaturas dinámicas -->
          <template v-for="subject in gradeConfig.subjects" :slot="subject.key" slot-scope="text, record">
            <div class="grade-cell">
              <a-tag 
                :color="getGradeColor(record.grades[subject.key])"
                @click="editGrade(record, subject.key)"
                style="cursor: pointer;"
              >
                {{ record.grades[subject.key] || '-' }}
              </a-tag>
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

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="viewStudentDetails(record)">
                <a-icon type="eye" /> Ver
              </a-button>
              <a-button type="link" size="small" @click="downloadReportCard(record)">
                <a-icon type="download" /> Boleta
              </a-button>
            </a-space>
          </template>
        </a-table>
      </div>

      <!-- Subida de Boletas Escaneadas -->
      <div class="upload-section" style="margin-top: 32px;">
        <h3>Subir Boletas Escaneadas</h3>
        <a-upload-dragger
          name="files"
          :multiple="true"
          :before-upload="beforeUpload"
          :file-list="uploadFileList"
          @change="handleUploadChange"
          @drop="handleDrop"
        >
          <p class="ant-upload-drag-icon">
            <a-icon type="inbox" />
          </p>
          <p class="ant-upload-text">Arrastra las boletas escaneadas aquí</p>
          <p class="ant-upload-hint">Soporta múltiples archivos. Formatos: PDF, JPG, PNG</p>
        </a-upload-dragger>
        
        <div style="margin-top: 16px;">
          <a-button type="primary" @click="processUploads" :loading="processingUploads" :disabled="!uploadFileList.length">
            <a-icon type="upload" /> Procesar Archivos
          </a-button>
        </div>
      </div>
    </a-card>

    <!-- Modal de Configuración de Clases -->
    <a-modal
      title="Configurar Clases por Grado"
      :visible="configModal.visible"
      @ok="saveConfig"
      @cancel="closeConfigModal"
      :confirmLoading="configModal.loading"
      width="800px"
    >
      <div v-if="configModal.visible">
        <h4>{{ getGradeText(filters.grade) }} - Configuración de Asignaturas</h4>
        
        <a-form :form="configForm">
          <a-form-item label="Asignaturas">
            <a-select
              mode="multiple"
              v-decorator="['subjects', { 
                rules: [{ required: true, message: 'Selecciona al menos una asignatura.' }] 
              }]"
              placeholder="Selecciona las asignaturas"
              style="width: 100%"
            >
              <a-select-option value="math">Matemáticas</a-select-option>
              <a-select-option value="science">Ciencias Naturales</a-select-option>
              <a-select-option value="social">Estudios Sociales</a-select-option>
              <a-select-option value="language">Lenguaje y Literatura</a-select-option>
              <a-select-option value="english">Inglés</a-select-option>
              <a-select-option value="art">Educación Artística</a-select-option>
              <a-select-option value="pe">Educación Física</a-select-option>
              <a-select-option value="religion">Religión</a-select-option>
              <a-select-option value="civics">Educación Cívica</a-select-option>
            </a-select>
          </a-form-item>

          <a-form-item label="Nota Mínima Aprobatoria">
            <a-input-number
              v-decorator="['minPassingGrade', { 
                initialValue: 6,
                rules: [{ required: true, message: 'Ingresa la nota mínima.' }] 
              }]"
              :min="0"
              :max="10"
              :precision="1"
              style="width: 100%"
            />
          </a-form-item>

          <a-form-item label="Máximo de Faltas">
            <a-input-number
              v-decorator="['maxAbsences', { 
                initialValue: 10,
                rules: [{ required: true, message: 'Ingresa el máximo de faltas.' }] 
              }]"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-form>

        <div v-if="configModal.selectedSubjects.length" style="margin-top: 16px;">
          <h4>Asignaturas Seleccionadas:</h4>
          <a-table
            :columns="subjectColumns"
            :data-source="configModal.selectedSubjects"
            :pagination="false"
            size="small"
            row-key="key"
          >
            <template slot="name" slot-scope="text, record">
              {{ getSubjectName(record.key) }}
            </template>
            <template slot="weight" slot-scope="text, record">
              <a-input-number
                v-model="record.weight"
                :min="0.1"
                :max="1"
                :step="0.1"
                :precision="1"
                size="small"
                style="width: 80px;"
              />
            </template>
          </a-table>
        </div>
      </div>
    </a-modal>

    <!-- Modal de Ingreso de Calificaciones -->
    <a-modal
      title="Ingresar Calificaciones"
      :visible="gradeEntryModal.visible"
      @ok="saveGrades"
      @cancel="closeGradeEntryModal"
      :confirmLoading="gradeEntryModal.loading"
      width="1000px"
    >
      <div v-if="gradeEntryModal.visible">
        <h4>{{ getGradeText(filters.grade) }} - {{ getPeriodText(filters.period) }}</h4>
        
        <a-table
          :columns="entryColumns"
          :data-source="gradeEntryModal.students"
          :pagination="false"
          :scroll="{ x: 800 }"
          row-key="id"
        >
          <!-- Columna de alumno -->
          <template slot="student" slot-scope="text, record">
            <div class="student-info">
              <a-avatar :src="record.avatar" :size="small">
                {{ record.name.charAt(0) }}
              </a-avatar>
              <div class="student-details">
                <div class="student-name">{{ record.name }}</div>
                <div class="student-id">{{ record.document }}</div>
              </div>
            </div>
          </template>

          <!-- Columnas de calificaciones dinámicas -->
          <template v-for="subject in gradeConfig.subjects" :slot="subject.key" slot-scope="text, record">
            <a-input-number
              v-model="record.grades[subject.key]"
              :min="0"
              :max="10"
              :precision="1"
              size="small"
              style="width: 80px;"
            />
          </template>

          <!-- Columna de promedio automático -->
          <template slot="average" slot-scope="text, record">
            <a-tag :color="getGradeColor(calculateAverage(record.grades))">
              {{ calculateAverage(record.grades).toFixed(1) }}
            </a-tag>
          </template>
        </a-table>
      </div>
    </a-modal>

    <!-- Modal de Detalles del Alumno -->
    <a-modal
      title="Detalles de Calificaciones"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="800px"
    >
      <div v-if="detailsModal.record">
        <a-descriptions title="Información del Alumno" :column="2" bordered size="small">
          <a-descriptions-item label="Nombre">
            {{ detailsModal.record.student.name }}
          </a-descriptions-item>
          <a-descriptions-item label="Documento">
            {{ detailsModal.record.student.document }}
          </a-descriptions-item>
          <a-descriptions-item label="Grado">
            {{ getGradeText(filters.grade) }}
          </a-descriptions-item>
          <a-descriptions-item label="Sección">
            {{ detailsModal.record.student.section || '-' }}
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

        <div style="margin-top: 24px;">
          <h4>Calificaciones por Asignatura</h4>
          <a-table
            :columns="detailColumns"
            :data-source="detailsModal.record.gradeDetails"
            :pagination="false"
            size="small"
            row-key="subject"
          >
            <template slot="grade" slot-scope="text">
              <a-tag :color="getGradeColor(text)">
                {{ text.toFixed(1) }}
              </a-tag>
            </template>
            <template slot="status" slot-scope="text">
              <a-tag :color="text.passed ? 'green' : 'red'">
                {{ text.passed ? 'Aprobado' : 'Reprobado' }}
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
  name: 'GradeReports',
  data() {
    return {
      loading: false,
      loadingReports: false,
      processingUploads: false,
      filters: {
        grade: '',
        period: '1',
        section: '',
      },
      gradeConfig: {
        subjects: [],
        minPassingGrade: 6,
        maxAbsences: 10,
      },
      gradeReports: [],
      uploadFileList: [],
      pagination: {
        current: 1,
        pageSize: 20,
        total: 0,
      },
      configModal: {
        visible: false,
        loading: false,
        selectedSubjects: [],
      },
      gradeEntryModal: {
        visible: false,
        loading: false,
        students: [],
      },
      detailsModal: {
        visible: false,
        record: null,
      },
    };
  },
  beforeCreate() {
    this.configForm = this.$form.createForm(this, { name: 'config_form' });
  },
  mounted() {
    // Cargar configuración inicial si hay un grado seleccionado
  },
  methods: {
    handleGradeChange() {
      this.fetchGradeConfig();
      this.fetchGradeReports();
    },
    fetchGradeConfig() {
      if (!this.filters.grade) return;
      
      this.loading = true;
      axios.get(`/api/grade-config/${this.filters.grade}`)
        .then(response => {
          this.gradeConfig = response.data || { subjects: [], minPassingGrade: 6, maxAbsences: 10 };
        })
        .catch(error => {
          console.error('Error fetching grade config:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchGradeReports() {
      if (!this.filters.grade) return;
      
      this.loadingReports = true;
      const params = {
        grade: this.filters.grade,
        period: this.filters.period,
        section: this.filters.section || undefined,
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
      };

      axios.get('/api/grade-reports', { params })
        .then(response => {
          this.gradeReports = response.data.data || [];
          this.pagination.total = response.data.total || 0;
        })
        .catch(error => {
          this.$message.error('Error al cargar las calificaciones');
          console.error('Error fetching grade reports:', error);
        })
        .finally(() => {
          this.loadingReports = false;
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchGradeReports();
    },
    showConfigModal() {
      this.configModal.visible = true;
      this.configModal.selectedSubjects = [];
      
      this.$nextTick(() => {
        this.configForm.setFieldsValue({
          subjects: this.gradeConfig.subjects.map(s => s.key),
          minPassingGrade: this.gradeConfig.minPassingGrade,
          maxAbsences: this.gradeConfig.maxAbsences,
        });
        this.updateSelectedSubjects();
      });
    },
    closeConfigModal() {
      this.configModal.visible = false;
      this.configModal.loading = false;
      this.configModal.selectedSubjects = [];
      this.configForm.resetFields();
    },
    saveConfig() {
      this.configForm.validateFields((err, values) => {
        if (!err) {
          this.configModal.loading = true;
          
          const configData = {
            grade: this.filters.grade,
            subjects: this.configModal.selectedSubjects,
            minPassingGrade: values.minPassingGrade,
            maxAbsences: values.maxAbsences,
          };

          axios.post('/api/grade-config', configData)
            .then(() => {
              this.$message.success('Configuración guardada correctamente');
              this.closeConfigModal();
              this.fetchGradeConfig();
            })
            .catch(error => {
              this.$message.error('Error al guardar la configuración');
              console.error('Error saving config:', error);
            })
            .finally(() => {
              this.configModal.loading = false;
            });
        }
      });
    },
    updateSelectedSubjects() {
      const subjects = this.configForm.getFieldValue('subjects') || [];
      this.configModal.selectedSubjects = subjects.map(key => ({
        key,
        name: this.getSubjectName(key),
        weight: 1.0,
      }));
    },
    showGradeEntryModal() {
      this.gradeEntryModal.visible = true;
      this.fetchStudentsForEntry();
    },
    closeGradeEntryModal() {
      this.gradeEntryModal.visible = false;
      this.gradeEntryModal.loading = false;
      this.gradeEntryModal.students = [];
    },
    fetchStudentsForEntry() {
      this.gradeEntryModal.loading = true;
      axios.get(`/api/students/${this.filters.grade}`, {
        params: { section: this.filters.section }
      })
        .then(response => {
          this.gradeEntryModal.students = response.data.map(student => ({
            ...student,
            grades: {},
          }));
        })
        .catch(error => {
          this.$message.error('Error al cargar los estudiantes');
          console.error('Error fetching students:', error);
        })
        .finally(() => {
          this.gradeEntryModal.loading = false;
        });
    },
    saveGrades() {
      this.gradeEntryModal.loading = true;
      
      const gradesData = this.gradeEntryModal.students.map(student => ({
        studentId: student.id,
        grades: student.grades,
        grade: this.filters.grade,
        period: this.filters.period,
        section: this.filters.section,
      }));

      axios.post('/api/grades/batch', { grades: gradesData })
        .then(() => {
          this.$message.success('Calificaciones guardadas correctamente');
          this.closeGradeEntryModal();
          this.fetchGradeReports();
        })
        .catch(error => {
          this.$message.error('Error al guardar las calificaciones');
          console.error('Error saving grades:', error);
        })
        .finally(() => {
          this.gradeEntryModal.loading = false;
        });
    },
    viewStudentDetails(record) {
      this.detailsModal.record = {
        ...record,
        gradeDetails: this.gradeConfig.subjects.map(subject => ({
          subject: subject.name,
          grade: record.grades[subject.key] || 0,
          passed: (record.grades[subject.key] || 0) >= this.gradeConfig.minPassingGrade,
        })),
      };
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.record = null;
    },
    editGrade(record, subjectKey) {
      // Implementar edición individual de calificación
      this.$message.info('Edición individual en desarrollo');
    },
    downloadReportCard(record) {
      axios.get(`/api/report-card/${record.id}`, {
        responseType: 'blob',
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `boleta_${record.student.name}_${moment().format('YYYY-MM-DD')}.pdf`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          this.$message.error('Error al descargar la boleta');
          console.error('Error downloading report card:', error);
        });
    },
    beforeUpload(file) {
      const isValidType = ['application/pdf', 'image/jpeg', 'image/png'].includes(file.type);
      if (!isValidType) {
        this.$message.error('Solo se permiten archivos PDF, JPG y PNG');
        return false;
      }
      const isLt10M = file.size / 1024 / 1024 < 10;
      if (!isLt10M) {
        this.$message.error('El archivo debe ser menor a 10MB');
        return false;
      }
      return false; // Prevenir upload automático
    },
    handleUploadChange({ fileList }) {
      this.uploadFileList = fileList;
    },
    handleDrop(e) {
      console.log('Drop:', e.dataTransfer.files);
    },
    processUploads() {
      this.processingUploads = true;
      const formData = new FormData();
      
      this.uploadFileList.forEach(file => {
        formData.append('files[]', file.originFileObj);
      });
      formData.append('grade', this.filters.grade);
      formData.append('period', this.filters.period);

      axios.post('/api/process-scanned-report-cards', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
        .then(() => {
          this.$message.success('Boletas procesadas correctamente');
          this.uploadFileList = [];
          this.fetchGradeReports();
        })
        .catch(error => {
          this.$message.error('Error al procesar las boletas');
          console.error('Error processing scanned cards:', error);
        })
        .finally(() => {
          this.processingUploads = false;
        });
    },
    calculateAverage(grades) {
      const validGrades = Object.values(grades).filter(g => g !== null && g !== undefined);
      if (!validGrades.length) return 0;
      return validGrades.reduce((sum, grade) => sum + grade, 0) / validGrades.length;
    },
    getGradeText(grade) {
      const grades = {
        '1': 'Primer Grado',
        '2': 'Segundo Grado',
        '3': 'Tercer Grado',
        '4': 'Cuarto Grado',
        '5': 'Quinto Grado',
        '6': 'Sexto Grado',
      };
      return grades[grade] || grade;
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
    getSubjectName(key) {
      const subjects = {
        'math': 'Matemáticas',
        'science': 'Ciencias Naturales',
        'social': 'Estudios Sociales',
        'language': 'Lenguaje y Literatura',
        'english': 'Inglés',
        'art': 'Educación Artística',
        'pe': 'Educación Física',
        'religion': 'Religión',
        'civics': 'Educación Cívica',
      };
      return subjects[key] || key;
    },
    getGradeColor(grade) {
      if (grade >= 9) return 'green';
      if (grade >= 7) return 'blue';
      if (grade >= 6) return 'orange';
      return 'red';
    },
    getGradeStatus(average) {
      if (average >= 7) return 'success';
      if (average >= 6) return 'normal';
      return 'exception';
    },
  },
  computed: {
    gradeColumns() {
      const baseColumns = [
        {
          title: 'Alumno',
          key: 'student',
          scopedSlots: { customRender: 'student' },
          width: 200,
          fixed: 'left',
        },
      ];

      // Columnas dinámicas de asignaturas
      this.gradeConfig.subjects.forEach(subject => {
        baseColumns.push({
          title: subject.name,
          key: subject.key,
          scopedSlots: { customRender: subject.key },
          width: 100,
        });
      });

      baseColumns.push(
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
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 150,
          fixed: 'right',
        }
      );

      return baseColumns;
    },
    entryColumns() {
      const baseColumns = [
        {
          title: 'Alumno',
          key: 'student',
          scopedSlots: { customRender: 'student' },
          width: 200,
          fixed: 'left',
        },
      ];

      // Columnas dinámicas de asignaturas para ingreso
      this.gradeConfig.subjects.forEach(subject => {
        baseColumns.push({
          title: subject.name,
          key: subject.key,
          scopedSlots: { customRender: subject.key },
          width: 100,
        });
      });

      baseColumns.push({
        title: 'Promedio',
        key: 'average',
        scopedSlots: { customRender: 'average' },
        width: 100,
      });

      return baseColumns;
    },
    subjectColumns() {
      return [
        {
          title: 'Asignatura',
          key: 'name',
          scopedSlots: { customRender: 'name' },
        },
        {
          title: 'Ponderación',
          key: 'weight',
          scopedSlots: { customRender: 'weight' },
          width: 100,
        },
      ];
    },
    detailColumns() {
      return [
        {
          title: 'Asignatura',
          dataIndex: 'subject',
          key: 'subject',
        },
        {
          title: 'Calificación',
          dataIndex: 'grade',
          key: 'grade',
          scopedSlots: { customRender: 'grade' },
          width: 100,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
      ];
    },
  },
};
</script>

<style scoped>
.grade-reports {
  padding: 24px;
}

.filters-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.grade-info-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #f0f2f5;
  border-radius: 8px;
}

.subjects-preview h4 {
  margin-bottom: 8px;
  color: #1890ff;
}

.grades-table-section h3 {
  margin-bottom: 16px;
  color: #1890ff;
}

.upload-section h3 {
  margin-bottom: 16px;
  color: #1890ff;
}

.student-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.student-details {
  display: flex;
  flex-direction: column;
}

.student-name {
  font-weight: 500;
  font-size: 14px;
}

.student-id {
  font-size: 12px;
  color: #666;
}

.grade-cell {
  text-align: center;
}
</style>
