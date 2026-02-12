<template>
  <a-form :form="form" @submit="handleSubmit">
    <a-form-item label="Foto">
      <div class="student-photo-row">
        <a-avatar
          shape="square"
          :size="120"
          :src="photoPreview || null"
          class="student-photo-avatar"
        >
          <a-icon v-if="!photoPreview" type="camera" style="color: #8c8c8c; font-size: 24px;" />
        </a-avatar>
        <div class="student-photo-actions">
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
        v-decorator="['fullName', { rules: [{ required: true, message: 'Por favor ingresa el nombre completo del estudiante!' }] }]"
        placeholder="Nombre Completo"
      />
    </a-form-item>

    <a-form-item label="Grado/Curso">
      <a-select
        show-search
        placeholder="Selecciona un grado"
        option-filter-prop="children"
        v-decorator="['gradeCourse']"
      >
        <a-select-option v-for="g in normalizedGrades" :key="g" :value="g">
          {{ g }}
        </a-select-option>
      </a-select>
    </a-form-item>

    <a-form-item label="Fecha de Nacimiento">
      <a-date-picker
        v-decorator="['birthDate', { rules: [{ required: true, message: 'Por favor selecciona la fecha de nacimiento!' }] }]"
        style="width: 100%"
      />
    </a-form-item>

    <!-- Sección de Padres/Encargados -->
    <a-divider orientation="left">Padres o Encargados</a-divider>
    
    <a-form-item label="Padre/Madre/Encargado Principal">
      <a-input
        :maxLength="200"
        v-decorator="['parent1_name']"
        placeholder="Nombre completo del padre/madre/encargado principal"
      />
    </a-form-item>

    <a-row :gutter="16">
      <a-col :span="12">
        <a-form-item label="Número de Identidad">
          <a-input
            :maxLength="50"
            v-decorator="['parent1_identity']"
            placeholder="DUI/NIT/Pass"
          />
        </a-form-item>
      </a-col>
      <a-col :span="12">
        <a-form-item label="Parentesco">
          <a-select
            v-decorator="['parent1_relationship']"
            placeholder="Seleccionar parentesco"
          >
            <a-select-option value="Padre">Padre</a-select-option>
            <a-select-option value="Madre">Madre</a-select-option>
            <a-select-option value="Tutor">Tutor</a-select-option>
            <a-select-option value="Encargado">Encargado</a-select-option>
            <a-select-option value="Abuelo/a">Abuelo/a</a-select-option>
            <a-select-option value="Otro">Otro</a-select-option>
          </a-select>
        </a-form-item>
      </a-col>
    </a-row>

    <a-row :gutter="16">
      <a-col :span="12">
        <a-form-item label="Teléfono">
          <a-input
            :maxLength="20"
            v-decorator="['parent1_phone']"
            placeholder="Teléfono de contacto"
          />
        </a-form-item>
      </a-col>
      <a-col :span="12">
        <a-form-item label="Ocupación">
          <a-input
            :maxLength="100"
            v-decorator="['parent1_occupation']"
            placeholder="Ocupación o trabajo"
          />
        </a-form-item>
      </a-col>
    </a-row>

    <a-form-item label="Correo Electrónico">
      <a-input
        :maxLength="200"
        v-decorator="['parent1_email']"
        placeholder="Correo electrónico (opcional)"
      />
    </a-form-item>

    <a-divider orientation="left">Padre/Madre/Encargado Secundario (Opcional)</a-divider>
    
    <a-form-item label="Nombre">
      <a-input
        :maxLength="200"
        v-decorator="['parent2_name']"
        placeholder="Nombre completo del segundo padre/madre/encargado"
      />
    </a-form-item>

    <a-row :gutter="16">
      <a-col :span="12">
        <a-form-item label="Número de Identidad">
          <a-input
            :maxLength="50"
            v-decorator="['parent2_identity']"
            placeholder="DUI/NIT/Pass"
          />
        </a-form-item>
      </a-col>
      <a-col :span="12">
        <a-form-item label="Parentesco">
          <a-select
            v-decorator="['parent2_relationship']"
            placeholder="Seleccionar parentesco"
          >
            <a-select-option value="Padre">Padre</a-select-option>
            <a-select-option value="Madre">Madre</a-select-option>
            <a-select-option value="Tutor">Tutor</a-select-option>
            <a-select-option value="Encargado">Encargado</a-select-option>
            <a-select-option value="Abuelo/a">Abuelo/a</a-select-option>
            <a-select-option value="Otro">Otro</a-select-option>
          </a-select>
        </a-form-item>
      </a-col>
    </a-row>

    <a-row :gutter="16">
      <a-col :span="12">
        <a-form-item label="Teléfono">
          <a-input
            :maxLength="20"
            v-decorator="['parent2_phone']"
            placeholder="Teléfono de contacto"
          />
        </a-form-item>
      </a-col>
      <a-col :span="12">
        <a-form-item label="Ocupación">
          <a-input
            :maxLength="100"
            v-decorator="['parent2_occupation']"
            placeholder="Ocupación o trabajo"
          />
        </a-form-item>
      </a-col>
    </a-row>


    <a-form-item>
      <a-button type="primary" html-type="submit">
        {{ submitText }}
      </a-button>
    </a-form-item>
  </a-form>
</template>

<script>
import moment from 'moment';

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
    grades: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      formLayout: 'horizontal',
      form: this.$form.createForm(this, { name: 'student_form' }),
      photoFile: null,
      photoPreview: '',
    };
  },
  computed: {
    normalizedGrades() {
      const list = Array.isArray(this.grades) ? this.grades : [];
      return list
        .filter(Boolean)
        .slice()
        .sort((a, b) => String(a).localeCompare(String(b), 'es', { sensitivity: 'base' }));
    },
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
          gradeCourse: values.gradeCourse || '',
          birthDate: values.birthDate ? moment(values.birthDate) : null,
          // Datos del padre/madre principal
          parent1_name: values.parent1_name || '',
          parent1_identity: values.parent1_identity || '',
          parent1_relationship: values.parent1_relationship || '',
          parent1_phone: values.parent1_phone || '',
          parent1_occupation: values.parent1_occupation || '',
          parent1_email: values.parent1_email || '',
          // Datos del padre/madre secundario
          parent2_name: values.parent2_name || '',
          parent2_identity: values.parent2_identity || '',
          parent2_relationship: values.parent2_relationship || '',
          parent2_phone: values.parent2_phone || '',
          parent2_occupation: values.parent2_occupation || '',
        });

        // Foto inicial (modo edición)
        this.clearPhoto(true);
        if (values.photo_url) {
          this.photoPreview = values.photo_url;
        }
      },
    },
  },
  methods: {
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
          // Convert moment object to string for birthDate
          values.birthDate = values.birthDate ? values.birthDate.format('YYYY-MM-DD') : null;
          // Adjuntar archivo (si existe) para que el padre lo envíe con FormData
          this.$emit('submitForm', { ...values, photoFile: this.photoFile });
          if (this.resetAfterSubmit) this.form.resetFields(); // Reset form after submission
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
.student-photo-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
}
.student-photo-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.student-photo-avatar {
  border: 2px solid rgba(17, 24, 39, 0.08);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
