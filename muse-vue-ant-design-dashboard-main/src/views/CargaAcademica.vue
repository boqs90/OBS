<template>
	<div class="layout">
		<a-card class="carga-academica-card" :bordered="false">
			<div class="card-header">
				<div class="header-content">
					<div class="header-info">
						<h2 class="page-title">
							<span class="icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9 11H7V13H9V11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M13 11H11V13H13V11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M17 11H15V13H17V11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M5 11H3V13H5V11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M9 5H7V7H9V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M13 5H11V7H13V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M17 5H15V7H17V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M5 5H3V7H5V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M9 17H7V19H9V17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M13 17H11V19H13V17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M17 17H15V19H17V17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M5 17H3V19H5V17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							Carga Académica
						</h2>
						<p class="page-description">Asigna asignaturas y docentes a cada grado, gestiona la distribución de carga académica</p>
					</div>
					<div class="header-actions">
						<a-button type="primary" @click="showCreateModal = true">
							<a-icon type="plus" />
							Nueva Asignación
						</a-button>
					</div>
				</div>
			</div>

			<!-- Filtros y búsqueda -->
			<div class="filters-section">
				<a-row :gutter="16" type="flex" align="middle">
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-input-search
							v-model="searchText"
							placeholder="Buscar asignación..."
							@search="handleSearch"
							allow-clear
						/>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-select
							v-model="gradeFilter"
							placeholder="Filtrar por grado"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los grados</a-select-option>
							<a-select-option value="pre-kinder">Pre-Kínder</a-select-option>
							<a-select-option value="kinder">Kínder</a-select-option>
							<a-select-option value="1ro">1er Grado</a-select-option>
							<a-select-option value="2do">2do Grado</a-select-option>
							<a-select-option value="3ro">3er Grado</a-select-option>
							<a-select-option value="4to">4to Grado</a-select-option>
							<a-select-option value="5to">5to Grado</a-select-option>
							<a-select-option value="6to">6to Grado</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-select
							v-model="teacherFilter"
							placeholder="Filtrar por docente"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
							show-search
							:filter-option="filterTeacherOption"
						>
							<a-select-option v-for="teacher in teachersData" :key="teacher.id" :value="teacher.id">
								{{ teacher.name }}
							</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-button @click="resetFilters">
							<a-icon type="reload" />
							Limpiar filtros
						</a-button>
					</a-col>
				</a-row>
			</div>

			<!-- Tabla de carga académica -->
			<a-table
				:columns="columns"
				:data-source="filteredAssignments"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				:row-key="record => record.id"
			>
				<template slot="grade" slot-scope="text">
					<a-tag :color="getGradeColor(text)">
						{{ getGradeLabel(text) }}
					</a-tag>
				</template>

				<template slot="teacher" slot-scope="text, record">
					<div class="teacher-info">
						<span class="name">{{ text }}</span>
						<span class="subjects">{{ record.subjects_count }} asignaturas</span>
					</div>
				</template>

				<template slot="subjects" slot-scope="text">
					<div class="subjects-list">
						<a-tag v-for="subject in text" :key="subject.id" color="blue" class="subject-tag">
							{{ subject.name }}
						</a-tag>
					</div>
				</template>

				<template slot="hours" slot-scope="text">
					<span class="hours">{{ text }}h</span>
				</template>

				<template slot="status" slot-scope="text">
					<a-badge :status="getStatusBadge(text)" :text="getStatusLabel(text)" />
				</template>

				<template slot="actions" slot-scope="text, record">
					<div class="table-actions">
						<a-button size="small" @click="viewAssignment(record)">
							<a-icon type="eye" />
						</a-button>
						<a-button size="small" @click="editAssignment(record)">
							<a-icon type="edit" />
						</a-button>
						<a-button size="small" type="danger" @click="deleteAssignment(record)">
							<a-icon type="delete" />
						</a-button>
					</div>
				</template>
			</a-table>
		</a-card>

		<!-- Modal para crear/editar asignación -->
		<a-modal
			:title="editingAssignment ? 'Editar Asignación' : 'Nueva Asignación'"
			:visible="showCreateModal"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirm-loading="saving"
			width="800px"
		>
			<a-form :form="form" :layout="'vertical'">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Grado">
							<a-select
								v-decorator="[
									'grade',
									{ rules: [{ required: true, message: 'El grado es requerido' }] }
								]"
								placeholder="Selecciona el grado"
								style="width: 100%"
								@change="onGradeChange"
							>
								<a-select-option value="pre-kinder">Pre-Kínder</a-select-option>
								<a-select-option value="kinder">Kínder</a-select-option>
								<a-select-option value="1ro">1er Grado</a-select-option>
								<a-select-option value="2do">2do Grado</a-select-option>
								<a-select-option value="3ro">3er Grado</a-select-option>
								<a-select-option value="4to">4to Grado</a-select-option>
								<a-select-option value="5to">5to Grado</a-select-option>
								<a-select-option value="6to">6to Grado</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Docente">
							<a-select
								v-decorator="[
									'teacher_id',
									{ rules: [{ required: true, message: 'El docente es requerido' }] }
								]"
								placeholder="Selecciona el docente"
								style="width: 100%"
								show-search
								:filter-option="filterTeacherOption"
							>
								<a-select-option v-for="teacher in teachersData" :key="teacher.id" :value="teacher.id">
									{{ teacher.name }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Asignaturas">
					<a-select
						v-decorator="[
							'subject_ids',
							{ rules: [{ required: true, message: 'Las asignaturas son requeridas' }] }
						]"
						placeholder="Selecciona las asignaturas"
						mode="multiple"
						style="width: 100%"
					>
						<a-select-option v-for="subject in subjectsData" :key="subject.id" :value="subject.id">
							{{ subject.name }}
						</a-select-option>
					</a-select>
				</a-form-item>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Horas Semanales">
							<a-input-number
								v-decorator="['weekly_hours']"
								placeholder="Total de horas semanales"
								:min="1"
								:max="40"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Año Lectivo">
							<a-date-picker
								v-decorator="[
									'academic_year',
									{ rules: [{ required: true, message: 'El año lectivo es requerido' }] }
								]"
								placeholder="Selecciona el año lectivo"
								mode="year"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Observaciones">
					<a-textarea
						v-decorator="['observations']"
						placeholder="Observaciones adicionales sobre la asignación"
						:rows="3"
					/>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal para ver detalles -->
		<a-modal
			title="Detalles de Asignación"
			:visible="showDetailsModal"
			@cancel="showDetailsModal = false"
			:footer="null"
			width="700px"
		>
			<div v-if="selectedAssignment" class="assignment-details">
				<a-descriptions :column="1" bordered>
					<a-descriptions-item label="Grado">
						<a-tag :color="getGradeColor(selectedAssignment.grade)">
							{{ getGradeLabel(selectedAssignment.grade) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Docente">
						{{ selectedAssignment.teacher_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Asignaturas">
						<div class="subjects-list">
							<a-tag v-for="subject in selectedAssignment.subjects" :key="subject.id" color="blue">
								{{ subject.name }}
							</a-tag>
						</div>
					</a-descriptions-item>
					<a-descriptions-item label="Horas Semanales">
						{{ selectedAssignment.weekly_hours }}h
					</a-descriptions-item>
					<a-descriptions-item label="Año Lectivo">
						{{ selectedAssignment.academic_year }}
					</a-descriptions-item>
					<a-descriptions-item label="Estado">
						<a-badge :status="getStatusBadge(selectedAssignment.status)" :text="getStatusLabel(selectedAssignment.status)" />
					</a-descriptions-item>
					<a-descriptions-item label="Creado">
						{{ formatDateTime(selectedAssignment.created_at) }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div class="observations-section" v-if="selectedAssignment.observations">
					<h4>Observaciones</h4>
					<div class="observations-content">
						{{ selectedAssignment.observations }}
					</div>
				</div>
			</div>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'CargaAcademica',
	data() {
		return {
			loading: false,
			assignmentsData: [],
			teachersData: [],
			subjectsData: [],
			searchText: '',
			gradeFilter: 'all',
			teacherFilter: 'all',
			showCreateModal: false,
			showDetailsModal: false,
			saving: false,
			editingAssignment: null,
			selectedAssignment: null,
			form: this.$form.createForm(this, { name: 'carga_academica_form' }),
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} asignaciones`,
			},
			columns: [
				{ title: 'Grado', dataIndex: 'grade', key: 'grade', width: 120, scopedSlots: { customRender: 'grade' } },
				{ title: 'Docente', dataIndex: 'teacher_name', key: 'teacher', scopedSlots: { customRender: 'teacher' } },
				{ title: 'Asignaturas', dataIndex: 'subjects', key: 'subjects', scopedSlots: { customRender: 'subjects' } },
				{ title: 'Horas', dataIndex: 'weekly_hours', key: 'hours', width: 80, scopedSlots: { customRender: 'hours' } },
				{ title: 'Estado', dataIndex: 'status', key: 'status', width: 120, scopedSlots: { customRender: 'status' } },
				{ title: 'Acciones', key: 'actions', width: 150, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
		};
	},
	computed: {
		filteredAssignments() {
			let filtered = this.assignmentsData;

			// Filtrar por texto de búsqueda
			if (this.searchText && this.searchText.trim() !== '') {
				const search = this.searchText.toLowerCase();
				filtered = filtered.filter(assignment => 
					assignment.teacher_name.toLowerCase().includes(search) ||
					assignment.grade.toLowerCase().includes(search)
				);
			}

			// Filtrar por grado
			if (this.gradeFilter !== 'all') {
				filtered = filtered.filter(assignment => assignment.grade === this.gradeFilter);
			}

			// Filtrar por docente
			if (this.teacherFilter !== 'all') {
				filtered = filtered.filter(assignment => assignment.teacher_id === this.teacherFilter);
			}

			return filtered;
		},
	},
	mounted() {
		this.fetchTeachers();
		this.fetchSubjects();
		this.fetchAssignments();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchTeachers() {
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((res) => {
					this.teachersData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener docentes:', err.response?.data || err);
					this.teachersData = [];
				});
		},
		fetchSubjects() {
			axios.get('http://localhost:8000/api/subjects', { headers: this.apiHeaders() })
				.then((res) => {
					this.subjectsData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener asignaturas:', err.response?.data || err);
					this.subjectsData = [];
				});
		},
		fetchAssignments() {
			this.loading = true;
			axios.get('http://localhost:8000/api/academic-assignments', { headers: this.apiHeaders() })
				.then((res) => {
					this.assignmentsData = res.data || [];
					this.pagination.total = this.assignmentsData.length;
				})
				.catch((err) => {
					console.error('Error al obtener asignaciones:', err.response?.data || err);
					this.$message.error('No se pudieron cargar las asignaciones');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		handleSearch() {
			this.pagination.current = 1;
		},
		handleFilter() {
			this.pagination.current = 1;
		},
		resetFilters() {
			this.searchText = '';
			this.gradeFilter = 'all';
			this.teacherFilter = 'all';
			this.pagination.current = 1;
		},
		handleTableChange(pagination) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		filterTeacherOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		getGradeColor(grade) {
			const colors = {
				'pre-kinder': 'pink',
				'kinder': 'purple',
				'1ro': 'blue',
				'2do': 'cyan',
				'3ro': 'green',
				'4to': 'lime',
				'5to': 'yellow',
				'6to': 'orange',
			};
			return colors[grade] || 'default';
		},
		getGradeLabel(grade) {
			const labels = {
				'pre-kinder': 'Pre-Kínder',
				'kinder': 'Kínder',
				'1ro': '1er Grado',
				'2do': '2do Grado',
				'3ro': '3er Grado',
				'4to': '4to Grado',
				'5to': '5to Grado',
				'6to': '6to Grado',
			};
			return labels[grade] || grade;
		},
		getStatusBadge(status) {
			const badges = {
				active: 'success',
				inactive: 'error',
				pending: 'processing',
			};
			return badges[status] || 'default';
		},
		getStatusLabel(status) {
			const labels = {
				active: 'Activa',
				inactive: 'Inactiva',
				pending: 'Pendiente',
			};
			return labels[status] || status;
		},
		formatDateTime(dateStr) {
			return moment(dateStr).format('DD/MM/YYYY HH:mm');
		},
		onGradeChange(value) {
			// Opcional: cargar asignaturas específicas para el grado seleccionado
		},
		viewAssignment(record) {
			this.selectedAssignment = record;
			this.showDetailsModal = true;
		},
		editAssignment(record) {
			this.editingAssignment = record;
			this.showCreateModal = true;
			this.form.setFieldsValue({
				grade: record.grade,
				teacher_id: record.teacher_id,
				subject_ids: record.subjects.map(s => s.id),
				weekly_hours: record.weekly_hours,
				academic_year: moment(record.academic_year, 'YYYY'),
				observations: record.observations,
			});
		},
		closeModal() {
			this.showCreateModal = false;
			this.saving = false;
			this.editingAssignment = null;
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				this.saving = true;
				const payload = {
					...values,
					academic_year: values.academic_year ? values.academic_year.format('YYYY') : null,
				};

				const request = this.editingAssignment
					? axios.put(`http://localhost:8000/api/academic-assignments/${this.editingAssignment.id}`, payload, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/academic-assignments', payload, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchAssignments();
						this.$message.success(this.editingAssignment ? 'Asignación actualizada correctamente' : 'Asignación creada correctamente');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar la asignación';
						console.error('Error guardando asignación:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		deleteAssignment(record) {
			this.$confirm({
				title: 'Eliminar Asignación',
				content: `¿Estás seguro de eliminar la asignación del grado "${this.getGradeLabel(record.grade)}"?`,
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.delete(`http://localhost:8000/api/academic-assignments/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchAssignments();
							this.$message.success('Asignación eliminada correctamente');
						})
						.catch((error) => {
							console.error('Error eliminando asignación:', error.response?.data || error);
							this.$message.error('No se pudo eliminar la asignación');
						});
				},
			});
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.carga-academica-card {
	.card-header {
		margin-bottom: 24px;
		
		.header-content {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 16px;
			flex-wrap: wrap;

			.header-info {
				flex: 1;
				min-width: 0;

				.page-title {
					display: flex;
					align-items: center;
					gap: 12px;
					margin: 0 0 8px 0;
					font-size: 24px;
					font-weight: 700;
					color: #1f2937;

					.icon {
						color: #7c3aed;
					}
				}

				.page-description {
					margin: 0;
					color: #6b7280;
					font-size: 14px;
					line-height: 1.5;
				}
			}

			.header-actions {
				flex-shrink: 0;
			}
		}
	}
}

.filters-section {
	margin-bottom: 24px;
	padding: 20px;
	background: #f8fafc;
	border-radius: 8px;
	border: 1px solid #e2e8f0;
}

.teacher-info {
	display: flex;
	flex-direction: column;
	gap: 4px;

	.name {
		font-weight: 600;
		color: #1f2937;
	}

	.subjects {
		font-size: 12px;
		color: #6b7280;
	}
}

.subjects-list {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;

	.subject-tag {
		margin-bottom: 4px;
	}
}

.hours {
	font-weight: 600;
	color: #7c3aed;
}

.table-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	flex-wrap: wrap;
}

.assignment-details {
	.subjects-list {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		margin-top: 8px;
	}
	
	.observations-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.observations-content {
			background: #f9fafb;
			padding: 16px;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			line-height: 1.6;
		}
	}
}

// Responsive
@media (max-width: 768px) {
	.layout {
		padding: 16px;
	}

	.header-content {
		flex-direction: column;
		align-items: stretch;
	}

	.header-actions {
		width: 100%;
		
		.ant-btn {
			width: 100%;
		}
	}
}

@media (max-width: 576px) {
	.filters-section {
		.ant-row {
			.ant-col {
				margin-bottom: 12px;
			}
		}
	}
}
</style>
