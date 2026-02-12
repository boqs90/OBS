<template>
	<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{padding: 0}">
		<template #title>
			<!-- Fila 1: Título + filtros -->
			<a-row type="flex" align="middle" class="table-header-row">
				<a-col :span="24" :md="12">
					<div class="table-header-left">
						<h5 v-if="showTitle" class="font-semibold m-0">Listado de Maestros</h5>
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
					<a-button v-if="showAddButton" class="btn-add-outline" @click="$emit('openAddTeacherModal')">
						<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M12 5v14M5 12h14" fill="none" />
						</svg>
						Agregar Maestro
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
			:scroll="{ x: 1400 }"
		>
			<template slot="teacherName" slot-scope="teacherName">
				<div class="table-avatar-info">
					<a-avatar
						class="table-clickable-image"
						shape="square"
						:src="teacherName.avatar"
						:style="teacherName.avatar ? {} : { backgroundColor: '#d9d9d9', cursor: 'default' }"
						@click.stop="teacherName.avatar ? openImagePreview(teacherName.avatar) : null"
					>
						<a-icon v-if="!teacherName.avatar" type="camera" style="color: #8c8c8c; font-size: 16px;" />
					</a-avatar>
					<div class="avatar-info">
						<h6>{{ teacherName.name }}</h6>
						<p>{{ teacherName.email }}</p>
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
					<a-button size="small" type="primary" ghost @click="$emit('editTeacher', row)">
						Editar
					</a-button>
					<a-popconfirm
						title="¿Seguro que deseas eliminar este maestro?"
						ok-text="Sí"
						cancel-text="No"
						@confirm="$emit('deleteTeacher', row)"
					>
						<a-button size="small" type="danger" ghost>
							Eliminar
						</a-button>
					</a-popconfirm>
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
export default ({
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
		showAddButton: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			headerBtns: 'all',
			imagePreviewVisible: false,
			imagePreviewSrc: '',
		}
	},
	computed: {
		filteredData() {
			const normalize = (v) => String(v ?? '').trim().toLowerCase();
			const status = (row) => normalize(row?.status);

			if (this.headerBtns === 'online') {
				return this.data.filter((row) => ['activo', 'active', '1', 'true'].includes(status(row)));
			}

			if (this.headerBtns === 'offline') {
				return this.data.filter((row) => ['inactivo', 'inactive', '0', 'false'].includes(status(row)));
			}

			return this.data;
		},
	},
	methods: {
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
})
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
	margin-right: 16px;
}

.table-clickable-image {
	cursor: zoom-in;
}

.table-clickable-image[style*="cursor: default"] {
	cursor: default !important;
}
</style>

