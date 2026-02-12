<template>

	<!-- Students Table Card -->
	<a-card
		:bordered="false"
		:class="['header-solid', 'h-full', headTight ? 'card-head-tight' : '']"
		:bodyStyle="{ padding: 0 }"
	>
		<template #title>
			<!-- Fila 1: Título + acciones -->
			<a-row type="flex" align="middle" class="table-header-row">
				<a-col :span="24" :md="12">
					<div class="table-header-left">
						<h5 v-if="showTitle" class="font-semibold m-0">{{ titleText }}</h5>
						<slot name="actions"></slot>
						<a-button v-if="showAddButton" class="btn-add-outline" @click="$emit('openAddStudentModal')">
							<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
								<path d="M12 5v14M5 12h14" fill="none" />
							</svg>
							Agregar Estudiante
						</a-button>
					</div>

					<!-- Buscar debajo del título (lado izquierdo) -->
					<div
						v-if="controlsLayout !== 'splitRow' && showSearch && searchPosition === 'leftBelowTitle'"
						class="table-header-search-left"
					>
						<a-input-search
							class="table-search table-search--left"
							v-model="searchText"
							:maxLength="200"
							allowClear
							:placeholder="searchPlaceholder"
						/>
					</div>
				</a-col>
				<a-col
					:span="24"
					:md="12"
					:class="[
						'table-header-right',
						(showSearch && searchPosition === 'leftBelowTitle') ? 'table-header-right--top' : ''
					]"
				>
					<slot name="headerActions"></slot>
					<a-radio-group v-if="showStatusFilters" v-model="authorsHeaderBtns" size="small" buttonStyle="solid">
						<a-radio-button value="all">Todas</a-radio-button>
						<a-radio-button value="online">Activo</a-radio-button>
						<a-radio-button value="offline">Inactivo</a-radio-button>
					</a-radio-group>
					<div v-if="controlsLayout !== 'splitRow'" class="table-header-search-filters">
						<a-input-search
							v-if="showSearch && searchPosition !== 'leftBelowTitle'"
							class="table-search"
							v-model="searchText"
							:maxLength="200"
							allowClear
							:placeholder="searchPlaceholder"
						/>
						<slot name="filters"></slot>
					</div>
				</a-col>
			</a-row>

			<!-- Fila 2 (opcional): Buscar a la izquierda + filtros a la derecha -->
			<a-row
				v-if="controlsLayout === 'splitRow'"
				type="flex"
				align="middle"
				class="table-controls-row"
			>
				<a-col :span="24" :md="12" class="table-controls-left">
					<a-input-search
						v-if="showSearch"
						class="table-search table-search--left"
						v-model="searchText"
						:maxLength="200"
						allowClear
						:placeholder="searchPlaceholder"
					/>
				</a-col>
				<a-col :span="24" :md="12" class="table-controls-right">
					<slot name="filters"></slot>
				</a-col>
			</a-row>
		</template>
		<a-table
			:rowKey="getRowKey"
			:columns="columns"
			:data-source="filteredData"
			:loading="loading"
			:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
			:locale="{ emptyText: 'No hay datos' }"
			:scroll="{ x: 940 }"
			:expandedRowKeys="enableExpand ? expandedRowKeys : []"
			@expand="onExpand"
		>

			<template slot="studentName" slot-scope="studentName, record">
				<div
					class="table-avatar-info"
					:class="enableExpand ? 'table-firstcol--expandable' : ''"
					:role="enableExpand ? 'button' : null"
					:tabindex="enableExpand ? 0 : null"
					@click="enableExpand ? toggleExpand(record) : null"
					@keydown.enter.prevent="enableExpand ? toggleExpand(record) : null"
					@keydown.space.prevent="enableExpand ? toggleExpand(record) : null"
				>
					<a-avatar
						class="table-clickable-image"
						shape="square"
						:src="studentName.avatar"
						:style="studentName.avatar ? {} : { backgroundColor: '#d9d9d9', cursor: 'default' }"
						@click.stop="studentName.avatar ? openImagePreview(studentName.avatar) : null"
					>
						<a-icon v-if="!studentName.avatar" type="camera" style="color: #8c8c8c; font-size: 16px;" />
					</a-avatar>
					<div class="avatar-info">
						<h6 class="table-firstcol__title">
							<span class="table-firstcol__caret" aria-hidden="true" v-if="enableExpand">
								{{ isExpanded(record) ? '▾' : '▸' }}
							</span>
							<span>{{ studentName.name }}</span>
						</h6>
						<p>{{ studentName.email }}</p>
					</div>
				</div>
			</template>

			<template slot="gradeCourse" slot-scope="gradeCourse">
				<div class="author-info">
					<h6 class="m-0">{{ gradeCourse.grade }}</h6>
					<p class="m-0 font-regular text-muted">{{ gradeCourse.course }}</p>
				</div>
			</template>

			<template slot="enrollmentStatus" slot-scope="enrollmentStatus">
				<a-tag class="tag-status" :class="enrollmentStatus === 'Activo' ? 'ant-tag-primary' : 'ant-tag-muted'">
					{{ displayEnrollmentStatus(enrollmentStatus) }}
				</a-tag>
			</template>

			<template v-if="enableExpand" slot="expandedRowRender" slot-scope="record">
				<slot name="expanded" :record="record">
					<div class="table-expanded">
						<div class="table-expanded__title">Detalle</div>
						<div class="table-expanded__grid">
							<div class="table-expanded__item">
								<div class="table-expanded__label">Grado/Curso</div>
								<div class="table-expanded__value">
									{{ (record && record._raw && record._raw.gradeCourse) ? record._raw.gradeCourse : ((record && record.gradeCourse && record.gradeCourse.grade) ? record.gradeCourse.grade : '-') }}
								</div>
							</div>
							<div class="table-expanded__item">
								<div class="table-expanded__label">Año</div>
								<div class="table-expanded__value">
									{{ (record && record._raw && record._raw.enrollmentYear != null) ? record._raw.enrollmentYear : '-' }}
								</div>
							</div>
							<div class="table-expanded__item">
								<div class="table-expanded__label">Fecha de matrícula</div>
								<div class="table-expanded__value">
									{{ formatDate((record && record._raw) ? (record._raw.enrolled_at || record._raw.created_at) : null) }}
								</div>
							</div>
							<div class="table-expanded__item">
								<div class="table-expanded__label">Estado</div>
								<div class="table-expanded__value">
									<a-tag :color="enrollmentStatusColor((record && record._raw && record._raw.enrollmentStatus) ? record._raw.enrollmentStatus : ((record && record.enrollmentStatus) ? record.enrollmentStatus : ''))">
										{{ displayEnrollmentStatus((record && record._raw && record._raw.enrollmentStatus) ? record._raw.enrollmentStatus : ((record && record.enrollmentStatus) ? record.enrollmentStatus : '-')) }}
									</a-tag>
								</div>
							</div>
						</div>
					</div>
				</slot>
			</template>

			<template slot="actions" slot-scope="_, row">
				<div class="table-actions">
					<slot name="rowActions" :row="row">
						<a-button size="small" type="primary" ghost @click="$emit('editStudent', row)">
							Editar
						</a-button>
						<template v-if="isStudentEnrolled(row)">
							<a-button size="small" type="danger" ghost disabled>
								Eliminar
							</a-button>
							<a-tooltip title="El estudiante tiene registros de matrícula activos. Elimine primero los registros de matrícula.">
								<a-icon type="info-circle" style="color: #f5222d; margin-left: 8px;" />
							</a-tooltip>
						</template>
						<template v-else>
							<a-popconfirm
								title="¿Seguro que deseas eliminar este estudiante?"
								ok-text="Sí"
								cancel-text="No"
								@confirm="$emit('deleteStudent', row)"
							>
								<a-button size="small" type="danger" ghost>
									Eliminar
								</a-button>
							</a-popconfirm>
						</template>
						<a-button 
							size="small" 
							:type="isStudentActive(row) ? 'default' : 'primary'" 
							ghost 
							@click="$emit('toggleStudentStatus', row)"
						>
							{{ isStudentActive(row) ? 'Desactivar' : 'Activar' }}
						</a-button>
					</slot>
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
	<!-- / Students Table Card -->

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
			controlsLayout: {
				type: String,
				default: 'inline', // 'inline' | 'splitRow'
			},
			showSearch: {
				type: Boolean,
				default: true,
			},
			searchPosition: {
				type: String,
				default: 'right',
			},
			searchPlaceholder: {
				type: String,
				default: 'Buscar...',
			},
			enableExpand: {
				type: Boolean,
				default: true,
			},
			showAddButton: {
				type: Boolean,
				default: true,
			},
			showTitle: {
				type: Boolean,
				default: true,
			},
			titleText: {
				type: String,
				default: 'Listado de Estudiantes',
			},
			headTight: {
				type: Boolean,
				default: false,
			},
			showStatusFilters: {
				type: Boolean,
				default: true,
			},
		},
		data() {
			return {
				// Active button for the "Authors" table's card header radio button group.
				authorsHeaderBtns: 'all',
				searchText: '',
				expandedRowKeys: [],
				imagePreviewVisible: false,
				imagePreviewSrc: '',
			}
		},
		computed: {
			filteredData() {
				const normalize = (v) => String(v ?? '').trim().toLowerCase();
				const status = (row) => normalize(row?.enrollmentStatus);

				let rows = this.data;
				if (this.showStatusFilters) {
					if (this.authorsHeaderBtns === 'online') {
						rows = rows.filter((row) => ['activo', 'active', '1', 'true'].includes(status(row)));
					}

					if (this.authorsHeaderBtns === 'offline') {
						rows = rows.filter((row) => ['inactivo', 'inactive', '0', 'false'].includes(status(row)));
					}
				}

				const q = normalize(this.searchText);
				if (!q) return rows;

				const toHaystack = (row) => {
					const parts = [
						row?.studentName?.name,
						row?.studentName?.email,
						row?.email,
						row?.fullName,
						row?.name,
						row?.gradeCourse?.grade,
						row?.gradeCourse?.course,
						row?.gradeCourse,
						row?.enrollmentStatus,
						row?.birthDate,
					];
					return normalize(parts.filter(Boolean).join(' '));
				};

				return rows.filter((row) => toHaystack(row).includes(q));
			},
		},
		methods: {
			displayEnrollmentStatus(value) {
				const v = String(value == null ? '' : value).trim();
				if (!v) return '-';
				if (v === 'Matriculado') return 'Matriculado';
				if (v === 'Sin matrícula') return 'Sin matrícula';
				if (v.toLowerCase() === 'pendiente') return 'Pendiente de pago';
				return v;
			},
			enrollmentStatusColor(value) {
				const v = String(value == null ? '' : value).trim().toLowerCase();
				if (v === 'matriculado') return 'green';
				if (v === 'sin matrícula') return 'default';
				if (v === 'activo') return 'green';
				if (v === 'inactivo') return 'default';
				if (v === 'pendiente') return 'gold';
				if (v === 'anulado') return 'red';
				return 'blue';
			},
			formatDate(value) {
				if (!value) return '-';
				const d = new Date(value);
				if (Number.isNaN(d.getTime())) {
					// Si viene "YYYY-MM-DD HH:mm:ss" o ISO, cortar hora si existe
					const raw = String(value);
					const onlyDate = raw.split('T')[0].split(' ')[0];
					if (/^\d{4}-\d{2}-\d{2}$/.test(onlyDate)) return onlyDate.replaceAll('-', '/');
					return onlyDate || raw;
				}
				const yyyy = d.getFullYear();
				const mm = String(d.getMonth() + 1).padStart(2, '0');
				const dd = String(d.getDate()).padStart(2, '0');
				return `${yyyy}/${mm}/${dd}`;
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
			getRowKey(record) {
				return record?.key ?? record?.id ?? record?._raw?.id;
			},
			isExpanded(record) {
				const k = this.getRowKey(record);
				return this.expandedRowKeys.includes(k);
			},
			toggleExpand(record) {
				const k = this.getRowKey(record);
				if (k == null) return;
				if (this.expandedRowKeys.includes(k)) {
					this.expandedRowKeys = this.expandedRowKeys.filter((x) => x !== k);
					return;
				}
				this.expandedRowKeys = [...this.expandedRowKeys, k];
			},
			onExpand(expanded, record) {
				if (!this.enableExpand) return;
				const k = this.getRowKey(record);
				if (k == null) return;
				if (expanded) {
					if (!this.expandedRowKeys.includes(k)) this.expandedRowKeys = [...this.expandedRowKeys, k];
				} else {
					this.expandedRowKeys = this.expandedRowKeys.filter((x) => x !== k);
				}
			},
			isStudentEnrolled(row) {
				// Verificar si el estudiante tiene matrícula activa usando la misma lógica que RegistroAlumnos
				const student = row?._raw || row;
				
				// Opción 1: Si tiene campo directo de matrícula
				if (student.enrollmentStatus) {
					const status = String(student.enrollmentStatus).trim().toLowerCase();
					if (['activo', 'active', '1', 'true'].includes(status)) {
						return true;
					}
				}
				
				// Opción 2: Si tiene array de matrículas
				if (student.enrollments && Array.isArray(student.enrollments)) {
					const activeEnrollments = student.enrollments.filter(enrollment => 
						enrollment.status === 'Activo' || enrollment.status === 'Active'
					);
					if (activeEnrollments.length > 0) {
						return true;
					}
				}
				
				// Opción 3: Si tiene campo booleano
				if (student.hasActiveEnrollment === true || student.is_enrolled === true) {
					return true;
				}
				
				// Opción 4: Verificar si el estado mostrado es "Matriculado"
				if (row.enrollmentStatus === 'Matriculado') {
					return true;
				}
				
				return false;
			},
			isStudentActive(row) {
				// Verificar si el estudiante está activo (no desactivado/inactivo)
				const student = row?._raw || row;
				
				// Opción 1: Campo directo de status
				if (student.status) {
					const status = String(student.status).trim().toLowerCase();
					return ['activo', 'active', '1', 'true', 'enabled'].includes(status);
				}
				
				// Opción 2: Campo is_active o active
				if (student.is_active === true || student.active === true || student.enabled === true) {
					return true;
				}
				
				// Opción 3: Si no hay campo de estado, asumir que está activo
				// (para compatibilidad con datos existentes)
				return true;
			},
		},
	})

</script>

<style scoped>
.card-head-tight :deep(.ant-card-head) {
	padding-top: 10px;
	padding-bottom: 10px;
}

.card-head-tight :deep(.ant-card-head-title) {
	padding: 0;
}

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

.table-header-right--top {
	align-self: flex-start;
}

.table-header-search-filters {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
	justify-content: flex-end;
	min-width: 0;
}

.table-header-search-left {
	margin-top: 10px;
	display: flex;
	justify-content: flex-start;
}

.table-controls-row {
	margin-top: 10px;
}

.table-controls-left {
	display: flex;
	justify-content: flex-start;
}

.table-controls-right {
	display: flex;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

.table-search {
	width: 220px;
	flex: 1 1 220px;
	min-width: 160px;
}

.table-search--left {
	width: 100%;
	max-width: 360px;
	min-width: 220px;
}

@media (max-width: 767px) {
	.table-header-right,
	.table-controls-right {
		justify-content: flex-start;
	}

	.table-search {
		width: 100%;
		min-width: 0;
	}

	.table-search--left {
		max-width: 100%;
		min-width: 0;
	}
}

.table-firstcol--expandable {
	cursor: pointer;
	user-select: none;
}

.table-clickable-image {
	cursor: zoom-in;
}

.table-clickable-image[style*="cursor: default"] {
	cursor: default !important;
}

.table-firstcol__title {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.table-firstcol__caret {
	display: inline-block;
	width: 14px;
	text-align: center;
	color: rgba(17, 24, 39, 0.55);
}

.table-expanded {
	padding: 10px 12px;
	background: rgba(17, 24, 39, 0.02);
	border: 1px solid rgba(17, 24, 39, 0.06);
	border-radius: 12px;
}

.table-expanded__title {
	font-weight: 800;
	color: #111827;
	margin-bottom: 8px;
}

.table-expanded__grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 10px 14px;
}

.table-expanded__item {
	text-align: center;
}

.table-expanded__label {
	font-size: 12px;
	color: rgba(17, 24, 39, 0.6);
	text-align: center;
}

.table-expanded__value {
	font-size: 13px;
	color: #111827;
	word-break: break-word;
	text-align: center;
}

.table-actions {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-end;
  white-space: nowrap;
  margin-right: 16px;
}
</style>