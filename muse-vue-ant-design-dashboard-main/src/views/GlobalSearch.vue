<template>
	<div class="global-search-page">
		<!-- Header de búsqueda -->
		<a-card :bordered="false" class="search-header-card">
			<a-row type="flex" align="middle" justify="space-between">
				<a-col :span="24" :md="16">
					<div class="search-input-container">
						<h1 class="search-title">Búsqueda Global</h1>
						<p class="search-subtitle">Busca estudiantes, maestros, empleados y más en todo el sistema</p>
						<a-input-search
							v-model="searchQuery"
							placeholder="Escribe al menos 2 caracteres para buscar..."
							size="large"
							@search="performSearch"
							@input="onInputChange"
							:loading="loading"
							class="search-input"
							allowClear
						>
							<template #prefix>
								<a-icon type="search" />
							</template>
						</a-input-search>
					</div>
				</a-col>
				<a-col :span="24" :md="8" class="search-stats-col">
					<div class="search-stats">
						<div class="stat-item">
							<span class="stat-number">{{ totalResults }}</span>
							<span class="stat-label">Resultados</span>
						</div>
						<div class="stat-item">
							<span class="stat-number">{{ searchTime }}</span>
							<span class="stat-label">Tiempo</span>
						</div>
					</div>
				</a-col>
			</a-row>
		</a-card>

		<!-- Filtros rápidos -->
		<a-card :bordered="false" class="filters-card" v-if="hasResults">
			<div class="filters-container">
				<h3>Filtrar por tipo:</h3>
				<a-radio-group v-model="activeFilter" @change="onFilterChange" buttonStyle="solid">
					<a-radio-button value="all">Todos</a-radio-button>
					<a-radio-button value="students">Estudiantes</a-radio-button>
					<a-radio-button value="teachers">Maestros</a-radio-button>
					<a-radio-button value="employees">Empleados</a-radio-button>
					<a-radio-button value="incidences">Incidencias</a-radio-button>
					<a-radio-button value="audit-logs">Actividad</a-radio-button>
				</a-radio-group>
			</div>
		</a-card>

		<!-- Loading State -->
		<div v-if="loading" class="loading-container">
			<a-spin size="large" />
			<p>Buscando en todo el sistema...</p>
		</div>

		<!-- Empty State -->
		<div v-else-if="!hasQueried" class="empty-state">
			<a-empty
				:image="require('@/assets/images/search.svg')"
				description="Comienza escribiendo para buscar"
			>
				<template #image>
					<a-icon type="search" style="font-size: 64px; color: #d9d9d9;" />
				</template>
			</a-empty>
		</div>

		<!-- No Results State -->
		<div v-else-if="!hasResults" class="no-results">
			<a-empty
				description="No se encontraron resultados"
			>
				<template #image>
					<a-icon type="file-search" style="font-size: 64px; color: #d9d9d9;" />
				</template>
				<a-button type="primary" @click="clearSearch">
					Limpiar búsqueda
				</a-button>
			</a-empty>
		</div>

		<!-- Results -->
		<div v-else class="search-results">
			<!-- Students -->
			<a-card :bordered="false" class="results-section" v-if="filteredResults.students.length > 0">
				<div class="section-header">
					<h3>
						<a-icon type="user" />
						Estudiantes ({{ filteredResults.students.length }})
					</h3>
					<a-button type="link" @click="viewAllResults('students')">
						Ver todos
					</a-button>
				</div>
				<a-list :data-source="filteredResults.students" :pagination="false">
					<a-list-item slot="renderItem" slot-scope="student">
						<a-list-item-meta>
							<a-avatar slot="avatar" :src="student.photo_url" style="background-color: #7c3aed;">
								{{ getInitials(student.name) }}
							</a-avatar>
							<div slot="title">
								<a @click="goToProfile('student', student.id)">{{ student.name }}</a>
							</div>
							<div slot="description">
								{{ student.grade || 'Sin grado' }} • {{ student.section || 'Sin sección' }}
							</div>
						</a-list-item-meta>
						<div>
							<a-tag color="blue">Estudiante</a-tag>
						</div>
					</a-list-item>
				</a-list>
			</a-card>

			<!-- Teachers -->
			<a-card :bordered="false" class="results-section" v-if="filteredResults.teachers.length > 0">
				<div class="section-header">
					<h3>
						<a-icon type="read" />
						Maestros ({{ filteredResults.teachers.length }})
					</h3>
					<a-button type="link" @click="viewAllResults('teachers')">
						Ver todos
					</a-button>
				</div>
				<a-list :data-source="filteredResults.teachers" :pagination="false">
					<a-list-item slot="renderItem" slot-scope="teacher">
						<a-list-item-meta>
							<a-avatar slot="avatar" :src="teacher.photo_url" style="background-color: #22c55e;">
								{{ getInitials(teacher.name) }}
							</a-avatar>
							<div slot="title">
								<a @click="goToProfile('teacher', teacher.id)">{{ teacher.name }}</a>
							</div>
							<div slot="description">
								{{ teacher.subject || 'Sin asignatura' }} • {{ teacher.email }}
							</div>
						</a-list-item-meta>
						<div>
							<a-tag color="green">Maestro</a-tag>
						</div>
					</a-list-item>
				</a-list>
			</a-card>

			<!-- Employees -->
			<a-card :bordered="false" class="results-section" v-if="filteredResults.employees.length > 0">
				<div class="section-header">
					<h3>
						<a-icon type="team" />
						Empleados ({{ filteredResults.employees.length }})
					</h3>
					<a-button type="link" @click="viewAllResults('employees')">
						Ver todos
					</a-button>
				</div>
				<a-list :data-source="filteredResults.employees" :pagination="false">
					<a-list-item slot="renderItem" slot-scope="employee">
						<a-list-item-meta>
							<a-avatar slot="avatar" :src="employee.photo_url" style="background-color: #f59e0b;">
								{{ getInitials(employee.name) }}
							</a-avatar>
							<div slot="title">
								<a @click="goToTables(employee.id)">{{ employee.name }}</a>
							</div>
							<div slot="description">
								{{ employee.position || 'Sin cargo' }} • {{ employee.department || 'Sin departamento' }}
							</div>
						</a-list-item-meta>
						<div>
							<a-tag color="orange">Empleado</a-tag>
						</div>
					</a-list-item>
				</a-list>
			</a-card>

			<!-- Incidences -->
			<a-card :bordered="false" class="results-section" v-if="filteredResults.incidences.length > 0">
				<div class="section-header">
					<h3>
						<a-icon type="exclamation-circle" />
						Incidencias ({{ filteredResults.incidences.length }})
					</h3>
					<a-button type="link" @click="viewAllResults('incidences')">
						Ver todos
					</a-button>
				</div>
				<a-list :data-source="filteredResults.incidences" :pagination="false">
					<a-list-item slot="renderItem" slot-scope="incidence">
						<a-list-item-meta>
							<a-avatar slot="avatar" style="background-color: #ef4444;">
								<a-icon type="exclamation-circle" />
							</a-avatar>
							<div slot="title">
								<a @click="goToIncidences()">{{ incidence.title || 'Incidencia' }}</a>
							</div>
							<div slot="description">
								{{ incidence.description || 'Sin descripción' }} • {{ formatDate(incidence.created_at) }}
							</div>
						</a-list-item-meta>
						<div>
							<a-tag :color="getIncidenceColor(incidence.type)">
								{{ incidence.type || 'General' }}
							</a-tag>
						</div>
					</a-list-item>
				</a-list>
			</a-card>

			<!-- Audit Logs -->
			<a-card :bordered="false" class="results-section" v-if="filteredResults.auditLogs.length > 0">
				<div class="section-header">
					<h3>
						<a-icon type="history" />
						Actividad ({{ filteredResults.auditLogs.length }})
					</h3>
					<a-button type="link" @click="viewAllResults('audit-logs')">
						Ver todos
					</a-button>
				</div>
				<a-list :data-source="filteredResults.auditLogs" :pagination="false">
					<a-list-item slot="renderItem" slot-scope="log">
						<a-list-item-meta>
							<a-avatar slot="avatar" style="background-color: #6b7280;">
								<a-icon :type="getActionIcon(log.action)" />
							</a-avatar>
							<div slot="title">
								<a @click="goToProfile(log.subject_type === 'student' ? 'student' : 'teacher', log.subject_id)">
									{{ getActionDescription(log) }}
								</a>
							</div>
							<div slot="description">
								{{ log.subject_name || 'Sujeto desconocido' }} • {{ formatDateTime(log.created_at) }}
							</div>
						</a-list-item-meta>
						<div>
							<a-tag :color="getActionColor(log.action)">
								{{ log.action }}
							</a-tag>
						</div>
					</a-list-item>
				</a-list>
			</a-card>
		</div>
	</div>
</template>

<script>
import searchService from '@/services/searchService';

export default {
	name: 'GlobalSearch',
	data() {
		return {
			searchQuery: '',
			loading: false,
			results: {
				students: [],
				teachers: [],
				employees: [],
				users: [],
				enrollments: [],
				incidences: [],
				auditLogs: []
			},
			activeFilter: 'all',
			hasQueried: false,
			searchTime: '0ms',
			searchTimeout: null
		};
	},
	computed: {
		totalResults() {
			return Object.values(this.results).reduce((total, arr) => total + arr.length, 0);
		},
		hasResults() {
			return this.totalResults > 0;
		},
		filteredResults() {
			if (this.activeFilter === 'all') {
				return this.results;
			}
			return {
				[this.activeFilter]: this.results[this.activeFilter] || []
			};
		}
	},
	mounted() {
		// Obtener query parameter de la URL
		const query = this.$route.query.q;
		if (query) {
			this.searchQuery = query;
			this.performSearch(query);
		}
	},
	methods: {
		async performSearch(query) {
			if (!query || query.trim().length < 2) {
				this.clearResults();
				return;
			}

			this.loading = true;
			this.hasQueried = true;
			const startTime = Date.now();

			try {
				this.results = await searchService.globalSearch(query.trim());
				this.searchTime = `${Date.now() - startTime}ms`;
			} catch (error) {
				console.error('Error en búsqueda:', error);
				this.$message.error('Error al realizar la búsqueda');
				this.clearResults();
			} finally {
				this.loading = false;
			}
		},
		onInputChange(e) {
			const value = e.target.value;
			this.searchQuery = value;

			// Limpiar timeout anterior
			if (this.searchTimeout) {
				clearTimeout(this.searchTimeout);
			}

			// Auto-búsqueda después de 500ms
			if (value.trim().length >= 2) {
				this.searchTimeout = setTimeout(() => {
					this.performSearch(value);
				}, 500);
			} else if (value.trim().length === 0) {
				this.clearResults();
			}
		},
		onFilterChange(e) {
			this.activeFilter = e.target.value;
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
			this.hasQueried = false;
			this.searchTime = '0ms';
		},
		clearSearch() {
			this.searchQuery = '';
			this.clearResults();
			this.$router.replace({ query: {} });
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
			this.$router.push({
				path: '/profile',
				query: { type: entityType, id: id }
			});
		},
		goToTables(employeeId = null) {
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
			this.$router.push('/tables');
		},
		viewAllResults(type) {
			// Navegar a la página específica con filtros aplicados
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
		}
	},
	watch: {
		'$route.query.q'(newQuery) {
			if (newQuery !== this.searchQuery) {
				this.searchQuery = newQuery || '';
				if (newQuery) {
					this.performSearch(newQuery);
				}
			}
		}
	},
	beforeDestroy() {
		if (this.searchTimeout) {
			clearTimeout(this.searchTimeout);
		}
	}
};
</script>

<style scoped>
.global-search-page {
	padding: 24px;
	max-width: 1200px;
	margin: 0 auto;
}

.search-header-card {
	margin-bottom: 24px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	border: none;
}

.search-header-card :deep(.ant-card-body) {
	padding: 32px;
}

.search-input-container h1 {
	color: white;
	margin-bottom: 8px;
	font-size: 32px;
	font-weight: 700;
}

.search-input-container p {
	color: rgba(255, 255, 255, 0.8);
	margin-bottom: 24px;
	font-size: 16px;
}

.search-input {
	max-width: 600px;
}

.search-input :deep(.ant-input) {
	height: 48px;
	font-size: 16px;
	border-radius: 24px;
	border: none;
	background: rgba(255, 255, 255, 0.9);
	backdrop-filter: blur(10px);
}

.search-input :deep(.ant-input:focus) {
	background: white;
	box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.search-stats-col {
	text-align: right;
}

.search-stats {
	display: flex;
	justify-content: flex-end;
	gap: 32px;
}

.stat-item {
	text-align: center;
}

.stat-number {
	display: block;
	font-size: 24px;
	font-weight: 700;
	color: white;
	line-height: 1;
}

.stat-label {
	font-size: 12px;
	color: rgba(255, 255, 255, 0.8);
	text-transform: uppercase;
	letter-spacing: 0.5px;
	margin-top: 4px;
}

.filters-card {
	margin-bottom: 24px;
}

.filters-container h3 {
	margin: 0 0 16px 0;
	color: #333;
}

.filters-container :deep(.ant-radio-group) {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}

.loading-container {
	text-align: center;
	padding: 64px 24px;
}

.loading-container p {
	margin-top: 16px;
	color: #666;
	font-size: 16px;
}

.empty-state,
.no-results {
	text-align: center;
	padding: 64px 24px;
}

.search-results {
	display: flex;
	flex-direction: column;
	gap: 24px;
}

.results-section {
	border-radius: 8px;
	transition: all 0.3s ease;
}

.results-section:hover {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
	padding-bottom: 12px;
	border-bottom: 1px solid #f0f0f0;
}

.section-header h3 {
	margin: 0;
	color: #333;
	font-size: 18px;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 8px;
}

.section-header h3 .anticon {
	color: #7c3aed;
}

/* Responsive */
@media (max-width: 768px) {
	.global-search-page {
		padding: 16px;
	}
	
	.search-header-card :deep(.ant-card-body) {
		padding: 24px 16px;
	}
	
	.search-input-container h1 {
		font-size: 24px;
	}
	
	.search-stats {
		justify-content: center;
		margin-top: 24px;
	}
	
	.search-stats-col {
		text-align: center;
	}
	
	.filters-container :deep(.ant-radio-group) {
		justify-content: center;
	}
}

@media (max-width: 480px) {
	.search-input {
		max-width: 100%;
	}
	
	.search-stats {
		gap: 16px;
	}
	
	.stat-number {
		font-size: 20px;
	}
	
	.section-header {
		flex-direction: column;
		align-items: flex-start;
		gap: 8px;
	}
}
</style>
