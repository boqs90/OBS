<template>
	<div class="layout">
		<a-card class="plan-estudios-card" :bordered="false">
			<div class="card-header">
				<div class="header-content">
					<div class="header-info">
						<h2 class="page-title">
							<span class="icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 4C2.89543 4 2 4.89543 2 6V7H22V6C22 4.89543 21.1046 4 20 4H4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M22 9H2V14C2 15.1046 2.89543 16 4 16H20C21.1046 16 22 15.1046 22 14V9ZM4 13C4 12.4477 4.44772 12 5 12H6C6.55228 12 7 12.4477 7 13C7 13.5523 6.55228 14 6 14H5C4.44772 14 4 13.5523 4 13ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H10C10.5523 14 11 13.5523 11 13C11 12.4477 10.5523 12 10 12H9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							Plan de Estudios
						</h2>
						<p class="page-description">Gestiona el plan de estudios, mallas curriculares y objetivos de aprendizaje por grado</p>
					</div>
					<div class="header-actions">
						<a-button type="primary" @click="showCreateModal = true">
							<a-icon type="plus" />
							Nuevo Plan
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
							placeholder="Buscar plan..."
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
							v-model="statusFilter"
							placeholder="Filtrar por estado"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los estados</a-select-option>
							<a-select-option value="draft">Borrador</a-select-option>
							<a-select-option value="active">Activo</a-select-option>
							<a-select-option value="archived">Archivado</a-select-option>
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

			<!-- Tabla de planes de estudio -->
			<a-table
				:columns="columns"
				:data-source="filteredPlans"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				:row-key="record => record.id"
			>
				<template slot="name" slot-scope="text, record">
					<div class="plan-name">
						<span class="name">{{ text }}</span>
						<span class="version">v{{ record.version }}</span>
					</div>
				</template>

				<template slot="grade" slot-scope="text">
					<a-tag :color="getGradeColor(text)">
						{{ getGradeLabel(text) }}
					</a-tag>
				</template>

				<template slot="status" slot-scope="text">
					<a-badge :status="getStatusBadge(text)" :text="getStatusLabel(text)" />
				</template>

				<template slot="actions" slot-scope="text, record">
					<div class="table-actions">
						<a-button size="small" @click="viewPlan(record)">
							<a-icon type="eye" />
						</a-button>
						<a-button size="small" @click="editPlan(record)">
							<a-icon type="edit" />
						</a-button>
						<a-button size="small" type="primary" @click="activatePlan(record)" v-if="record.status === 'draft'">
							<a-icon type="check" />
						</a-button>
						<a-button size="small" type="danger" @click="deletePlan(record)">
							<a-icon type="delete" />
						</a-button>
					</div>
				</template>
			</a-table>
		</a-card>

		<!-- Modal para crear/editar plan -->
		<a-modal
			:title="editingPlan ? 'Editar Plan de Estudios' : 'Nuevo Plan de Estudios'"
			:visible="showCreateModal"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirm-loading="saving"
			width="900px"
		>
			<a-form :form="form" :layout="'vertical'">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Nombre del Plan">
							<a-input
								v-decorator="[
									'name',
									{ rules: [{ required: true, message: 'El nombre es requerido' }] }
								]"
								placeholder="Ej: Plan de Estudios 2024"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Versión">
							<a-input-number
								v-decorator="['version']"
								placeholder="Ej: 1.0"
								:min="1"
								:step="0.1"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

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

				<a-form-item label="Descripción">
					<a-textarea
						v-decorator="['description']"
						placeholder="Descripción general del plan de estudios"
						:rows="3"
					/>
				</a-form-item>

				<a-form-item label="Objetivos de Aprendizaje">
					<a-textarea
						v-decorator="[
							'learning_objectives',
							{ rules: [{ required: true, message: 'Los objetivos son requeridos' }] }
						]"
						placeholder="Define los objetivos de aprendizaje para este grado"
						:rows="4"
					/>
				</a-form-item>

				<a-form-item label="Competencias a Desarrollar">
					<a-textarea
						v-decorator="['competencies']"
						placeholder="Competencias clave que se desarrollarán"
						:rows="3"
					/>
				</a-form-item>

				<a-form-item label="Estado">
					<a-select
						v-decorator="[
							'status',
							{ rules: [{ required: true, message: 'El estado es requerido' }] }
						]"
						placeholder="Selecciona el estado"
						style="width: 100%"
					>
						<a-select-option value="draft">Borrador</a-select-option>
						<a-select-option value="active">Activo</a-select-option>
						<a-select-option value="archived">Archivado</a-select-option>
					</a-select>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal para ver detalles -->
		<a-modal
			title="Detalles del Plan de Estudios"
			:visible="showDetailsModal"
			@cancel="showDetailsModal = false"
			:footer="null"
			width="800px"
		>
			<div v-if="selectedPlan" class="plan-details">
				<a-descriptions :column="2" bordered>
					<a-descriptions-item label="Nombre">
						{{ selectedPlan.name }}
					</a-descriptions-item>
					<a-descriptions-item label="Versión">
						v{{ selectedPlan.version }}
					</a-descriptions-item>
					<a-descriptions-item label="Grado">
						<a-tag :color="getGradeColor(selectedPlan.grade)">
							{{ getGradeLabel(selectedPlan.grade) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Año Lectivo">
						{{ selectedPlan.academic_year }}
					</a-descriptions-item>
					<a-descriptions-item label="Estado">
						<a-badge :status="getStatusBadge(selectedPlan.status)" :text="getStatusLabel(selectedPlan.status)" />
					</a-descriptions-item>
					<a-descriptions-item label="Creado">
						{{ formatDateTime(selectedPlan.created_at) }}
					</a-descriptions-item>
					<a-descriptions-item label="Actualizado">
						{{ formatDateTime(selectedPlan.updated_at) }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div class="objectives-section">
					<h4>Objetivos de Aprendizaje</h4>
					<div class="objectives-content" v-html="formatText(selectedPlan.learning_objectives)"></div>
				</div>
				
				<div class="competencies-section">
					<h4>Competencias</h4>
					<div class="competencies-content" v-html="formatText(selectedPlan.competencies)"></div>
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
	name: 'PlanEstudios',
	data() {
		return {
			loading: false,
			plansData: [],
			searchText: '',
			gradeFilter: 'all',
			statusFilter: 'all',
			showCreateModal: false,
			showDetailsModal: false,
			saving: false,
			editingPlan: null,
			selectedPlan: null,
			form: this.$form.createForm(this, { name: 'plan_estudios_form' }),
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} planes`,
			},
			columns: [
				{ title: 'Nombre', dataIndex: 'name', key: 'name', scopedSlots: { customRender: 'name' } },
				{ title: 'Grado', dataIndex: 'grade', key: 'grade', width: 120, scopedSlots: { customRender: 'grade' } },
				{ title: 'Año Lectivo', dataIndex: 'academic_year', key: 'academic_year', width: 120 },
				{ title: 'Estado', dataIndex: 'status', key: 'status', width: 120, scopedSlots: { customRender: 'status' } },
				{ title: 'Acciones', key: 'actions', width: 200, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
		};
	},
	computed: {
		filteredPlans() {
			let filtered = this.plansData;

			// Filtrar por texto de búsqueda
			if (this.searchText && this.searchText.trim() !== '') {
				const search = this.searchText.toLowerCase();
				filtered = filtered.filter(plan => 
					plan.name.toLowerCase().includes(search) ||
					plan.description.toLowerCase().includes(search)
				);
			}

			// Filtrar por grado
			if (this.gradeFilter !== 'all') {
				filtered = filtered.filter(plan => plan.grade === this.gradeFilter);
			}

			// Filtrar por estado
			if (this.statusFilter !== 'all') {
				filtered = filtered.filter(plan => plan.status === this.statusFilter);
			}

			return filtered;
		},
	},
	mounted() {
		this.fetchPlans();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchPlans() {
			this.loading = true;
			axios.get('http://localhost:8000/api/study-plans', { headers: this.apiHeaders() })
				.then((res) => {
					this.plansData = res.data || [];
					this.pagination.total = this.plansData.length;
				})
				.catch((err) => {
					console.error('Error al obtener planes de estudio:', err.response?.data || err);
					this.$message.error('No se pudieron cargar los planes de estudio');
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
			this.statusFilter = 'all';
			this.pagination.current = 1;
		},
		handleTableChange(pagination) {
			this.pagination = { ...this.pagination, ...pagination };
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
				draft: 'default',
				active: 'success',
				archived: 'error',
			};
			return badges[status] || 'default';
		},
		getStatusLabel(status) {
			const labels = {
				draft: 'Borrador',
				active: 'Activo',
				archived: 'Archivado',
			};
			return labels[status] || status;
		},
		formatDateTime(dateStr) {
			return moment(dateStr).format('DD/MM/YYYY HH:mm');
		},
		formatText(text) {
			return text ? text.replace(/\n/g, '<br>') : '';
		},
		viewPlan(record) {
			this.selectedPlan = record;
			this.showDetailsModal = true;
		},
		editPlan(record) {
			this.editingPlan = record;
			this.showCreateModal = true;
			this.form.setFieldsValue({
				name: record.name,
				version: record.version,
				grade: record.grade,
				academic_year: moment(record.academic_year, 'YYYY'),
				description: record.description,
				learning_objectives: record.learning_objectives,
				competencies: record.competencies,
				status: record.status,
			});
		},
		closeModal() {
			this.showCreateModal = false;
			this.saving = false;
			this.editingPlan = null;
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

				const request = this.editingPlan
					? axios.put(`http://localhost:8000/api/study-plans/${this.editingPlan.id}`, payload, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/study-plans', payload, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchPlans();
						this.$message.success(this.editingPlan ? 'Plan actualizado correctamente' : 'Plan creado correctamente');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar el plan';
						console.error('Error guardando plan:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		activatePlan(record) {
			this.$confirm({
				title: 'Activar Plan',
				content: `¿Estás seguro de activar el plan "${record.name}"?`,
				okText: 'Activar',
				okType: 'primary',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.put(`http://localhost:8000/api/study-plans/${record.id}`, { status: 'active' }, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchPlans();
							this.$message.success('Plan activado correctamente');
						})
						.catch((error) => {
							console.error('Error activando plan:', error.response?.data || error);
							this.$message.error('No se pudo activar el plan');
						});
				},
			});
		},
		deletePlan(record) {
			this.$confirm({
				title: 'Eliminar Plan',
				content: `¿Estás seguro de eliminar el plan "${record.name}"?`,
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.delete(`http://localhost:8000/api/study-plans/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchPlans();
							this.$message.success('Plan eliminado correctamente');
						})
						.catch((error) => {
							console.error('Error eliminando plan:', error.response?.data || error);
							this.$message.error('No se pudo eliminar el plan');
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

.plan-estudios-card {
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

.plan-name {
	display: flex;
	flex-direction: column;
	gap: 4px;

	.name {
		font-weight: 600;
		color: #1f2937;
	}

	.version {
		font-size: 11px;
		color: #6b7280;
		background: #f3f4f6;
		padding: 2px 6px;
		border-radius: 4px;
		display: inline-block;
		width: fit-content;
	}
}

.table-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	flex-wrap: wrap;
}

.plan-details {
	.objectives-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.objectives-content {
			background: #f9fafb;
			padding: 16px;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			line-height: 1.6;
		}
	}
	
	.competencies-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.competencies-content {
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
