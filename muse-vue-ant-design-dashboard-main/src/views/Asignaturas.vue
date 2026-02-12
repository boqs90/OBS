<template>
	<div class="layout">
		<a-card class="subjects-card" :bordered="false">
			<div class="card-header">
				<div class="header-content">
					<div class="header-info">
						<h2 class="page-title">
							<span class="icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							Administración de Asignaturas
						</h2>
						<p class="page-description">Gestiona las asignaturas del sistema para su uso en boletas y otros módulos</p>
					</div>
					<div class="header-actions">
						<a-button type="primary" @click="showCreateModal = true">
							<a-icon type="plus" />
							Nueva Asignatura
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
							placeholder="Buscar asignatura..."
							@search="handleSearch"
							allow-clear
						/>
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
							<a-select-option value="active">Activa</a-select-option>
							<a-select-option value="inactive">Inactiva</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-select
							v-model="levelFilter"
							placeholder="Filtrar por nivel"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los niveles</a-select-option>
							<a-select-option value="primaria">Primaria</a-select-option>
							<a-select-option value="secundaria">Secundaria</a-select-option>
							<a-select-option value="bachillerato">Bachillerato</a-select-option>
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

			<!-- Tabla de asignaturas -->
			<a-table
				:columns="columns"
				:data-source="filteredSubjects"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				:row-key="record => record.id"
			>
				<template slot="name" slot-scope="text, record">
					<div class="subject-name">
						<span class="name">{{ text }}</span>
						<span class="code">{{ record.code }}</span>
					</div>
				</template>

				<template slot="level" slot-scope="text">
					<a-tag :color="getLevelColor(text)">
						{{ getLevelLabel(text) }}
					</a-tag>
				</template>

				<template slot="status" slot-scope="text">
					<a-badge :status="getStatusBadge(text)" :text="getStatusLabel(text)" />
				</template>

				<template slot="actions" slot-scope="text, record">
					<div class="table-actions">
						<a-button size="small" @click="editSubject(record)">
							<a-icon type="edit" />
						</a-button>
						<a-button size="small" type="danger" @click="deleteSubject(record)">
							<a-icon type="delete" />
						</a-button>
					</div>
				</template>
			</a-table>
		</a-card>

		<!-- Modal para crear/editar asignatura -->
		<a-modal
			:title="editingSubject ? 'Editar Asignatura' : 'Nueva Asignatura'"
			:visible="showCreateModal"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirm-loading="saving"
			width="600px"
		>
			<a-form :form="form" :layout="'vertical'">
				<a-form-item label="Nombre de la Asignatura">
					<a-input
						v-decorator="[
							'name',
							{ rules: [{ required: true, message: 'El nombre es requerido' }] }
						]"
						placeholder="Ej: Matemáticas"
					/>
				</a-form-item>

				<a-form-item label="Código">
					<a-input
						v-decorator="[
							'code',
							{ rules: [{ required: true, message: 'El código es requerido' }] }
						]"
						placeholder="Ej: MAT101"
					/>
				</a-form-item>

				<a-form-item label="Nivel Educativo">
					<a-select
						v-decorator="[
							'level',
							{ rules: [{ required: true, message: 'El nivel es requerido' }] }
						]"
						placeholder="Selecciona el nivel"
					>
						<a-select-option value="primaria">Primaria</a-select-option>
						<a-select-option value="secundaria">Secundaria</a-select-option>
						<a-select-option value="bachillerato">Bachillerato</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Descripción">
					<a-textarea
						v-decorator="['description']"
						placeholder="Descripción opcional de la asignatura"
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
					>
						<a-select-option value="active">Activa</a-select-option>
						<a-select-option value="inactive">Inactiva</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Créditos">
					<a-input-number
						v-decorator="['credits']"
						placeholder="Número de créditos"
						:min="0"
						:max="10"
						style="width: 100%"
					/>
				</a-form-item>
			</a-form>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
	name: 'Asignaturas',
	data() {
		return {
			loading: false,
			subjectsData: [],
			searchText: '',
			statusFilter: 'all',
			levelFilter: 'all',
			showCreateModal: false,
			saving: false,
			editingSubject: null,
			form: this.$form.createForm(this, { name: 'subject_form' }),
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} asignaturas`,
			},
			columns: [
				{ title: 'Nombre', dataIndex: 'name', key: 'name', scopedSlots: { customRender: 'name' } },
				{ title: 'Código', dataIndex: 'code', key: 'code', width: 120 },
				{ title: 'Nivel', dataIndex: 'level', key: 'level', width: 120, scopedSlots: { customRender: 'level' } },
				{ title: 'Créditos', dataIndex: 'credits', key: 'credits', width: 100 },
				{ title: 'Estado', dataIndex: 'status', key: 'status', width: 120, scopedSlots: { customRender: 'status' } },
				{ title: 'Acciones', key: 'actions', width: 120, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
		};
	},
	computed: {
		filteredSubjects() {
			let filtered = this.subjectsData;

			// Filtrar por texto de búsqueda
			if (this.searchText && this.searchText.trim() !== '') {
				const search = this.searchText.toLowerCase();
				filtered = filtered.filter(subject => 
					subject.name.toLowerCase().includes(search) ||
					subject.code.toLowerCase().includes(search) ||
					(subject.description && subject.description.toLowerCase().includes(search))
				);
			}

			// Filtrar por estado
			if (this.statusFilter !== 'all') {
				filtered = filtered.filter(subject => subject.status === this.statusFilter);
			}

			// Filtrar por nivel
			if (this.levelFilter !== 'all') {
				filtered = filtered.filter(subject => subject.level === this.levelFilter);
			}

			return filtered;
		},
	},
	mounted() {
		this.fetchSubjects();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchSubjects() {
			this.loading = true;
			axios.get('http://localhost:8000/api/subjects', { headers: this.apiHeaders() })
				.then((res) => {
					this.subjectsData = res.data || [];
					this.pagination.total = this.subjectsData.length;
				})
				.catch((err) => {
					console.error('Error al obtener asignaturas:', err.response?.data || err);
					this.$message.error('No se pudieron cargar las asignaturas');
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
			this.statusFilter = 'all';
			this.levelFilter = 'all';
			this.pagination.current = 1;
		},
		handleTableChange(pagination) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		getLevelColor(level) {
			const colors = {
				primaria: 'green',
				secundaria: 'blue',
				bachillerato: 'purple',
			};
			return colors[level] || 'default';
		},
		getLevelLabel(level) {
			const labels = {
				primaria: 'Primaria',
				secundaria: 'Secundaria',
				bachillerato: 'Bachillerato',
			};
			return labels[level] || level;
		},
		getStatusBadge(status) {
			const badges = {
				active: 'success',
				inactive: 'error',
			};
			return badges[status] || 'default';
		},
		getStatusLabel(status) {
			const labels = {
				active: 'Activa',
				inactive: 'Inactiva',
			};
			return labels[status] || status;
		},
		editSubject(record) {
			this.editingSubject = record;
			this.showCreateModal = true;
			this.form.setFieldsValue({
				name: record.name,
				code: record.code,
				level: record.level,
				description: record.description,
				status: record.status,
				credits: record.credits,
			});
		},
		closeModal() {
			this.showCreateModal = false;
			this.saving = false;
			this.editingSubject = null;
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				this.saving = true;
				const request = this.editingSubject
					? axios.put(`http://localhost:8000/api/subjects/${this.editingSubject.id}`, values, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/subjects', values, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchSubjects();
						this.$message.success(this.editingSubject ? 'Asignatura actualizada correctamente' : 'Asignatura creada correctamente');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar la asignatura';
						console.error('Error guardando asignatura:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		deleteSubject(record) {
			this.$confirm({
				title: 'Eliminar Asignatura',
				content: `¿Estás seguro de eliminar la asignatura "${record.name}"?`,
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.delete(`http://localhost:8000/api/subjects/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchSubjects();
							this.$message.success('Asignatura eliminada correctamente');
						})
						.catch((error) => {
							console.error('Error eliminando asignatura:', error.response?.data || error);
							this.$message.error('No se pudo eliminar la asignatura');
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

.subjects-card {
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

.subject-name {
	display: flex;
	flex-direction: column;
	gap: 4px;

	.name {
		font-weight: 600;
		color: #1f2937;
	}

	.code {
		font-size: 12px;
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
