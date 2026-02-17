<template>
  <div class="global-search-results">
    <!-- Loading State -->
    <div v-if="loading" class="search-loading">
      <a-spin size="small" />
      <span>Buscando...</span>
    </div>

    <!-- No Results -->
    <div v-else-if="!hasResults" class="search-no-results">
      <a-empty description="No se encontraron resultados">
        <template #image>
          <a-icon type="file-search" style="font-size: 64px; color: #d9d9d9;" />
        </template>
      </a-empty>
    </div>

    <!-- Results -->
    <div v-else class="search-results-content">
      <!-- Students -->
      <div v-if="results.students.length > 0" class="search-section">
        <div class="search-section-header">
          <h4>Estudiantes ({{ results.students.length }})</h4>
          <a-button type="link" size="small" @click="viewAll('students')">
            Ver todos
          </a-button>
        </div>
        <div class="search-results-list">
          <div
            v-for="student in results.students.slice(0, 3)"
            :key="`student-${student.id}`"
            class="search-result-item"
            @click="goToProfile('student', student.id)"
          >
            <a-avatar :size="32" :src="student.photo_url" style="background-color: #7c3aed;">
              {{ getInitials(student.name) }}
            </a-avatar>
            <div class="search-result-info">
              <div class="search-result-title">{{ student.name }}</div>
              <div class="search-result-subtitle">
                {{ student.grade || 'Sin grado' }} • {{ student.section || 'Sin sección' }}
              </div>
            </div>
            <a-tag color="blue">Estudiante</a-tag>
          </div>
        </div>
      </div>

      <!-- Teachers -->
      <div v-if="results.teachers.length > 0" class="search-section">
        <div class="search-section-header">
          <h4>Maestros ({{ results.teachers.length }})</h4>
          <a-button type="link" size="small" @click="viewAll('teachers')">
            Ver todos
          </a-button>
        </div>
        <div class="search-results-list">
          <div
            v-for="teacher in results.teachers.slice(0, 3)"
            :key="`teacher-${teacher.id}`"
            class="search-result-item"
            @click="goToProfile('teacher', teacher.id)"
          >
            <a-avatar :size="32" :src="teacher.photo_url" style="background-color: #22c55e;">
              {{ getInitials(teacher.name) }}
            </a-avatar>
            <div class="search-result-info">
              <div class="search-result-title">{{ teacher.name }}</div>
              <div class="search-result-subtitle">
                {{ teacher.subject || 'Sin asignatura' }}
              </div>
            </div>
            <a-tag color="green">Maestro</a-tag>
          </div>
        </div>
      </div>

      <!-- Employees -->
      <div v-if="results.employees.length > 0" class="search-section">
        <div class="search-section-header">
          <h4>Empleados ({{ results.employees.length }})</h4>
          <a-button type="link" size="small" @click="viewAll('employees')">
            Ver todos
          </a-button>
        </div>
        <div class="search-results-list">
          <div
            v-for="employee in results.employees.slice(0, 3)"
            :key="`employee-${employee.id}`"
            class="search-result-item"
            @click="goToTables(employee.id)"
          >
            <a-avatar :size="32" :src="employee.photo_url" style="background-color: #f59e0b;">
              {{ getInitials(employee.name) }}
            </a-avatar>
            <div class="search-result-info">
              <div class="search-result-title">{{ employee.name }}</div>
              <div class="search-result-subtitle">
                {{ employee.position || 'Sin cargo' }}
              </div>
            </div>
            <a-tag color="orange">Empleado</a-tag>
          </div>
        </div>
      </div>

      <!-- Incidences -->
      <div v-if="results.incidences.length > 0" class="search-section">
        <div class="search-section-header">
          <h4>Incidencias ({{ results.incidences.length }})</h4>
          <a-button type="link" size="small" @click="viewAll('incidences')">
            Ver todos
          </a-button>
        </div>
        <div class="search-results-list">
          <div
            v-for="incidence in results.incidences.slice(0, 3)"
            :key="`incidence-${incidence.id}`"
            class="search-result-item"
            @click="goToIncidences()"
          >
            <a-avatar :size="32" style="background-color: #ef4444;">
              <a-icon type="exclamation-circle" />
            </a-avatar>
            <div class="search-result-info">
              <div class="search-result-title">{{ incidence.title || 'Incidencia' }}</div>
              <div class="search-result-subtitle">
                {{ formatDate(incidence.created_at) }}
              </div>
            </div>
            <a-tag :color="getIncidenceColor(incidence.type)">
              {{ incidence.type || 'General' }}
            </a-tag>
          </div>
        </div>
      </div>

      <!-- Audit Logs -->
      <div v-if="results.auditLogs.length > 0" class="search-section">
        <div class="search-section-header">
          <h4>Actividad ({{ results.auditLogs.length }})</h4>
          <a-button type="link" size="small" @click="viewAll('audit-logs')">
            Ver todos
          </a-button>
        </div>
        <div class="search-results-list">
          <div
            v-for="log in results.auditLogs.slice(0, 3)"
            :key="`log-${log.id}`"
            class="search-result-item"
            @click="goToProfile(log.subject_type === 'student' ? 'student' : 'teacher', log.subject_id)"
          >
            <a-avatar :size="32" style="background-color: #6b7280;">
              <a-icon :type="getActionIcon(log.action)" />
            </a-avatar>
            <div class="search-result-info">
              <div class="search-result-title">{{ getActionDescription(log) }}</div>
              <div class="search-result-subtitle">
                {{ formatDateTime(log.created_at) }}
              </div>
            </div>
            <a-tag :color="getActionColor(log.action)">
              {{ log.action }}
            </a-tag>
          </div>
        </div>
      </div>
    </div>

    <!-- View All Button -->
    <div v-if="hasResults" class="search-footer">
      <a-button type="primary" block @click="viewAllResults">
        Ver todos los resultados ({{ totalResults }})
      </a-button>
    </div>
  </div>
</template>

<script>
import searchService from '@/services/searchService';

export default {
  name: 'GlobalSearchResults',
  props: {
    query: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      loading: false,
      results: {
        students: [],
        teachers: [],
        employees: [],
        users: [],
        enrollments: [],
        incidences: [],
        auditLogs: []
      }
    };
  },
  computed: {
    hasResults() {
      return Object.values(this.results).some(arr => arr.length > 0);
    },
    totalResults() {
      return Object.values(this.results).reduce((total, arr) => total + arr.length, 0);
    }
  },
  watch: {
    query: {
      handler(newQuery) {
        if (newQuery && newQuery.trim().length >= 2) {
          this.performSearch(newQuery);
        } else {
          this.clearResults();
        }
      },
      immediate: true
    }
  },
  methods: {
    async performSearch(query) {
      this.loading = true;
      try {
        this.results = await searchService.globalSearch(query);
      } catch (error) {
        console.error('Error en búsqueda:', error);
        this.$message.error('Error al realizar la búsqueda');
      } finally {
        this.loading = false;
      }
    },
    clearResults() {
      this.results = {
        students: [],
        teachers: [],
        employees: [],
        users: [],
        enrollments: [],
        incidences: [],
        auditLogs: []
      };
    },
    getInitials(name) {
      if (!name) return '?';
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('es-ES');
    },
    formatDateTime(date) {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    getIncidenceColor(type) {
      const colors = {
        'disciplinaria': 'red',
        'académica': 'orange',
        'asistencia': 'blue',
        'comportamiento': 'purple',
        'otra': 'default'
      };
      return colors[type?.toLowerCase()] || 'default';
    },
    getActionIcon(action) {
      const icons = {
        'created': 'plus-circle',
        'updated': 'edit',
        'deleted': 'delete',
        'login': 'login',
        'logout': 'logout',
        'viewed': 'eye'
      };
      return icons[action?.toLowerCase()] || 'info-circle';
    },
    getActionColor(action) {
      const colors = {
        'created': 'green',
        'updated': 'blue',
        'deleted': 'red',
        'login': 'purple',
        'logout': 'orange',
        'viewed': 'default'
      };
      return colors[action?.toLowerCase()] || 'default';
    },
    getActionDescription(log) {
      const descriptions = {
        'created': 'Registro creado',
        'updated': 'Registro actualizado',
        'deleted': 'Registro eliminado',
        'login': 'Inicio de sesión',
        'logout': 'Cierre de sesión',
        'viewed': 'Registro visto'
      };
      return descriptions[log.action?.toLowerCase()] || log.action || 'Acción desconocida';
    },
    goToProfile(entityType, id) {
      this.$emit('close');
      this.$router.push({
        path: '/profile',
        query: { type: entityType, id: id }
      });
    },
    goToTables(employeeId = null) {
      this.$emit('close');
      if (employeeId) {
        this.$router.push({
          path: '/tables',
          query: { employee: employeeId }
        });
      } else {
        this.$router.push('/tables');
      }
    },
    goToIncidences() {
      this.$emit('close');
      this.$router.push('/tables');
    },
    viewAll(type) {
      this.$emit('close');
      // Navegar a la página correspondiente con el filtro aplicado
      switch (type) {
        case 'students':
        case 'teachers':
          this.$router.push({
            path: '/profile',
            query: { type: type.slice(0, -1) } // Remove 's' for singular
          });
          break;
        case 'employees':
          this.goToTables();
          break;
        case 'incidences':
          this.goToIncidences();
          break;
        case 'audit-logs':
          this.$router.push('/profile');
          break;
      }
    },
    viewAllResults() {
      this.$emit('close');
      // Navegar a una página de búsqueda global completa
      this.$router.push({
        path: '/search',
        query: { q: this.query }
      });
    }
  }
};
</script>

<style scoped>
.global-search-results {
  max-height: 400px;
  overflow-y: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  gap: 8px;
  color: #666;
}

.search-no-results {
  padding: 20px;
  text-align: center;
}

.search-results-content {
  padding: 8px 0;
}

.search-section {
  margin-bottom: 16px;
}

.search-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.search-section-header h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.search-results-list {
  padding: 0;
}

.search-result-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.search-result-item:hover {
  background-color: #f8f9fa;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-info {
  flex: 1;
  margin-left: 12px;
  margin-right: 12px;
  min-width: 0;
}

.search-result-title {
  font-weight: 500;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-result-subtitle {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-footer {
  padding: 12px 16px;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
}

/* Scrollbar styling */
.global-search-results::-webkit-scrollbar {
  width: 6px;
}

.global-search-results::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.global-search-results::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.global-search-results::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
