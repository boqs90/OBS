<!-- Registro de Empleados (CRUD) -->

<template>
	<div class="registro-page">
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full">
					<EmployeesTable
						:data="employeesData"
						:loading="loadingEmployees"
						:columns="employeesColumns"
						@openAddEmployeeModal="openCreate"
						@editEmployee="openEdit"
						@deleteEmployee="deleteEmployee"
					/>

					<a-modal
						:title="editingEmployee ? 'Editar Empleado' : 'Registrar Empleado'"
						:visible="showModal"
						:footer="null"
						@cancel="closeModal"
					>
						<EmployeeForm
							:initialValues="editingEmployee"
							:positions="positions"
							:submitText="editingEmployee ? 'Actualizar' : 'Guardar'"
							:resetAfterSubmit="!editingEmployee"
							@submitForm="handleSubmit"
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

import EmployeesTable from '@/components/Employees/EmployeesTable';
import EmployeeForm from '@/components/Employees/EmployeeForm';

const employeesColumns = [
	{ title: 'Nombre', dataIndex: 'employeeName', scopedSlots: { customRender: 'employeeName' }, width: 320 },
	{ title: 'Identidad', dataIndex: 'identityNumber', class: 'text-muted', width: 170 },
	{ title: 'Cargo', dataIndex: 'positionName', class: 'text-muted', width: 220 },
	{ title: 'Teléfono', dataIndex: 'phone', class: 'text-muted', width: 160 },
	{ title: 'Ingreso', dataIndex: 'entryDate', class: 'text-muted', width: 130 },
	{ title: 'Egreso', dataIndex: 'exitDate', class: 'text-muted', width: 130 },
	{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 140 },
	{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 170, align: 'right' },
];

export default ({
	name: 'RegistroEmpleados',
	components: { EmployeesTable, EmployeeForm },
	data() {
		return {
			employeesColumns,
			employeesData: [],
			loadingEmployees: false,
			showModal: false,
			editingEmployee: null,
			positions: [],
		};
	},
	mounted() {
		this.fetchPositions();
		this.fetchEmployees();
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
		apiHeaders() {
			const authToken = getToken();
			return { Authorization: `Bearer ${authToken}` };
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
		fetchPositions() {
			axios.get('http://localhost:8000/api/positions', { headers: this.apiHeaders() })
				.then((res) => { this.positions = res.data || []; })
				.catch((err) => {
					console.error('Error al obtener cargos:', err.response?.data || err);
					this.positions = [];
				});
		},
		fetchEmployees() {
			this.loadingEmployees = true;
			axios.get('http://localhost:8000/api/employees', { headers: this.apiHeaders() })
				.then((res) => {
					this.employeesData = (res.data || []).map((e) => ({
						key: e.id,
						_raw: e,
						employeeName: { name: e.fullName, email: e.email || '', avatar: e.photo_url || null },
						identityNumber: e.identityNumber || '',
						positionName: e.position?.name || '',
						phone: e.phone || '',
						entryDate: this.formatDate(e.entryDate),
						exitDate: this.formatDate(e.exitDate),
						status: e.status || 'Activo',
					}));
				})
				.catch((err) => console.error('Error al obtener empleados:', err.response?.data || err))
				.finally(() => { this.loadingEmployees = false; });
		},
		openCreate() {
			this.editingEmployee = null;
			this.showModal = true;
		},
		openEdit(row) {
			if (this.isProtectedEmployee(row)) {
				if (this.$message) this.$message.info('Este empleado está protegido y no se puede modificar.');
				return;
			}
			const rawData = row && row._raw ? row._raw : null;
			if (rawData) {
				this.editingEmployee = {
					id: rawData.id,
					fullName: rawData.fullName || '',
					email: rawData.email || '',
					phone: rawData.phone || '',
					salary: rawData.salary || 0,
					identityNumber: rawData.identityNumber || '',
					position_id: rawData.position_id || rawData.position?.id || null,
					entryDate: rawData.entryDate || null,
					exitDate: rawData.exitDate || null,
					status: rawData.status || 'Activo',
					photo_url: rawData.photo_url || null,
				};
			} else {
				this.editingEmployee = null;
			}
			this.showModal = true;
		},
		closeModal() {
			this.showModal = false;
			this.editingEmployee = null;
		},
		handleSubmit(payload) {
			const editing = !!(this.editingEmployee && this.editingEmployee.id);
			const url = editing
				? `http://localhost:8000/api/employees/${this.editingEmployee.id}`
				: 'http://localhost:8000/api/employees';
			const method = editing ? 'post' : 'post'; // Usar POST para FormData con _method

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
				if (editing) {
					formData.append('_method', 'PUT');
				}

				axios({ method, url, data: formData, headers: { ...this.apiHeaders(), 'Content-Type': 'multipart/form-data' } })
					.then(() => {
						this.closeModal();
						this.fetchEmployees();
					})
					.catch((err) => console.error('Error guardando empleado:', err.response?.data || err));
			} else {
				// Sin archivo, enviar como JSON normal
				if (editing) {
					// Para edición sin foto, agregar _method al payload
					payload._method = 'PUT';
				}

				axios({ method, url, data: payload, headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
					.then(() => {
						this.closeModal();
						this.fetchEmployees();
					})
					.catch((err) => console.error('Error guardando empleado:', err.response?.data || err));
			}
		},
		deleteEmployee(row) {
			const id = row && (row.key || row.id);
			if (!id) return;
			axios.delete(`http://localhost:8000/api/employees/${id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchEmployees())
				.catch((err) => console.error('Error eliminando empleado:', err.response?.data || err));
		},
	},
});
</script>

