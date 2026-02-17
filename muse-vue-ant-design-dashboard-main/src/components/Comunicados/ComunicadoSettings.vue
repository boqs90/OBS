<template>
  <div class="comunicado-settings">
    <a-card title="Configuración de Comunicados" :bordered="false">
      <a-form :layout="vertical" :form="form" @submit="handleSubmit">
        <!-- Configuración General -->
        <h3 class="section-title">Configuración General</h3>
        
        <a-form-item label="Estado del Sistema">
          <a-switch 
            v-decorator="['enabled', { initialValue: settings.enabled }]"
            checkedChildren="Activo"
            unCheckedChildren="Inactivo"
          />
          <div class="form-help">
            Activa o desactiva todo el sistema de comunicados
          </div>
        </a-form-item>

        <a-form-item label="Mostrar al Iniciar Sesión">
          <a-switch 
            v-decorator="['showOnLogin', { initialValue: settings.showOnLogin }]"
            checkedChildren="Mostrar"
            unCheckedChildren="Ocultar"
          />
          <div class="form-help">
            Muestra automáticamente el modal de comunicados cuando los usuarios inician sesión
          </div>
        </a-form-item>

        <a-form-item label="Cierre Automático">
          <a-switch 
            v-decorator="['autoClose', { initialValue: settings.autoClose }]"
            checkedChildren="Activado"
            unCheckedChildren="Desactivado"
          />
          <div class="form-help">
            Cierra automáticamente el modal después de un tiempo determinado
          </div>
        </a-form-item>

        <a-form-item 
          v-if="form.getFieldValue('autoClose')"
          label="Tiempo de Cierre (segundos)"
        >
          <a-input-number
            v-decorator="['autoCloseDelay', { 
              initialValue: settings.autoCloseDelay,
              min: 5,
              max: 300
            }]"
            :step="5"
            style="width: 200px;"
          />
          <div class="form-help">
            Tiempo en segundos antes de que el modal se cierre automáticamente
          </div>
        </a-form-item>

        <!-- Configuración de Archivos -->
        <h3 class="section-title">Configuración de Archivos</h3>
        
        <a-form-item label="Permitir Subida de Archivos">
          <a-switch 
            v-decorator="['allowUploads', { initialValue: settings.allowUploads }]"
            checkedChildren="Permitido"
            unCheckedChildren="Bloqueado"
          />
          <div class="form-help">
            Permite que los usuarios suban archivos a los comunicados
          </div>
        </a-form-item>

        <a-form-item 
          v-if="form.getFieldValue('allowUploads')"
          label="Tamaño Máximo de Archivo (MB)"
        >
          <a-input-number
            v-decorator="['maxFileSize', { 
              initialValue: settings.maxFileSize,
              min: 1,
              max: 100
            }]"
            style="width: 200px;"
          />
          <div class="form-help">
            Tamaño máximo permitido para cada archivo en megabytes
          </div>
        </a-form-item>

        <a-form-item 
          v-if="form.getFieldValue('allowUploads')"
          label="Tipos de Archivo Permitidos"
        >
          <a-checkbox-group v-decorator="['allowedFileTypes', { initialValue: settings.allowedFileTypes }]">
            <a-row>
              <a-col :span="8">
                <a-checkbox value="image/*">Imágenes</a-checkbox>
              </a-col>
              <a-col :span="8">
                <a-checkbox value="video/*">Videos</a-checkbox>
              </a-col>
              <a-col :span="8">
                <a-checkbox value="application/pdf">PDF</a-checkbox>
              </a-col>
              <a-col :span="8">
                <a-checkbox value=".doc,.docx">Word</a-checkbox>
              </a-col>
              <a-col :span="8">
                <a-checkbox value=".xls,.xlsx">Excel</a-checkbox>
              </a-col>
              <a-col :span="8">
                <a-checkbox value=".ppt,.pptx">PowerPoint</a-checkbox>
              </a-col>
            </a-row>
          </a-checkbox-group>
          <div class="form-help">
            Selecciona los tipos de archivo que los usuarios pueden subir
          </div>
        </a-form-item>

        <!-- Configuración de Aceptación -->
        <h3 class="section-title">Configuración de Aceptación</h3>
        
        <a-form-item label="Requerir Aceptación">
          <a-switch 
            v-decorator="['requireAcceptance', { initialValue: settings.requireAcceptance }]"
            checkedChildren="Requerido"
            unCheckedChildren="Opcional"
          />
          <div class="form-help">
            Obliga a los usuarios a marcar que han leído cada comunicado
          </div>
        </a-form-item>

        <!-- Botones de Acción -->
        <a-form-item>
          <a-space>
            <a-button @click="resetToDefaults">
              <a-icon type="reload" />
              Restablecer Valores por Defecto
            </a-button>
            <a-button type="primary" html-type="submit" :loading="saving">
              <a-icon type="save" />
              Guardar Configuración
            </a-button>
          </a-space>
        </a-form-item>
      </a-form>
    </a-card>

    <!-- Vista Previa -->
    <a-card title="Vista Previa" :bordered="false" style="margin-top: 24px;">
      <div class="preview-container">
        <div class="preview-info">
          <h4>Así se verá el modal para los usuarios:</h4>
          
          <div class="preview-features">
            <div class="feature-item">
              <a-icon :type="form.getFieldValue('enabled') ? 'check-circle' : 'close-circle'" :style="{ color: form.getFieldValue('enabled') ? '#52c41a' : '#ff4d4f' }" />
              <span>Sistema {{ form.getFieldValue('enabled') ? 'activo' : 'inactivo' }}</span>
            </div>
            
            <div class="feature-item">
              <a-icon :type="form.getFieldValue('showOnLogin') ? 'check-circle' : 'close-circle'" :style="{ color: form.getFieldValue('showOnLogin') ? '#52c41a' : '#ff4d4f' }" />
              <span>{{ form.getFieldValue('showOnLogin') ? 'Se mostrará' : 'No se mostrará' }} al iniciar sesión</span>
            </div>
            
            <div class="feature-item">
              <a-icon :type="form.getFieldValue('autoClose') ? 'check-circle' : 'close-circle'" :style="{ color: form.getFieldValue('autoClose') ? '#52c41a' : '#ff4d4f' }" />
              <span>{{ form.getFieldValue('autoClose') ? 'Cierre automático' : 'Cierre manual' }}</span>
              <span v-if="form.getFieldValue('autoClose')">({{ form.getFieldValue('autoCloseDelay') }}s)</span>
            </div>
            
            <div class="feature-item">
              <a-icon :type="form.getFieldValue('allowUploads') ? 'check-circle' : 'close-circle'" :style="{ color: form.getFieldValue('allowUploads') ? '#52c41a' : '#ff4d4f' }" />
              <span>{{ form.getFieldValue('allowUploads') ? 'Subida de archivos' : 'Sin subida' }}</span>
              <span v-if="form.getFieldValue('allowUploads')">({{ form.getFieldValue('maxFileSize') }}MB)</span>
            </div>
            
            <div class="feature-item">
              <a-icon :type="form.getFieldValue('requireAcceptance') ? 'check-circle' : 'close-circle'" :style="{ color: form.getFieldValue('requireAcceptance') ? '#52c41a' : '#ff4d4f' }" />
              <span>{{ form.getFieldValue('requireAcceptance') ? 'Aceptación requerida' : 'Aceptación opcional' }}</span>
            </div>
          </div>
        </div>
      </div>
    </a-card>
  </div>
</template>

<script>
import comunicadoService from '@/services/comunicadoService';

export default {
  name: 'ComunicadoSettings',
  data() {
    return {
      form: this.$form.createForm(this, {
        enabled: [],
        showOnLogin: [],
        autoClose: [],
        autoCloseDelay: [],
        allowUploads: [],
        maxFileSize: [],
        allowedFileTypes: [],
        requireAcceptance: []
      }),
      settings: comunicadoService.getSettings(),
      saving: false
    };
  },
  mounted() {
    this.loadSettings();
  },
  methods: {
    loadSettings() {
      this.settings = comunicadoService.getSettings();
      this.form.setFieldsValue(this.settings);
    },

    async handleSubmit(e) {
      e.preventDefault();
      this.form.validateFields(async (err, values) => {
        if (err) {
          console.error('Validation errors:', err);
          return;
        }

        this.saving = true;
        try {
          const success = comunicadoService.saveSettings(values);
          if (success) {
            this.$message.success('Configuración guardada correctamente');
            this.settings = { ...this.settings, ...values };
          } else {
            this.$message.error('Error al guardar la configuración');
          }
        } catch (error) {
          console.error('Error saving settings:', error);
          this.$message.error('Error al guardar la configuración');
        } finally {
          this.saving = false;
        }
      });
    },

    resetToDefaults() {
      this.$confirm({
        title: 'Restablecer Configuración',
        content: '¿Estás seguro de que quieres restablecer todos los valores a los predeterminados?',
        okText: 'Restablecer',
        okType: 'danger',
        cancelText: 'Cancelar',
        onOk: () => {
          const defaults = comunicadoService.getDefaultSettings();
          this.form.setFieldsValue(defaults);
          this.$message.info('Valores restablecidos. Haz clic en "Guardar Configuración" para aplicar los cambios.');
        }
      });
    }
  }
};
</script>

<style scoped>
.comunicado-settings {
  max-width: 800px;
}

.section-title {
  margin: 24px 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f0f0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.form-help {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
  font-style: italic;
}

.preview-container {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.preview-info h4 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 16px;
  font-weight: 600;
}

.preview-features {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.feature-item span {
  color: #333;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
  .comunicado-settings {
    max-width: 100%;
  }
  
  .preview-features {
    gap: 6px;
  }
  
  .feature-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>
