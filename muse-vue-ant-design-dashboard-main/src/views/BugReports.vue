<template>
  <div class="bug-reports">
    <a-card title="Reporte de Errores y Mejoras del Sistema" :loading="loading">
      <!-- Botones de Acción Rápida -->
      <div class="action-buttons">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-button type="danger" size="large" @click="showBugModal" block>
              <a-icon type="bug" /> Reportar Error
            </a-button>
          </a-col>
          <a-col :span="12">
            <a-button type="primary" size="large" @click="showImprovementModal" block>
              <a-icon type="bulb" /> Solicitar Mejora
            </a-button>
          </a-col>
        </a-row>
      </div>

      <!-- Filtros y Estadísticas -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-select
              v-model="filters.type"
              placeholder="Tipo"
              style="width: 100%"
              @change="fetchReports"
              allowClear
            >
              <a-select-option value="bug">Error</a-select-option>
              <a-select-option value="improvement">Mejora</a-select-option>
              <a-select-option value="feature">Nueva Funcionalidad</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.priority"
              placeholder="Prioridad"
              style="width: 100%"
              @change="fetchReports"
              allowClear
            >
              <a-select-option value="critical">Crítica</a-select-option>
              <a-select-option value="high">Alta</a-select-option>
              <a-select-option value="medium">Media</a-select-option>
              <a-select-option value="low">Baja</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model="filters.status"
              placeholder="Estado"
              style="width: 100%"
              @change="fetchReports"
              allowClear
            >
              <a-select-option value="open">Abierto</a-select-option>
              <a-select-option value="in_progress">En Progreso</a-select-option>
              <a-select-option value="resolved">Resuelto</a-select-option>
              <a-select-option value="closed">Cerrado</a-select-option>
              <a-select-option value="rejected">Rechazado</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-input-search
              v-model="filters.search"
              placeholder="Buscar por título o descripción"
              @search="fetchReports"
              style="width: 100%"
              allowClear
            />
          </a-col>
        </a-row>
      </div>

      <!-- Estadísticas -->
      <div class="stats-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Total de Reportes"
              :value="statistics.total"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Abiertos"
              :value="statistics.open"
              :value-style="{ color: '#fa8c16' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="En Progreso"
              :value="statistics.inProgress"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Resueltos"
              :value="statistics.resolved"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de Reportes -->
      <div class="table-section">
        <a-table
          :columns="reportColumns"
          :data-source="reports"
          :loading="loading"
          :pagination="pagination"
          @change="handleTableChange"
          :scroll="{ x: 1400 }"
          row-key="id"
        >
          <!-- Columna de tipo -->
          <template slot="type" slot-scope="text, record">
            <a-tag :color="getTypeColor(record.type)">
              <a-icon :type="getTypeIcon(record.type)" />
              {{ getTypeText(record.type) }}
            </a-tag>
          </template>

          <!-- Columna de título -->
          <template slot="title" slot-scope="text, record">
            <div class="report-title">
              <div class="title-text">{{ record.title }}</div>
              <div class="report-id">#{{ record.id }}</div>
            </div>
          </template>

          <!-- Columna de prioridad -->
          <template slot="priority" slot-scope="text, record">
            <a-tag :color="getPriorityColor(record.priority)">
              {{ getPriorityText(record.priority) }}
            </a-tag>
          </template>

          <!-- Columna de estado -->
          <template slot="status" slot-scope="text, record">
            <a-badge :status="getStatusBadge(record.status)" :text="getStatusText(record.status)" />
          </template>

          <!-- Columna de usuario -->
          <template slot="user" slot-scope="text, record">
            <div class="user-info">
              <a-avatar :src="record.user.avatar" :size="small">
                {{ record.user.name.charAt(0) }}
              </a-avatar>
              <span class="user-name">{{ record.user.name }}</span>
            </div>
          </template>

          <!-- Columna de fecha -->
          <template slot="date" slot-scope="text, record">
            {{ formatDate(record.created_at) }}
          </template>

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="viewDetails(record)">
                <a-icon type="eye" /> Ver
              </a-button>
              <a-button type="link" size="small" @click="editStatus(record)">
                <a-icon type="edit" /> Estado
              </a-button>
              <a-dropdown>
                <a-button type="link" size="small">
                  <a-icon type="more" />
                </a-button>
                <a-menu slot="overlay">
                  <a-menu-item @click="addComment(record)">
                    <a-icon type="message" /> Comentar
                  </a-menu-item>
                  <a-menu-item @click="assignTo(record)">
                    <a-icon type="user" /> Asignar
                  </a-menu-item>
                  <a-menu-divider />
                  <a-menu-item @click="closeReport(record)" style="color: #f5222d;">
                    <a-icon type="close" /> Cerrar
                  </a-menu-item>
                </a-menu>
              </a-dropdown>
            </a-space>
          </template>
        </a-table>
      </div>
    </a-card>

    <!-- Modal de Reporte de Error -->
    <a-modal
      title="Reportar Error del Sistema"
      :visible="bugModal.visible"
      @ok="submitBug"
      @cancel="closeBugModal"
      :confirmLoading="bugModal.loading"
      width="800px"
    >
      <a-form :form="bugForm" layout="vertical">
        <a-form-item label="Título del Error">
          <a-input
            v-decorator="['title', { 
              rules: [{ required: true, message: 'Ingresa un título descriptivo.' }] 
            }]"
            placeholder="Ej: Error al guardar datos en el formulario de matrícula"
          />
        </a-form-item>

        <a-form-item label="Sección del Sistema">
          <a-select
            v-decorator="['section', { 
              rules: [{ required: true, message: 'Selecciona la sección afectada.' }] 
            }]"
            placeholder="Selecciona la sección"
          >
            <a-select-option value="dashboard">Dashboard</a-select-option>
            <a-select-option value="matricula">Matrícula</a-select-option>
            <a-select-option value="pagos">Pagos</a-select-option>
            <a-select-option value="reportes">Reportes</a-select-option>
            <a-select-option value="inventario">Inventario</a-select-option>
            <a-select-option value="usuarios">Usuarios</a-select-option>
            <a-select-option value="other">Otro</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Prioridad">
          <a-radio-group
            v-decorator="['priority', { 
              initialValue: 'medium',
              rules: [{ required: true, message: 'Selecciona la prioridad.' }] 
            }]"
          >
            <a-radio value="low">Baja</a-radio>
            <a-radio value="medium">Media</a-radio>
            <a-radio value="high">Alta</a-radio>
            <a-radio value="critical">Crítica</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item label="Descripción del Error">
          <a-textarea
            v-decorator="['description', { 
              rules: [{ required: true, message: 'Describe el error en detalle.' }] 
            }]"
            placeholder="Describe qué sucede, cuándo ocurre y cómo reproducirlo..."
            :rows="4"
          />
        </a-form-item>

        <a-form-item label="Pasos para Reproducir">
          <a-textarea
            v-decorator="['steps']"
            placeholder="1. Ir a...\n2. Hacer clic en...\n3. El error ocurre cuando..."
            :rows="3"
          />
        </a-form-item>

        <a-form-item label="Captura de Pantalla (opcional)">
          <a-upload
            name="screenshot"
            list-type="picture-card"
            class="screenshot-uploader"
            :show-upload-list="false"
            :before-upload="beforeUploadScreenshot"
            @change="handleScreenshotChange"
          >
            <img v-if="bugModal.screenshotUrl" :src="bugModal.screenshotUrl" alt="screenshot" style="width: 100%; height: 100%; object-fit: cover;" />
            <div v-else>
              <a-icon :type="bugModal.uploading ? 'loading' : 'plus'" />
              <div class="ant-upload-text">Subir Captura</div>
            </div>
          </a-upload>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal de Solicitud de Mejora -->
    <a-modal
      title="Solicitar Mejora del Sistema"
      :visible="improvementModal.visible"
      @ok="submitImprovement"
      @cancel="closeImprovementModal"
      :confirmLoading="improvementModal.loading"
      width="800px"
    >
      <a-form :form="improvementForm" layout="vertical">
        <a-form-item label="Título de la Mejora">
          <a-input
            v-decorator="['title', { 
              rules: [{ required: true, message: 'Ingresa un título descriptivo.' }] 
            }]"
            placeholder="Ej: Agregar filtro avanzado en reportes de asistencia"
          />
        </a-form-item>

        <a-form-item label="Tipo de Mejora">
          <a-radio-group
            v-decorator="['improvementType', { 
              initialValue: 'improvement',
              rules: [{ required: true, message: 'Selecciona el tipo de mejora.' }] 
            }]"
          >
            <a-radio value="improvement">Mejora Existente</a-radio>
            <a-radio value="feature">Nueva Funcionalidad</a-radio>
            <a-radio value="optimization">Optimización</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item label="Sección del Sistema">
          <a-select
            v-decorator="['section', { 
              rules: [{ required: true, message: 'Selecciona la sección afectada.' }] 
            }]"
            placeholder="Selecciona la sección"
          >
            <a-select-option value="dashboard">Dashboard</a-select-option>
            <a-select-option value="matricula">Matrícula</a-select-option>
            <a-select-option value="pagos">Pagos</a-select-option>
            <a-select-option value="reportes">Reportes</a-select-option>
            <a-select-option value="inventario">Inventario</a-select-option>
            <a-select-option value="usuarios">Usuarios</a-select-option>
            <a-select-option value="other">Otro</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Prioridad">
          <a-radio-group
            v-decorator="['priority', { 
              initialValue: 'medium',
              rules: [{ required: true, message: 'Selecciona la prioridad.' }] 
            }]"
          >
            <a-radio value="low">Baja</a-radio>
            <a-radio value="medium">Media</a-radio>
            <a-radio value="high">Alta</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item label="Descripción de la Mejora">
          <a-textarea
            v-decorator="['description', { 
              rules: [{ required: true, message: 'Describe la mejora solicitada.' }] 
            }]"
            placeholder="Describe qué mejora necesitas y por qué sería útil..."
            :rows="4"
          />
        </a-form-item>

        <a-form-item label="Beneficio Esperado">
          <a-textarea
            v-decorator="['benefit']"
            placeholder="¿Qué beneficios traería esta mejora al sistema?"
            :rows="3"
          />
        </a-form-item>

        <a-form-item label="Solución Sugerida (opcional)">
          <a-textarea
            v-decorator="['solution']"
            placeholder="Si tienes alguna idea de cómo implementar la mejora, descríbela aquí..."
            :rows="3"
          />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal de Detalles -->
    <a-modal
      title="Detalles del Reporte"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="800px"
    >
      <div v-if="detailsModal.report">
        <a-descriptions title="Información General" :column="2" bordered size="small">
          <a-descriptions-item label="ID">
            #{{ detailsModal.report.id }}
          </a-descriptions-item>
          <a-descriptions-item label="Tipo">
            <a-tag :color="getTypeColor(detailsModal.report.type)">
              <a-icon :type="getTypeIcon(detailsModal.report.type)" />
              {{ getTypeText(detailsModal.report.type) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Título">
            {{ detailsModal.report.title }}
          </a-descriptions-item>
          <a-descriptions-item label="Prioridad">
            <a-tag :color="getPriorityColor(detailsModal.report.priority)">
              {{ getPriorityText(detailsModal.report.priority) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Estado">
            <a-badge :status="getStatusBadge(detailsModal.report.status)" :text="getStatusText(detailsModal.report.status)" />
          </a-descriptions-item>
          <a-descriptions-item label="Reportado por">
            {{ detailsModal.report.user.name }}
          </a-descriptions-item>
          <a-descriptions-item label="Fecha">
            {{ formatDate(detailsModal.report.created_at) }}
          </a-descriptions-item>
          <a-descriptions-item label="Sección">
            {{ getSectionText(detailsModal.report.section) }}
          </a-descriptions-item>
        </a-descriptions>

        <div style="margin-top: 24px;">
          <h4>Descripción</h4>
          <p>{{ detailsModal.report.description }}</p>
        </div>

        <div v-if="detailsModal.report.steps" style="margin-top: 16px;">
          <h4>Pasos para Reproducir</h4>
          <pre>{{ detailsModal.report.steps }}</pre>
        </div>

        <div v-if="detailsModal.report.screenshot" style="margin-top: 16px;">
          <h4>Captura de Pantalla</h4>
          <img :src="detailsModal.report.screenshot" style="max-width: 100%; border: 1px solid #d9d9d9; border-radius: 4px;" />
        </div>

        <div style="margin-top: 24px;">
          <h4>Comentarios</h4>
          <a-list
            :data-source="detailsModal.report.comments || []"
            item-layout="horizontal"
          >
            <a-list-item slot="renderItem" slot-scope="item">
              <a-list-item-meta>
                <a-avatar slot="avatar" :src="item.user.avatar">
                  {{ item.user.name.charAt(0) }}
                </a-avatar>
                <a-list-item-meta-title>
                  {{ item.user.name }}
                  <span style="float: right; font-size: 12px; color: #666;">
                    {{ formatDate(item.created_at) }}
                  </span>
                </a-list-item-meta-title>
                <a-list-item-meta-description>
                  {{ item.comment }}
                </a-list-item-meta-description>
              </a-list-item-meta>
            </a-list-item>
          </a-list>
        </div>
      </div>
    </a-modal>

    <!-- Modal de Cambio de Estado -->
    <a-modal
      title="Cambiar Estado"
      :visible="statusModal.visible"
      @ok="updateStatus"
      @cancel="closeStatusModal"
      :confirmLoading="statusModal.loading"
    >
      <a-form :form="statusForm" layout="vertical">
        <a-form-item label="Nuevo Estado">
          <a-select
            v-decorator="['status', { 
              rules: [{ required: true, message: 'Selecciona el nuevo estado.' }] 
            }]"
            placeholder="Selecciona estado"
          >
            <a-select-option value="open">Abierto</a-select-option>
            <a-select-option value="in_progress">En Progreso</a-select-option>
            <a-select-option value="resolved">Resuelto</a-select-option>
            <a-select-option value="closed">Cerrado</a-select-option>
            <a-select-option value="rejected">Rechazado</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Comentario">
          <a-textarea
            v-decorator="['comment']"
            placeholder="Agrega un comentario sobre el cambio de estado..."
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
  name: 'BugReports',
  data() {
    return {
      loading: false,
      reports: [],
      filters: {
        type: '',
        priority: '',
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
        open: 0,
        inProgress: 0,
        resolved: 0,
      },
      bugModal: {
        visible: false,
        loading: false,
        screenshotUrl: '',
        uploading: false,
      },
      improvementModal: {
        visible: false,
        loading: false,
      },
      detailsModal: {
        visible: false,
        report: null,
      },
      statusModal: {
        visible: false,
        loading: false,
        report: null,
      },
    };
  },
  beforeCreate() {
    this.bugForm = this.$form.createForm(this, { name: 'bug_form' });
    this.improvementForm = this.$form.createForm(this, { name: 'improvement_form' });
    this.statusForm = this.$form.createForm(this, { name: 'status_form' });
  },
  mounted() {
    this.fetchReports();
    this.fetchStatistics();
  },
  methods: {
    fetchReports() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        type: this.filters.type || undefined,
        priority: this.filters.priority || undefined,
        status: this.filters.status || undefined,
        search: this.filters.search || undefined,
      };

      axios.get('/api/bug-reports', { params })
        .then(response => {
          this.reports = response.data.data || [];
          this.pagination.total = response.data.total || 0;
        })
        .catch(error => {
          this.$message.error('Error al cargar los reportes');
          console.error('Error fetching reports:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/bug-reports/statistics')
        .then(response => {
          this.statistics = response.data || { total: 0, open: 0, inProgress: 0, resolved: 0 };
        })
        .catch(error => {
          console.error('Error fetching statistics:', error);
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchReports();
    },
    showBugModal() {
      this.bugModal.visible = true;
      this.$nextTick(() => {
        this.bugForm.resetFields();
      });
    },
    closeBugModal() {
      this.bugModal.visible = false;
      this.bugModal.loading = false;
      this.bugModal.screenshotUrl = '';
      this.bugForm.resetFields();
    },
    submitBug() {
      this.bugForm.validateFields((err, values) => {
        if (!err) {
          this.bugModal.loading = true;
          
          const formData = new FormData();
          formData.append('type', 'bug');
          Object.keys(values).forEach(key => {
            formData.append(key, values[key]);
          });
          
          if (this.bugModal.screenshotUrl && typeof this.bugModal.screenshotUrl !== 'string') {
            formData.append('screenshot', this.bugModal.screenshotUrl);
          }

          axios.post('/api/bug-reports', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          })
            .then(() => {
              this.$message.success('Error reportado correctamente');
              this.closeBugModal();
              this.fetchReports();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al reportar el error');
              console.error('Error submitting bug:', error);
            })
            .finally(() => {
              this.bugModal.loading = false;
            });
        }
      });
    },
    showImprovementModal() {
      this.improvementModal.visible = true;
      this.$nextTick(() => {
        this.improvementForm.resetFields();
      });
    },
    closeImprovementModal() {
      this.improvementModal.visible = false;
      this.improvementModal.loading = false;
      this.improvementForm.resetFields();
    },
    submitImprovement() {
      this.improvementForm.validateFields((err, values) => {
        if (!err) {
          this.improvementModal.loading = true;
          
          const reportData = {
            ...values,
            type: values.improvementType === 'feature' ? 'feature' : 'improvement',
          };

          axios.post('/api/bug-reports', reportData)
            .then(() => {
              this.$message.success('Solicitud de mejora enviada correctamente');
              this.closeImprovementModal();
              this.fetchReports();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al enviar la solicitud');
              console.error('Error submitting improvement:', error);
            })
            .finally(() => {
              this.improvementModal.loading = false;
            });
        }
      });
    },
    viewDetails(report) {
      this.detailsModal.report = report;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.report = null;
    },
    editStatus(report) {
      this.statusModal.report = report;
      this.statusModal.visible = true;
      this.$nextTick(() => {
        this.statusForm.setFieldsValue({
          status: report.status,
        });
      });
    },
    closeStatusModal() {
      this.statusModal.visible = false;
      this.statusModal.loading = false;
      this.statusModal.report = null;
      this.statusForm.resetFields();
    },
    updateStatus() {
      this.statusForm.validateFields((err, values) => {
        if (!err) {
          this.statusModal.loading = true;
          
          axios.put(`/api/bug-reports/${this.statusModal.report.id}/status`, values)
            .then(() => {
              this.$message.success('Estado actualizado correctamente');
              this.closeStatusModal();
              this.fetchReports();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al actualizar el estado');
              console.error('Error updating status:', error);
            })
            .finally(() => {
              this.statusModal.loading = false;
            });
        }
      });
    },
    addComment(report) {
      // Implementar modal de comentarios
      this.$message.info('Función de comentarios en desarrollo');
    },
    assignTo(report) {
      // Implementar modal de asignación
      this.$message.info('Función de asignación en desarrollo');
    },
    closeReport(report) {
      this.$confirm({
        title: '¿Estás seguro de cerrar este reporte?',
        content: 'Esta acción no se puede deshacer.',
        onOk: () => {
          axios.put(`/api/bug-reports/${report.id}/close`)
            .then(() => {
              this.$message.success('Reporte cerrado correctamente');
              this.fetchReports();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al cerrar el reporte');
              console.error('Error closing report:', error);
            });
        },
      });
    },
    beforeUploadScreenshot(file) {
      const isImage = file.type.startsWith('image/');
      if (!isImage) {
        this.$message.error('Solo se permiten archivos de imagen');
        return false;
      }
      const isLt5M = file.size / 1024 / 1024 < 5;
      if (!isLt5M) {
        this.$message.error('La imagen debe ser menor a 5MB');
        return false;
      }
      return false; // Prevenir upload automático
    },
    handleScreenshotChange({ file }) {
      this.bugModal.screenshotUrl = file;
    },
    getTypeColor(type) {
      const colors = {
        bug: 'red',
        improvement: 'blue',
        feature: 'green',
      };
      return colors[type] || 'default';
    },
    getTypeIcon(type) {
      const icons = {
        bug: 'bug',
        improvement: 'bulb',
        feature: 'plus-circle',
      };
      return icons[type] || 'file';
    },
    getTypeText(type) {
      const types = {
        bug: 'Error',
        improvement: 'Mejora',
        feature: 'Nueva Funcionalidad',
      };
      return types[type] || type;
    },
    getPriorityColor(priority) {
      const colors = {
        critical: 'red',
        high: 'orange',
        medium: 'blue',
        low: 'green',
      };
      return colors[priority] || 'default';
    },
    getPriorityText(priority) {
      const priorities = {
        critical: 'Crítica',
        high: 'Alta',
        medium: 'Media',
        low: 'Baja',
      };
      return priorities[priority] || priority;
    },
    getStatusBadge(status) {
      const badges = {
        open: 'error',
        in_progress: 'processing',
        resolved: 'success',
        closed: 'default',
        rejected: 'warning',
      };
      return badges[status] || 'default';
    },
    getStatusText(status) {
      const statuses = {
        open: 'Abierto',
        in_progress: 'En Progreso',
        resolved: 'Resuelto',
        closed: 'Cerrado',
        rejected: 'Rechazado',
      };
      return statuses[status] || status;
    },
    getSectionText(section) {
      const sections = {
        dashboard: 'Dashboard',
        matricula: 'Matrícula',
        pagos: 'Pagos',
        reportes: 'Reportes',
        inventario: 'Inventario',
        usuarios: 'Usuarios',
        other: 'Otro',
      };
      return sections[section] || section;
    },
    formatDate(dateString) {
      return moment(dateString).format('DD/MM/YYYY HH:mm');
    },
  },
  computed: {
    reportColumns() {
      return [
        {
          title: 'Tipo',
          key: 'type',
          scopedSlots: { customRender: 'type' },
          width: 120,
        },
        {
          title: 'Título',
          key: 'title',
          scopedSlots: { customRender: 'title' },
          width: 250,
        },
        {
          title: 'Prioridad',
          key: 'priority',
          scopedSlots: { customRender: 'priority' },
          width: 100,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 120,
        },
        {
          title: 'Reportado por',
          key: 'user',
          scopedSlots: { customRender: 'user' },
          width: 150,
        },
        {
          title: 'Fecha',
          key: 'date',
          scopedSlots: { customRender: 'date' },
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
};
</script>

<style scoped>
.bug-reports {
  padding: 24px;
}

.action-buttons {
  margin-bottom: 24px;
  padding: 20px;
  background: #fafafa;
  border-radius: 8px;
}

.filters-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #f0f2f5;
  border-radius: 8px;
}

.stats-section {
  margin: 24px 0;
  padding: 16px;
  background: #f0f2f5;
  border-radius: 8px;
}

.table-section {
  margin-top: 24px;
}

.report-title {
  display: flex;
  flex-direction: column;
}

.title-text {
  font-weight: 500;
  font-size: 14px;
}

.report-id {
  font-size: 12px;
  color: #666;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-name {
  font-size: 14px;
}

.screenshot-uploader {
  text-align: center;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  padding: 20px;
  cursor: pointer;
  transition: border-color 0.3s;
}

.screenshot-uploader:hover {
  border-color: #1890ff;
}
</style>
