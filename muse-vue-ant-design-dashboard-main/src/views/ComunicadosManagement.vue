<template>
  <div class="comunicados-management">
    <!-- Header -->
    <div class="page-header">
      <a-row type="flex" justify="space-between" align="middle">
        <a-col>
          <h1 class="page-title">Gestión de Comunicados</h1>
          <p class="page-subtitle">Crea y administra los comunicados del sistema</p>
        </a-col>
        <a-col>
          <a-space>
            <a-button @click="refreshData">
              <a-icon type="reload" />
              Actualizar
            </a-button>
            <a-button type="primary" @click="showCreateModal">
              <a-icon type="plus" />
              Nuevo Comunicado
            </a-button>
          </a-space>
        </a-col>
      </a-row>
    </div>

    <!-- Estadísticas -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="Total Comunicados"
            :value="stats.total"
            :value-style="{ color: '#3f8600' }"
          >
            <template #prefix>
              <a-icon type="file-text" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="Activos"
            :value="stats.activos"
            :value-style="{ color: '#1890ff' }"
          >
            <template #prefix>
              <a-icon type="check-circle" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="Por Período"
            :value="stats.periodo"
            :value-style="{ color: '#722ed1' }"
          >
            <template #prefix>
              <a-icon type="calendar" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="Un Vistazo"
            :value="stats.un_vistazo"
            :value-style="{ color: '#fa8c16' }"
          >
            <template #prefix>
              <a-icon type="eye" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
    </a-row>

    <!-- Filtros -->
    <a-card :bordered="false" class="filters-card">
      <a-row :gutter="16" type="flex" align="middle">
        <a-col :span="6">
          <a-input
            v-model="filters.search"
            placeholder="Buscar por título o autor"
            @change="handleSearch"
            allowClear
          >
            <a-icon slot="prefix" type="search" />
          </a-input>
        </a-col>
        <a-col :span="4">
          <a-select
            v-model="filters.tipo_periodo"
            placeholder="Tipo de período"
            @change="handleFilterChange"
            allowClear
          >
            <a-select-option value="permanente">Permanente</a-select-option>
            <a-select-option value="periodo">Por período</a-select-option>
            <a-select-option value="un_vistazo">Un solo vistazo</a-select-option>
          </a-select>
        </a-col>
        <a-col :span="4">
          <a-select
            v-model="filters.prioridad"
            placeholder="Prioridad"
            @change="handleFilterChange"
            allowClear
          >
            <a-select-option value="alta">Alta</a-select-option>
            <a-select-option value="media">Media</a-select-option>
            <a-select-option value="baja">Baja</a-select-option>
          </a-select>
        </a-col>
        <a-col :span="4">
          <a-select
            v-model="filters.estado"
            placeholder="Estado"
            @change="handleFilterChange"
            allowClear
          >
            <a-select-option value="true">Activo</a-select-option>
            <a-select-option value="false">Inactivo</a-select-option>
          </a-select>
        </a-col>
        <a-col :span="6">
          <a-space>
            <a-button @click="clearFilters">
              <a-icon type="clear" />
              Limpiar
            </a-button>
            <a-button type="primary" @click="exportData">
              <a-icon type="export" />
              Exportar
            </a-button>
          </a-space>
        </a-col>
      </a-row>
    </a-card>

    <!-- Tabla de Comunicados -->
    <a-card :bordered="false" title="Comunicados">
      <a-table
        :columns="columns"
        :data-source="filteredComunicados"
        :loading="loading"
        :pagination="pagination"
        @change="handleTableChange"
        rowKey="id"
      >
        <!-- Columna de título -->
        <template #title="text, record">
          <div class="comunicado-title">
            <strong>{{ text }}</strong>
            <div class="comunicado-meta">
              <span class="author">{{ record.autor }}</span>
              <span class="date">{{ formatDate(record.fecha_publicacion) }}</span>
            </div>
          </div>
        </template>

        <!-- Columna de tipo de período -->
        <template #tipo_periodo="text">
          <a-tag :color="getPeriodTypeColor(text)">
            <a-icon :type="getPeriodIcon(text)" />
            {{ getPeriodLabel(text) }}
          </a-tag>
        </template>

        <!-- Columna de prioridad -->
        <template #prioridad="text">
          <a-tag :color="getPriorityColor(text)">
            <a-icon type="flag" />
            {{ text }}
          </a-tag>
        </template>

        <!-- Columna de estado -->
        <template #activo="text">
          <a-tag :color="text ? 'green' : 'red'">
            <a-icon :type="text ? 'check-circle' : 'close-circle'" />
            {{ text ? 'Activo' : 'Inactivo' }}
          </a-tag>
        </template>

        <!-- Columna de período -->
        <template #periodo="record">
          <div v-if="record.tipo_periodo === 'periodo'">
            <div class="period-info">
              <div v-if="record.fecha_inicio">
                <a-icon type="calendar" />
                Desde: {{ formatDate(record.fecha_inicio) }}
              </div>
              <div v-if="record.fecha_fin">
                <a-icon type="calendar" />
                Hasta: {{ formatDate(record.fecha_fin) }}
              </div>
              <div v-if="isExpiringSoon(record)" class="expiring-warning">
                <a-icon type="warning" />
                Por expirar ({{ getDaysRemaining(record) }} días)
              </div>
            </div>
          </div>
          <div v-else-if="record.tipo_periodo === 'un_vistazo'">
            <a-tag color="orange">
              <a-icon type="eye" />
              Desaparece al leer
            </a-tag>
          </div>
          <div v-else>
            <a-tag color="purple">
              <a-icon type="infinity" />
              Permanente
            </a-tag>
          </div>
        </template>

        <!-- Columna de acciones -->
        <template #action="text, record">
          <a-space>
            <a-button type="link" size="small" @click="viewComunicado(record)">
              <a-icon type="eye" />
              Ver
            </a-button>
            <a-button type="link" size="small" @click="editComunicado(record)">
              <a-icon type="edit" />
              Editar
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              danger
              @click="deleteComunicado(record)"
            >
              <a-icon type="delete" />
              Eliminar
            </a-button>
            <a-dropdown>
              <a-button type="link" size="small">
                <a-icon type="more" />
              </a-button>
              <a-menu slot="overlay">
                <a-menu-item @click="duplicateComunicado(record)">
                  <a-icon type="copy" />
                  Duplicar
                </a-menu-item>
                <a-menu-item @click="toggleStatus(record)">
                  <a-icon :type="record.activo ? 'pause' : 'play'" />
                  {{ record.activo ? 'Desactivar' : 'Activar' }}
                </a-menu-item>
                <a-menu-item @click="viewStats(record)">
                  <a-icon type="bar-chart" />
                  Estadísticas
                </a-menu-item>
              </a-menu>
            </a-dropdown>
          </a-space>
        </template>
      </a-table>
    </a-card>

    <!-- Modal de Crear/Editar -->
    <a-modal
      :visible="modalVisible"
      :title="modalTitle"
      :width="900"
      :footer="null"
      @cancel="closeModal"
    >
      <comunicado-form
        :comunicado="selectedComunicado"
        :isEditing="isEditing"
        @success="handleFormSuccess"
        @cancel="closeModal"
      />
    </a-modal>

    <!-- Modal de Vista Previa -->
    <a-modal
      :visible="previewVisible"
      :title="previewComunicado ? previewComunicado.titulo : ''"
      :width="800"
      :footer="null"
      @cancel="previewVisible = false"
    >
      <comunicado-preview
        :comunicado="previewComunicado"
        v-if="previewComunicado"
      />
    </a-modal>
  </div>
</template>

<script>
import comunicadoService from '@/services/comunicadoService';
import ComunicadoForm from '@/components/Comunicados/ComunicadoForm.vue';
import ComunicadoPreview from '@/components/Comunicados/ComunicadoPreview.vue';

export default {
  name: 'ComunicadosManagement',
  components: {
    ComunicadoForm,
    ComunicadoPreview
  },
  data() {
    return {
      loading: false,
      comunicados: [],
      filteredComunicados: [],
      stats: {
        total: 0,
        activos: 0,
        periodo: 0,
        un_vistazo: 0
      },
      filters: {
        search: '',
        tipo_periodo: undefined,
        prioridad: undefined,
        estado: undefined
      },
      pagination: {
        current: 1,
        pageSize: 10,
        total: 0,
        showSizeChanger: true,
        showQuickJumper: true,
        showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} comunicados`
      },
      modalVisible: false,
      previewVisible: false,
      selectedComunicado: null,
      previewComunicado: null,
      isEditing: false
    };
  },
  computed: {
    columns() {
      return [
        {
          title: 'Título',
          dataIndex: 'titulo',
          key: 'titulo',
          scopedSlots: { customRender: 'title' },
          width: 300
        },
        {
          title: 'Tipo',
          dataIndex: 'tipo_periodo',
          key: 'tipo_periodo',
          scopedSlots: { customRender: 'tipo_periodo' },
          width: 120
        },
        {
          title: 'Prioridad',
          dataIndex: 'prioridad',
          key: 'prioridad',
          scopedSlots: { customRender: 'prioridad' },
          width: 100
        },
        {
          title: 'Período',
          key: 'periodo',
          scopedSlots: { customRender: 'periodo' },
          width: 200
        },
        {
          title: 'Estado',
          dataIndex: 'activo',
          key: 'activo',
          scopedSlots: { customRender: 'activo' },
          width: 100
        },
        {
          title: 'Acciones',
          key: 'action',
          scopedSlots: { customRender: 'action' },
          width: 200
        }
      ];
    },
    modalTitle() {
      return this.isEditing ? 'Editar Comunicado' : 'Crear Nuevo Comunicado';
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    async loadData() {
      this.loading = true;
      try {
        // Cargar comunicados (simulado - en producción llamaría a la API)
        this.comunicados = await this.mockComunicados();
        this.filteredComunicados = [...this.comunicados];
        this.calculateStats();
        this.pagination.total = this.comunicados.length;
      } catch (error) {
        console.error('Error loading comunicados:', error);
        this.$message.error('Error al cargar los comunicados');
      } finally {
        this.loading = false;
      }
    },

    mockComunicados() {
      // Datos simulados - en producción vendrían de la API
      return [
        {
          id: 1,
          titulo: 'Bienvenida al Sistema',
          contenido: '<p>Te damos la bienvenida al nuevo sistema de gestión educativa.</p>',
          autor: 'Administración',
          prioridad: 'alta',
          tipo_periodo: 'permanente',
          activo: true,
          fecha_publicacion: '2024-01-15',
          require_acceptance: true,
          allow_comments: false
        },
        {
          id: 2,
          titulo: 'Mantenimiento Programado',
          contenido: '<p>El sistema estará en mantenimiento este fin de semana.</p>',
          autor: 'Soporte Técnico',
          prioridad: 'media',
          tipo_periodo: 'periodo',
          activo: true,
          fecha_publicacion: '2024-02-01',
          fecha_inicio: '2024-02-10',
          fecha_fin: '2024-02-12',
          require_acceptance: false,
          allow_comments: true
        },
        {
          id: 3,
          titulo: 'Nueva Política de Privacidad',
          contenido: '<p>Hemos actualizado nuestra política de privacidad.</p>',
          autor: 'Legal',
          prioridad: 'alta',
          tipo_periodo: 'un_vistazo',
          activo: true,
          fecha_publicacion: '2024-02-05',
          require_acceptance: true,
          allow_comments: false,
          leido: false
        }
      ];
    },

    calculateStats() {
      this.stats = {
        total: this.comunicados.length,
        activos: this.comunicados.filter(c => c.activo).length,
        periodo: this.comunicados.filter(c => c.tipo_periodo === 'periodo').length,
        un_vistazo: this.comunicados.filter(c => c.tipo_periodo === 'un_vistazo').length
      };
    },

    handleSearch() {
      this.applyFilters();
    },

    handleFilterChange() {
      this.applyFilters();
    },

    applyFilters() {
      let filtered = [...this.comunicados];

      // Filtro de búsqueda
      if (this.filters.search) {
        const search = this.filters.search.toLowerCase();
        filtered = filtered.filter(c => 
          c.titulo.toLowerCase().includes(search) ||
          c.autor.toLowerCase().includes(search)
        );
      }

      // Filtro de tipo de período
      if (this.filters.tipo_periodo) {
        filtered = filtered.filter(c => c.tipo_periodo === this.filters.tipo_periodo);
      }

      // Filtro de prioridad
      if (this.filters.prioridad) {
        filtered = filtered.filter(c => c.prioridad === this.filters.prioridad);
      }

      // Filtro de estado
      if (this.filters.estado !== undefined) {
        const isActive = this.filters.estado === 'true';
        filtered = filtered.filter(c => c.activo === isActive);
      }

      this.filteredComunicados = filtered;
      this.pagination.total = filtered.length;
    },

    clearFilters() {
      this.filters = {
        search: '',
        tipo_periodo: undefined,
        prioridad: undefined,
        estado: undefined
      };
      this.applyFilters();
    },

    handleTableChange(pagination) {
      this.pagination = { ...this.pagination, ...pagination };
    },

    showCreateModal() {
      this.selectedComunicado = null;
      this.isEditing = false;
      this.modalVisible = true;
    },

    editComunicado(comunicado) {
      this.selectedComunicado = { ...comunicado };
      this.isEditing = true;
      this.modalVisible = true;
    },

    viewComunicado(comunicado) {
      this.previewComunicado = comunicado;
      this.previewVisible = true;
    },

    async deleteComunicado(comunicado) {
      this.$confirm({
        title: '¿Eliminar comunicado?',
        content: `¿Estás seguro de que quieres eliminar "${comunicado.titulo}"?`,
        okText: 'Eliminar',
        okType: 'danger',
        cancelText: 'Cancelar',
        onOk: async () => {
          try {
            const success = await comunicadoService.deleteComunicado(comunicado.id);
            if (success) {
              this.$message.success('Comunicado eliminado correctamente');
              this.loadData();
            }
          } catch (error) {
            this.$message.error('Error al eliminar el comunicado');
          }
        }
      });
    },

    duplicateComunicado(comunicado) {
      const duplicated = { ...comunicado };
      delete duplicated.id;
      duplicated.titulo = `${comunicado.titulo} (Copia)`;
      this.selectedComunicado = duplicated;
      this.isEditing = false;
      this.modalVisible = true;
    },

    async toggleStatus(comunicado) {
      try {
        const updatedData = { activo: !comunicado.activo };
        await comunicadoService.updateComunicado(comunicado.id, updatedData);
        this.$message.success(`Comunicado ${comunicado.activo ? 'desactivado' : 'activado'} correctamente`);
        this.loadData();
      } catch (error) {
        this.$message.error('Error al cambiar el estado del comunicado');
      }
    },

    viewStats(comunicado) {
      this.$message.info('Estadísticas del comunicado - Función en desarrollo');
    },

    handleFormSuccess(result) {
      this.$message.success(`Comunicado ${this.isEditing ? 'actualizado' : 'creado'} correctamente`);
      this.closeModal();
      this.loadData();
    },

    closeModal() {
      this.modalVisible = false;
      this.selectedComunicado = null;
      this.isEditing = false;
    },

    refreshData() {
      this.loadData();
    },

    exportData() {
      this.$message.info('Exportación de datos - Función en desarrollo');
    },

    // Métodos auxiliares
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES');
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

    getPeriodTypeColor(tipo) {
      switch (tipo) {
        case 'permanente': return 'purple';
        case 'periodo': return 'blue';
        case 'un_vistazo': return 'orange';
        default: return 'default';
      }
    },

    getPriorityColor(priority) {
      switch (priority) {
        case 'alta': return 'red';
        case 'media': return 'orange';
        case 'baja': return 'green';
        default: return 'default';
      }
    },

    isExpiringSoon(comunicado) {
      return comunicadoService.isExpiringSoon(comunicado);
    },

    getDaysRemaining(comunicado) {
      return comunicadoService.getDaysRemaining(comunicado);
    }
  }
};
</script>

<style scoped>
.comunicados-management {
  padding: 24px;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

.page-subtitle {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.stats-row {
  margin-bottom: 24px;
}

.stat-card {
  text-align: center;
}

.filters-card {
  margin-bottom: 24px;
}

.comunicado-title strong {
  display: block;
  margin-bottom: 4px;
}

.comunicado-meta {
  font-size: 12px;
  color: #666;
}

.comunicado-meta span {
  margin-right: 12px;
}

.period-info {
  font-size: 12px;
}

.period-info div {
  margin-bottom: 2px;
  color: #666;
}

.expiring-warning {
  color: #fa8c16;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .comunicados-management {
    padding: 16px;
  }
  
  .stats-row .ant-col {
    margin-bottom: 16px;
  }
  
  .filters-card .ant-row .ant-col {
    margin-bottom: 8px;
  }
}
</style>
