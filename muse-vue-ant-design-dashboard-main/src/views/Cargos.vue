<!-- Administración de Cargos (CRUD) -->

<template>
	<div>
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
					<template #title>
						<a-row type="flex" align="middle" class="table-header-row">
							<a-col :span="24" :md="12">
								<div class="table-header-left">
									<h5 class="font-semibold m-0">Cargos</h5>
								</div>
							</a-col>
							<a-col :span="24" :md="12" class="table-header-right">
								<a-radio-group v-model="statusFilter" class="header-status-filter">
									<a-radio-button value="Todos">Todos</a-radio-button>
									<a-radio-button value="Activo">Activo</a-radio-button>
									<a-radio-button value="Inactivo">Inactivo</a-radio-button>
								</a-radio-group>
								<a-input-search
									v-model="searchText"
									:maxLength="200"
									allowClear
									placeholder="Buscar..."
									class="header-search"
								/>
								<a-button class="btn-add-outline" @click="openCreate">
									<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
										<path d="M12 5v14M5 12h14" fill="none" />
									</svg>
									Agregar cargo
								</a-button>
							</a-col>
						</a-row>
					</template>

					<a-table
						:columns="columns"
						:data-source="filteredPositions"
						:loading="loading"
						:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
						:locale="{ emptyText: 'No hay datos' }"
						:scroll="{ x: 850 }"
					>
						<template slot="name" slot-scope="value, row">
							<div class="position-name-wrap">
								<span :class="isDirectora(row) ? 'position-name position-name--directora' : 'position-name'">
									{{ value }}
								</span>
								<a-tag v-if="isDirectora(row)" class="position-tag-required">Obligatorio</a-tag>
							</div>
						</template>

						<template slot="status" slot-scope="status">
							<a-tag :class="String(status) === 'Activo' ? 'ant-tag-primary' : 'ant-tag-muted'">
								{{ status }}
							</a-tag>
						</template>

						<template slot="actions" slot-scope="_, row">
							<div class="table-actions">
								<!-- Botón de desactivar/activar - siempre visible excepto para directora -->
								<a-popconfirm
									v-if="!isDirectora(row)"
									:title="isActiveStatus(row) ? '¿Desactivar este cargo?' : '¿Activar este cargo?'"
									:ok-text="isActiveStatus(row) ? 'Desactivar' : 'Activar'"
									:ok-type="isActiveStatus(row) ? 'warning' : 'primary'"
									cancel-text="Cancelar"
									@confirm="toggleStatus(row)"
								>
									<a-button
										size="small"
										:type="isActiveStatus(row) ? 'warning' : 'primary'"
									>
										{{ isActiveStatus(row) ? 'Desactivar' : 'Activar' }}
									</a-button>
								</a-popconfirm>
								
								<!-- Tag para directora -->
								<a-tag v-if="isDirectora(row)" class="position-tag-required">Protegido</a-tag>
								
								<!-- Botones de editar y eliminar para cargos que no son del sistema -->
								<template v-if="!isDirectora(row) && !isSystemPosition(row)">
									<a-button size="small" type="primary" ghost @click="openEdit(row)">
										Editar
									</a-button>
									<a-popconfirm
										title="¿Eliminar este cargo?"
										ok-text="Eliminar"
										ok-type="danger"
										cancel-text="Cancelar"
										@confirm="deletePosition(row)"
									>
										<a-button size="small" type="danger" ghost>
											Eliminar
										</a-button>
									</a-popconfirm>
								</template>
								
								<!-- Tag para cargos del sistema -->
								<a-tag v-if="isSystemPosition(row)" class="position-tag-required">Del sistema</a-tag>
							</div>
						</template>
					</a-table>
				</a-card>

				<a-modal
					:title="editing ? 'Editar cargo' : 'Agregar cargo'"
					:visible="showModal"
					:forceRender="true"
					:footer="null"
					@cancel="closeModal"
				>
					<a-form :form="form" @submit="handleSubmit">
						<a-form-item label="Nombre">
							<a-input
								:maxLength="200"
								v-decorator="['name', { rules: [{ required: true, message: 'Ingresa el nombre del cargo.' }] }]"
								placeholder="Ej: Director"
							/>
						</a-form-item>

						<a-form-item label="Estado">
							<a-select
								v-decorator="['status', { rules: [{ required: true, message: 'Selecciona el estado.' }] }]"
								placeholder="Selecciona el estado"
							>
								<a-select-option value="Activo">Activo</a-select-option>
								<a-select-option value="Inactivo">Inactivo</a-select-option>
							</a-select>
						</a-form-item>

						<a-form-item>
							<a-button type="primary" html-type="submit" :loading="saving">
								Guardar
							</a-button>
						</a-form-item>
					</a-form>
				</a-modal>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default ({
	name: 'Cargos',
	data() {
		return {
			positions: [],
			searchText: '',
			statusFilter: 'Todos',
			loading: false,
			saving: false,
			showModal: false,
			editingPosition: null,
			form: null,
			columns: [
				{ title: 'Cargo', dataIndex: 'name', scopedSlots: { customRender: 'name' }, width: 300 },
				{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 140 },
				{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 200, align: 'right' },
			],
		};
	},
	created() {
		// Inicializar el form después de que data() exista (evita que data lo sobrescriba a null).
		this.ensureForm();
	},
	computed: {
		editing() {
			return !!(this.editingPosition && this.editingPosition.id);
		},
		filteredPositions() {
			const filter = String(this.statusFilter || 'Todos');
			const q = String(this.searchText || '').trim().toLowerCase();
			return (this.positions || []).filter((p) => {
				if (filter !== 'Todos' && String(p?.status || '') !== filter) return false;
				if (!q) return true;
				const parts = [
					p?.name,
					p?.status,
				].filter(Boolean).join(' ').toLowerCase();
				return parts.includes(q);
			});
		},
	},
	mounted() {
		this.fetchPositions();
	},
	methods: {
		isActiveStatus(row) {
			return String((row && row.status) ? row.status : '').trim() === 'Activo';
		},
		isDirectora(row) {
			const name = String(row?.name ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
			return name === 'directora';
		},
		isSystemPosition(row) {
			return !!row?.is_system;
		},
		isExistingPosition(row) {
			// Consideramos que un cargo ya existe si tiene un ID
			// Queremos que el botón de eliminar aparezca para cargos que no son del sistema
			return !!(row?.id);
		},
		ensureForm() {
			if (!this.form) {
				this.form = this.$form.createForm(this, { name: 'positions_form' });
			}
		},
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchPositions() {
			this.loading = true;
			axios.get('http://localhost:8000/api/positions', { headers: this.apiHeaders() })
				.then((res) => { this.positions = res.data || []; })
				.catch((err) => console.error('Error al obtener cargos:', err.response?.data || err))
				.finally(() => { this.loading = false; });
		},
		openCreate() {
			this.ensureForm();
			this.editingPosition = null;
			this.showModal = true;
			this.$nextTick(() => {
				// AntD Vue: en modales, a veces los campos no están "registrados" al primer tick.
				// forceRender ayuda, y este setTimeout asegura que ya renderizó.
				setTimeout(() => {
					if (!this.form || typeof this.form.resetFields !== 'function') return;
					this.form.resetFields();
					this.form.setFieldsValue({ name: '', status: 'Activo' });
				}, 0);
			});
		},
		openEdit(row) {
			if (this.isDirectora(row)) {
				if (this.$message) this.$message.info('El cargo "Directora" es obligatorio y no se puede modificar.');
				return;
			}
			this.ensureForm();
			this.editingPosition = row;
			this.showModal = true;
			this.$nextTick(() => {
				setTimeout(() => {
					if (!this.form || typeof this.form.setFieldsValue !== 'function') return;
					// reset primero para evitar valores viejos
					if (typeof this.form.resetFields === 'function') this.form.resetFields();
					this.form.setFieldsValue({
						name: row?.name || '',
						status: row?.status || 'Activo',
					});
				}, 0);
			});
		},
		closeModal() {
			this.showModal = false;
			this.editingPosition = null;
			if (this.form && typeof this.form.resetFields === 'function') this.form.resetFields();
		},
		handleSubmit(e) {
			e.preventDefault();
			this.ensureForm();
			if (!this.form || typeof this.form.validateFields !== 'function') return;
			this.form.validateFields((err, values) => {
				if (err) return;

				// Validación local: evitar nombres duplicados al editar/crear (trim + colapsar espacios, case-insensitive)
				const normalize = (v) => String(v ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
				const nameKey = normalize(values?.name);
				if (nameKey) {
					const exists = (this.positions || []).some((p) => {
						const sameId = this.editing && String(p?.id) === String(this.editingPosition?.id);
						if (sameId) return false;
						return normalize(p?.name) === nameKey;
					});
					if (exists) {
						if (this.form && typeof this.form.setFields === 'function') {
							this.form.setFields({
								name: { value: values?.name, errors: [new Error('Ya existe un cargo con ese nombre.')] },
							});
						}
						if (this.$message && typeof this.$message.error === 'function') {
							this.$message.error('Ya existe un cargo con ese nombre.');
						}
						return;
					}
				}

				this.saving = true;

				const url = this.editing
					? `http://localhost:8000/api/positions/${this.editingPosition.id}`
					: 'http://localhost:8000/api/positions';
				const method = this.editing ? 'put' : 'post';

				axios({
					method,
					url,
					data: values,
					headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' },
				})
					.then(() => {
						this.closeModal();
						this.fetchPositions();
					})
					.catch((error) => {
						const payload = error?.response?.data || {};
						const errors = payload?.errors || {};

						// Pinta errores en el form si aplica
						if (this.form && typeof this.form.setFields === 'function') {
							const fieldErrors = {};
							if (errors?.name?.[0]) fieldErrors.name = { value: values?.name, errors: [new Error(errors.name[0])] };
							if (errors?.status?.[0]) fieldErrors.status = { value: values?.status, errors: [new Error(errors.status[0])] };
							if (Object.keys(fieldErrors).length) this.form.setFields(fieldErrors);
						}

						const msg = errors?.name?.[0] || errors?.status?.[0] || payload?.message || 'No se pudo guardar el cargo.';
						if (this.$message && typeof this.$message.error === 'function') this.$message.error(msg);
						console.error('Error guardando cargo:', payload || error);
					})
					.finally(() => { this.saving = false; });
			});
		},
		deletePosition(row) {
			if (!row?.id) return;
			if (this.isDirectora(row)) {
				if (this.$message) this.$message.info('El cargo "Directora" es obligatorio y no se puede eliminar.');
				return;
			}
			axios.delete(`http://localhost:8000/api/positions/${row.id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchPositions())
				.catch((error) => console.error('Error eliminando cargo:', error.response?.data || error));
		},
		toggleStatus(row) {
			if (!row?.id) return;
			if (this.isDirectora(row)) {
				if (this.$message) this.$message.info('El cargo "Directora" es obligatorio y no se puede desactivar.');
				return;
			}
			const next = String(row?.status) === 'Activo' ? 'Inactivo' : 'Activo';
			axios.patch(`http://localhost:8000/api/positions/${row.id}/status`, { status: next }, {
				headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' },
			})
				.then(() => this.fetchPositions())
				.catch((error) => {
					const msg = error?.response?.data?.message || 'No se pudo cambiar el estado del cargo.';
					if (this.$message) this.$message.error(msg);
					console.error('Error cambiando estado del cargo:', error.response?.data || error);
				});
		},
	},
})
</script>

<style scoped>
.header-search {
	width: 240px;
	min-width: 180px;
}

.header-status-filter {
	margin-right: 10px;
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

.position-name-wrap {
	display: inline-flex;
	align-items: center;
	gap: 10px;
	min-width: 0;
}

.position-name {
	font-weight: 700;
	color: #111827;
}

/* Texto tipo logo: colorido y llamativo */
.position-name--directora {
	background: linear-gradient(90deg, #7c3aed, #06b6d4, #22c55e);
	-webkit-background-clip: text;
	background-clip: text;
	color: transparent;
	text-shadow: 0 10px 18px rgba(124, 58, 237, 0.14);
	letter-spacing: 0.2px;
}

.position-tag-required {
	border-radius: 999px;
	font-weight: 800;
	color: #111827;
	background: rgba(124, 58, 237, 0.10);
	border-color: rgba(124, 58, 237, 0.25);
}

/* Alineación de botones en modales de confirmación */
::v-deep .ant-popconfirm-buttons {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: flex-end;
	align-items: center;
}

::v-deep .ant-popconfirm-buttons .ant-btn {
	margin-left: 0 !important;
	flex: 0 0 auto;
}

/* En pantallas pequeñas, alinear verticalmente */
@media (max-width: 576px) {
	::v-deep .ant-popconfirm-buttons {
		flex-direction: column;
		align-items: stretch;
		width: 100%;
	}
	
	::v-deep .ant-popconfirm-buttons .ant-btn {
		width: 100%;
		margin-bottom: 8px;
	}
	
	::v-deep .ant-popconfirm-buttons .ant-btn:last-child {
		margin-bottom: 0;
	}
}
</style>

