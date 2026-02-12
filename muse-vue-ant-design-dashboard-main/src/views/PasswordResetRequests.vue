<template>
  <div class="password-reset-requests">
    <a-card title="Solicitudes de Restablecimiento de Contraseña" :loading="loading">
      <!-- Filtros -->
      <div class="filters-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-select
              v-model="filters.status"
              placeholder="Filtrar por estado"
              style="width: 100%"
              @change="fetchRequests"
            >
              <a-select-option value="">Todos</a-select-option>
              <a-select-option value="pending">Pendientes</a-select-option>
              <a-select-option value="approved">Aprobados</a-select-option>
              <a-select-option value="rejected">Rechazados</a-select-option>
              <a-select-option value="completed">Completados</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-input-search
              v-model="filters.search"
              placeholder="Buscar por correo o usuario"
              @search="fetchRequests"
              style="width: 100%"
            />
          </a-col>
          <a-col :span="6">
            <a-range-picker
              v-model="filters.dateRange"
              @change="fetchRequests"
              style="width: 100%"
            />
          </a-col>
          <a-col :span="6">
            <a-button type="primary" @click="fetchRequests" :loading="loading">
              <a-icon type="reload" /> Actualizar
            </a-button>
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de solicitudes -->
      <a-table
        :columns="columns"
        :data-source="requests"
        :loading="loading"
        :pagination="pagination"
        @change="handleTableChange"
        row-key="id"
        class="requests-table"
      >
        <!-- Columna de estado -->
        <template slot="status" slot-scope="text, record">
          <a-tag :color="getStatusColor(record.status)">
            {{ getStatusText(record.status) }}
          </a-tag>
        </template>

        <!-- Columna de fecha -->
        <template slot="created_at" slot-scope="text">
          {{ formatDate(text) }}
        </template>

        <!-- Columna de acciones -->
        <template slot="action" slot-scope="text, record">
          <div class="action-buttons">
            <a-button
              v-if="record.status === 'pending'"
              type="primary"
              size="small"
              @click="showApproveModal(record)"
              :disabled="record.being_processed"
            >
              <a-icon type="check" /> Aprobar
            </a-button>
            
            <a-button
              v-if="record.status === 'pending'"
              type="danger"
              size="small"
              @click="showRejectModal(record)"
              :disabled="record.being_processed"
            >
              <a-icon type="close" /> Rechazar
            </a-button>

            <a-button
              v-if="record.status === 'approved' && !record.being_processed"
              type="success"
              size="small"
              @click="showResetModal(record)"
            >
              <a-icon type="key" /> Restablecer
            </a-button>

            <a-button
              v-if="record.being_processed"
              type="default"
              size="small"
              disabled
            >
              <a-icon type="loading" /> Procesando...
            </a-button>

            <a-button
              type="default"
              size="small"
              @click="showDetailsModal(record)"
            >
              <a-icon type="eye" /> Detalles
            </a-button>
          </div>
        </template>
      </a-table>
    </a-card>

    <!-- Modal para aprobar solicitud -->
    <a-modal
      title="Aprobar Solicitud"
      :visible="approveModal.visible"
      @ok="handleApprove"
      @cancel="closeApproveModal"
      :confirmLoading="approveModal.loading"
    >
      <p>¿Estás seguro que deseas aprobar esta solicitud de restablecimiento?</p>
      <p><strong>Usuario:</strong> {{ approveModal.request && approveModal.request.user ? approveModal.request.user.name : '' }} ({{ approveModal.request && approveModal.request.user ? approveModal.request.user.email : '' }})</p>
      <p><strong>Motivo:</strong> {{ approveModal.request ? approveModal.request.reason : '' }}</p>
    </a-modal>

    <!-- Modal para rechazar solicitud -->
    <a-modal
      title="Rechazar Solicitud"
      :visible="rejectModal.visible"
      @ok="handleReject"
      @cancel="closeRejectModal"
      :confirmLoading="rejectModal.loading"
    >
      <a-form :form="rejectForm">
        <a-form-item label="Motivo del rechazo">
          <a-textarea
            v-decorator="['rejection_reason', { 
              rules: [{ required: true, message: 'Por favor, indica el motivo del rechazo.' }] 
            }]"
            placeholder="Explica por qué se rechaza esta solicitud..."
            :rows="4"
          />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal para restablecer contraseña -->
    <a-modal
      title="Restablecer Contraseña"
      :visible="resetModal.visible"
      @ok="handleResetPassword"
      @cancel="closeResetModal"
      :confirmLoading="resetModal.loading"
      width="500px"
    >
      <a-form :form="resetForm">
        <a-alert
          message="Restablecimiento de contraseña"
          description="Ingresa la nueva contraseña para el usuario. Se le notificará por correo electrónico."
          type="info"
          show-icon
          style="margin-bottom: 20px;"
        />
        
        <a-form-item label="Nueva contraseña">
          <a-input-password
            v-decorator="['new_password', { 
              rules: [
                { required: true, message: 'Ingresa la nueva contraseña.' },
                { min: 8, message: 'Mínimo 8 caracteres.' },
                { pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/, message: 'Debe incluir mayúsculas, minúsculas y números.' }
              ] 
            }]"
            placeholder="Nueva contraseña"
          />
        </a-form-item>

        <a-form-item label="Confirmar contraseña">
          <a-input-password
            v-decorator="['password_confirmation', { 
              rules: [
                { required: true, message: 'Confirma la contraseña.' },
                { validator: compareToFirstPassword }
              ] 
            }]"
            placeholder="Confirmar contraseña"
          />
        </a-form-item>

        <a-form-item>
          <a-checkbox v-model="resetModal.sendEmail">
            Enviar notificación por correo al usuario
          </a-checkbox>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal de detalles -->
    <a-modal
      title="Detalles de la Solicitud"
      :visible="detailsModal.visible"
      @cancel="closeDetailsModal"
      :footer="null"
      width="600px"
    >
      <div v-if="detailsModal.request">
        <a-descriptions :column="2" bordered>
          <a-descriptions-item label="ID">{{ detailsModal.request.id }}</a-descriptions-item>
          <a-descriptions-item label="Estado">
            <a-tag :color="getStatusColor(detailsModal.request.status)">
              {{ getStatusText(detailsModal.request.status) }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="Usuario">{{ detailsModal.request && detailsModal.request.user ? detailsModal.request.user.name : '' }}</a-descriptions-item>
          <a-descriptions-item label="Correo">{{ detailsModal.request && detailsModal.request.user ? detailsModal.request.user.email : '' }}</a-descriptions-item>
          <a-descriptions-item label="Fecha solicitud">{{ formatDate(detailsModal.request && detailsModal.request.created_at ? detailsModal.request.created_at : '') }}</a-descriptions-item>
          <a-descriptions-item label="IP">{{ detailsModal.request ? detailsModal.request.ip_address : '' }}</a-descriptions-item>
          <a-descriptions-item label="Motivo" :span="2">{{ detailsModal.request ? detailsModal.request.reason : '' }}</a-descriptions-item>
          <a-descriptions-item label="Procesado por" v-if="detailsModal.request && detailsModal.request.processed_by">
            {{ detailsModal.request && detailsModal.request.processed_by ? detailsModal.request.processed_by.name : '' }}
          </a-descriptions-item>
          <a-descriptions-item label="Fecha procesamiento" v-if="detailsModal.request && detailsModal.request.processed_at">
            {{ formatDate(detailsModal.request && detailsModal.request.processed_at ? detailsModal.request.processed_at : '') }}
          </a-descriptions-item>
          <a-descriptions-item label="Notas" :span="2" v-if="detailsModal.request && detailsModal.request.notes">
            {{ detailsModal.request ? detailsModal.request.notes : '' }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PasswordResetRequests',
  data() {
    return {
      loading: false,
      requests: [],
      filters: {
        status: '',
        search: '',
        dateRange: null,
      },
      pagination: {
        current: 1,
        pageSize: 10,
        total: 0,
      },
      approveModal: {
        visible: false,
        loading: false,
        request: null,
      },
      rejectModal: {
        visible: false,
        loading: false,
        request: null,
      },
      resetModal: {
        visible: false,
        loading: false,
        request: null,
        sendEmail: true,
      },
      detailsModal: {
        visible: false,
        request: null,
      },
    };
  },
  computed: {
    columns() {
      return [
        {
          title: 'ID',
          dataIndex: 'id',
          key: 'id',
          width: 80,
        },
        {
          title: 'Usuario',
          dataIndex: ['user', 'name'],
          key: 'user_name',
        },
        {
          title: 'Correo',
          dataIndex: ['user', 'email'],
          key: 'user_email',
        },
        {
          title: 'Motivo',
          dataIndex: 'reason',
          key: 'reason',
          ellipsis: true,
        },
        {
          title: 'Fecha',
          dataIndex: 'created_at',
          key: 'created_at',
          scopedSlots: { customRender: 'created_at' },
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
        },
        {
          title: 'Acciones',
          key: 'action',
          scopedSlots: { customRender: 'action' },
        },
      ];
    },
  },
  beforeCreate() {
    this.rejectForm = this.$form.createForm(this, { name: 'reject_form' });
    this.resetForm = this.$form.createForm(this, { name: 'reset_form' });
  },
  mounted() {
    this.fetchRequests();
  },
  methods: {
    fetchRequests() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        status: this.filters.status || undefined,
        search: this.filters.search || undefined,
        date_from: this.filters.dateRange?.[0]?.format('YYYY-MM-DD'),
        date_to: this.filters.dateRange?.[1]?.format('YYYY-MM-DD'),
      };

      axios.get('/api/password-reset-requests', { params })
        .then(response => {
          this.requests = response.data.data || [];
          this.pagination.total = response.data.total || 0;
        })
        .catch(error => {
          this.$message.error('Error al cargar las solicitudes');
          console.error('Error fetching password reset requests:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchRequests();
    },
    showApproveModal(request) {
      this.approveModal.request = request;
      this.approveModal.visible = true;
    },
    closeApproveModal() {
      this.approveModal.visible = false;
      this.approveModal.request = null;
      this.approveModal.loading = false;
    },
    handleApprove() {
      this.approveModal.loading = true;
      axios.post(`/api/password-reset-requests/${this.approveModal.request.id}/approve`)
        .then(() => {
          this.$message.success('Solicitud aprobada correctamente');
          this.closeApproveModal();
          this.fetchRequests();
        })
        .catch(error => {
          this.$message.error('Error al aprobar la solicitud');
          console.error('Error approving request:', error);
        })
        .finally(() => {
          this.approveModal.loading = false;
        });
    },
    showRejectModal(request) {
      this.rejectModal.request = request;
      this.rejectModal.visible = true;
    },
    closeRejectModal() {
      this.rejectModal.visible = false;
      this.rejectModal.request = null;
      this.rejectModal.loading = false;
      this.rejectForm.resetFields();
    },
    handleReject() {
      this.rejectForm.validateFields((err, values) => {
        if (!err) {
          this.rejectModal.loading = true;
          axios.post(`/api/password-reset-requests/${this.rejectModal.request.id}/reject`, {
            rejection_reason: values.rejection_reason,
          })
            .then(() => {
              this.$message.success('Solicitud rechazada correctamente');
              this.closeRejectModal();
              this.fetchRequests();
            })
            .catch(error => {
              this.$message.error('Error al rechazar la solicitud');
              console.error('Error rejecting request:', error);
            })
            .finally(() => {
              this.rejectModal.loading = false;
            });
        }
      });
    },
    showResetModal(request) {
      this.resetModal.request = request;
      this.resetModal.visible = true;
    },
    closeResetModal() {
      this.resetModal.visible = false;
      this.resetModal.request = null;
      this.resetModal.loading = false;
      this.resetForm.resetFields();
    },
    handleResetPassword() {
      this.resetForm.validateFields((err, values) => {
        if (!err) {
          this.resetModal.loading = true;
          axios.post(`/api/password-reset-requests/${this.resetModal.request.id}/reset-password`, {
            new_password: values.new_password,
            password_confirmation: values.password_confirmation,
            send_email: this.resetModal.sendEmail,
          })
            .then(() => {
              this.$message.success('Contraseña restablecida correctamente');
              this.closeResetModal();
              this.fetchRequests();
            })
            .catch(error => {
              this.$message.error('Error al restablecer la contraseña');
              console.error('Error resetting password:', error);
            })
            .finally(() => {
              this.resetModal.loading = false;
            });
        }
      });
    },
    showDetailsModal(request) {
      this.detailsModal.request = request;
      this.detailsModal.visible = true;
    },
    closeDetailsModal() {
      this.detailsModal.visible = false;
      this.detailsModal.request = null;
    },
    compareToFirstPassword(rule, value, callback) {
      const form = this.resetForm;
      if (value && value !== form.getFieldValue('new_password')) {
        callback('Las contraseñas no coinciden.');
      } else {
        callback();
      }
    },
    getStatusColor(status) {
      const colors = {
        pending: 'orange',
        approved: 'blue',
        rejected: 'red',
        completed: 'green',
      };
      return colors[status] || 'default';
    },
    getStatusText(status) {
      const texts = {
        pending: 'Pendiente',
        approved: 'Aprobado',
        rejected: 'Rechazado',
        completed: 'Completado',
      };
      return texts[status] || status;
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleString('es-ES');
    },
  },
};
</script>

<style scoped>
.password-reset-requests {
  padding: 24px;
}

.filters-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.requests-table {
  margin-top: 16px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.action-buttons .ant-btn {
  margin: 0;
}
</style>