<template>
  <a-modal
    :visible="visible"
    :title="modalTitle"
    :width="800"
    :closable="!autoClose"
    :maskClosable="!autoClose"
    :footer="null"
    @cancel="handleCancel"
    @ok="handleOk"
    :class="['comunicado-modal', { 'auto-close': autoClose }]"
  >
    <template v-if="loading">
      <div class="loading-container">
        <a-spin size="large" />
        <p>Cargando comunicados...</p>
      </div>
    </template>

    <template v-else-if="!hasComunicados">
      <div class="empty-state">
        <a-empty description="No hay comunicados disponibles">
          <template #image>
            <a-icon type="notification" style="font-size: 64px; color: #d9d9d9;" />
          </template>
        </a-empty>
      </div>
    </template>

    <template v-else>
      <!-- Tabs para múltiples comunicados -->
      <a-tabs v-model="activeTab" type="card" @change="handleTabChange">
        <a-tab-pane
          v-for="comunicado in comunicados"
          :key="comunicado.id"
          :tab="comunicado.titulo"
        >
          <div class="comunicado-content">
            <!-- Información del comunicado -->
            <div class="comunicado-header">
              <div class="comunicado-info">
                <h3>{{ comunicado.titulo }}</h3>
                <div class="comunicado-meta">
                  <span class="date">
                    <a-icon type="calendar" />
                    {{ formatDate(comunicado.fecha_publicacion) }}
                  </span>
                  <span class="author">
                    <a-icon type="user" />
                    {{ comunicado.autor }}
                  </span>
                  <span v-if="comunicado.prioridad" :class="['priority', comunicado.prioridad]">
                    <a-icon type="flag" />
                    {{ comunicado.prioridad }}
                  </span>
                  <span :class="['period-type', comunicado.tipo_periodo]">
                    <a-icon :type="getPeriodIcon(comunicado.tipo_periodo)" />
                    {{ getPeriodLabel(comunicado) }}
                  </span>
                  <span v-if="getDaysRemaining(comunicado) !== null" class="days-remaining">
                    <a-icon type="clock-circle" />
                    {{ getDaysRemaining(comunicado) }} días restantes
                  </span>
                  <span v-if="isExpiringSoon(comunicado)" class="expiring-soon">
                    <a-icon type="warning" />
                    Por expirar
                  </span>
                </div>
                <div v-if="comunicado.tipo_periodo === 'periodo'" class="period-info">
                  <span class="period-text">{{ formatPeriodo(comunicado) }}</span>
                </div>
                <div v-if="comunicado.tipo_periodo === 'un_vistazo'" class="single-view-info">
                  <a-alert
                    message="Comunicado de un solo vistazo"
                    description="Este comunicado desaparecerá después de ser leído"
                    type="info"
                    show-icon
                    size="small"
                  />
                </div>
              </div>
              <div class="comunicado-actions">
                <a-checkbox
                  v-if="requireAcceptance"
                  :checked="acceptedCommunicados[comunicado.id]"
                  @change="(e) => handleAcceptance(comunicado.id, e.target.checked)"
                >
                  He leído y entendido este comunicado
                </a-checkbox>
              </div>
            </div>

            <!-- Contenido del comunicado -->
            <div class="comunicado-body">
              <div v-if="comunicado.contenido" class="content-text" v-html="comunicado.contenido"></div>
              
              <!-- Archivos adjuntos -->
              <div v-if="comunicado.archivos && comunicado.archivos.length > 0" class="files-section">
                <h4>
                  <a-icon type="paperclip" />
                  Archivos adjuntos ({{ comunicado.archivos.length }})
                </h4>
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
                      <a-button
                        v-if="canDeleteFiles"
                        type="text"
                        size="small"
                        danger
                        @click.stop="deleteFile(file)"
                      >
                        <a-icon type="delete" />
                      </a-button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a-tab-pane>
      </a-tabs>

      <!-- Área de subida de archivos -->
      <div v-if="allowUploads" class="upload-section">
        <a-divider />
        <h4>
          <a-icon type="upload" />
          Subir archivos
        </h4>
        <a-upload-dragger
          :file-list="fileList"
          :multiple="true"
          :before-upload="beforeUpload"
          @change="handleUploadChange"
          @drop="handleDrop"
          @preview="handlePreview"
          :show-upload-list="false"
          class="upload-dragger"
        >
          <p class="ant-upload-drag-icon">
            <a-icon type="inbox" />
          </p>
          <p class="ant-upload-text">Arrastra archivos aquí o haz clic para seleccionar</p>
          <p class="ant-upload-hint">
            Se permiten imágenes, videos y documentos (Máx. {{ maxFileSize }}MB)
          </p>
        </a-upload-dragger>

        <!-- Lista de archivos subiendo -->
        <div v-if="uploadingFiles.length > 0" class="uploading-files">
          <h5>Subiendo archivos...</h5>
          <div v-for="file in uploadingFiles" :key="file.uid" class="uploading-file">
            <div class="file-info">
              <a-icon :type="getFileIcon(file.name, file.type)" />
              <span>{{ file.name }}</span>
            </div>
            <div class="upload-progress">
              <a-progress :percent="file.percent" :status="file.status" size="small" />
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Botones de acción -->
    <div class="modal-footer">
      <a-space>
        <a-button @click="handleCancel">
          {{ requireAcceptance ? 'Más tarde' : 'Cerrar' }}
        </a-button>
        <a-button
          v-if="requireAcceptance"
          type="primary"
          :disabled="!allAccepted"
          @click="handleOk"
        >
          Aceptar y continuar
        </a-button>
      </a-space>
    </div>

    <!-- Modal de vista previa -->
    <a-modal
      :visible="previewVisible"
      :title="previewFile ? previewFile.nombre : ''"
      :footer="null"
      :width="800"
      @cancel="previewVisible = false"
      class="preview-modal"
    >
      <div class="preview-content">
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
  </a-modal>
</template>

<script>
import comunicadoService from '@/services/comunicadoService';
import { getUser } from '@/utils/auth';
import { isSuperUser } from '@/utils/permissions';

export default {
  name: 'ComunicadoModal',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    autoClose: {
      type: Boolean,
      default: false
    },
    autoCloseDelay: {
      type: Number,
      default: 10000
    }
  },
  data() {
    return {
      loading: true,
      comunicados: [],
      activeTab: 0,
      fileList: [],
      uploadingFiles: [],
      previewVisible: false,
      previewFile: null,
      acceptedCommunicados: {},
      settings: comunicadoService.getSettings(),
      autoCloseTimer: null
    };
  },
  computed: {
    modalTitle() {
      const count = this.comunicados.length;
      return count === 1 ? 'Comunicado Importante' : `Comunicados Importantes (${count})`;
    },
    hasComunicados() {
      return this.comunicados.length > 0;
    },
    requireAcceptance() {
      return this.settings.requireAcceptance;
    },
    allowUploads() {
      return this.settings.allowUploads;
    },
    maxFileSize() {
      return this.settings.maxFileSize;
    },
    allAccepted() {
      if (!this.requireAcceptance) return true;
      return this.comunicados.every(c => this.acceptedCommunicados[c.id]);
    },
    canDeleteFiles() {
      const user = getUser();
      return isSuperUser() || (user && user.role === 'administrador');
    }
  },
  watch: {
    visible(newVal) {
      if (newVal) {
        this.loadComunicados();
        if (this.autoClose) {
          this.startAutoClose();
        }
      } else {
        this.stopAutoClose();
      }
    }
  },
  methods: {
    async loadComunicados() {
      this.loading = true;
      try {
        const comunicados = await comunicadoService.getActiveComunicados();
        this.comunicados = comunicados;
        
        // Marbrar como leídos los comunicados que ya fueron aceptados
        comunicados.forEach(comunicado => {
          if (comunicado.leido) {
            this.$set(this.acceptedCommunicados, comunicado.id, true);
          }
        });
      } catch (error) {
        console.error('Error loading comunicados:', error);
        this.$message.error('Error al cargar los comunicados');
      } finally {
        this.loading = false;
      }
    },

    handleTabChange(tabIndex) {
      this.activeTab = tabIndex;
    },

    handleAcceptance(comunicadoId, accepted) {
      this.$set(this.acceptedCommunicados, comunicado.id, accepted);
      
      if (accepted) {
        comunicadoService.markAsRead(comunicadoId);
        
        // Si es de un solo vistazo, podría necesitar cerrar el modal o actualizar
        const comunicado = this.comunicados.find(c => c.id === comunicadoId);
        if (comunicado && comunicado.tipo_periodo === 'un_vistazo') {
          // Marcar como leído y actualizar la lista
          setTimeout(() => {
            this.loadComunicados();
          }, 1000);
        }
      }
    },

    handleCancel() {
      this.$emit('cancel');
    },

    handleOk() {
      if (this.requireAcceptance && !this.allAccepted) {
        this.$message.warning('Debes aceptar todos los comunicados para continuar');
        return;
      }
      
      this.$emit('ok');
    },

    startAutoClose() {
      if (this.autoCloseTimer) {
        clearTimeout(this.autoCloseTimer);
      }
      
      this.autoCloseTimer = setTimeout(() => {
        this.$emit('auto-close');
      }, this.autoCloseDelay);
    },

    stopAutoClose() {
      if (this.autoCloseTimer) {
        clearTimeout(this.autoCloseTimer);
        this.autoCloseTimer = null;
      }
    },

    beforeUpload(file) {
      try {
        comunicadoService.validateFile(file);
        return false; // Prevenir subida automática, la manejamos manualmente
      } catch (error) {
        this.$message.error(error.message);
        return false;
      }
    },

    handleUploadChange({ file, fileList }) {
      this.fileList = fileList;
    },

    handleDrop(e) {
      e.preventDefault();
      const files = Array.from(e.dataTransfer.files);
      files.forEach(file => this.uploadFile(file));
    },

    async uploadFile(file) {
      const uploadingFile = {
        uid: Date.now() + Math.random(),
        name: file.name,
        type: file.type,
        size: file.size,
        percent: 0,
        status: 'uploading'
      };

      this.uploadingFiles.push(uploadingFile);

      try {
        // Simular progreso
        const progressInterval = setInterval(() => {
          if (uploadingFile.percent < 90) {
            uploadingFile.percent += 10;
          }
        }, 200);

        const result = await comunicadoService.uploadFile(file);
        
        clearInterval(progressInterval);
        uploadingFile.percent = 100;
        uploadingFile.status = 'done';

        // Actualizar el comunicado actual con el nuevo archivo
        const currentComunicado = this.comunicados[this.activeTab];
        if (currentComunicado) {
          if (!currentComunicado.archivos) {
            this.$set(currentComunicado, 'archivos', []);
          }
          currentComunicado.archivos.push({
            id: result.id,
            nombre: file.name,
            tipo: file.type,
            tamano: file.size,
            preview_url: result.preview_url,
            download_url: result.download_url
          });
        }

        setTimeout(() => {
          this.uploadingFiles = this.uploadingFiles.filter(f => f.uid !== uploadingFile.uid);
        }, 2000);

        this.$message.success(`Archivo ${file.name} subido correctamente`);
      } catch (error) {
        clearInterval(progressInterval);
        uploadingFile.status = 'error';
        this.$message.error(`Error al subir ${file.name}: ${error.message}`);
      }
    },

    handlePreview(file) {
      this.previewFile = file;
      this.previewVisible = true;
    },

    previewFile(file) {
      this.handlePreview(file);
    },

    downloadFile(file) {
      const link = document.createElement('a');
      link.href = file.download_url || comunicadoService.getDownloadUrl(file.id);
      link.download = file.nombre;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    async deleteFile(file) {
      this.$confirm({
        title: '¿Eliminar archivo?',
        content: `¿Estás seguro de que quieres eliminar ${file.nombre}?`,
        okText: 'Eliminar',
        okType: 'danger',
        cancelText: 'Cancelar',
        onOk: async () => {
          try {
            const success = await comunicadoService.deleteFile(file.id);
            if (success) {
              const currentComunicado = this.comunicados[this.activeTab];
              if (currentComunicado && currentComunicado.archivos) {
                currentComunicado.archivos = currentComunicado.archivos.filter(f => f.id !== file.id);
              }
              this.$message.success('Archivo eliminado correctamente');
            }
          } catch (error) {
            this.$message.error('Error al eliminar el archivo');
          }
        }
      });
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
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
    },

    // Métodos para manejo de períodos
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

    getPeriodLabel(comunicado) {
      if (!comunicado.tipo_periodo) return 'Sin definir';
      
      switch (comunicado.tipo_periodo) {
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

    formatPeriodo(comunicado) {
      return comunicadoService.formatPeriodo(comunicado);
    },

    getDaysRemaining(comunicado) {
      return comunicadoService.getDaysRemaining(comunicado);
    },

    isExpiringSoon(comunicado) {
      return comunicadoService.isExpiringSoon(comunicado);
    }
  },

  beforeDestroy() {
    this.stopAutoClose();
  }
};
</script>

<style scoped>
.comunicado-modal {
  max-height: 80vh;
}

.comunicado-modal.auto-close .ant-modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.comunicado-modal.auto-close .ant-modal-title {
  color: white;
}

.loading-container {
  text-align: center;
  padding: 40px;
}

.empty-state {
  text-align: center;
  padding: 40px;
}

.comunicado-content {
  max-height: 60vh;
  overflow-y: auto;
}

.comunicado-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #f0f0f0;
}

.comunicado-info h3 {
  margin: 0 0 10px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.comunicado-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  font-size: 12px;
  color: #666;
}

.comunicado-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.priority {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
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
  font-size: 11px;
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

.days-remaining {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  background: #52c41a;
  color: white;
}

.expiring-soon {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  background: #fa8c16;
  color: white;
  animation: pulse 2s infinite;
}

.period-info {
  margin-top: 8px;
}

.period-text {
  font-size: 12px;
  color: #666;
  font-style: italic;
}

.single-view-info {
  margin-top: 8px;
}

.comunicado-body {
  line-height: 1.6;
}

.content-text {
  margin-bottom: 20px;
  color: #333;
}

.files-section h4 {
  margin: 20px 0 15px 0;
  color: #333;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.file-item {
  border: 1px solid #d9d9d9;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: white;
}

.file-item:hover {
  border-color: #1890ff;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.2);
}

.file-preview {
  width: 60px;
  height: 60px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
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
  font-size: 24px;
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

.upload-section {
  margin-top: 20px;
}

.upload-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.upload-dragger {
  border: 2px dashed #d9d9d9;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  background: #fafafa;
  transition: border-color 0.3s ease;
}

.upload-dragger:hover {
  border-color: #1890ff;
}

.uploading-files {
  margin-top: 15px;
}

.uploading-files h5 {
  margin-bottom: 10px;
  color: #333;
}

.uploading-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px;
  background: #f5f5f5;
  border-radius: 4px;
  margin-bottom: 8px;
}

.uploading-file .file-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.upload-progress {
  min-width: 150px;
}

.modal-footer {
  text-align: right;
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #f0f0f0;
}

.preview-content {
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
  .comunicado-modal {
    margin: 0;
    max-width: 100vw;
  }
  
  .comunicado-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .comunicado-meta {
    flex-direction: column;
    gap: 8px;
  }
  
  .files-grid {
    grid-template-columns: 1fr;
  }
  
  .uploading-file {
    flex-direction: column;
    gap: 10px;
  }
}
</style>
