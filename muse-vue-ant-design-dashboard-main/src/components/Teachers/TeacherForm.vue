<template>
  <a-form :form="form" @submit="handleSubmit">
    <a-form-item label="Foto">
      <div class="teacher-photo-row">
        <a-avatar
          shape="square"
          :size="120"
          :src="photoPreview || null"
          class="teacher-photo-avatar"
        >
          <a-icon v-if="!photoPreview" type="camera" style="color: #8c8c8c; font-size: 24px;" />
        </a-avatar>
        <div class="teacher-photo-actions">
          <a-upload
            :beforeUpload="beforeUpload"
            :showUploadList="false"
            accept="image/*"
          >
            <a-button class="btn-add-outline">
              <svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5v14M5 12h14" fill="none" />
              </svg>
              {{ photoPreview ? 'Cambiar foto' : 'Subir foto' }}
            </a-button>
          </a-upload>
          <a-button
            v-if="photoPreview && !editing"
            type="link"
            @click="clearPhoto"
          >
            Quitar
          </a-button>
          <a-button
            v-if="photoPreview && editing && selectedNewPhoto"
            type="link"
            @click="clearPhoto"
          >
            Quitar cambio
          </a-button>
        </div>
      </div>
    </a-form-item>
    <a-form-item label="Nombre Completo">
      <a-input
        :maxLength="200"
        v-decorator="['fullName', { rules: [{ required: true, message: 'Por favor ingresa el nombre completo.' }] }]"
        placeholder="Nombre Completo"
      />
    </a-form-item>

    <a-form-item label="Correo">
      <a-input
        :maxLength="200"
        v-decorator="['email', { rules: [{ required: true, type: 'email', message: 'Por favor ingresa un correo válido.' }] }]"
        placeholder="correo@dominio.com"
      />
    </a-form-item>

    <a-form-item label="Teléfono">
      <a-input :maxLength="200" v-decorator="['phone']" placeholder="Opcional" />
    </a-form-item>

    <a-form-item label="Cargo">
      <a-select
        v-decorator="['position', { rules: [{ required: true, message: 'Selecciona el cargo.' }] }]"
        placeholder="Selecciona el cargo"
        :loading="loadingPositions"
      >
        <a-select-option v-for="pos in positions" :key="pos" :value="pos">
          {{ pos }}
        </a-select-option>
      </a-select>
    </a-form-item>

    <a-form-item label="Fecha de nacimiento">
      <a-date-picker
        v-decorator="['birthDate', { rules: [{ required: true, message: 'Selecciona la fecha de nacimiento.' }] }]"
        style="width: 100%"
      />
    </a-form-item>

    <a-form-item label="Número de identidad">
      <a-input
        :maxLength="200"
        v-decorator="['identityNumber', { rules: [{ required: true, message: 'Ingresa el número de identidad.' }] }]"
        placeholder="Número de identidad"
      />
    </a-form-item>

    <a-form-item label="Fecha de ingreso">
      <a-date-picker
        v-decorator="['entryDate', { rules: [{ required: true, message: 'Selecciona la fecha de ingreso.' }] }]"
        style="width: 100%"
      />
    </a-form-item>

    <a-form-item label="Fecha de egreso (opcional)">
      <a-date-picker v-decorator="['exitDate']" style="width: 100%" />
    </a-form-item>

    <a-form-item label="Estado">
      <a-select
        v-decorator="['status', { rules: [{ required: true, message: 'Selecciona el estado.' }] }]"
        placeholder="Selecciona el estado"
      >
        <a-select-option value="Activo">Activo</a-select-option>
        <a-select-option value="Inactivo">Inactivo</a-select-option>
      </a-select>
    </a-form-item>

    <a-form-item>
      <a-button type="primary" html-type="submit">
        {{ submitText }}
      </a-button>
    </a-form-item>
  </a-form>
</template>

<script>
import moment from 'moment';
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
  props: {
    initialValues: {
      type: Object,
      default: null,
    },
    submitText: {
      type: String,
      default: 'Guardar',
    },
    resetAfterSubmit: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      form: this.$form.createForm(this, { name: 'teacher_form' }),
      photoFile: null,
      photoPreview: '',
      positions: [],
      loadingPositions: false,
    };
  },
  computed: {
    editing() {
      return !!(this.initialValues && this.initialValues.id);
    },
    selectedNewPhoto() {
      return !!this.photoFile;
    },
  },
  watch: {
    initialValues: {
      immediate: true,
      handler(values) {
        if (!this.form) return;
        if (!values) {
          this.form.resetFields();
          this.clearPhoto(true);
          return;
        }
        this.form.setFieldsValue({
          fullName: values.fullName || '',
          email: values.email || '',
          phone: values.phone || '',
          position: values.position || '',
          birthDate: values.birthDate ? moment(values.birthDate) : null,
          identityNumber: values.identityNumber || '',
          entryDate: values.entryDate ? moment(values.entryDate) : null,
          exitDate: values.exitDate ? moment(values.exitDate) : null,
          status: values.status || 'Activo',
        });

        // Foto inicial (modo edición)
        this.clearPhoto(true);
        if (values.photo_url) {
          this.photoPreview = values.photo_url;
        }
      },
    },
  },
  mounted() {
    this.fetchPositions();
  },
  methods: {
    fetchPositions() {
      this.loadingPositions = true;
      const authToken = getToken();
      axios.get('http://localhost:8000/api/positions', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((response) => {
          // Filtrar solo posiciones activas
          this.positions = (response.data || [])
            .filter(pos => pos.status === 'Activo')
            .map(pos => pos.name);
        })
        .catch((error) => {
          console.error('Error al obtener cargos:', error.response?.data || error);
          this.positions = [];
        })
        .finally(() => {
          this.loadingPositions = false;
        });
    },
    beforeUpload(file) {
      const isImage = file && String(file.type || '').startsWith('image/');
      if (!isImage) {
        this.$message && this.$message.error('La foto debe ser una imagen.');
        return false;
      }

      // Liberar preview anterior si era objectURL
      if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
        try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
      }

      this.photoFile = file;
      this.photoPreview = URL.createObjectURL(file);
      return false; // evitar auto-upload, lo mandamos junto al formulario
    },
    clearPhoto(silent = false) {
      if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
        try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
      }
      this.photoFile = null;
      this.photoPreview = '';
      if (!silent && this.$message) this.$message.info('Foto removida.');
    },
    handleSubmit(e) {
      e.preventDefault();
      this.form.validateFields((err, values) => {
        if (!err) {
          values.birthDate = values.birthDate ? values.birthDate.format('YYYY-MM-DD') : null;
          values.entryDate = values.entryDate ? values.entryDate.format('YYYY-MM-DD') : null;
          values.exitDate = values.exitDate ? values.exitDate.format('YYYY-MM-DD') : null;
          
          // Adjuntar archivo de foto si existe
          const payload = { ...values, photoFile: this.photoFile };
          this.$emit('submitForm', payload);
          if (this.resetAfterSubmit) this.form.resetFields();
        }
      });
    },
  },
  beforeDestroy() {
    if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
      try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
    }
  },
};
</script>

<style scoped>
.teacher-photo-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
}
.teacher-photo-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.teacher-photo-avatar {
  border: 2px solid rgba(17, 24, 39, 0.08);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
