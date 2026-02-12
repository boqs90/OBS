<template>
	<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
		<template #title>
			<div class="table-header">
				<div class="table-header-top">
					<h6 class="mb-0">Planilla de Nómina</h6>
				</div>
				<div class="table-header-controls">
					<a-input-search
						v-model="searchText"
						placeholder="Buscar por nombre del empleado, identidad o cargo..."
						style="width: 300px"
						allow-clear
						@search="handleSearch"
					/>
					<a-select
						v-model="selectedPeriod"
						placeholder="Período"
						style="width: 150px; min-height: 32px;"
						:getPopupContainer="() => document.body"
						@change="handlePeriodChange"
					>
						<a-select-option value="current">Mes Actual</a-select-option>
						<a-select-option value="last">Mes Anterior</a-select-option>
						<a-select-option value="custom">Personalizado</a-select-option>
					</a-select>
					<a-button type="primary" @click="showAddPayrollModal">
						<a-icon type="plus" /> Generar Planilla
					</a-button>
				</div>
			</div>
		</template>

		<!-- Filtros Adicionales -->
		<div class="filters-section">
			<a-row :gutter="16">
				<a-col :span="6">
					<a-select
						v-model="filterDepartment"
						placeholder="Departamento"
						allow-clear
						@change="handleFilterChange"
						style="min-height: 32px;"
						:getPopupContainer="() => document.body"
					>
						<a-select-option value="academico">Académico</a-select-option>
						<a-select-option value="administrativo">Administrativo</a-select-option>
						<a-select-option value="mantenimiento">Mantenimiento</a-select-option>
						<a-select-option value="servicios">Servicios</a-select-option>
					</a-select>
				</a-col>
				<a-col :span="6">
					<a-select
						v-model="filterStatus"
						placeholder="Estado de Pago"
						allow-clear
						@change="handleFilterChange"
						style="min-height: 32px;"
						:getPopupContainer="() => document.body"
					>
						<a-select-option value="pending">Pendiente</a-select-option>
						<a-select-option value="processed">Procesado</a-select-option>
						<a-select-option value="paid">Pagado</a-select-option>
						<a-select-option value="cancelled">Cancelado</a-select-option>
					</a-select>
				</a-col>
				<a-col :span="6">
					<a-date-picker
						v-model="customDateRange"
						mode="month"
						placeholder="Seleccionar mes"
						style="width: 100%; min-height: 32px;"
						@change="handleCustomDateChange"
					/>
				</a-col>
				<a-col :span="6">
					<a-button @click="exportPayroll" :loading="exporting">
						<a-icon type="download" /> Exportar Excel
					</a-button>
				</a-col>
			</a-row>
		</div>

		<!-- Tabla de Planilla -->
		<a-table
			:columns="columns"
			:data-source="filteredPayroll"
			:loading="loading"
			:pagination="pagination"
			@change="handleTableChange"
			row-key="id"
			:row-selection="rowSelection"
		>
			<!-- Columna de Empleado -->
			<template #employee="text, record">
				<div class="employee-info">
					<div class="employee-name">{{ record.employee_name }}</div>
					<div class="employee-details">
						<span>ID: {{ record.employee_identity }}</span>
						<span>{{ record.position }}</span>
					</div>
				</div>
			</template>

			<!-- Columna de Salario -->
			<template #salary="text">
				<span class="salary-amount">
					{{ formatCurrency(text) }}
				</span>
			</template>

			<!-- Columna de Deducciones -->
			<template #deductions="text, record">
				<div class="deductions-breakdown">
					<div class="deduction-item">
						<span class="deduction-label">ISSS (3%):</span>
						<span class="deduction-value">{{ formatCurrency(record.isss_deduction) }}</span>
					</div>
					<div class="deduction-item">
						<span class="deduction-label">AFP (4%):</span>
						<span class="deduction-value">{{ formatCurrency(record.afp_deduction) }}</span>
					</div>
					<div class="deduction-item">
						<span class="deduction-label">Renta (10%):</span>
						<span class="deduction-value">{{ formatCurrency(record.rent_deduction) }}</span>
					</div>
					<div class="deduction-item">
						<span class="deduction-label">Otros:</span>
						<span class="deduction-value">{{ formatCurrency(record.other_deductions) }}</span>
					</div>
					<div class="deduction-total">
						<span class="total-label">Total Deducciones:</span>
						<span class="total-value">{{ formatCurrency(record.total_deductions) }}</span>
					</div>
				</div>
			</template>

			<!-- Columna de Neto -->
			<template #net_pay="text">
				<span class="net-pay-amount">
					{{ formatCurrency(text) }}
				</span>
			</template>

			<!-- Columna de Estado -->
			<template #status="text">
				<a-tag :color="getStatusColor(text)">
					{{ getStatusText(text) }}
				</a-tag>
			</template>

			<!-- Columna de Acciones -->
			<template #action="text, record">
				<div class="table-actions">
					<a-button size="small" type="primary" ghost @click="viewPayroll(record)">
						<a-icon type="eye" /> Ver
					</a-button>
					<a-button 
						size="small" 
						type="primary" 
						ghost 
						@click="editPayroll(record)"
						:disabled="record.status === 'paid'"
					>
						<a-icon type="edit" /> Editar
					</a-button>
					<a-dropdown>
						<a-menu slot="overlay">
							<a-menu-item @click="processPayroll(record)" :disabled="record.status === 'paid'">
								<a-icon type="check-circle" /> Procesar
							</a-menu-item>
							<a-menu-item @click="markAsPaid(record)" :disabled="record.status === 'paid'">
								<a-icon type="dollar" /> Marcar como Pagado
							</a-menu-item>
							<a-menu-item @click="generatePayslip(record)">
								<a-icon type="file-pdf" /> Generar Recibo
							</a-menu-item>
							<a-menu-divider />
							<a-menu-item @click="deletePayroll(record)" :disabled="record.status === 'paid'">
								<a-icon type="delete" /> Eliminar
							</a-menu-item>
						</a-menu>
						<a-button size="small">
							<a-icon type="more" />
						</a-button>
					</a-dropdown>
				</div>
			</template>
		</a-table>

		<!-- Resumen de Planilla -->
		<div class="payroll-summary">
			<a-row :gutter="16">
				<a-col :span="6">
					<a-statistic
						title="Total Empleados"
						:value="filteredPayroll.length"
						:style="{ color: '#3f8600' }"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total Salario Bruto"
						:value="totalGrossSalary"
						:precision="2"
						:style="{ color: '#1890ff' }"
						:formatter="formatCurrency"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total Deducciones"
						:value="totalDeductions"
						:precision="2"
						:style="{ color: '#cf1322' }"
						:formatter="formatCurrency"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total Neto a Pagar"
						:value="totalNetPay"
						:precision="2"
						:style="{ color: '#52c41a' }"
						:formatter="formatCurrency"
					/>
				</a-col>
			</a-row>
		</div>

		<!-- Modal de Generar Planilla -->
		<a-modal
			v-model="payrollModalVisible"
			title="Generar Planilla de Nómina"
			:width="800"
			@ok="handlePayrollSubmit"
			@cancel="resetPayrollForm"
		>
			<a-form :form="payrollForm" layout="vertical">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Período de Pago">
							<a-month-picker
								v-decorator="[
									'payroll_period',
									{ rules: [{ required: true, message: 'Por favor seleccione el período' }] }
								]"
								placeholder="Seleccione el período"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Fecha de Pago">
							<a-date-picker
								v-decorator="[
									'payment_date',
									{ rules: [{ required: true, message: 'Por favor seleccione la fecha de pago' }] }
								]"
								placeholder="Fecha de pago"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Seleccionar Empleados">
					<a-transfer
						v-decorator="['employees']"
						:data-source="employees"
						:titles="['Empleados Disponibles', 'Empleados Seleccionados']"
						:target-keys="targetKeys"
						:selected-keys="selectedKeys"
						:render="item => item.title"
						:disabled="false"
						@change="handleTransferChange"
						@selectChange="handleSelectChange"
						:list-style="{
							width: 300,
							height: 300,
						}"
						:show-search="true"
						:filter-option="(inputValue, option) => option.title.toLowerCase().indexOf(inputValue.toLowerCase()) > -1"
					/>
				</a-form-item>

				<a-divider>Opciones Adicionales</a-divider>

				<a-row :gutter="16">
					<a-col :span="8">
						<a-form-item label="Incluir Bonos">
							<a-checkbox v-model="includeBonuses">
								Incluir bonos y comisiones
							</a-checkbox>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="Incluir Horas Extra">
							<a-checkbox v-model="includeOvertime">
								Calcular horas extra
							</a-checkbox>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="Incluir Deducciones">
							<a-checkbox v-model="includeDeductions" checked>
								Aplicar deducciones legales
							</a-checkbox>
						</a-form-item>
					</a-col>
				</a-row>
			</a-form>
		</a-modal>

		<!-- Modal de Ver Detalles -->
		<a-modal
			v-model="detailsModalVisible"
			title="Detalles de Planilla"
			:width="800"
			:footer="null"
		>
			<div v-if="selectedPayroll">
				<a-descriptions :column="2" bordered>
					<a-descriptions-item label="Empleado">
						{{ selectedPayroll.employee_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Identidad">
						{{ selectedPayroll.employee_identity }}
					</a-descriptions-item>
					<a-descriptions-item label="Cargo">
						{{ selectedPayroll.position }}
					</a-descriptions-item>
					<a-descriptions-item label="Departamento">
						{{ selectedPayroll.department }}
					</a-descriptions-item>
					<a-descriptions-item label="Período">
						{{ selectedPayroll.payroll_period }}
					</a-descriptions-item>
					<a-descriptions-item label="Fecha de Pago">
						{{ formatDate(selectedPayroll.payment_date) }}
					</a-descriptions-item>
					<a-descriptions-item label="Salario Base">
						<span class="salary-amount">{{ formatCurrency(selectedPayroll.base_salary) }}</span>
					</a-descriptions-item>
					<a-descriptions-item label="Salario Bruto">
						<span class="salary-amount">{{ formatCurrency(selectedPayroll.gross_salary) }}</span>
					</a-descriptions-item>
				</a-descriptions>
				
				<div style="margin-top: 20px;">
					<h6>Desglose de Deducciones</h6>
					<a-table
						:columns="deductionColumns"
						:data-source="deductionData"
						:pagination="false"
						size="small"
					>
						<template #amount="text">
							{{ formatCurrency(text) }}
						</template>
					</a-table>
				</div>
				
				<div style="margin-top: 20px; text-align: right;">
					<h3>Neto a Pagar: <span class="net-pay-amount">{{ formatCurrency(selectedPayroll.net_pay) }}</span></h3>
				</div>
			</div>
		</a-modal>
	</a-card>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default ({
	data() {
		return {
			loading: false,
			exporting: false,
			payroll: [],
			employees: [],
			searchText: '',
			selectedPeriod: 'current',
			filterDepartment: null,
			filterStatus: null,
			customDateRange: null,
			targetKeys: [],
			selectedKeys: [],
			includeBonuses: false,
			includeOvertime: false,
			includeDeductions: true,
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} registros`
			},
			payrollModalVisible: false,
			detailsModalVisible: false,
			selectedPayroll: null,
			payrollForm: this.$form.createForm(this),
			columns: [
				{
					title: 'Empleado',
					dataIndex: 'employee_name',
					key: 'employee_name',
					width: 250,
					scopedSlots: { customRender: 'employee' }
				},
				{
					title: 'Salario Base',
					dataIndex: 'base_salary',
					key: 'base_salary',
					width: 120,
					sorter: true,
					scopedSlots: { customRender: 'salary' }
				},
				{
					title: 'Salario Bruto',
					dataIndex: 'gross_salary',
					key: 'gross_salary',
					width: 120,
					sorter: true,
					scopedSlots: { customRender: 'salary' }
				},
				{
					title: 'Deducciones',
					key: 'deductions',
					width: 200,
					scopedSlots: { customRender: 'deductions' }
				},
				{
					title: 'Neto a Pagar',
					dataIndex: 'net_pay',
					key: 'net_pay',
					width: 120,
					sorter: true,
					scopedSlots: { customRender: 'net_pay' }
				},
				{
					title: 'Estado',
					dataIndex: 'status',
					key: 'status',
					width: 100,
					scopedSlots: { customRender: 'status' }
				},
				{
					title: 'Acciones',
					key: 'action',
					width: 200,
					scopedSlots: { customRender: 'action' }
				}
			],
			deductionColumns: [
				{
					title: 'Concepto',
					dataIndex: 'concept',
					key: 'concept'
				},
				{
					title: 'Porcentaje',
					dataIndex: 'percentage',
					key: 'percentage'
				},
				{
					title: 'Monto',
					dataIndex: 'amount',
					key: 'amount',
					scopedSlots: { customRender: 'amount' }
				}
			]
		}
	},
	computed: {
		filteredPayroll() {
			let filtered = this.payroll;
			
			// Filtro de búsqueda
			if (this.searchText) {
				const searchLower = this.searchText.toLowerCase();
				filtered = filtered.filter(record => 
					record.employee_name.toLowerCase().includes(searchLower) ||
					record.employee_identity.toLowerCase().includes(searchLower) ||
					record.position.toLowerCase().includes(searchLower)
				);
			}
			
			// Filtro de departamento
			if (this.filterDepartment) {
				filtered = filtered.filter(record => record.department === this.filterDepartment);
			}
			
			// Filtro de estado
			if (this.filterStatus) {
				filtered = filtered.filter(record => record.status === this.filterStatus);
			}
			
			return filtered;
		},
		
		totalGrossSalary() {
			return this.filteredPayroll.reduce((sum, record) => sum + record.gross_salary, 0);
		},
		
		totalDeductions() {
			return this.filteredPayroll.reduce((sum, record) => sum + record.total_deductions, 0);
		},
		
		totalNetPay() {
			return this.filteredPayroll.reduce((sum, record) => sum + record.net_pay, 0);
		},
		
		rowSelection() {
			return {
				onChange: (selectedRowKeys, selectedRows) => {
					this.selectedRowKeys = selectedRowKeys;
				},
				getCheckboxProps: record => ({
					disabled: record.status === 'paid',
				}),
			};
		},
		
		deductionData() {
			if (!this.selectedPayroll) return [];
			
			return [
				{
					concept: 'ISSS',
					percentage: '3%',
					amount: this.selectedPayroll.isss_deduction
				},
				{
					concept: 'AFP',
					percentage: '4%',
					amount: this.selectedPayroll.afp_deduction
				},
				{
					concept: 'Renta',
					percentage: '10%',
					amount: this.selectedPayroll.rent_deduction
				},
				{
					concept: 'Otras Deducciones',
					percentage: '-',
					amount: this.selectedPayroll.other_deductions
				}
			];
		}
	},
	created() {
		this.fetchPayroll();
		this.fetchEmployees();
	},
	methods: {
		authHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		
		async fetchPayroll() {
			this.loading = true;
			try {
				const response = await axios.get('http://localhost:8000/api/payroll', { 
					headers: this.authHeaders() 
				});
				this.payroll = response.data || [];
				this.pagination.total = this.payroll.length;
				console.log('Payroll data loaded:', this.payroll);
			} catch (error) {
				console.error('Error fetching payroll:', error);
				console.error('Error response:', error.response);
				this.$message.error('Error al cargar la planilla');
				
				// Cargar datos de ejemplo para desarrollo
				this.loadMockData();
			} finally {
				this.loading = false;
			}
		},
		
		async fetchEmployees() {
			try {
				const response = await axios.get('http://localhost:8000/api/employees', { 
					headers: this.authHeaders() 
				});
				this.employees = response.data.map(emp => ({
					key: emp.id,
					title: `${emp.name} - ${emp.position}`,
					...emp
				}));
				console.log('Employees data loaded:', this.employees);
			} catch (error) {
				console.error('Error fetching employees:', error);
				console.error('Error response:', error.response);
				
				// Cargar datos de ejemplo para desarrollo
				this.loadMockEmployees();
			}
		},
		
		loadMockData() {
			// Datos de ejemplo para desarrollo
			this.payroll = [
				{
					id: 1,
					employee_name: "Juan Pérez García",
					employee_identity: "0801-1990-12345",
					position: "Profesor de Matemáticas",
					department: "academico",
					base_salary: 15000,
					gross_salary: 16500,
					isss_deduction: 450,
					afp_deduction: 600,
					rent_deduction: 1650,
					other_deductions: 200,
					total_deductions: 2900,
					net_pay: 13600,
					status: "pending",
					payroll_period: "2024-02",
					payment_date: "2024-02-15"
				},
				{
					id: 2,
					employee_name: "María Rodríguez López",
					employee_identity: "0801-1985-67890",
					position: "Secretaria Administrativa",
					department: "administrativo",
					base_salary: 8000,
					gross_salary: 8800,
					isss_deduction: 240,
					afp_deduction: 320,
					rent_deduction: 880,
					other_deductions: 100,
					total_deductions: 1540,
					net_pay: 7260,
					status: "processed",
					payroll_period: "2024-02",
					payment_date: "2024-02-15"
				},
				{
					id: 3,
					employee_name: "Carlos Hernández Martínez",
					employee_identity: "0801-1992-11111",
					position: "Profesor de Inglés",
					department: "academico",
					base_salary: 14000,
					gross_salary: 15400,
					isss_deduction: 420,
					afp_deduction: 560,
					rent_deduction: 1540,
					other_deductions: 150,
					total_deductions: 2670,
					net_pay: 12730,
					status: "paid",
					payroll_period: "2024-02",
					payment_date: "2024-02-15"
				},
				{
					id: 4,
					employee_name: "Ana Sofía Castillo",
					employee_identity: "0801-1988-22222",
					position: "Personal de Mantenimiento",
					department: "mantenimiento",
					base_salary: 6000,
					gross_salary: 6600,
					isss_deduction: 180,
					afp_deduction: 240,
					rent_deduction: 660,
					other_deductions: 50,
					total_deductions: 1130,
					net_pay: 5470,
					status: "pending",
					payroll_period: "2024-02",
					payment_date: "2024-02-15"
				},
				{
					id: 5,
					employee_name: "Roberto Mejía González",
					employee_identity: "0801-1995-33333",
					position: "Director",
					department: "administrativo",
					base_salary: 25000,
					gross_salary: 27500,
					isss_deduction: 750,
					afp_deduction: 1000,
					rent_deduction: 2750,
					other_deductions: 500,
					total_deductions: 5000,
					net_pay: 22500,
					status: "processed",
					payroll_period: "2024-02",
					payment_date: "2024-02-15"
				}
			];
			this.pagination.total = this.payroll.length;
			console.log('Mock payroll data loaded:', this.payroll);
		},
		
		loadMockEmployees() {
			// Datos de ejemplo para el transfer component
			this.employees = [
				{
					key: 1,
					title: "Juan Pérez García - Profesor de Matemáticas",
					id: 1,
					name: "Juan Pérez García",
					position: "Profesor de Matemáticas"
				},
				{
					key: 2,
					title: "María Rodríguez López - Secretaria Administrativa",
					id: 2,
					name: "María Rodríguez López",
					position: "Secretaria Administrativa"
				},
				{
					key: 3,
					title: "Carlos Hernández Martínez - Profesor de Inglés",
					id: 3,
					name: "Carlos Hernández Martínez",
					position: "Profesor de Inglés"
				},
				{
					key: 4,
					title: "Ana Sofía Castillo - Personal de Mantenimiento",
					id: 4,
					name: "Ana Sofía Castillo",
					position: "Personal de Mantenimiento"
				},
				{
					key: 5,
					title: "Roberto Mejía González - Director",
					id: 5,
					name: "Roberto Mejía González",
					position: "Director"
				}
			];
			console.log('Mock employees data loaded:', this.employees);
		},
		
		handleSearch() {
			this.pagination.current = 1;
		},
		
		handleFilterChange() {
			this.pagination.current = 1;
		},
		
		handlePeriodChange(value) {
			// Lógica para filtrar por período
			console.log('Period changed:', value);
		},
		
		handleCustomDateChange(date) {
			// Lógica para filtrar por fecha personalizada
			console.log('Custom date changed:', date);
		},
		
		handleTableChange(pagination, filters, sorter) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		
		showAddPayrollModal() {
			this.payrollModalVisible = true;
			this.$nextTick(() => {
				this.payrollForm.resetFields();
			});
		},
		
		editPayroll(record) {
			// Implementar edición de planilla
			console.log('Edit payroll:', record);
		},
		
		viewPayroll(record) {
			this.selectedPayroll = record;
			this.detailsModalVisible = true;
		},
		
		async handlePayrollSubmit() {
			this.payrollForm.validateFields(async (err, values) => {
				if (!err) {
					try {
						const payload = {
							...values,
							payroll_period: values.payroll_period.format('YYYY-MM'),
							payment_date: values.payment_date.format('YYYY-MM-DD'),
							employees: this.targetKeys,
							include_bonuses: this.includeBonuses,
							include_overtime: this.includeOvertime,
							include_deductions: this.includeDeductions
						};
						
						await axios.post('http://localhost:8000/api/payroll/generate', payload, {
							headers: this.authHeaders()
						});
						
						this.$message.success('Planilla generada exitosamente');
						this.payrollModalVisible = false;
						this.fetchPayroll();
					} catch (error) {
						console.error('Error generating payroll:', error);
						this.$message.error('Error al generar la planilla');
					}
				}
			});
		},
		
		async processPayroll(record) {
			try {
				await axios.put(`http://localhost:8000/api/payroll/${record.id}/process`, {}, {
					headers: this.authHeaders()
				});
				this.$message.success('Planilla procesada exitosamente');
				this.fetchPayroll();
			} catch (error) {
				console.error('Error processing payroll:', error);
				this.$message.error('Error al procesar la planilla');
			}
		},
		
		async markAsPaid(record) {
			try {
				await axios.put(`http://localhost:8000/api/payroll/${record.id}/paid`, {}, {
					headers: this.authHeaders()
				});
				this.$message.success('Planilla marcada como pagada');
				this.fetchPayroll();
			} catch (error) {
				console.error('Error marking as paid:', error);
				this.$message.error('Error al marcar como pagada');
			}
		},
		
		async deletePayroll(record) {
			try {
				await axios.delete(`http://localhost:8000/api/payroll/${record.id}`, {
					headers: this.authHeaders()
				});
				this.$message.success('Planilla eliminada exitosamente');
				this.fetchPayroll();
			} catch (error) {
				console.error('Error deleting payroll:', error);
				this.$message.error('Error al eliminar la planilla');
			}
		},
		
		generatePayslip(record) {
			// Generar PDF del recibo de pago
			window.open(`http://localhost:8000/api/payroll/${record.id}/payslip`, '_blank');
		},
		
		async exportPayroll() {
			this.exporting = true;
			try {
				const response = await axios.get('http://localhost:8000/api/payroll/export', {
					headers: this.authHeaders(),
					responseType: 'blob'
				});
				
				// Descargar archivo
				const url = window.URL.createObjectURL(new Blob([response.data]));
				const link = document.createElement('a');
				link.href = url;
				link.setAttribute('download', `planilla_${new Date().toISOString().split('T')[0]}.xlsx`);
				document.body.appendChild(link);
				link.click();
				link.remove();
				
				this.$message.success('Planilla exportada exitosamente');
			} catch (error) {
				console.error('Error exporting payroll:', error);
				this.$message.error('Error al exportar la planilla');
			} finally {
				this.exporting = false;
			}
		},
		
		resetPayrollForm() {
			this.payrollForm.resetFields();
			this.targetKeys = [];
			this.selectedKeys = [];
			this.includeBonuses = false;
			this.includeOvertime = false;
			this.includeDeductions = true;
		},
		
		handleTransferChange(targetKeys) {
			this.targetKeys = targetKeys;
		},
		
		handleSelectChange(sourceSelectedKeys, targetSelectedKeys) {
			this.selectedKeys = [...sourceSelectedKeys, ...targetSelectedKeys];
		},
		
		formatCurrency(amount) {
			return new Intl.NumberFormat('es-HN', {
				style: 'currency',
				currency: 'HNL'
			}).format(amount);
		},
		
		formatDate(date) {
			return this.$moment(date).format('DD/MM/YYYY');
		},
		
		getStatusColor(status) {
			const colors = {
				pending: 'orange',
				processed: 'blue',
				paid: 'green',
				cancelled: 'red'
			};
			return colors[status] || 'default';
		},
		
		getStatusText(status) {
			const texts = {
				pending: 'Pendiente',
				processed: 'Procesado',
				paid: 'Pagado',
				cancelled: 'Cancelado'
			};
			return texts[status] || status;
		}
	}
})
</script>

<style scoped>
.table-header {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.table-header-top {
	display: flex;
	align-items: center;
}

.table-header-controls {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

.filters-section {
	background: #f5f5f5;
	padding: 16px;
	margin: 16px;
	border-radius: 8px;
}

/* Fix para selects con altura corta */
.filters-section .ant-select,
.table-header-controls .ant-select {
	min-height: 32px;
	line-height: 32px;
}

.filters-section .ant-select-selector,
.table-header-controls .ant-select-selector {
	min-height: 32px !important;
	height: 32px !important;
}

.filters-section .ant-select-selection__rendered,
.table-header-controls .ant-select-selection__rendered {
	line-height: 30px !important;
}

.filters-section .ant-select-selection__placeholder,
.table-header-controls .ant-select-selection__placeholder {
	line-height: 30px !important;
}

/* Fix para dropdown options visibility */
.ant-select-dropdown {
	z-index: 9999 !important;
}

.ant-select-dropdown-menu {
	max-height: 256px !important;
	overflow-y: auto !important;
}

.ant-select-dropdown-menu-item {
	height: auto !important;
	padding: 8px 12px !important;
	line-height: normal !important;
	white-space: nowrap !important;
}

/* Fix para dropdown positioning */
.ant-select-dropdown-placement-bottomLeft {
	top: 100% !important;
	left: 0 !important;
}

.ant-select-dropdown-placement-topLeft {
	bottom: 100% !important;
	left: 0 !important;
}

/* Fix para Transfer component titles */
.ant-transfer-list-header {
	height: auto !important;
	line-height: normal !important;
	padding: 12px 16px !important;
	white-space: normal !important;
	word-wrap: break-word !important;
}

.ant-transfer-list-header-title {
	display: block !important;
	line-height: normal !important;
	height: auto !important;
}

.employee-info {
	display: flex;
	flex-direction: column;
}

.employee-name {
	font-weight: 600;
	color: #262626;
}

.employee-details {
	display: flex;
	gap: 12px;
	font-size: 12px;
	color: #8c8c8c;
}

.salary-amount {
	font-weight: 600;
	color: #1890ff;
}

.net-pay-amount {
	font-weight: 700;
	color: #52c41a;
	font-size: 14px;
}

.deductions-breakdown {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.deduction-item {
	display: flex;
	justify-content: space-between;
	font-size: 12px;
}

.deduction-label {
	color: #8c8c8c;
}

.deduction-value {
	color: #262626;
}

.deduction-total {
	display: flex;
	justify-content: space-between;
	font-weight: 600;
	border-top: 1px solid #d9d9d9;
	padding-top: 4px;
	margin-top: 4px;
}

.total-label {
	color: #262626;
}

.total-value {
	color: #cf1322;
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

.payroll-summary {
	background: #fafafa;
	padding: 16px;
	margin: 16px;
	border-radius: 8px;
	border: 1px solid #d9d9d9;
}

@media (max-width: 768px) {
	.table-header-controls {
		justify-content: flex-start;
	}
	.filters-section .ant-row {
		flex-direction: column;
	}
	.filters-section .ant-col {
		width: 100% !important;
		margin-bottom: 8px;
	}
}
</style>
