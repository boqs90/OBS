<template>
  <div class="comunicado-form">
    <a-form :layout="vertical" :form="form" @submit="handleSubmit">
      <!-- Información Básica -->
      <a-card title="Información Básica" :bordered="false" class="form-section">
        <a-form-item label="Título del Comunicado" required>
          <a-input
            v-decorator="['titulo', { 
              rules: [{ required: true, message: 'El título es requerido' }]
            }]"
            placeholder="Ingresa el título del comunicado"
            :maxLength="200"
            showCount
          />
        </a-form-item>

        <a-form-item label="Contenido">
          <a-textarea
            v-decorator="['contenido']"
            placeholder="Ingresa el contenido del comunicado (puedes usar HTML)"
            :rows="6"
            :maxLength="5000"
            showCount
          />
        </a-form-item>

        <a-form-item label="Autor">
          <a-input
            v-decorator="['autor', { 
              initialValue: currentUser ? currentUser.name : ''
            }]"
            placeholder="Nombre del autor"
            :maxLength="100"
          />
        </a-form-item>

        <a-form-item label="Prioridad">
          <a-select
            v-decorator="['prioridad', { initialValue: 'media' }]"
            placeholder="Selecciona la prioridad"
          >
            <a-select-option value="baja">Baja</a-select-option>
            <a-select-option value="media">Media</a-select-option>
            <a-select-option value="alta">Alta</a-select-option>
          </a-select>
        </a-form-item>
      </a-card>

      <!-- Configuración de Período -->
      <a-card title="Configuración de Período" :bordered="false" class="form-section">
        <a-form-item label="Tipo de Período" required>
          <a-radio-group
            v-decorator="['tipo_periodo', { 
              initialValue: 'permanente',
              rules: [{ required: true, message: 'El tipo de período es requerido' }]
            }]"
            @change="handlePeriodTypeChange"
          >
            <a-radio value="permanente">
              <a-icon type="infinity" />
              Permanente
            </a-radio>
            <a-radio value="periodo">
              <a-icon type="calendar" />
              Por período de tiempo
            </a-radio>
            <a-radio value="un_vistazo">
              <a-icon type="eye" />
              Un solo vistazo
            </a-radio>
          </a-radio-group>
        </a-form-item>

        <!-- Opciones para período de tiempo -->
        <template v-if="form.getFieldValue('tipo_periodo') === 'periodo'">
          <a-row :gutter="16">
            <a-col :span="12">
              <a-form-item label="Fecha de Inicio">
                <a-date-picker
                  v-decorator="['fecha_inicio']"
                  style="width: 100%"
                  placeholder="Selecciona fecha de inicio"
                  :disabledDate="disabledStartDate"
                />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Fecha de Fin">
                <a-date-picker
                  v-decorator="['fecha_fin', { 
                    rules: [{ 
                      validator: validateEndDate 
                    }]
                  }]"
                  style="width: 100%"
                  placeholder="Selecciona fecha de fin"
                  :disabledDate="disabledEndDate"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-alert
            message="Información del Período"
            description="El comunicado solo será visible durante el período especificado. Si no seleccionas fechas, será permanente."
            type="info"
            show-icon
            style="margin-bottom: 16px;"
          />
        </template>

        <!-- Opciones para un solo vistazo -->
        <template v-if="form.getFieldValue('tipo_periodo') === 'un_vistazo'">
          <a-alert
            message="Comunicado de Un Solo Vistazo"
            description="Este comunicado desaparecerá automáticamente después de que el usuario lo lea y marque como aceptado. Ideal para avisos importantes que solo necesitan ser vistos una vez."
            type="warning"
            show-icon
            style="margin-bottom: 16px;"
          />
        </template>

        <!-- Opciones para permanente -->
        <template v-if="form.getFieldValue('tipo_periodo') === 'permanente'">
          <a-alert
            message="Comunicado Permanente"
            description="Este comunicado estará siempre visible para los usuarios hasta que sea desactivado manualmente."
            type="success"
            show-icon
            style="margin-bottom: 16px;"
          />
        </template>
      </a-card>

      <!-- Configuración Adicional -->
      <a-card title="Configuración Adicional" :bordered="false" class="form-section">
        <a-form-item label="Estado">
          <a-switch
            v-decorator="['activo', { initialValue: true }]"
            checkedChildren="Activo"
            unCheckedChildren="Inactivo"
          />
          <div class="form-help">
            Los comunicados inactivos no serán visibles para los usuarios
          </div>
        </a-form-item>

        <a-form-item label="Requerir Aceptación">
          <a-switch
            v-decorator="['require_acceptance', { initialValue: false }]"
            checkedChildren="Requerido"
            unCheckedChildren="Opcional"
          />
          <div class="form-help">
            Obliga a los usuarios a marcar que han leído y entendido el comunicado
          </div>
        </a-form-item>

        <a-form-item label="Permitir Comentarios">
          <a-switch
            v-decorator="['allow_comments', { initialValue: false }]"
            checkedChildren="Permitido"
            unCheckedChildren="Bloqueado"
          />
          <div class="form-help">
            Permite que los usuarios dejen comentarios sobre el comunicado
          </div>
        </a-form-item>
      </a-card>

      <!-- Vista Previa -->
      <a-card title="Vista Previa" :bordered="false" class="form-section">
        <div class="preview-container">
          <div class="preview-header">
            <h4>{{ form.getFieldValue('titulo') || 'Título del comunicado' }}</h4>
            <div class="preview-meta">
              <span class="preview-author">
                <a-icon type="user" />
                {{ form.getFieldValue('autor') || 'Autor' }}
              </span>
              <span :class="['preview-priority', form.getFieldValue('prioridad') || 'media']">
                <a-icon type="flag" />
                {{ form.getFieldValue('prioridad') || 'Media' }}
              </span>
              <span :class="['preview-period-type', form.getFieldValue('tipo_periodo') || 'permanente']">
                <a-icon :type="getPeriodIcon(form.getFieldValue('tipo_periodo'))" />
                {{ getPeriodLabel(form.getFieldValue('tipo_periodo')) }}
              </span>
            </div>
          </div>
          <div class="preview-content">
            <div v-if="form.getFieldValue('contenido')" v-html="form.getFieldValue('contenido')"></div>
            <div v-else class="preview-placeholder">
              El contenido del comunicado aparecerá aquí...
            </div>
          </div>
        </div>
      </a-card>

      <!-- Botones de Acción -->
      <div class="form-actions">
        <a-space>
          <a-button @click="handleCancel">
            <a-icon type="close" />
            Cancelar
          </a-button>
          <a-button @click="handleReset">
            <a-icon type="reload" />
            Restablecer
          </a-button>
          <a-button type="primary" html-type="submit" :loading="saving">
            <a-icon type="save" />
            {{ isEditing ? 'Actualizar' : 'Crear' }} Comunicado
          </a-button>
        </a-space>
      </div>
    </a-form>
  </div>
</template>

<script>
import comunicadoService from '@/services/comunicadoService';
import { getUser } from '@/utils/auth';
import moment from 'moment';

export default {
  name: 'ComunicadoForm',
  props: {
    comunicado: {
      type: Object,
      default: null
    },
    isEditing: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      form: this.$form.createForm(this),
      saving: false,
      currentUser: getUser()
    };
  },
  computed: {
    formTitle() {
      return this.isEditing ? 'Editar Comunicado' : 'Crear Nuevo Comunicado';
    }
  },
  mounted() {
    if (this.isEditing && this.comunicado) {
      this.loadComunicado();
    }
  },
  methods: {
    loadComunicado() {
      if (!this.comunicado) return;

      this.form.setFieldsValue({
        titulo: this.comunicado.titulo,
        contenido: this.comunicado.contenido,
        autor: this.comunicado.autor,
        prioridad: this.comunicado.prioridad,
        tipo_periodo: this.comunicado.tipo_periodo,
        fecha_inicio: this.comunicado.fecha_inicio ? moment(this.comunicado.fecha_inicio) : null,
        fecha_fin: this.comunicado.fecha_fin ? moment(this.comunicado.fecha_fin) : null,
        activo: this.comunicado.activo,
        require_acceptance: this.comunicado.require_acceptance,
        allow_comments: this.comunicado.allow_comments
      });
    },

    handlePeriodTypeChange(e) {
      const tipoPeriodo = e.target.value;
      
      // Limpiar fechas si cambia el tipo
      if (tipoPeriodo !== 'periodo') {
        this.form.setFieldsValue({
          fecha_inicio: null,
          fecha_fin: null
        });
      }
    },

    disabledStartDate(current) {
      const endDate = this.form.getFieldValue('fecha_fin');
      if (!endDate) {
        return current && current < moment().startOf('day');
      }
      return current && (current < moment().startOf('day') || current > endDate);
    },

    disabledEndDate(current) {
      const startDate = this.form.getFieldValue('fecha_inicio');
      if (!startDate) {
        return current && current < moment().startOf('day');
      }
      return current && current < startDate;
    },

    validateEndDate(rule, value, callback) {
      const tipoPeriodo = this.form.getFieldValue('tipo_periodo');
      
      if (tipoPeriodo === 'periodo') {
        const startDate = this.form.getFieldValue('fecha_inicio');
        
        if (!startDate && !value) {
          callback('Debes seleccionar al menos una fecha (inicio o fin)');
          return;
        }
        
        if (startDate && value && value.isBefore(startDate, 'day')) {
          callback('La fecha de fin debe ser posterior a la fecha de inicio');
          return;
        }
      }
      
      callback();
    },

    getPeriodIcon(tipoPeriodo) {
      switch (tipoPeriodo) {
        case 'permanente':
          return 'infinity';
        case 'periodo':
          return 'calendar';
        case 'un_vistazo':
          return 'eye';
        default:
          return 'info-circle';
      }
    },

    getPeriodLabel(tipoPeriodo) {
      switch (tipoPeriodo) {
        case 'permanente':
          return 'Permanente';
        case 'periodo':
          return 'Por período';
        case 'un_vistazo':
          return 'Un solo vistazo';
        default:
          return 'Sin definir';
      }
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
          // Formatear fechas si existen
          if (values.fecha_inicio) {
            values.fecha_inicio = values.fecha_inicio.format('YYYY-MM-DD');
          }
          if (values.fecha_fin) {
            values.fecha_fin = values.fecha_fin.format('YYYY-MM-DD');
          }

          let result;
          if (this.isEditing) {
            result = await comunicadoService.updateComunicado(this.comunicado.id, values);
            this.$message.success('Comunicado actualizado correctamente');
          } else {
            result = await comunicadoService.createComunicado(values);
            this.$message.success('Comunicado creado correctamente');
          }

          this.$emit('success', result);
          
          if (!this.isEditing) {
            this.handleReset();
          }
        } catch (error) {
          console.error('Error saving comunicado:', error);
          this.$message.error(`Error al ${this.isEditing ? 'actualizar' : 'crear'} el comunicado`);
        } finally {
          this.saving = false;
        }
      });
    },

    handleCancel() {
      this.$emit('cancel');
    },

    handleReset() {
      this.form.resetFields();
      if (this.currentUser) {
        this.form.setFieldsValue({
          autor: this.currentUser.name
        });
      }
    }
  }
};
</script>

<style scoped>
.comunicado-form {
  max-width: 800px;
}

.form-section {
  margin-bottom: 24px;
}

.form-help {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
  font-style: italic;
}

.preview-container {
  border: 1px solid #d9d9d9;
  border-radius: 8px;
  padding: 16px;
  background: #fafafa;
}

.preview-header h4 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 16px;
  font-weight: 600;
}

.preview-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 12px;
}

.preview-author,
.preview-priority,
.preview-period-type {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}

.preview-author {
  background: #f0f0f0;
  color: #666;
}

.preview-priority.alta {
  background: #ff4d4f;
  color: white;
}

.preview-priority.media {
  background: #fa8c16;
  color: white;
}

.preview-priority.baja {
  background: #52c41a;
  color: white;
}

.preview-period-type.permanente {
  background: #722ed1;
  color: white;
}

.preview-period-type.periodo {
  background: #1890ff;
  color: white;
}

.preview-period-type.un_vistazo {
  background: #fa8c16;
  color: white;
}

.preview-content {
  margin-top: 12px;
  padding: 12px;
  background: white;
  border-radius: 4px;
  min-height: 60px;
}

.preview-placeholder {
  color: #999;
  font-style: italic;
}

.form-actions {
  text-align: right;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

/* Responsive */
@media (max-width: 768px) {
  .comunicado-form {
    max-width: 100%;
  }
  
  .preview-meta {
    flex-direction: column;
    gap: 4px;
  }
  
  .form-actions {
    text-align: left;
  }
  
  .form-actions .ant-space {
    width: 100%;
  }
  
  .form-actions .ant-btn {
    width: 100%;
  }
}
</style>
