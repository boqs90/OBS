<template>
	<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
		<template #title>
			<!-- Fila 1: Título + filtros -->
			<a-row type="flex" align="middle" class="table-header-row">
				<a-col :span="24" :md="12">
					<div class="table-header-left">
						<h5 v-if="showTitle" class="font-semibold m-0">Listado de Empleados</h5>
					</div>
				</a-col>
				<a-col :span="24" :md="12" class="table-header-right--top">
					<a-radio-group v-model="headerBtns" size="small" buttonStyle="solid">
						<a-radio-button value="all">Todos</a-radio-button>
						<a-radio-button value="online">Activos</a-radio-button>
						<a-radio-button value="offline">Inactivos</a-radio-button>
					</a-radio-group>
				</a-col>
			</a-row>

			<!-- Fila 2: Buscador + botón agregar -->
			<a-row type="flex" align="middle" class="table-controls-row">
				<a-col :span="24" :md="12" class="table-controls-left">
					<a-input-search
						class="table-search table-search--left"
						v-model="searchText"
						:maxLength="200"
						allowClear
						placeholder="Buscar..."
					/>
				</a-col>
				<a-col :span="24" :md="12" class="table-controls-right">
					<a-button class="btn-add-outline" @click="$emit('openAddEmployeeModal')">
						<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M12 5v14M5 12h14" fill="none" />
						</svg>
						Registrar Empleado
					</a-button>
				</a-col>
			</a-row>
		</template>

		<a-table
			:columns="columns"
			:data-source="filteredData"
			:loading="loading"
			:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
			:locale="{ emptyText: 'No hay datos' }"
			:scroll="{ x: 1200 }"
			:expandedRowKeys="expandedRowKeys"
			@expand="onExpand"
		>
			<template slot="employeeName" slot-scope="employeeName">
				<div class="table-avatar-info">
					<a-avatar
						class="table-clickable-image"
						shape="square"
						:src="employeeName.avatar"
						:style="employeeName.avatar ? {} : { backgroundColor: '#d9d9d9', cursor: 'default' }"
						@click.stop="employeeName.avatar ? openImagePreview(employeeName.avatar) : null"
					>
						<a-icon v-if="!employeeName.avatar" type="camera" style="color: #8c8c8c; font-size: 16px;" />
					</a-avatar>
					<div class="avatar-info">
						<h6>{{ employeeName.name }}</h6>
						<p>{{ employeeName.email }}</p>
					</div>
				</div>
			</template>

			<template slot="status" slot-scope="status">
				<a-tag class="tag-status" :class="String(status) === 'Activo' ? 'ant-tag-primary' : 'ant-tag-muted'">
					{{ status }}
				</a-tag>
			</template>

			<template slot="actions" slot-scope="_, row">
				<div class="table-actions">
					<a-tag v-if="isProtectedEmployee(row)" class="position-tag-required">Protegido</a-tag>
					<template v-else>
						<a-button size="small" type="primary" ghost @click="$emit('editEmployee', row)">
							Editar
						</a-button>
						<a-popconfirm
							title="¿Seguro que deseas eliminar este empleado?"
							ok-text="Sí"
							cancel-text="No"
							@confirm="$emit('deleteEmployee', row)"
						>
							<a-button size="small" type="danger" ghost>
								Eliminar
							</a-button>
						</a-popconfirm>
					</template>
				</div>
			</template>

			<template slot="expandedRowRender" slot-scope="record">
				<div class="table-expanded">
					<div class="table-expanded__title">Información Adicional</div>
					<div class="table-expanded__grid">
						<div class="table-expanded__item">
							<div class="table-expanded__label">Fecha de Ingreso</div>
							<div class="table-expanded__value">
								{{ record.entryDate || 'No especificada' }}
							</div>
						</div>
						<div class="table-expanded__item">
							<div class="table-expanded__label">Fecha de Egreso</div>
							<div class="table-expanded__value">
								{{ record.exitDate || 'Activo' }}
							</div>
						</div>
						<div class="table-expanded__item">
							<div class="table-expanded__label">Salario</div>
							<div class="table-expanded__value">
								<span class="salary-value">
									{{ formatSalary((record._raw && record._raw.salary) || 0) }}
								</span>
							</div>
						</div>
						<div class="table-expanded__item">
							<div class="table-expanded__label">Identidad</div>
							<div class="table-expanded__value">
								{{ record.identityNumber || 'No especificada' }}
							</div>
						</div>
					</div>
				</div>
			</template>
		</a-table>

		<!-- Preview imagen (al hacer click) -->
		<a-modal
			:visible="imagePreviewVisible"
			:footer="null"
			centered
			:destroyOnClose="true"
			@cancel="closeImagePreview"
		>
			<img
				v-if="imagePreviewSrc"
				:src="imagePreviewSrc"
				alt="Vista previa"
				style="width: 100%; max-height: 70vh; object-fit: contain;"
			/>
		</a-modal>
	</a-card>
</template>

<script>
export default {
	props: {
		data: {
			type: Array,
			default: () => [],
		},
		loading: {
			type: Boolean,
			default: false,
		},
		columns: {
			type: Array,
			default: () => [],
		},
		showTitle: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			headerBtns: 'all',
			searchText: '',
			imagePreviewVisible: false,
			imagePreviewSrc: '',
			expandedRowKeys: [],
		}
	},
	computed: {
		filteredData() {
			const normalize = (v) => String(v ?? '').trim().toLowerCase();
			const status = (row) => normalize(row?.status);

			let rows = this.data;
			if (this.headerBtns === 'online') {
				rows = rows.filter((row) => ['activo', 'active', '1', 'true'].includes(status(row)));
			}
			if (this.headerBtns === 'offline') {
				rows = rows.filter((row) => ['inactivo', 'inactive', '0', 'false'].includes(status(row)));
			}

			const q = normalize(this.searchText);
			if (!q) return rows;

			const toHaystack = (row) => {
				const parts = [
					row?.employeeName?.name,
					row?.employeeName?.email,
					row?.email,
					row?.identityNumber,
					row?.positionName,
					row?.phone,
					row?.status,
				];
				return normalize(parts.filter(Boolean).join(' '));
			};
			return rows.filter((row) => toHaystack(row).includes(q));
		},
	},
	methods: {
		isProtectedEmployee(row) {
			const name = String(row?.employeeName?.name || '')
				.trim()
				.replace(/\s+/g, ' ')
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, ''); // Elimina acentos
			return name === 'isabel martinez' || name === 'gabriela nunez';
		},
		onExpand(expanded, record) {
			if (expanded) {
				this.expandedRowKeys = [...this.expandedRowKeys, record.key];
			} else {
				this.expandedRowKeys = this.expandedRowKeys.filter(key => key !== record.key);
			}
		},
		formatSalary(salary) {
			const num = Number(salary || 0);
			return new Intl.NumberFormat('es-HN', {
				style: 'currency',
				currency: 'HNL'
			}).format(num);
		},
		openImagePreview(src) {
			if (!src) return;
			this.imagePreviewSrc = src;
			this.imagePreviewVisible = true;
		},
		closeImagePreview() {
			this.imagePreviewVisible = false;
			this.imagePreviewSrc = '';
		},
	},
}
</script>

<style scoped>
.table-header-left {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

.table-header-right--top {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

.table-controls-row {
	margin-top: 16px;
}

.table-controls-left {
	display: flex;
	align-items: center;
}

.table-controls-right {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

@media (max-width: 767px) {
	.table-header-right--top {
		justify-content: flex-start;
	}
	.table-controls-right {
		justify-content: flex-start;
	}
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 0 !important;
	padding-right: 0 !important;
}

.table-clickable-image {
	cursor: zoom-in;
}

.table-clickable-image[style*="cursor: default"] {
	cursor: default !important;
}
</style>

