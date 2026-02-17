import axios from 'axios';

class ComunicadoService {
  constructor() {
    this.baseURL = 'http://localhost:8000/api';
    this.storageKey = 'obs_comunicado_settings';
  }

  // Obtener headers de autenticación
  getAuthHeaders() {
    const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    return {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    };
  }

  // Obtener headers para subida de archivos
  getUploadHeaders() {
    const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    return {
      'Authorization': `Bearer ${token}`
    };
  }

  // Obtener configuración del modal de comunicados
  getSettings() {
    try {
      const saved = localStorage.getItem(this.storageKey);
      return saved ? JSON.parse(saved) : {
        enabled: true,
        showOnLogin: true,
        autoClose: false,
        autoCloseDelay: 10000,
        allowUploads: true,
        maxFileSize: 10, // MB
        allowedFileTypes: ['image/*', 'video/*', 'application/pdf', '.doc,.docx,.xls,.xlsx,.ppt,.pptx'],
        requireAcceptance: false
      };
    } catch (error) {
      console.error('Error loading comunicado settings:', error);
      return this.getDefaultSettings();
    }
  }

  // Guardar configuración
  saveSettings(settings) {
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(settings));
      return true;
    } catch (error) {
      console.error('Error saving comunicado settings:', error);
      return false;
    }
  }

  // Obtener configuración por defecto
  getDefaultSettings() {
    return {
      enabled: true,
      showOnLogin: true,
      autoClose: false,
      autoCloseDelay: 10000,
      allowUploads: true,
      maxFileSize: 10,
      allowedFileTypes: ['image/*', 'video/*', 'application/pdf', '.doc,.docx,.xls,.xlsx,.ppt,.pptx'],
      requireAcceptance: false
    };
  }

  // Obtener comunicados activos con filtrado por período y tipo
  async getActiveComunicados() {
    try {
      const response = await axios.get(`${this.baseURL}/comunicados/active`, {
        headers: this.getAuthHeaders()
      });
      
      const comunicados = response.data || [];
      const currentUser = this.getCurrentUser();
      
      // Filtrar comunicados según tipo y período
      return comunicados.filter(comunicado => {
        // Verificar si el comunicado está activo
        if (!comunicado.activo) return false;
        
        // Verificar período de tiempo
        if (comunicado.tipo_periodo === 'periodo') {
          const now = new Date();
          const startDate = comunicado.fecha_inicio ? new Date(comunicado.fecha_inicio) : null;
          const endDate = comunicado.fecha_fin ? new Date(comunicado.fecha_fin) : null;
          
          // Si hay fecha de inicio, verificar que ya haya comenzado
          if (startDate && now < startDate) return false;
          
          // Si hay fecha de fin, verificar que no haya terminado
          if (endDate && now > endDate) return false;
        }
        
        // Para comunicados de un solo vistazo, verificar si ya fue leído
        if (comunicado.tipo_periodo === 'un_vistazo') {
          return !comunicado.leido;
        }
        
        return true;
      });
    } catch (error) {
      console.error('Error loading comunicados:', error);
      return [];
    }
  }

  // Crear comunicado con opciones de período
  async createComunicado(comunicadoData) {
    try {
      const response = await axios.post(`${this.baseURL}/comunicados`, comunicadoData, {
        headers: this.getAuthHeaders()
      });
      return response.data;
    } catch (error) {
      console.error('Error creating comunicado:', error);
      throw error;
    }
  }

  // Actualizar comunicado
  async updateComunicado(comunicadoId, comunicadoData) {
    try {
      const response = await axios.put(`${this.baseURL}/comunicados/${comunicadoId}`, comunicadoData, {
        headers: this.getAuthHeaders()
      });
      return response.data;
    } catch (error) {
      console.error('Error updating comunicado:', error);
      throw error;
    }
  }

  // Eliminar comunicado
  async deleteComunicado(comunicadoId) {
    try {
      await axios.delete(`${this.baseURL}/comunicados/${comunicadoId}`, {
        headers: this.getAuthHeaders()
      });
      return true;
    } catch (error) {
      console.error('Error deleting comunicado:', error);
      return false;
    }
  }

  // Verificar si un comunicado debe mostrarse
  shouldShowComunicado(comunicado) {
    const now = new Date();
    
    // Verificar si está activo
    if (!comunicado.activo) return false;
    
    // Verificar según tipo de período
    switch (comunicado.tipo_periodo) {
      case 'permanente':
        return true;
        
      case 'periodo':
        const startDate = comunicado.fecha_inicio ? new Date(comunicado.fecha_inicio) : null;
        const endDate = comunicado.fecha_fin ? new Date(comunicado.fecha_fin) : null;
        
        // Verificar que ya haya comenzado
        if (startDate && now < startDate) return false;
        
        // Verificar que no haya terminado
        if (endDate && now > endDate) return false;
        
        return true;
        
      case 'un_vistazo':
        // Solo mostrar si no ha sido leído
        return !comunicado.leido;
        
      default:
        return true;
    }
  }

  // Obtener comunicados expirados (para limpieza)
  async getExpiredComunicados() {
    try {
      const response = await axios.get(`${this.baseURL}/comunicados/expired`, {
        headers: this.getAuthHeaders()
      });
      return response.data || [];
    } catch (error) {
      console.error('Error loading expired comunicados:', error);
      return [];
    }
  }

  // Marcar comunicado como leído y manejar tipo de período
  async markAsRead(comunicadoId) {
    try {
      await axios.post(`${this.baseURL}/comunicados/${comunicadoId}/read`, {}, {
        headers: this.getAuthHeaders()
      });
      
      // Si es de un solo vistazo, podría necesitar lógica adicional
      return true;
    } catch (error) {
      console.error('Error marking comunicado as read:', error);
      return false;
    }
  }

  // Obtener estadísticas de comunicados
  async getComunicadoStats() {
    try {
      const response = await axios.get(`${this.baseURL}/comunicados/stats`, {
        headers: this.getAuthHeaders()
      });
      return response.data || {};
    } catch (error) {
      console.error('Error loading comunicado stats:', error);
      return {};
    }
  }

  // Formatear fecha de período
  formatPeriodo(comunicado) {
    if (!comunicado.tipo_periodo || comunicado.tipo_periodo === 'permanente') {
      return 'Permanente';
    }
    
    if (comunicado.tipo_periodo === 'un_vistazo') {
      return 'Un solo vistazo';
    }
    
    if (comunicado.tipo_periodo === 'periodo') {
      const startDate = comunicado.fecha_inicio ? new Date(comunicado.fecha_inicio) : null;
      const endDate = comunicado.fecha_fin ? new Date(comunicado.fecha_fin) : null;
      
      if (startDate && endDate) {
        return `Del ${this.formatDate(startDate)} al ${this.formatDate(endDate)}`;
      } else if (startDate) {
        return `Desde ${this.formatDate(startDate)}`;
      } else if (endDate) {
        return `Hasta ${this.formatDate(endDate)}`;
      }
    }
    
    return 'Sin definir';
  }

  // Verificar si un comunicado está por expirar
  isExpiringSoon(comunicado) {
    if (comunicado.tipo_periodo !== 'periodo' || !comunicado.fecha_fin) {
      return false;
    }
    
    const now = new Date();
    const endDate = new Date(comunicado.fecha_fin);
    const daysUntilExpiry = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
    
    return daysUntilExpiry <= 3 && daysUntilExpiry > 0;
  }

  // Obtener días restantes
  getDaysRemaining(comunicado) {
    if (comunicado.tipo_periodo !== 'periodo' || !comunicado.fecha_fin) {
      return null;
    }
    
    const now = new Date();
    const endDate = new Date(comunicado.fecha_fin);
    const daysRemaining = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
    
    return daysRemaining > 0 ? daysRemaining : 0;
  }

  // Subir archivo
  async uploadFile(file, comunicadoId = null) {
    const formData = new FormData();
    formData.append('file', file);
    
    if (comunicadoId) {
      formData.append('comunicado_id', comunicadoId);
    }

    try {
      const response = await axios.post(`${this.baseURL}/comunicados/upload`, formData, {
        headers: this.getUploadHeaders(),
        onUploadProgress: (progressEvent) => {
          const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          return progress;
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error uploading file:', error);
      throw error;
    }
  }

  // Validar archivo
  validateFile(file) {
    const settings = this.getSettings();
    
    // Validar tamaño
    const maxSize = settings.maxFileSize * 1024 * 1024; // Convertir MB a bytes
    if (file.size > maxSize) {
      throw new Error(`El archivo es demasiado grande. Máximo permitido: ${settings.maxFileSize}MB`);
    }

    // Validar tipo de archivo
    const allowedTypes = settings.allowedFileTypes;
    const isValidType = allowedTypes.some(type => {
      if (type.includes('*')) {
        return file.type.startsWith(type.replace('*', ''));
      }
      return file.type === type || file.name.toLowerCase().endsWith(type.toLowerCase());
    });

    if (!isValidType) {
      throw new Error('Tipo de archivo no permitido');
    }

    return true;
  }

  // Obtener archivos de un comunicado
  async getComunicadoFiles(comunicadoId) {
    try {
      const response = await axios.get(`${this.baseURL}/comunicados/${comunicadoId}/files`, {
        headers: this.getAuthHeaders()
      });
      return response.data || [];
    } catch (error) {
      console.error('Error loading comunicado files:', error);
      return [];
    }
  }

  // Eliminar archivo
  async deleteFile(fileId) {
    try {
      await axios.delete(`${this.baseURL}/comunicados/files/${fileId}`, {
        headers: this.getAuthHeaders()
      });
      return true;
    } catch (error) {
      console.error('Error deleting file:', error);
      return false;
    }
  }

  // Obtener URL de vista previa
  getPreviewUrl(fileId) {
    return `${this.baseURL}/comunicados/files/${fileId}/preview`;
  }

  // Obtener URL de descarga
  getDownloadUrl(fileId) {
    return `${this.baseURL}/comunicados/files/${fileId}/download`;
  }

  // Formatear tamaño de archivo
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // Obtener tipo de icono según el tipo de archivo
  getFileIcon(fileName, fileType) {
    const extension = fileName.split('.').pop().toLowerCase();
    const type = fileType.toLowerCase();

    // Imágenes
    if (type.startsWith('image/')) {
      return 'file-image';
    }

    // Videos
    if (type.startsWith('video/')) {
      return 'video-camera';
    }

    // PDF
    if (type === 'application/pdf' || extension === 'pdf') {
      return 'file-pdf';
    }

    // Documentos de Word
    if (['doc', 'docx'].includes(extension) || type.includes('word')) {
      return 'file-word';
    }

    // Documentos de Excel
    if (['xls', 'xlsx'].includes(extension) || type.includes('excel')) {
      return 'file-excel';
    }

    // Documentos de PowerPoint
    if (['ppt', 'pptx'].includes(extension) || type.includes('powerpoint')) {
      return 'file-ppt';
    }

    // Archivos comprimidos
    if (['zip', 'rar', '7z'].includes(extension) || type.includes('zip')) {
      return 'file-zip';
    }

    // Texto
    if (['txt', 'md'].includes(extension) || type.startsWith('text/')) {
      return 'file-text';
    }

    // Por defecto
    return 'file';
  }

  // Verificar si es imagen
  isImage(fileType) {
    return fileType.startsWith('image/');
  }

  // Verificar si es video
  isVideo(fileType) {
    return fileType.startsWith('video/');
  }

  // Verificar si es PDF
  isPDF(fileType, fileName) {
    return fileType === 'application/pdf' || fileName.toLowerCase().endsWith('.pdf');
  }
}

export default new ComunicadoService();
