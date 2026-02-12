<!-- Registro de Grados (CRUD) -->

<template>
	<div>
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
					<template #title>
						<a-row type="flex" align="middle" class="table-header-row">
							<a-col :span="24" :md="12">
								<div class="table-header-left">
									<h5 class="font-semibold m-0">Grados</h5>
								</div>
							</a-col>
							<a-col :span="24" :md="12" class="table-header-right">
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
									Agregar grado
								</a-button>
							</a-col>
						</a-row>
					</template>

					<a-table
						:columns="columns"
						:data-source="filteredGrades"
						:loading="loading"
						:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
						:locale="{ emptyText: 'No hay datos' }"
						:scroll="{ x: 750 }"
						:rowKey="record => record.id"
					>
						<template slot="status" slot-scope="status">
							<a-tag :class="String(status) === 'Activo' ? 'ant-tag-primary' : 'ant-tag-muted'">
								{{ status }}
							</a-tag>
						</template>

						<template slot="protection" slot-scope="_, row">
							<a-tag v-if="isProtectedGrade(row)" class="position-tag-required">
								🛡️ Protegido
							</a-tag>
							<a-tag v-else class="tag-editable">
								✅ Editable
							</a-tag>
						</template>

						<template slot="actions" slot-scope="_, row">
							<div class="table-actions">
								<a-button 
									size="small" 
									type="primary" 
									ghost
									@click="openEdit(row)"
									:disabled="!canEditGrade(row)"
								>
									Editar
								</a-button>

								<a-popconfirm
									v-if="String((row && row.status) ? row.status : '') === 'Activo'"
									title="¿Desactivar este grado? No aparecerá en las listas."
									ok-text="Sí, desactivar"
									cancel-text="No"
									@confirm="deactivateGrade(row)"
								>
									<a-button 
										size="small" 
										type="default"
										class="btn-warning-outline"
									>
										Desactivar
									</a-button>
								</a-popconfirm>

								<a-popconfirm
									v-else
									title="¿Activar este grado? Aparecerá en las listas."
									ok-text="Sí, activar"
									cancel-text="No"
									@confirm="activateGrade(row)"
								>
									<a-button 
										size="small" 
										type="primary" 
										ghost
									>
										Activar
									</a-button>
								</a-popconfirm>

								<a-popconfirm
									v-if="canDeleteGrade(row)"
									title="¿Seguro que deseas eliminar este grado?"
									ok-text="Sí"
									cancel-text="No"
									@confirm="deleteGrade(row)"
								>
									<a-button 
										size="small" 
										type="danger" 
										ghost
									>
										Eliminar
									</a-button>
								</a-popconfirm>
								<a-tag v-else-if="!canEditGrade(row)" class="position-tag-required">Protegido</a-tag>
								<a-tag v-else class="position-tag-required">En uso</a-tag>
							</div>
						</template>
					</a-table>
				</a-card>

				<a-modal
					:title="editing ? 'Editar grado' : 'Agregar grado'"
					:visible="showModal"
					:forceRender="true"
					:footer="null"
					@cancel="closeModal"
				>
					<a-form ref="form" @submit="handleSubmit">
						<a-form-item label="Nombre">
							<a-input
								:maxLength="200"
								v-decorator="['name', { rules: [{ required: true, message: 'Ingresa el nombre del grado.' }] }]"
								placeholder="Ej: 1ro Primaria"
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
	name: 'RegistroGrados',
	data() {
		return {
			grades: [],
			searchText: '',
			loading: false,
			saving: false,
			showModal: false,
			editingGrade: null,
			form: null,
			columns: [
				{ title: 'Grado', dataIndex: 'name', width: 220 },
				{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 100 },
				{ title: 'Protección', dataIndex: 'is_protected', scopedSlots: { customRender: 'protection' }, width: 100 },
				{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 220, align: 'right' },
			],
		};
	},
	beforeCreate() {
		// No inicializar aquí, se hará en mounted
	},
	computed: {
		editing() {
			return !!(this.editingGrade && this.editingGrade.id);
		},
		filteredGrades() {
			const normalize = (v) => String(v ?? '').trim().toLowerCase();
			const q = normalize(this.searchText);
			if (!q) return this.grades;

			return (this.grades || []).filter((g) => {
				const parts = [
					g?.name,
					g?.status,
				];
				return normalize(parts.filter(Boolean).join(' ')).includes(q);
			});
		},
	},
	mounted() {
		// Inicializar el formulario de Ant Design Vue
		this.$nextTick(() => {
			if (this.$refs.form) {
				this.form = this.$refs.form;
			}
		});
		this.fetchGrades();
	},
	methods: {
		normalizeGradeName(v) {
			return String(v ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
		},
		canEditGrade(row) {
			// Los grados existentes son protegidos (no se pueden editar)
			// Los nuevos grados que se agreguen sí se pueden editar
			if (!row || !row.id) return true; // Si es nuevo (no tiene id), se puede editar
			
			// Si es un grado existente, verificar si es "nuevo" (creado recientemente)
			if (row.created_at) {
				const createdDate = new Date(row.created_at);
				const now = new Date();
				const hoursDiff = (now - createdDate) / (1000 * 60 * 60);
				return hoursDiff < 1; // Solo editable si tiene menos de 1 hora
			}
			
			return false; // Grados antiguos no se pueden editar
		},
		canDeleteGrade(row) {
			// Solo los grados nuevos se pueden eliminar
			return this.canEditGrade(row);
		},
		isProtectedGrade(row) {
			// Grado protegido si no se puede editar
			return !this.canEditGrade(row);
		},
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchGrades() {
			this.loading = true;
			axios.get('http://localhost:8000/api/grades', { headers: this.apiHeaders() })
				.then((res) => {
					const list = (res.data || []);
					this.grades = list.sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || ''), 'es', { sensitivity: 'base' }));
				})
				.catch((err) => console.error('Error al obtener grados:', err.response?.data || err))
				.finally(() => { this.loading = false; });
		},
		openCreate() {
			this.editingGrade = null;
			this.showModal = true;
			this.$nextTick(() => {
				if (this.form) {
					this.form.resetFields();
					this.form.setFieldsValue({ status: 'Activo' });
				}
			});
		},
		openEdit(row) {
			this.editingGrade = row;
			this.showModal = true;
			this.$nextTick(() => {
				// En modales, a veces los campos no están registrados al primer tick.
				// Forzamos render y luego reseteamos para evitar valores "viejos".
				setTimeout(() => {
					if (!this.form) return;
					if (typeof this.form.resetFields === 'function') this.form.resetFields();
					if (typeof this.form.setFieldsValue === 'function') {
						this.form.setFieldsValue({
							name: row?.name || '',
							status: row?.status || 'Activo',
						});
					}
				}, 0);
			});
		},
		closeModal() {
			this.showModal = false;
			this.editingGrade = null;
			if (this.form && typeof this.form.resetFields === 'function') {
				this.form.resetFields();
			}
		},
		handleSubmit(e) {
			e.preventDefault();
			
			// Verificar que el formulario exista
			if (!this.form) {
				console.error('Formulario no disponible');
				return;
			}
			
			// Usar el formulario de Ant Design Vue
			this.form.validateFields((err, values) => {
				if (err) {
					console.log('Errores de validación:', err);
					return;
				}

				console.log('Valores del formulario:', values);

				// Validación local: evitar nombres duplicados
				const normalize = (v) => String(v ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
				const nameKey = normalize(values?.name);
				if (nameKey) {
					const exists = (this.grades || []).some((g) => {
						const sameId = this.editing && String(g?.id) === String(this.editingGrade?.id);
						if (sameId) return false;
						return normalize(g?.name) === nameKey;
					});
					if (exists) {
						if (this.$message) {
							this.$message.error('Ya existe un grado con ese nombre.');
						}
						return;
					}
				}

				this.saving = true;

				const url = this.editing
					? `http://localhost:8000/api/grades/${this.editingGrade.id}`
					: 'http://localhost:8000/api/grades';
				const method = this.editing ? 'put' : 'post';

				const payload = {
					name: values?.name,
					status: values?.status,
				};

				axios({
					method,
					url,
					data: payload,
					headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' },
				})
					.then(() => {
						this.closeModal();
						this.fetchGrades();
					})
					.catch((error) => {
						const data = error?.response?.data || {};
						const errors = data?.errors || {};

						// Pinta errores en el form si aplica
						if (this.form && typeof this.form.setFields === 'function') {
							const fieldErrors = {};
							if (errors?.name?.[0]) fieldErrors.name = { value: values?.name, errors: [new Error(errors.name[0])] };
							if (errors?.status?.[0]) fieldErrors.status = { value: values?.status, errors: [new Error(errors.status[0])] };
							if (Object.keys(fieldErrors).length) this.form.setFields(fieldErrors);
						}

						const msg = errors?.name?.[0] || errors?.status?.[0] || data?.message || 'No se pudo guardar el grado.';
						if (this.$message && typeof this.$message.error === 'function') this.$message.error(msg);
						console.error('Error guardando grado:', data || error);
					})
					.finally(() => { this.saving = false; });
			});
		},
		deactivateGrade(row) {
			if (!row?.id) return;
			axios.put(`http://localhost:8000/api/grades/${row.id}`, {
				name: row?.name,
				status: 'Inactivo',
			}, { headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
				.then(() => {
					this.$message?.success?.('Grado desactivado.');
					this.fetchGrades();
				})
				.catch((error) => {
					const msg = error?.response?.data?.message || 'No se pudo desactivar el grado.';
					console.error('Error desactivando grado:', error.response?.data || error);
					this.$message?.error?.(msg);
				});
		},
		activateGrade(row) {
			if (!row?.id) return;
			axios.put(`http://localhost:8000/api/grades/${row.id}`, {
				name: row?.name,
				status: 'Activo',
			}, { headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
				.then(() => {
					this.$message?.success?.('Grado activado.');
					this.fetchGrades();
				})
				.catch((error) => {
					const msg = error?.response?.data?.message || 'No se pudo activar el grado.';
					console.error('Error activando grado:', error.response?.data || error);
					this.$message?.error?.(msg);
				});
		},
		deleteGrade(row) {
			if (!row?.id) return;
			axios.delete(`http://localhost:8000/api/grades/${row.id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchGrades())
				.catch((error) => {
					const msg = error?.response?.data?.message;
					if (error?.response?.status === 409 && msg) {
						this.$message.error(msg);
						return;
					}
					console.error('Error eliminando grado:', error.response?.data || error);
					this.$message.error('No se pudo eliminar el grado.');
				});
		},
	},
})
</script>

<style scoped>
.table-header-left {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

.table-header-right {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

.header-search {
	width: 240px;
	min-width: 180px;
}

@media (max-width: 767px) {
	.table-header-right {
		justify-content: flex-start;
	}

	.header-search {
		width: 100%;
		min-width: 0;
	}
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

/* Botón "Desactivar" amarillo (outline) - Estilo tabla usuarios */
.btn-warning-outline.ant-btn {
	background: transparent;
	border: 1px solid #f59e0b;
	color: #b45309;
	box-shadow: none;
}

.btn-warning-outline.ant-btn:hover,
.btn-warning-outline.ant-btn:focus {
	background: rgba(245, 158, 11, 0.10);
	border-color: #d97706;
	color: #92400e;
}

.btn-warning-outline.ant-btn:active {
	background: rgba(245, 158, 11, 0.18);
	border-color: #b45309;
	color: #78350f;
}

/* Etiquetas de estado */
.position-tag-required {
	background: transparent;
	border: 1px solid #8c8c8c;
	color: #8c8c8c;
	font-weight: 600;
	font-size: 11px;
	padding: 2px 8px;
	border-radius: 10px;
}

.tag-editable {
	background: #52c41a;
	border-color: #52c41a;
	color: #fff;
	font-weight: 600;
	font-size: 11px;
	padding: 2px 8px;
	border-radius: 10px;
}

/* Mejoras visuales para la tabla */
.ant-table-tbody > tr:hover > td {
	background: #fafafa !important;
}

/* Estilos para botones deshabilitados */
.ant-btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

/* Responsive para móviles */
@media (max-width: 768px) {
	.table-actions {
		flex-direction: column;
		gap: 4px;
		align-items: stretch;
	}
	
	.table-actions .ant-btn {
		width: 100%;
	}
}
</style>

