<template>
  <div class="import-students-container">
    <a-card :bordered="false" class="header-solid">
      <template #title>
        <h5 class="font-semibold m-0">
          <a-icon type="file-excel" style="color: #52c41a; margin-right: 8px;" />
          Importar Alumnos desde Excel
        </h5>
      </template>

      <!-- Instrucciones -->
      <a-alert
        message="Instrucciones para el archivo Excel"
        description="El archivo debe tener pestañas por cada grado. Cada pestaña debe contener las columnas: Nombre Completo, Fecha de Nacimiento (DD/MM/YYYY), Grado, Padre/Madre Principal, DUI/NIT, Parentesco, Teléfono, Ocupación, Email."
        type="info"
        show-icon
        style="margin-bottom: 24px;"
      />

      <!-- Upload de archivo -->
      <div class="upload-section">
        <a-upload
          :beforeUpload="beforeUpload"
          :showUploadList="false"
          accept=".xlsx,.xls"
          :disabled="loading"
        >
          <a-button 
            type="primary" 
            :loading="loading"
            :disabled="loading"
            size="large"
            style="margin-bottom: 16px;"
          >
            <a-icon type="upload" />
            {{ loading ? 'Analizando archivo...' : 'Seleccionar archivo Excel' }}
          </a-button>
        </a-upload>
        
        <!-- Spinner de análisis -->
        <div v-if="loading" class="analysis-spinner">
          <a-spin size="large" tip="Analizando archivo Excel...">
            <div class="spinner-content">
              <a-icon type="file-excel" style="font-size: 48px; color: #52c41a; margin-bottom: 16px;" />
              <p>Procesando pestañas y validando datos...</p>
            </div>
          </a-spin>
        </div>
      </div>

      <!-- Vista previa de datos -->
      <div v-if="previewData.length > 0" class="preview-section">
        <a-divider orientation="left">Vista Previa de Datos</a-divider>
        
        <!-- Tabs por grado -->
        <a-tabs v-model="activeTab" type="card">
          <a-tab-pane 
            v-for="(gradeData, gradeName) in groupedPreviewData" 
            :key="gradeName" 
            :tab="`${gradeName} (${gradeData.length} alumnos)`"
          >
            <a-table
              :columns="previewColumns"
              :data-source="gradeData"
              :pagination="{ pageSize: 20, showSizeChanger: true, pageSizeOptions: ['10', '20', '50', '100'] }"
              size="small"
              :scroll="{ x: 800 }"
              :loading="loading"
            >
              <template slot="birthDate" slot-scope="date">
                {{ formatDate(date) }}
              </template>
              <template slot="status" slot-scope="status">
                <a-tag :color="status === 'Válido' ? 'green' : 'red'">
                  {{ status }}
                </a-tag>
              </template>
            </a-table>
          </a-tab-pane>
        </a-tabs>

        <!-- Botones de acción -->
        <div class="action-buttons" style="margin-top: 24px;">
          <a-space>
            <a-button @click="cancelImport" :disabled="loading">
              Cancelar
            </a-button>
            <a-button 
              type="primary" 
              @click="confirmImport" 
              :loading="loading"
              :disabled="hasErrors"
            >
              <a-icon type="check" />
              Importar {{ validStudentsCount }} alumnos válidos
            </a-button>
          </a-space>
        </div>

        <!-- Resumen -->
        <a-alert
          v-if="importSummary"
          :message="importSummary.message"
          :type="importSummary.type"
          show-icon
          style="margin-top: 16px;"
        />
      </div>

      <!-- Errores de validación -->
      <div v-if="validationErrors.length > 0" class="errors-section">
        <a-divider orientation="left">Errores Encontrados</a-divider>
        <a-list
          :data-source="validationErrors"
          size="small"
        >
          <a-list-item slot="renderItem" slot-scope="error">
            <a-list-item-meta>
              <span slot="title" style="color: #f5222d;">
                <a-icon type="exclamation-circle" />
                {{ error.grade }} - Fila {{ error.row }}: {{ error.error }}
              </span>
              <span slot="description">{{ error.data }}</span>
            </a-list-item-meta>
          </a-list-item>
        </a-list>
      </div>
    </a-card>
  </div>
</template>

<script>
import * as XLSX from 'xlsx';
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
  name: 'ImportStudents',
  data() {
    return {
      loading: false,
      file: null,
      previewData: [],
      validationErrors: [],
      activeTab: '0',
      importSummary: null,
      gradesOptions: [],
    };
  },
  computed: {
    previewColumns() {
      return [
        { title: 'Nombre Completo', dataIndex: 'fullName', width: 200, fixed: 'left' },
        { title: 'Identidad', dataIndex: 'identity', width: 140 },
        { title: 'Fecha Nacimiento', dataIndex: 'birthDate', scopedSlots: { customRender: 'birthDate' }, width: 120 },
        { title: 'ID Padre/Encargado', dataIndex: 'parentId', width: 140 },
        { title: 'Padre/Madre Principal', dataIndex: 'parent1_name', width: 180 },
        { title: 'Parentesco', dataIndex: 'parent1_relationship', width: 100 },
        { title: 'Teléfono', dataIndex: 'parent1_phone', width: 120 },
        { title: 'Ocupación', dataIndex: 'parent1_occupation', width: 120 },
        { title: 'Email', dataIndex: 'parent1_email', width: 150 },
        { title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 80, fixed: 'right' },
      ];
    },
    groupedPreviewData() {
      const grouped = {};
      this.previewData.forEach(student => {
        if (!grouped[student.grade]) {
          grouped[student.grade] = [];
        }
        grouped[student.grade].push(student);
      });
      return grouped;
    },
    validStudentsCount() {
      return this.previewData.filter(student => student.status === 'Válido').length;
    },
    hasErrors() {
      return this.validationErrors.length > 0 || this.validStudentsCount === 0;
    }
  },
  mounted() {
    this.fetchGrades();
  },
  methods: {
    async fetchGrades() {
      try {
        const authToken = getToken();
        const response = await axios.get('http://localhost:8000/api/grades', {
          headers: { Authorization: `Bearer ${authToken}` }
        });
        
        this.gradesOptions = (response.data || [])
          .filter(g => String(g?.status || 'Activo') === 'Activo')
          .map(g => g?.name)
          .filter(Boolean);
      } catch (error) {
        console.error('Error al obtener grados:', error);
      }
    },

    beforeUpload(file) {
      const isExcel = file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || 
                     file.type === 'application/vnd.ms-excel';
      
      if (!isExcel) {
        this.$message.error('Por favor sube un archivo Excel (.xlsx o .xls)');
        return false;
      }

      this.file = file;
      this.processExcelFile(file);
      return false; // Prevenir auto-upload
    },

    async processExcelFile(file) {
      this.loading = true;
      this.previewData = [];
      this.validationErrors = [];
      this.importSummary = null;

      try {
        const data = await this.readExcelFile(file);
        this.validateAndProcessData(data);
      } catch (error) {
        console.error('Error procesando archivo:', error);
        this.$message.error('Error al procesar el archivo Excel');
      } finally {
        this.loading = false;
      }
    },

    readExcelFile(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = (e) => {
          try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            resolve(workbook);
          } catch (error) {
            reject(error);
          }
        };

        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
      });
    },

    validateAndProcessData(workbook) {
      const sheetNames = workbook.SheetNames;
      const allData = [];

      sheetNames.forEach((sheetName, sheetIndex) => {
        const worksheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

        if (jsonData.length < 2) {
          this.validationErrors.push({
            grade: sheetName,
            row: 1,
            error: 'La pestaña no tiene datos suficientes',
            data: sheetName
          });
          return;
        }

        // Obtener encabezados
        const headers = jsonData[0];
        const dataRows = jsonData.slice(1);

        dataRows.forEach((row, rowIndex) => {
          const studentData = this.parseStudentRow(row, headers, sheetName, rowIndex + 2);
          
          if (studentData) {
            allData.push(studentData);
          }
        });
      });

      this.previewData = allData;
      
      if (allData.length === 0) {
        this.importSummary = {
          message: 'No se encontraron datos válidos en el archivo',
          type: 'warning'
        };
      } else {
        this.importSummary = {
          message: `Se encontraron ${allData.length} alumnos. ${this.validStudentsCount} son válidos para importar.`,
          type: this.validationErrors.length > 0 ? 'warning' : 'success'
        };
      }
    },

    parseStudentRow(row, headers, grade, rowNumber) {
      const student = {
        grade: grade,
        row: rowNumber,
        status: 'Válido'
      };

      // Mapear columnas según tu formato exacto
      const headerMap = {
        // Datos del alumno
        'nombre completo': 'fullName',
        'identidad': 'identity',
        'fecha de nacimiento': 'birthDate',
        'nacimiento': 'birthDate',
        'fecha': 'birthDate',
        
        // Datos del padre/encargado
        'id padre o encargado': 'parentId',
        'id padre': 'parentId',
        'id encargado': 'parentId',
        'padre madre principal': 'parent1_name',
        'padre/madre': 'parent1_name',
        'padre': 'parent1_name',
        'madre': 'parent1_name',
        'encargado': 'parent1_name',
        'responsable': 'parent1_name',
        'parentesco': 'parent1_relationship',
        'relacion': 'parent1_relationship',
        'teléfono': 'parent1_phone',
        'telefono': 'parent1_phone',
        'celular': 'parent1_phone',
        'contacto': 'parent1_phone',
        'ocupación': 'parent1_occupation',
        'ocupacion': 'parent1_occupation',
        'trabajo': 'parent1_occupation',
        'profesion': 'parent1_occupation',
        'profesión': 'parent1_occupation',
        'email': 'parent1_email',
        'correo': 'parent1_email',
        'correo electronico': 'parent1_email',
        'correo electrónico': 'parent1_email',
        'mail': 'parent1_email'
      };

      // Procesar cada columna
      headers.forEach((header, index) => {
        if (!header) return;
        
        const normalizedHeader = header.toString().toLowerCase().trim();
        const field = headerMap[normalizedHeader];
        
        if (field) {
          student[field] = row[index] || '';
        }
      });

      // Validar datos requeridos
      const errors = [];
      
      if (!student.fullName || student.fullName.toString().trim() === '') {
        errors.push('Nombre completo es requerido');
      }
      
      if (!student.birthDate) {
        errors.push('Fecha de nacimiento es requerida');
      } else {
        // Validar formato de fecha
        const date = this.parseDate(student.birthDate);
        if (!date) {
          errors.push('Formato de fecha inválido (use DD/MM/YYYY)');
        } else {
          student.birthDate = date;
        }
      }

      // Validar campos del padre/encargado
      if (!student.parent1_name || student.parent1_name.toString().trim() === '') {
        errors.push('Nombre del padre/madre/encargado es requerido');
      }
      
      if (!student.parent1_relationship || student.parent1_relationship.toString().trim() === '') {
        errors.push('Parentesco es requerido');
      }
      
      // Asignar grado de la pestaña (no viene en tus datos)
      student.grade = grade;

      // Validar que el grado exista
      if (!this.gradesOptions.includes(student.grade)) {
        errors.push(`Grado "${student.grade}" no existe en el sistema`);
      }

      if (errors.length > 0) {
        student.status = 'Inválido';
        this.validationErrors.push({
          grade: grade,
          row: rowNumber,
          error: errors.join(', '),
          data: `Nombre: ${student.fullName || 'N/A'}`
        });
      }

      return student;
    },

    parseDate(dateString) {
      if (!dateString) return null;
      
      // Intentar diferentes formatos
      const formats = ['DD/MM/YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD', 'MM/DD/YYYY'];
      
      for (const format of formats) {
        const date = moment(dateString, format, true);
        if (date.isValid()) {
          return date.format('YYYY-MM-DD');
        }
      }
      
      return null;
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      return moment(dateString).format('DD/MM/YYYY');
    },

    async confirmImport() {
      this.loading = true;
      
      try {
        const validStudents = this.previewData.filter(student => student.status === 'Válido');
        let successCount = 0;
        let errorCount = 0;

        for (const student of validStudents) {
          try {
            await this.saveStudent(student);
            successCount++;
          } catch (error) {
            console.error('Error guardando alumno:', student.fullName, error);
            errorCount++;
          }
        }

        this.importSummary = {
          message: `Importación completada: ${successCount} alumnos importados exitosamente${errorCount > 0 ? `, ${errorCount} errores` : ''}`,
          type: errorCount > 0 ? 'warning' : 'success'
        };

        if (successCount > 0) {
          this.$message.success(`${successCount} alumnos importados exitosamente`);
        }

      } catch (error) {
        console.error('Error en importación:', error);
        this.$message.error('Error durante la importación');
        this.importSummary = {
          message: 'Error durante la importación',
          type: 'error'
        };
      } finally {
        this.loading = false;
      }
    },

    async saveStudent(student) {
      const authToken = getToken();
      
      const form = new FormData();
      form.append('fullName', student.fullName);
      form.append('birthDate', student.birthDate);
      form.append('gradeCourse', student.grade);
      
      // Datos del padre/encargado
      form.append('parent1_name', student.parent1_name || '');
      form.append('parent1_identity', student.identity || ''); // Usar identidad del alumno como referencia
      form.append('parent1_relationship', student.parent1_relationship || '');
      form.append('parent1_phone', student.parent1_phone || '');
      form.append('parent1_occupation', student.parent1_occupation || '');
      form.append('parent1_email', student.parent1_email || '');
      
      // Guardar ID del padre si existe
      if (student.parentId && student.parentId !== 'no ingresado') {
        form.append('parent_id', student.parentId);
      }

      return axios.post('http://localhost:8000/api/students', form, {
        headers: { 
          Authorization: `Bearer ${authToken}`,
          'Content-Type': 'multipart/form-data'
        }
      });
    },

    cancelImport() {
      this.previewData = [];
      this.validationErrors = [];
      this.importSummary = null;
      this.file = null;
    }
  }
};
</script>

<style scoped>
.import-students-container {
  padding: 24px;
}

.preview-section {
  margin-top: 24px;
}

.errors-section {
  margin-top: 24px;
}

.action-buttons {
  text-align: center;
}

:deep(.ant-tabs-tab) {
  font-size: 14px;
}

:deep(.ant-table-small) {
  font-size: 12px;
}
</style>
