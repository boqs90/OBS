<!-- Registro de Maestros (CRUD) -->

<template>
	<div class="registro-page">
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full">
					<template #title>
						<h5 class="font-semibold m-0">Registro de Maestros</h5>
					</template>

					<TeachersTable
						:data="teachersData"
						:loading="loadingTeachers"
						:columns="teachersColumns"
						:showTitle="false"
						@openAddTeacherModal="openCreateTeacher"
						@editTeacher="openEditTeacher"
						@deleteTeacher="deleteTeacher"
					/>

					<a-modal
						:title="editingTeacher ? 'Editar Maestro' : 'Registrar Maestro'"
						:visible="showTeacherModal"
						:footer="null"
						@cancel="closeTeacherModal"
					>
						<TeacherForm
							:initialValues="editingTeacher"
							:submitText="editingTeacher ? 'Actualizar' : 'Guardar'"
							:resetAfterSubmit="!editingTeacher"
							@submitForm="handleSubmitTeacher"
						/>
					</a-modal>
				</a-card>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

import TeachersTable from '@/components/Teachers/TeachersTable';
import TeacherForm from '@/components/Teachers/TeacherForm';

const teachersColumns = [
	{ title: 'Nombre', dataIndex: 'teacherName', scopedSlots: { customRender: 'teacherName' }, width: 320 },
	{ title: 'Identidad', dataIndex: 'identityNumber', class: 'text-muted', width: 170 },
	{ title: 'Cargo', dataIndex: 'position', class: 'text-muted', width: 200 },
	{ title: 'Teléfono', dataIndex: 'phone', class: 'text-muted', width: 160 },
	{ title: 'Ingreso', dataIndex: 'entryDate', class: 'text-muted', width: 120 },
	{ title: 'Egreso', dataIndex: 'exitDate', class: 'text-muted', width: 120 },
	{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 140 },
	{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 170, align: 'right' },
];

export default ({
	name: 'RegistroMaestros',
	components: { TeachersTable, TeacherForm },
	data() {
		return {
			loadingTeachers: false,
			teachersData: [],
			teachersColumns,
			showTeacherModal: false,
			editingTeacher: null,
		}
	},
	mounted() {
		this.fetchTeachers();
	},
	methods: {
		apiHeaders() {
			const authToken = getToken();
			return { Authorization: `Bearer ${authToken}` };
		},
		openCreateTeacher() {
			this.editingTeacher = null;
			this.showTeacherModal = true;
		},
		openEditTeacher(row) {
			this.editingTeacher = row && row._raw ? row._raw : null;
			this.showTeacherModal = true;
		},
		closeTeacherModal() {
			this.showTeacherModal = false;
			this.editingTeacher = null;
		},
		formatDate(value) {
			if (!value) return '';
			const d = new Date(value);
			if (Number.isNaN(d.getTime())) return String(value);
			const dd = String(d.getDate()).padStart(2, '0');
			const mm = String(d.getMonth() + 1).padStart(2, '0');
			const yyyy = d.getFullYear();
			return `${dd}/${mm}/${yyyy}`;
		},
		fetchTeachers() {
			this.loadingTeachers = true;
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((response) => {
					this.teachersData = (response.data || []).map((teacher) => ({
						key: teacher.id,
						_raw: teacher,
						teacherName: { name: teacher.fullName, email: teacher.email, avatar: teacher.photo_url || null },
						identityNumber: teacher.identityNumber || '',
						position: teacher.position || '',
						phone: teacher.phone || '',
						entryDate: this.formatDate(teacher.entryDate),
						exitDate: this.formatDate(teacher.exitDate),
						status: teacher.status || 'Activo',
					}));
				})
				.catch((error) => console.error('Error al obtener maestros:', error.response?.data || error))
				.finally(() => { this.loadingTeachers = false; });
		},
		handleSubmitTeacher(payload) {
			const url = this.editingTeacher
				? `http://localhost:8000/api/teachers/${this.editingTeacher.id}`
				: 'http://localhost:8000/api/teachers';
			const method = this.editingTeacher ? 'post' : 'post'; // Usar POST para FormData con _method

			// Si hay archivo, usar FormData
			if (payload.photoFile) {
				const formData = new FormData();
				
				// Agregar todos los campos del formulario
				Object.keys(payload).forEach(key => {
					if (key !== 'photoFile') {
						formData.append(key, payload[key] || '');
					}
				});
				
				// Agregar archivo de foto
				formData.append('photo', payload.photoFile);
				
				// Agregar método para edición (PUT simulado)
				if (this.editingTeacher) {
					formData.append('_method', 'PUT');
				}

				axios({ method, url, data: formData, headers: { ...this.apiHeaders(), 'Content-Type': 'multipart/form-data' } })
					.then(() => {
						this.closeTeacherModal();
						this.fetchTeachers();
					})
					.catch((error) => console.error('Error guardando maestro:', error.response?.data || error));
			} else {
				// Sin archivo, enviar como JSON normal
				const dataPayload = this.editingTeacher
					? { ...payload, _method: 'PUT' } // Para edición
					: payload;

				axios({ method, url, data: dataPayload, headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
					.then(() => {
						this.closeTeacherModal();
						this.fetchTeachers();
					})
					.catch((error) => console.error('Error guardando maestro:', error.response?.data || error));
			}
		},
		deleteTeacher(row) {
			const id = row && (row.key || row.id);
			if (!id) return;
			axios.delete(`http://localhost:8000/api/teachers/${id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchTeachers())
				.catch((error) => console.error('Error eliminando maestro:', error.response?.data || error));
		},
	},
})
</script>

