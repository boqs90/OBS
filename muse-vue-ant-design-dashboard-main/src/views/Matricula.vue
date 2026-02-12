<!-- 
	This is the tables page, it uses the dashboard layout in: 
	"./layouts/Dashboard.vue" .
 -->

<template>
	<div>

		<!-- Authors Table -->
		<a-row :gutter="24" type="flex">

			<!-- Authors Table Column -->
			<a-col :span="24" class="mb-24">

				<!-- Authors Table Card -->
				<StudentsTable
					:data="filteredTableData"
					:loading="loadingStudents"
					:columns="table1Columns"
					:showAddButton="false"
					:showStatusFilters="false"
					:showSearch="false"
					controlsLayout="splitRow"
					titleText="Listado de Matriculados"
					:headTight="true"
				>
					<template #rowActions="{ row }">
						<a-button size="small" @click="openEditEnrollment(row)">
							Editar
						</a-button>
						<a-popconfirm
							title="¿Anular esta matrícula?"
							overlayClassName="enrollment-cancel-popconfirm"
							ok-text="Anular"
							ok-type="danger"
							cancel-text="Cancelar"
							@confirm="cancelEnrollment(row)"
						>
							<a-button size="small" class="btn-warning">
								Anular
							</a-button>
						</a-popconfirm>
					</template>
					<template #filters>
						<div class="matricula-filters">
							<a-input-search
								v-model="searchText"
								:maxLength="200"
								allowClear
								placeholder="Buscar..."
								class="matricula-search"
							/>
							<a-date-picker
								class="matricula-year"
								placeholder="Año"
								format="YYYY"
								:mode="yearPickerMode"
								:open="yearPickerOpen"
								:value="selectedYearMoment"
								allowClear
								@openChange="onYearOpenChange"
								@panelChange="onYearPanelChange"
								@change="onYearCleared"
							/>

							<a-select
								v-model="selectedEnrollmentStatus"
								class="matricula-status"
								placeholder="Estado"
								allowClear
								@change="onStatusCleared"
							>
								<a-select-option value="all">Todos los estados</a-select-option>
								<a-select-option value="Activo">Activo</a-select-option>
								<a-select-option value="Inactivo">Inactivo</a-select-option>
								<a-select-option value="Pendiente">Pendiente de pago</a-select-option>
								<a-select-option value="Anulado">Anulado</a-select-option>
							</a-select>

							<a-select
								v-model="selectedGrade"
								class="matricula-grade"
								placeholder="Grado"
								allowClear
								@change="onGradeCleared"
							>
								<a-select-option value="all">Todos los grados</a-select-option>
								<a-select-option v-for="g in gradesOptions" :key="g" :value="g">
									{{ g }}
								</a-select-option>
							</a-select>

							<a-button class="btn-add-outline matricula-btn" @click="openEnrollmentModal">
								<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
									<path d="M12 5v14M5 12h14" fill="none" />
								</svg>
								Registro de matrícula
							</a-button>
						</div>
					</template>
				</StudentsTable>
				<!-- / Authors Table Card -->

				<a-modal
					:title="editingEnrollment ? 'Editar matrícula' : 'Registro de matrícula'"
					:visible="showEnrollmentModal"
					:footer="null"
					@cancel="closeEnrollmentModal"
				>
					<a-form :form="enrollmentForm" @submit="handleSubmitEnrollment">
						<a-form-item label="Estudiante">
							<a-select
								show-search
								placeholder="Selecciona un estudiante"
								option-filter-prop="children"
								v-decorator="['studentId', { rules: [{ required: true, message: 'Selecciona un estudiante.' }] }]"
								@change="handleStudentChange"
								:disabled="!!editingEnrollment"
							>
								<a-select-option
									v-for="s in studentsOptions"
									:key="s.id"
									:value="s.id"
								>
									{{ s.label }}
								</a-select-option>
							</a-select>
						</a-form-item>

						<a-form-item label="Información del estudiante">
							<div style="line-height: 1.4">
								<div><strong>Nombre:</strong> {{ selectedStudentInfo.fullName || '-' }}</div>
								<div><strong>Fecha de matrícula:</strong> {{ selectedStudentInfo.enrollmentDate || '-' }}</div>
								<div><strong>Estado actual:</strong> {{ displayEnrollmentStatus(selectedStudentInfo.enrollmentStatus) }}</div>
							</div>
						</a-form-item>

						<a-form-item label="Grado">
							<a-select
								show-search
								placeholder="Selecciona un grado"
								option-filter-prop="children"
								v-decorator="['gradeCourse', { rules: [{ required: true, message: 'Selecciona el grado.' }] }]"
							>
								<a-select-option v-for="g in gradesOptions" :key="g" :value="g">
									{{ g }}
								</a-select-option>
							</a-select>
						</a-form-item>

						<a-form-item label="Estado de matrícula">
							<a-select
								placeholder="Selecciona el estado"
								v-decorator="['enrollmentStatus', { rules: [{ required: true, message: 'Selecciona el estado.' }] }]"
							>
								<a-select-option value="Activo">Activo</a-select-option>
								<a-select-option value="Inactivo">Inactivo</a-select-option>
								<a-select-option value="Pendiente">Pendiente de pago</a-select-option>
								<a-select-option value="Anulado">Anulado</a-select-option>
							</a-select>
						</a-form-item>

						<a-form-item label="Año">
							<a-date-picker
								style="width: 100%"
								placeholder="Año"
								format="YYYY"
								:mode="enrollmentYearPickerMode"
								:open="enrollmentYearPickerOpen"
								allowClear
								v-decorator="['enrollmentYear', { rules: [{ required: true, message: 'Selecciona el año.' }] }]"
								@openChange="onEnrollmentYearOpenChange"
								@panelChange="onEnrollmentYearPanelChange"
								@change="onEnrollmentYearCleared"
							/>
						</a-form-item>

						<a-form-item>
							<a-button type="primary" html-type="submit" :loading="savingEnrollment">
								Guardar matrícula
							</a-button>
						</a-form-item>
					</a-form>
				</a-modal>

			</a-col>
			<!-- / Authors Table Column -->

		</a-row>
		<!-- / Authors Table -->

	</div>
</template>

<script>

	// "Authors" table component.
	import StudentsTable from '../components/Students/StudentsTable' ;
	import axios from 'axios';
	import { getToken } from '@/utils/auth';
	import moment from 'moment';
	
	// "Authors" table list of columns and their properties.
	const table1Columns = [
		{
			title: 'Nombre Completo',
			dataIndex: 'studentName',
			scopedSlots: { customRender: 'studentName' },
			width: 260,
		},
		{
			title: 'Grado/Curso',
			dataIndex: 'gradeCourse',
			scopedSlots: { customRender: 'gradeCourse' },
			width: 180,
		},
		{
			title: 'Fecha de matrícula',
			dataIndex: 'enrollmentDate',
			class: 'text-muted',
			width: 170,
		},
		{
			title: 'Estado de Matrícula',
			dataIndex: 'enrollmentStatus',
			scopedSlots: { customRender: 'enrollmentStatus' },
			width: 170,
		},
		{
			title: 'Acciones',
			scopedSlots: { customRender: 'actions' },
			width: 220,
			align: 'right',
		},
	];
	
	export default ({
		components: {
			StudentsTable,
		},
		data() {
			return {
				// Preseleccionar el año actual al cargar
				searchText: '',
				selectedYear: String(new Date().getFullYear()),
				selectedGrade: 'all',
				selectedEnrollmentStatus: 'all',
				yearPickerOpen: false,
				yearPickerMode: 'year',
				showEnrollmentModal: false,
				savingEnrollment: false,
				selectedStudentId: null,
				enrollmentForm: this.$form.createForm(this, { name: 'enrollment_form' }),
				enrollmentYearPickerOpen: false,
				enrollmentYearPickerMode: 'year',
				editingEnrollment: null,
				loadingStudents: false,
				// Se llena desde la API en fetchStudents()
				table1Data: [],
				gradesOptions: [],

				// Associating "Authors" table columns with its corresponding property.
				table1Columns: table1Columns,
			}
		},
		mounted() {
			this.fetchStudents();
			this.fetchGrades();
		},
		computed: {
			selectedYearMoment() {
				if (!this.selectedYear || this.selectedYear === 'all') return null;
				const y = Number(this.selectedYear);
				if (Number.isNaN(y)) return null;
				return moment(String(y), 'YYYY');
			},
			availableYears() {
				const years = new Set();
				(this.table1Data || []).forEach((row) => {
					const raw = row?._raw || {};
					const y = raw.enrollmentYear;
					if (y != null && String(y).trim() !== '') years.add(Number(y));
				});
				return Array.from(years).sort((a, b) => b - a);
			},
			filteredTableData() {
				const normalize = (v) => String(v ?? '').trim().toLowerCase();
				let rows = (this.table1Data || []).slice();

				// filtro por año
				if (this.selectedYear && this.selectedYear !== 'all') {
					rows = rows.filter((row) => String(row?._raw?.enrollmentYear ?? '') === String(this.selectedYear));
				}

				// filtro por grado
				if (this.selectedGrade && this.selectedGrade !== 'all') {
					const selected = normalize(this.selectedGrade);
					rows = rows.filter((row) => {
						const raw = row?._raw || {};
						const grade = raw.gradeCourse || row?.gradeCourse?.grade || row?.gradeCourse || '';
						return normalize(grade) === selected;
					});
				}

				// filtro por estado de matrícula
				if (this.selectedEnrollmentStatus && this.selectedEnrollmentStatus !== 'all') {
					const selected = normalize(this.selectedEnrollmentStatus);
					rows = rows.filter((row) => {
						const raw = row?._raw || {};
						const status = raw.enrollmentStatus || row?.enrollmentStatus || '';
						return normalize(status) === selected;
					});
				}

				// filtro por búsqueda (antes lo hacía StudentsTable internamente)
				const q = normalize(this.searchText);
				if (q) {
					const toHaystack = (row) => {
						const raw = row?._raw || {};
						const parts = [
							raw.fullName,
							row?.studentName?.name,
							raw.gradeCourse,
							row?.gradeCourse?.grade,
							raw.enrollmentStatus,
							raw.enrollmentYear,
							raw.created_at,
							row?.enrollmentDate,
						];
						return normalize(parts.filter(Boolean).join(' '));
					};
					rows = rows.filter((row) => toHaystack(row).includes(q));
				}

				return rows;
			},
			studentsOptions() {
				return (this.table1Data || []).map((row) => {
					const raw = row?._raw || {};
					return {
						id: raw.id ?? row.key,
						label: raw.fullName || row?.studentName?.name || `ID ${raw.id ?? row.key}`,
					};
				}).filter((x) => x.id != null);
			},
			selectedStudentInfo() {
				const id = this.selectedStudentId;
				if (!id) return {};
				const row = (this.table1Data || []).find((r) => String(r?.key) === String(id) || String(r?._raw?.id) === String(id));
				const raw = row?._raw || {};
				return {
					id: raw.id ?? row?.key,
					fullName: raw.fullName || row?.studentName?.name || '',
					enrollmentDate: this.formatDateYMD(
						raw.created_at || raw.enrollment_date || raw.enrollmentDate || row?.enrollmentDate || ''
					),
					enrollmentStatus: raw.enrollmentStatus || row?.enrollmentStatus || '',
					gradeCourse: raw.gradeCourse || row?.gradeCourse?.grade || '',
					enrollmentYear: raw.enrollmentYear || '',
				};
			},
		},
		methods: {
			displayEnrollmentStatus(value) {
				const v = String(value == null ? '' : value).trim();
				if (!v) return '-';
				if (v.toLowerCase() === 'pendiente') return 'Pendiente de pago';
				return v;
			},
			onStatusCleared(value) {
				// allowClear: cuando limpian, value llega undefined
				if (!value) this.selectedEnrollmentStatus = 'all';
			},
			onGradeCleared(value) {
				// allowClear: cuando limpian, value llega undefined
				if (!value) this.selectedGrade = 'all';
			},
			toYearMoment(value) {
				if (value == null || String(value).trim() === '') return null;
				const y = Number(value);
				if (Number.isNaN(y)) return null;
				return moment(String(y), 'YYYY');
			},
			formatDateYMD(value) {
				if (!value) return '';

				// Si viene ISO, cortamos la hora (YYYY-MM-DDTHH:mm...)
				if (typeof value === 'string') {
					const isoDate = value.split('T')[0];
					if (/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return isoDate.replaceAll('-', '/');
				}

				const d = new Date(value);
				if (Number.isNaN(d.getTime())) {
					// fallback: quitar hora si viene "YYYY-MM-DD HH:mm:ss"
					const raw = String(value);
					const onlyDate = raw.split(' ')[0];
					return /^\d{4}-\d{2}-\d{2}$/.test(onlyDate) ? onlyDate.replaceAll('-', '/') : onlyDate;
				}
				const yyyy = d.getFullYear();
				const mm = String(d.getMonth() + 1).padStart(2, '0');
				const dd = String(d.getDate()).padStart(2, '0');
				return `${yyyy}/${mm}/${dd}`;
			},
			apiHeaders() {
				const authToken = getToken();
				return { Authorization: `Bearer ${authToken}` };
			},
			fetchGrades() {
				axios.get('http://localhost:8000/api/grades', { headers: this.apiHeaders() })
					.then((res) => {
						const list = (res.data || [])
							.filter((g) => String(g?.status || 'Activo') === 'Activo')
							.map((g) => g?.name)
							.filter(Boolean);
						this.gradesOptions = list.sort((a, b) => String(a).localeCompare(String(b), 'es', { sensitivity: 'base' }));
					})
					.catch((err) => {
						console.error('Error al obtener grados:', err.response?.data || err);
						this.gradesOptions = [];
					});
			},
			onYearOpenChange(open) {
				this.yearPickerOpen = open;
				if (open) this.yearPickerMode = 'year';
			},
			onYearPanelChange(value) {
				// value es moment() del año seleccionado
				if (!value) return;
				this.selectedYear = String(value.year());
				this.yearPickerOpen = false;
				this.yearPickerMode = 'year';
			},
			onYearCleared(value) {
				// Si borran el picker, value llega null
				if (!value) this.selectedYear = 'all';
			},
			onEnrollmentYearOpenChange(open) {
				this.enrollmentYearPickerOpen = open;
				if (open) this.enrollmentYearPickerMode = 'year';
			},
			onEnrollmentYearPanelChange(value) {
				// value es moment() del año seleccionado
				if (!value) return;
				this.enrollmentForm.setFieldsValue({ enrollmentYear: value });
				this.enrollmentYearPickerOpen = false;
				this.enrollmentYearPickerMode = 'year';
			},
			onEnrollmentYearCleared(value) {
				// Si borran el picker, value llega null
				if (!value) this.enrollmentForm.setFieldsValue({ enrollmentYear: null });
			},
			openEnrollmentModal() {
				this.showEnrollmentModal = true;
				this.savingEnrollment = false;
				this.editingEnrollment = null;
				this.selectedStudentId = null;
				this.enrollmentForm.resetFields();
				this.enrollmentForm.setFieldsValue({
					enrollmentYear: this.toYearMoment(new Date().getFullYear()),
					enrollmentStatus: 'Activo',
				});
			},
			openEditEnrollment(row) {
				const raw = row?._raw || {};
				const enrollmentId = raw.enrollment_id ?? raw.enrollmentId;
				if (!enrollmentId) return;

				this.editingEnrollment = { ...raw, enrollment_id: enrollmentId };
				this.showEnrollmentModal = true;
				this.savingEnrollment = false;

				// fijar estudiante (no editable)
				this.selectedStudentId = raw.student_id || null;

				this.$nextTick(() => {
					this.enrollmentForm.resetFields();
					this.enrollmentForm.setFieldsValue({
						studentId: this.selectedStudentId,
						gradeCourse: raw.gradeCourse || '',
						enrollmentStatus: raw.enrollmentStatus || 'Activo',
						enrollmentYear: this.toYearMoment(raw.enrollmentYear || new Date().getFullYear()),
					});
				});
			},
			closeEnrollmentModal() {
				this.showEnrollmentModal = false;
				this.savingEnrollment = false;
				this.editingEnrollment = null;
				this.selectedStudentId = null;
				this.enrollmentForm.resetFields();
			},
			handleStudentChange(value) {
				this.selectedStudentId = value;
				// Autollenar grado/año si ya existen
				const info = this.selectedStudentInfo || {};
				this.enrollmentForm.setFieldsValue({
					gradeCourse: info.gradeCourse || '',
					enrollmentStatus: info.enrollmentStatus || 'Activo',
					enrollmentYear: this.toYearMoment(info.enrollmentYear || new Date().getFullYear()),
				});
			},
			handleSubmitEnrollment(e) {
				e.preventDefault();
				this.enrollmentForm.validateFields((err, values) => {
					if (err) return;

					const info = this.selectedStudentInfo || {};
					if (!info.id) return;

					this.savingEnrollment = true;
					const authToken = getToken();

					const yearValue = values.enrollmentYear;
					const enrollmentYear = yearValue && typeof yearValue.year === 'function'
						? yearValue.year()
						: Number(yearValue);

					const payload = {
						student_id: info.id,
						enrollmentStatus: values.enrollmentStatus || this.editingEnrollment?.enrollmentStatus || 'Activo',
						gradeCourse: values.gradeCourse,
						enrollmentYear,
					};

					const url = this.editingEnrollment?.enrollment_id
						? `http://localhost:8000/api/enrollments/${this.editingEnrollment.enrollment_id}`
						: 'http://localhost:8000/api/enrollments';
					const method = this.editingEnrollment?.enrollment_id ? 'put' : 'post';

					axios({
						method,
						url,
						data: payload,
						headers: {
							'Authorization': `Bearer ${authToken}`,
							'Content-Type': 'application/json',
						},
					})
					.then(() => {
						this.closeEnrollmentModal();
						this.fetchStudents();
					})
					.catch((error) => {
						const msg = error?.response?.data?.message;
						if (error?.response?.status === 409 && msg) {
							this.$message.error(msg);
							return;
						}
						console.error('Error registrando matrícula:', error.response?.data || error);
						this.$message.error('No se pudo registrar la matrícula.');
					})
					.finally(() => {
						this.savingEnrollment = false;
					});
				});
			},
			cancelEnrollment(row) {
				const raw = row?._raw || {};
				const enrollmentId = raw.enrollment_id ?? raw.enrollmentId;
				if (!enrollmentId) return;

				const authToken = getToken();
				axios.patch(`http://localhost:8000/api/enrollments/${enrollmentId}/cancel`, {}, {
					headers: { 'Authorization': `Bearer ${authToken}` },
				})
					.then(() => {
						this.$message.success('Matrícula anulada.');
						this.fetchStudents();
					})
					.catch((error) => {
						const msg = error?.response?.data?.message;
						if (msg) this.$message.error(msg);
						else this.$message.error('No se pudo anular la matrícula.');
						console.error('Error anulando matrícula:', error.response?.data || error);
					});
			},
			fetchStudents() {
				this.loadingStudents = true;
				const authToken = getToken();
				axios.get('http://localhost:8000/api/enrollments', {
					headers: {
						'Authorization': `Bearer ${authToken}`,
					}
				})
				.then(response => {
					this.table1Data = (response.data || []).map(enrollment => {
						const student = enrollment.student || {};
						return {
							key: enrollment.student_id ?? student.id ?? enrollment.id,
							_raw: {
								...enrollment,
								enrollment_id: enrollment.id,
								id: student.id,
								student_id: enrollment.student_id ?? student.id,
								fullName: student.fullName,
								enrollmentYear: enrollment.enrollmentYear ?? student.enrollmentYear ?? null,
							},
						studentName: {
							name: student.fullName,
							email: '', // Puedes agregar un campo de email si tu modelo Student lo tiene
							avatar: student.photo_url || 'images/face-2.jpg',
						},
						gradeCourse: {
							grade: enrollment.gradeCourse ?? student.gradeCourse,
							course: '', // Puedes agregar un campo de curso más específico
						},
						// Fecha de matrícula (preferir created_at del registro de matrícula)
						enrollmentDate: this.formatDateYMD(
							enrollment.created_at || enrollment.enrollment_date || enrollment.enrollmentDate || ''
						),
						enrollmentStatus: enrollment.enrollmentStatus ?? student.enrollmentStatus,
					};
					});

					// Si el filtro estaba en un año que ya no existe, vuelve a "Todos"
					// (pero dejamos el año actual aunque aún no haya registros)
					const currentYear = String(new Date().getFullYear());
					if (
						this.selectedYear !== 'all' &&
						!this.availableYears.map(String).includes(String(this.selectedYear)) &&
						String(this.selectedYear) !== currentYear
					) {
						this.selectedYear = 'all';
					}
				})
				.catch(error => {
					console.error('Error al obtener matrículas:', error.response?.data || error);
				})
				.finally(() => {
					this.loadingStudents = false;
				});
			},
		},
	})

</script>

<style lang="scss">
.matricula-filters {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 10px;
	flex-wrap: wrap;
}

.matricula-search {
	width: 260px;
}

.matricula-year {
	width: 160px;
}

.matricula-status {
	width: 200px;
}

.matricula-grade,
.matricula-btn {
	width: 200px;
}

/* En md y sm: alinear buscador y botón (misma fila / mismo ancho) */
@media (max-width: 991px) {
	.matricula-filters {
		justify-content: flex-start;
	}

	.matricula-search,
	.matricula-status,
	.matricula-btn {
		width: 200px;
	}
}

/* En pantallas muy pequeñas: que ocupen todo el ancho */
@media (max-width: 575px) {
	.matricula-search,
	.matricula-year,
	.matricula-status,
	.matricula-grade,
	.matricula-btn {
		width: 100%;
	}
}

.btn-warning.ant-btn {
	background: #fbbf24; /* amarillo */
	border-color: #f59e0b;
	color: #111827;
	box-shadow: none;
}

.btn-warning.ant-btn:hover,
.btn-warning.ant-btn:focus {
	background: #f59e0b;
	border-color: #d97706;
	color: #111827;
}

.btn-warning.ant-btn:active {
	background: #d97706;
	border-color: #b45309;
	color: #111827;
}

/* Popconfirm (Anular) - botones más ordenados */
::v-deep .enrollment-cancel-popconfirm .ant-popover-message-title {
	padding-right: 0;
	line-height: 1.2;
	font-weight: 700;
}

::v-deep .enrollment-cancel-popconfirm .ant-popover-buttons {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	margin-top: 12px;
}

/* Mantener Cancelar a la izquierda y Anular a la derecha */
::v-deep .enrollment-cancel-popconfirm .ant-popover-buttons .ant-btn {
	min-width: 96px;
	border-radius: 10px;
}
</style>