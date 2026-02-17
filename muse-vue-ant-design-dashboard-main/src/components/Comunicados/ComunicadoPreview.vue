<template>
  <div class="comunicado-preview">
    <!-- Header del Comunicado -->
    <div class="preview-header">
      <div class="preview-info">
        <h2>{{ comunicado.titulo }}</h2>
        <div class="preview-meta">
          <span class="author">
            <a-icon type="user" />
            {{ comunicado.autor }}
          </span>
          <span class="date">
            <a-icon type="calendar" />
            {{ formatDate(comunicado.fecha_publicacion) }}
          </span>
          <span v-if="comunicado.prioridad" :class="['priority', comunicado.prioridad]">
            <a-icon type="flag" />
            {{ comunicado.prioridad }}
          </span>
          <span :class="['period-type', comunicado.tipo_periodo]">
            <a-icon :type="getPeriodIcon(comunicado.tipo_periodo)" />
            {{ getPeriodLabel(comunicado) }}
          </span>
          <span :class="['status', comunicado.activo ? 'active' : 'inactive']">
            <a-icon :type="comunicado.activo ? 'check-circle' : 'close-circle'" />
            {{ comunicado.activo ? 'Activo' : 'Inactivo' }}
          </span>
        </div>
      </div>
      <div class="preview-actions">
        <a-space>
          <a-button type="primary" @click="editComunicado">
            <a-icon type="edit" />
            Editar
          </a-button>
          <a-button @click="printComunicado">
            <a-icon type="printer" />
            Imprimir
          </a-button>
          <a-button @click="shareComunicado">
            <a-icon type="share-alt" />
            Compartir
          </a-button>
        </a-space>
      </div>
    </div>

    <!-- Información del Período -->
    <div v-if="comunicado.tipo_periodo === 'periodo'" class="period-info-card">
      <a-alert
        :message="`Vigencia: ${formatPeriodo(comunicado)}`"
        :description="getPeriodDescription(comunicado)"
        :type="isExpiringSoon(comunicado) ? 'warning' : 'info'"
        show-icon
      />
    </div>

    <div v-else-if="comunicado.tipo_periodo === 'un_vistazo'" class="single-view-info">
      <a-alert
        message="Comunicado de Un Solo Vistazo"
        description="Este comunicado desaparecerá automáticamente después de que el usuario lo lea y marque como aceptado."
        type="warning"
        show-icon
      />
    </div>

    <div v-else-if="comunicado.tipo_periodo === 'permanente'" class="permanent-info">
      <a-alert
        message="Comunicado Permanente"
        description="Este comunicado estará siempre visible para los usuarios hasta que sea desactivado manualmente."
        type="success"
        show-icon
      />
    </div>

    <!-- Contenido del Comunicado -->
    <div class="preview-content">
      <div class="content-wrapper">
        <div v-if="comunicado.contenido" class="content-text" v-html="comunicado.contenido"></div>
        <div v-else class="no-content">
          <a-empty description="Este comunicado no tiene contenido" />
        </div>
      </div>
    </div>

    <!-- Archivos Adjuntos -->
    <div v-if="comunicado.archivos && comunicado.archivos.length > 0" class="files-section">
      <h3>
        <a-icon type="paperclip" />
        Archivos Adjuntos ({{ comunicado.archivos.length }})
      </h3>
      <div class="files-grid">
        <div
          v-for="file in comunicado.archivos"
          :key="file.id"
          class="file-item"
          @click="previewFile(file)"
        >
          <div class="file-preview">
            <!-- Vista previa de imagen -->
            <img
              v-if="file.preview_url && isImage(file.tipo)"
              :src="file.preview_url"
              :alt="file.nombre"
              class="preview-image"
            />
            <!-- Icono para otros tipos de archivo -->
            <div v-else class="file-icon">
              <a-icon :type="getFileIcon(file.nombre, file.tipo)" />
            </div>
          </div>
          <div class="file-info">
            <div class="file-name">{{ file.nombre }}</div>
            <div class="file-meta">
              <span class="file-size">{{ formatFileSize(file.tamano) }}</span>
              <span class="file-type">{{ getFileTypeLabel(file.tipo) }}</span>
            </div>
          </div>
          <div class="file-actions">
            <a-button type="text" size="small" @click.stop="downloadFile(file)">
              <a-icon type="download" />
            </a-button>
            <a-button type="text" size="small" @click.stop="viewFile(file)">
              <a-icon type="eye" />
            </a-button>
          </div>
        </div>
      </div>
    </div>

    <!-- Configuración Adicional -->
    <div class="config-section">
      <h3>Configuración</h3>
      <a-descriptions :column="2" bordered size="small">
        <a-descriptions-item label="Requiere Aceptación">
          <a-tag :color="comunicado.require_acceptance ? 'red' : 'green'">
            {{ comunicado.require_acceptance ? 'Sí' : 'No' }}
          </a-tag>
        </a-descriptions-item>
        <a-descriptions-item label="Permite Comentarios">
          <a-tag :color="comunicado.allow_comments ? 'blue' : 'default'">
            {{ comunicado.allow_comments ? 'Sí' : 'No' }}
          </a-tag>
        </a-descriptions-item>
        <a-descriptions-item label="Fecha de Creación">
          {{ formatDateTime(comunicado.created_at) }}
        </a-descriptions-item>
        <a-descriptions-item label="Última Actualización">
          {{ formatDateTime(comunicado.updated_at) }}
        </a-descriptions-item>
      </a-descriptions>
    </div>

    <!-- Estadísticas -->
    <div class="stats-section">
      <h3>Estadísticas de Visualización</h3>
      <a-row :gutter="16">
        <a-col :span="6">
          <a-statistic
            title="Vistas Totales"
            :value="stats.total_views || 0"
            :value-style="{ color: '#3f8600' }"
          >
            <template #prefix>
              <a-icon type="eye" />
            </template>
          </a-statistic>
        </a-col>
        <a-col :span="6">
          <a-statistic
            title="Usuarios Únicos"
            :value="stats.unique_users || 0"
            :value-style="{ color: '#1890ff' }"
          >
            <template #prefix>
              <a-icon type="user" />
            </template>
          </a-statistic>
        </a-col>
        <a-col :span="6">
          <a-statistic
            title="Aceptaciones"
            :value="stats.acceptances || 0"
            :value-style="{ color: '#722ed1' }"
          >
            <template #prefix>
              <a-icon type="check-circle" />
            </template>
          </a-statistic>
        </a-col>
        <a-col :span="6">
          <a-statistic
            title="Comentarios"
            :value="stats.comments || 0"
            :value-style="{ color: '#fa8c16' }"
          >
            <template #prefix>
              <a-icon type="message" />
            </template>
          </a-statistic>
        </a-col>
      </a-row>
    </div>

    <!-- Modal de vista previa de archivo -->
    <a-modal
      :visible="filePreviewVisible"
      :title="previewFile ? previewFile.nombre : ''"
      :footer="null"
      :width="800"
      @cancel="filePreviewVisible = false"
      class="preview-modal"
    >
      <div class="file-preview-content">
        <!-- Vista previa de imagen -->
        <img
          v-if="previewFile && isImage(previewFile.tipo)"
          :src="previewFile.preview_url"
          :alt="previewFile.nombre"
          style="max-width: 100%; height: auto;"
        />
        
        <!-- Vista previa de video -->
        <video
          v-else-if="previewFile && isVideo(previewFile.tipo)"
          :src="previewFile.preview_url"
          controls
          style="max-width: 100%; height: auto;"
        />
        
        <!-- Vista previa de PDF -->
        <iframe
          v-else-if="previewFile && isPDF(previewFile.tipo, previewFile.nombre)"
          :src="previewFile.preview_url"
          style="width: 100%; height: 500px; border: none;"
        />
        
        <!-- Mensaje para otros tipos -->
        <div v-else class="preview-placeholder">
          <a-icon :type="getFileIcon(previewFile.nombre, previewFile.tipo)" style="font-size: 64px;" />
          <p>Este tipo de archivo no se puede previsualizar</p>
          <a-button type="primary" @click="downloadFile(previewFile)">
            <a-icon type="download" />
            Descargar archivo
          </a-button>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script>
import comunicadoService from '@/services/comunicadoService';

export default {
  name: 'ComunicadoPreview',
  props: {
    comunicado: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      filePreviewVisible: false,
      previewFile: null,
      stats: {
        total_views: 0,
        unique_users: 0,
        acceptances: 0,
        comments: 0
      }
    };
  },
  mounted() {
    this.loadStats();
  },
  methods: {
    async loadStats() {
      try {
        // Simular carga de estadísticas - en producción llamaría a la API
        this.stats = {
          total_views: Math.floor(Math.random() * 1000),
          unique_users: Math.floor(Math.random() * 500),
          acceptances: Math.floor(Math.random() * 300),
          comments: Math.floor(Math.random() * 50)
        };
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },

    editComunicado() {
      this.$emit('edit', this.comunicado);
    },

    printComunicado() {
      window.print();
    },

    shareComunicado() {
      // Simular compartir - en producción implementaría funcionalidad real
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        this.$message.success('Enlace copiado al portapapeles');
      }).catch(() => {
        this.$message.error('Error al copiar el enlace');
      });
    },

    previewFile(file) {
      this.previewFile = file;
      this.filePreviewVisible = true;
    },

    downloadFile(file) {
      const link = document.createElement('a');
      link.href = file.download_url || comunicadoService.getDownloadUrl(file.id);
      link.download = file.nombre;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    viewFile(file) {
      this.previewFile(file);
    },

    // Métodos auxiliares
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },

    formatDateTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatPeriodo(comunicado) {
      return comunicadoService.formatPeriodo(comunicado);
    },

    getPeriodDescription(comunicado) {
      if (comunicado.tipo_periodo !== 'periodo') return '';
      
      const daysRemaining = comunicadoService.getDaysRemaining(comunicado);
      if (daysRemaining !== null) {
        return daysRemaining > 0 
          ? `Quedan ${daysRemaining} días para que expire`
          : 'Este comunicado ha expirado';
      }
      
      return 'Período de vigencia definido';
    },

    getPeriodIcon(tipo) {
      switch (tipo) {
        case 'permanente': return 'infinity';
        case 'periodo': return 'calendar';
        case 'un_vistazo': return 'eye';
        default: return 'info-circle';
      }
    },

    getPeriodLabel(tipo) {
      switch (tipo) {
        case 'permanente': return 'Permanente';
        case 'periodo': return 'Por período';
        case 'un_vistazo': return 'Un solo vistazo';
        default: return 'Sin definir';
      }
    },

    isExpiringSoon(comunicado) {
      return comunicadoService.isExpiringSoon(comunicado);
    },

    formatFileSize(bytes) {
      return comunicadoService.formatFileSize(bytes);
    },

    getFileIcon(fileName, fileType) {
      return comunicadoService.getFileIcon(fileName, fileType);
    },

    getFileTypeLabel(fileType) {
      if (fileType.startsWith('image/')) return 'Imagen';
      if (fileType.startsWith('video/')) return 'Video';
      if (fileType === 'application/pdf') return 'PDF';
      if (fileType.includes('word')) return 'Word';
      if (fileType.includes('excel')) return 'Excel';
      if (fileType.includes('powerpoint')) return 'PowerPoint';
      return 'Archivo';
    },

    isImage(fileType) {
      return comunicadoService.isImage(fileType);
    },

    isVideo(fileType) {
      return comunicadoService.isVideo(fileType);
    },

    isPDF(fileType, fileName) {
      return comunicadoService.isPDF(fileType, fileName);
    }
  }
};
</script>

<style scoped>
.comunicado-preview {
  max-width: 1000px;
  margin: 0 auto;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.preview-info h2 {
  margin: 0 0 12px 0;
  color: #333;
  font-size: 24px;
  font-weight: 600;
}

.preview-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 14px;
}

.preview-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.priority {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.priority.alta {
  background: #ff4d4f;
  color: white;
}

.priority.media {
  background: #fa8c16;
  color: white;
}

.priority.baja {
  background: #52c41a;
  color: white;
}

.period-type {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  background: #1890ff;
  color: white;
}

.period-type.permanente {
  background: #722ed1;
}

.period-type.periodo {
  background: #1890ff;
}

.period-type.un_vistazo {
  background: #fa8c16;
}

.status {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.status.active {
  background: #52c41a;
  color: white;
}

.status.inactive {
  background: #ff4d4f;
  color: white;
}

.preview-actions {
  flex-shrink: 0;
}

.period-info-card,
.single-view-info,
.permanent-info {
  margin-bottom: 24px;
}

.preview-content {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 24px;
}

.content-wrapper {
  line-height: 1.6;
  color: #333;
}

.no-content {
  text-align: center;
  padding: 40px 0;
}

.files-section {
  margin-bottom: 24px;
}

.files-section h3 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}

.file-item {
  border: 1px solid #d9d9d9;
  border-radius: 8px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: white;
}

.file-item:hover {
  border-color: #1890ff;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.2);
}

.file-preview {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.file-icon {
  font-size: 32px;
  color: #666;
}

.file-info {
  flex: 1;
}

.file-name {
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
  word-break: break-all;
}

.file-meta {
  font-size: 12px;
  color: #666;
  display: flex;
  gap: 8px;
}

.file-actions {
  display: flex;
  gap: 4px;
  margin-top: 8px;
}

.config-section,
.stats-section {
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
}

.config-section h3,
.stats-section h3 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.preview-modal .preview-content {
  text-align: center;
}

.preview-placeholder {
  padding: 40px;
}

.preview-placeholder p {
  margin: 20px 0;
  color: #666;
}

/* Responsive */
@media (max-width: 768px) {
  .comunicado-preview {
    padding: 0 16px;
  }
  
  .preview-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .preview-meta {
    flex-direction: column;
    gap: 8px;
  }
  
  .files-grid {
    grid-template-columns: 1fr;
  }
  
  .config-section .ant-descriptions,
  .stats-section .ant-row {
    text-align: center;
  }
}

@media print {
  .preview-actions,
  .config-section,
  .stats-section {
    display: none;
  }
  
  .comunicado-preview {
    padding: 0;
  }
}
</style>
