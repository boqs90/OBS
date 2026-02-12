<template>
	<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
		<template #title>
			<div class="table-header">
				<div class="table-header-top">
					<h6 class="mb-0">Contabilidad - Sistema SAR</h6>
				</div>
				<div class="table-header-controls">
					<a-input-search
						v-model="searchText"
						placeholder="Buscar por número de factura, cliente o concepto..."
						style="width: 300px"
						allow-clear
						@search="handleSearch"
					/>
					<a-select
						v-model="selectedPeriod"
						placeholder="Período Fiscal"
						style="width: 150px; min-height: 32px;"
						:getPopupContainer="() => document.body"
						@change="handlePeriodChange"
					>
						<a-select-option value="current">Mes Actual</a-select-option>
						<a-select-option value="last">Mes Anterior</a-select-option>
						<a-select-option value="quarter">Trimestre</a-select-option>
						<a-select-option value="year">Año Fiscal</a-select-option>
					</a-select>
					<a-button type="primary" @click="showAddTransactionModal">
						<a-icon type="plus" /> Nueva Transacción
					</a-button>
				</div>
			</div>
		</template>

		<!-- Filtros SAR -->
		<div class="filters-section">
			<a-row :gutter="16">
				<a-col :span="6">
					<a-select
						v-model="filterType"
						placeholder="Tipo de Comprobante"
						allow-clear
						@change="handleFilterChange"
						style="min-height: 32px;"
						:getPopupContainer="() => document.body"
					>
						<a-select-option value="factura">Factura</a-select-option>
						<a-select-option value="credito_fiscal">Crédito Fiscal</a-select-option>
						<a-select-option value="nota_credito">Nota de Crédito</a-select-option>
						<a-select-option value="nota_debito">Nota de Débito</a-select-option>
						<a-select-option value="comprobante_retencion">Comprobante de Retención</a-select-option>
					</a-select>
				</a-col>
				<a-col :span="6">
					<a-select
						v-model="filterStatus"
						placeholder="Estado SAR"
						allow-clear
						@change="handleFilterChange"
						style="min-height: 32px;"
						:getPopupContainer="() => document.body"
					>
						<a-select-option value="pending">Pendiente</a-select-option>
						<a-select-option value="submitted">Enviado a SAR</a-select-option>
						<a-select-option value="accepted">Aceptado</a-select-option>
						<a-select-option value="rejected">Rechazado</a-select-option>
					</a-select>
				</a-col>
				<a-col :span="6">
					<a-date-picker
						v-model="dateRange"
						mode="range"
						placeholder="Rango de fechas"
						style="width: 100%; min-height: 32px;"
						@change="handleDateChange"
					/>
				</a-col>
				<a-col :span="6">
					<a-button @click="exportSARReport" :loading="exporting">
						<a-icon type="download" /> Exportar SAR
					</a-button>
				</a-col>
			</a-row>
		</div>

		<!-- Tabla de Transacciones Contables -->
		<a-table
			:columns="columns"
			:data-source="filteredTransactions"
			:loading="loading"
			:pagination="pagination"
			@change="handleTableChange"
			row-key="id"
		>
			<!-- Columna de Factura -->
			<template #invoice="text, record">
				<div class="invoice-info">
					<div class="invoice-number">{{ record.invoice_number }}</div>
					<div class="invoice-type">
						<a-tag :color="getInvoiceTypeColor(record.invoice_type)">
							{{ getInvoiceTypeText(record.invoice_type) }}
						</a-tag>
					</div>
				</div>
			</template>

			<!-- Columna de Cliente -->
			<template #client="text, record">
				<div class="client-info">
					<div class="client-name">{{ record.client_name }}</div>
					<div class="client-details">
						<span>RTN: {{ record.client_rtn }}</span>
					</div>
				</div>
			</template>

			<!-- Columna de Montos -->
			<template #amounts="text, record">
				<div class="amounts-breakdown">
					<div class="amount-item">
						<span class="amount-label">Gravado:</span>
						<span class="amount-value">{{ formatCurrency(record.taxed_amount) }}</span>
					</div>
					<div class="amount-item">
						<span class="amount-label">Exento:</span>
						<span class="amount-value">{{ formatCurrency(record.exempt_amount) }}</span>
					</div>
					<div class="amount-item">
						<span class="amount-label">IVA (15%):</span>
						<span class="amount-value">{{ formatCurrency(record.iva_amount) }}</span>
					</div>
					<div class="amount-total">
						<span class="total-label">Total:</span>
						<span class="total-value">{{ formatCurrency(record.total_amount) }}</span>
					</div>
				</div>
			</template>

			<!-- Columna de Estado SAR -->
			<template #sar_status="text">
				<a-tag :color="getSARStatusColor(text)">
					{{ getSARStatusText(text) }}
				</a-tag>
			</template>

			<!-- Columna de CO -->
			<template #co_number="text, record">
				<div v-if="record.co_number">
					<span class="co-number">{{ record.co_number }}</span>
					<div class="co-date">{{ formatDate(record.co_date) }}</div>
				</div>
				<span v-else class="no-co">Sin CO</span>
			</template>

			<!-- Columna de Acciones -->
			<template #action="text, record">
				<div class="table-actions">
					<a-button size="small" type="primary" ghost @click="viewTransaction(record)">
						<a-icon type="eye" /> Ver
					</a-button>
					<a-button 
						size="small" 
						type="primary" 
						ghost 
						@click="editTransaction(record)"
						:disabled="record.sar_status === 'submitted'"
					>
						<a-icon type="edit" /> Editar
					</a-button>
					<a-dropdown>
						<a-menu slot="overlay">
							<a-menu-item @click="submitToSAR(record)" :disabled="record.sar_status !== 'pending'">
								<a-icon type="upload" /> Enviar a SAR
							</a-menu-item>
							<a-menu-item @click="generatePDF(record)">
								<a-icon type="file-pdf" /> Generar PDF
							</a-menu-item>
							<a-menu-item @click="sendEmail(record)">
								<a-icon type="mail" /> Enviar por Email
							</a-menu-item>
							<a-menu-divider />
							<a-menu-item @click="deleteTransaction(record)" :disabled="record.sar_status === 'submitted'">
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

		<!-- Resumen Contable -->
		<div class="accounting-summary">
			<a-row :gutter="16">
				<a-col :span="6">
					<a-statistic
						title="Total Transacciones"
						:value="filteredTransactions.length"
						:style="{ color: '#3f8600' }"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total Gravado"
						:value="totalTaxedAmount"
						:precision="2"
						:style="{ color: '#1890ff' }"
						:formatter="formatCurrency"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total IVA (15%)"
						:value="totalIVAAmount"
						:precision="2"
						:style="{ color: '#cf1322' }"
						:formatter="formatCurrency"
					/>
				</a-col>
				<a-col :span="6">
					<a-statistic
						title="Total General"
						:value="totalGeneralAmount"
						:precision="2"
						:style="{ color: '#52c41a' }"
						:formatter="formatCurrency"
					/>
				</a-col>
			</a-row>
		</div>

		<!-- Modal de Nueva Transacción -->
		<a-modal
			v-model="transactionModalVisible"
			title="Nueva Transacción Contable"
			:width="800"
			@ok="handleTransactionSubmit"
			@cancel="resetTransactionForm"
		>
			<a-form :form="transactionForm" layout="vertical">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Tipo de Comprobante">
							<a-select
								v-decorator="[
									'invoice_type',
									{ rules: [{ required: true, message: 'Por favor seleccione el tipo' }] }
								]"
								placeholder="Seleccione tipo de comprobante"
							>
								<a-select-option value="factura">Factura</a-select-option>
								<a-select-option value="credito_fiscal">Crédito Fiscal</a-select-option>
								<a-select-option value="nota_credito">Nota de Crédito</a-select-option>
								<a-select-option value="nota_debito">Nota de Débito</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Número de Factura">
							<a-input
								v-decorator="[
									'invoice_number',
									{ rules: [{ required: true, message: 'Por favor ingrese el número' }] }
								]"
								placeholder="Ej: 001-01-2024-00001"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Nombre del Cliente">
							<a-input
								v-decorator="[
									'client_name',
									{ rules: [{ required: true, message: 'Por favor ingrese el nombre' }] }
								]"
								placeholder="Nombre completo del cliente"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="RTN del Cliente">
							<a-input
								v-decorator="[
									'client_rtn',
									{ rules: [{ required: true, message: 'Por favor ingrese el RTN' }] }
								]"
								placeholder="RTN del cliente"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="8">
						<a-form-item label="Monto Gravado">
							<a-input-number
								v-decorator="[
									'taxed_amount',
									{ initialValue: 0 }
								]"
								:min="0"
								:precision="2"
								style="width: 100%"
								formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
								parser="value => value.replace(/\$\s?|(,*)/g, '')"
								@change="calculateTotals"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="Monto Exento">
							<a-input-number
								v-decorator="[
									'exempt_amount',
									{ initialValue: 0 }
								]"
								:min="0"
								:precision="2"
								style="width: 100%"
								formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
								parser="value => value.replace(/\$\s?|(,*)/g, '')"
								@change="calculateTotals"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="IVA (15%)">
							<a-input-number
								v-decorator="[
									'iva_amount',
									{ initialValue: 0 }
								]"
								:min="0"
								:precision="2"
								style="width: 100%"
								disabled
								formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
								parser="value => value.replace(/\$\s?|(,*)/g, '')"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Total">
							<a-input-number
								v-decorator="[
									'total_amount',
									{ initialValue: 0 }
								]"
								:min="0"
								:precision="2"
								style="width: 100%"
								disabled
								formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
								parser="value => value.replace(/\$\s?|(,*)/g, '')"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Fecha de Emisión">
							<a-date-picker
								v-decorator="[
									'issue_date',
									{ rules: [{ required: true, message: 'Por favor seleccione la fecha' }] }
								]"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Descripción/Concepto">
					<a-textarea
						v-decorator="['description']"
						placeholder="Descripción de la transacción"
						:rows="3"
					/>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal de Ver Detalles -->
		<a-modal
			v-model="detailsModalVisible"
			title="Detalles de Transacción"
			:width="800"
			:footer="null"
		>
			<div v-if="selectedTransaction">
				<a-descriptions :column="2" bordered>
					<a-descriptions-item label="Tipo de Comprobante">
						<a-tag :color="getInvoiceTypeColor(selectedTransaction.invoice_type)">
							{{ getInvoiceTypeText(selectedTransaction.invoice_type) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Número de Factura">
						{{ selectedTransaction.invoice_number }}
					</a-descriptions-item>
					<a-descriptions-item label="Cliente">
						{{ selectedTransaction.client_name }}
					</a-descriptions-item>
					<a-descriptions-item label="RTN">
						{{ selectedTransaction.client_rtn }}
					</a-descriptions-item>
					<a-descriptions-item label="Fecha de Emisión">
						{{ formatDate(selectedTransaction.issue_date) }}
					</a-descriptions-item>
					<a-descriptions-item label="Estado SAR">
						<a-tag :color="getSARStatusColor(selectedTransaction.sar_status)">
							{{ getSARStatusText(selectedTransaction.sar_status) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="CO">
						<span v-if="selectedTransaction.co_number">
							{{ selectedTransaction.co_number }} ({{ formatDate(selectedTransaction.co_date) }})
						</span>
						<span v-else>Sin CO</span>
					</a-descriptions-item>
					<a-descriptions-item label="Descripción">
						{{ selectedTransaction.description || 'Sin descripción' }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div style="margin-top: 20px;">
					<h6>Desglose de Montos</h6>
					<a-table
						:columns="amountColumns"
						:data-source="amountData"
						:pagination="false"
						size="small"
					>
						<template #amount="text">
							{{ formatCurrency(text) }}
						</template>
					</a-table>
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
			transactions: [],
			searchText: '',
			selectedPeriod: 'current',
			filterType: null,
			filterStatus: null,
			dateRange: null,
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} registros`
			},
			transactionModalVisible: false,
			detailsModalVisible: false,
			selectedTransaction: null,
			transactionForm: this.$form.createForm(this),
			columns: [
				{
					title: 'Factura',
					dataIndex: 'invoice_number',
					key: 'invoice_number',
					width: 150,
					scopedSlots: { customRender: 'invoice' }
				},
				{
					title: 'Cliente',
					dataIndex: 'client_name',
					key: 'client_name',
					width: 200,
					scopedSlots: { customRender: 'client' }
				},
				{
					title: 'Montos',
					key: 'amounts',
					width: 200,
					scopedSlots: { customRender: 'amounts' }
				},
				{
					title: 'Estado SAR',
					dataIndex: 'sar_status',
					key: 'sar_status',
					width: 120,
					scopedSlots: { customRender: 'sar_status' }
				},
				{
					title: 'CO',
					key: 'co_number',
					width: 120,
					scopedSlots: { customRender: 'co_number' }
				},
				{
					title: 'Fecha',
					dataIndex: 'issue_date',
					key: 'issue_date',
					width: 100,
					sorter: true
				},
				{
					title: 'Acciones',
					key: 'action',
					width: 200,
					scopedSlots: { customRender: 'action' }
				}
			],
			amountColumns: [
				{
					title: 'Concepto',
					dataIndex: 'concept',
					key: 'concept'
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
		filteredTransactions() {
			let filtered = this.transactions;
			
			// Filtro de búsqueda
			if (this.searchText) {
				const searchLower = this.searchText.toLowerCase();
				filtered = filtered.filter(record => 
					record.invoice_number.toLowerCase().includes(searchLower) ||
					record.client_name.toLowerCase().includes(searchLower) ||
					(record.description && record.description.toLowerCase().includes(searchLower))
				);
			}
			
			// Filtro de tipo
			if (this.filterType) {
				filtered = filtered.filter(record => record.invoice_type === this.filterType);
			}
			
			// Filtro de estado
			if (this.filterStatus) {
				filtered = filtered.filter(record => record.sar_status === this.filterStatus);
			}
			
			return filtered;
		},
		
		totalTaxedAmount() {
			return this.filteredTransactions.reduce((sum, record) => sum + record.taxed_amount, 0);
		},
		
		totalIVAAmount() {
			return this.filteredTransactions.reduce((sum, record) => sum + record.iva_amount, 0);
		},
		
		totalGeneralAmount() {
			return this.filteredTransactions.reduce((sum, record) => sum + record.total_amount, 0);
		},
		
		amountData() {
			if (!this.selectedTransaction) return [];
			
			return [
				{
					concept: 'Monto Gravado',
					amount: this.selectedTransaction.taxed_amount
				},
				{
					concept: 'Monto Exento',
					amount: this.selectedTransaction.exempt_amount
				},
				{
					concept: 'IVA (15%)',
					amount: this.selectedTransaction.iva_amount
				},
				{
					concept: 'Total',
					amount: this.selectedTransaction.total_amount
				}
			];
		}
	},
	created() {
		this.fetchTransactions();
	},
	methods: {
		authHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		
		async fetchTransactions() {
			this.loading = true;
			try {
				const response = await axios.get('http://localhost:8000/api/accounting/transactions', { 
					headers: this.authHeaders() 
				});
				this.transactions = response.data || [];
				this.pagination.total = this.transactions.length;
				console.log('Accounting transactions loaded:', this.transactions);
			} catch (error) {
				console.error('Error fetching transactions:', error);
				console.error('Error response:', error.response);
				this.$message.error('Error al cargar las transacciones');
				
				// Cargar datos de ejemplo para desarrollo
				this.loadMockData();
			} finally {
				this.loading = false;
			}
		},
		
		loadMockData() {
			// Datos de ejemplo para desarrollo
			this.transactions = [
				{
					id: 1,
					invoice_type: 'factura',
					invoice_number: '001-01-2024-00001',
					client_name: 'Juan Pérez García',
					client_rtn: '0801-1990-12345',
					taxed_amount: 10000,
					exempt_amount: 2000,
					iva_amount: 1500,
					total_amount: 13500,
					sar_status: 'accepted',
					co_number: 'DTE-01-2024-00001',
					co_date: '2024-02-01',
					issue_date: '2024-02-01',
					description: 'Matrícula estudiantil - Primer grado'
				},
				{
					id: 2,
					invoice_type: 'credito_fiscal',
					invoice_number: 'CF-01-2024-00001',
					client_name: 'María Rodríguez López',
					client_rtn: '0801-1985-67890',
					taxed_amount: 15000,
					exempt_amount: 0,
					iva_amount: 2250,
					total_amount: 17250,
					sar_status: 'submitted',
					co_number: null,
					co_date: null,
					issue_date: '2024-02-05',
					description: 'Servicios educativos - Secundaria'
				},
				{
					id: 3,
					invoice_type: 'factura',
					invoice_number: '001-01-2024-00002',
					client_name: 'Carlos Hernández Martínez',
					client_rtn: '0801-1992-11111',
					taxed_amount: 8000,
					exempt_amount: 5000,
					iva_amount: 1200,
					total_amount: 14200,
					sar_status: 'pending',
					co_number: null,
					co_date: null,
					issue_date: '2024-02-10',
					description: 'Uniformes escolares y útiles'
				}
			];
			this.pagination.total = this.transactions.length;
			console.log('Mock accounting data loaded:', this.transactions);
		},
		
		handleSearch() {
			this.pagination.current = 1;
		},
		
		handleFilterChange() {
			this.pagination.current = 1;
		},
		
		handlePeriodChange(value) {
			console.log('Period changed:', value);
		},
		
		handleDateChange(dates) {
			this.dateRange = dates;
			console.log('Date range changed:', dates);
		},
		
		handleTableChange(pagination, filters, sorter) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		
		showAddTransactionModal() {
			this.transactionModalVisible = true;
			this.$nextTick(() => {
				this.transactionForm.resetFields();
			});
		},
		
		editTransaction(record) {
			console.log('Edit transaction:', record);
		},
		
		viewTransaction(record) {
			this.selectedTransaction = record;
			this.detailsModalVisible = true;
		},
		
		async handleTransactionSubmit() {
			this.transactionForm.validateFields(async (err, values) => {
				if (!err) {
					try {
						const payload = {
							...values,
							issue_date: values.issue_date.format('YYYY-MM-DD')
						};
						
						await axios.post('http://localhost:8000/api/accounting/transactions', payload, {
							headers: this.authHeaders()
						});
						
						this.$message.success('Transacción registrada exitosamente');
						this.transactionModalVisible = false;
						this.fetchTransactions();
					} catch (error) {
						console.error('Error saving transaction:', error);
						this.$message.error('Error al guardar la transacción');
					}
				}
			});
		},
		
		async submitToSAR(record) {
			try {
				await axios.put(`http://localhost:8000/api/accounting/transactions/${record.id}/submit-to-sar`, {}, {
					headers: this.authHeaders()
				});
				this.$message.success('Transacción enviada a SAR exitosamente');
				this.fetchTransactions();
			} catch (error) {
				console.error('Error submitting to SAR:', error);
				this.$message.error('Error al enviar a SAR');
			}
		},
		
		generatePDF(record) {
			window.open(`http://localhost:8000/api/accounting/transactions/${record.id}/pdf`, '_blank');
		},
		
		sendEmail(record) {
			this.$message.info('Función de envío por email en desarrollo');
		},
		
		async deleteTransaction(record) {
			try {
				await axios.delete(`http://localhost:8000/api/accounting/transactions/${record.id}`, {
					headers: this.authHeaders()
				});
				this.$message.success('Transacción eliminada exitosamente');
				this.fetchTransactions();
			} catch (error) {
				console.error('Error deleting transaction:', error);
				this.$message.error('Error al eliminar la transacción');
			}
		},
		
		async exportSARReport() {
			this.exporting = true;
			try {
				const response = await axios.get('http://localhost:8000/api/accounting/export-sar', {
					headers: this.authHeaders(),
					responseType: 'blob'
				});
				
				// Descargar archivo
				const url = window.URL.createObjectURL(new Blob([response.data]));
				const link = document.createElement('a');
				link.href = url;
				link.setAttribute('download', `sar_report_${new Date().toISOString().split('T')[0]}.xlsx`);
				document.body.appendChild(link);
				link.click();
				link.remove();
				
				this.$message.success('Reporte SAR exportado exitosamente');
			} catch (error) {
				console.error('Error exporting SAR report:', error);
				this.$message.error('Error al exportar el reporte SAR');
			} finally {
				this.exporting = false;
			}
		},
		
		resetTransactionForm() {
			this.transactionForm.resetFields();
		},
		
		calculateTotals() {
			const form = this.transactionForm;
			const taxedAmount = form.getFieldValue('taxed_amount') || 0;
			const exemptAmount = form.getFieldValue('exempt_amount') || 0;
			const ivaAmount = taxedAmount * 0.15; // 15% IVA
			const totalAmount = taxedAmount + exemptAmount + ivaAmount;
			
			form.setFieldsValue({
				iva_amount: ivaAmount,
				total_amount: totalAmount
			});
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
		
		getInvoiceTypeColor(type) {
			const colors = {
				factura: 'blue',
				credito_fiscal: 'green',
				nota_credito: 'orange',
				nota_debito: 'red',
				comprobante_retencion: 'purple'
			};
			return colors[type] || 'default';
		},
		
		getInvoiceTypeText(type) {
			const texts = {
				factura: 'Factura',
				credito_fiscal: 'Crédito Fiscal',
				nota_credito: 'Nota de Crédito',
				nota_debito: 'Nota de Débito',
				comprobante_retencion: 'Comp. Retención'
			};
			return texts[type] || type;
		},
		
		getSARStatusColor(status) {
			const colors = {
				pending: 'orange',
				submitted: 'blue',
				accepted: 'green',
				rejected: 'red'
			};
			return colors[status] || 'default';
		},
		
		getSARStatusText(status) {
			const texts = {
				pending: 'Pendiente',
				submitted: 'Enviado',
				accepted: 'Aceptado',
				rejected: 'Rechazado'
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

.invoice-info {
	display: flex;
	flex-direction: column;
}

.invoice-number {
	font-weight: 600;
	color: #262626;
}

.invoice-type {
	margin-top: 4px;
}

.client-info {
	display: flex;
	flex-direction: column;
}

.client-name {
	font-weight: 600;
	color: #262626;
}

.client-details {
	font-size: 12px;
	color: #8c8c8c;
	margin-top: 2px;
}

.amounts-breakdown {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.amount-item {
	display: flex;
	justify-content: space-between;
	font-size: 12px;
}

.amount-label {
	color: #8c8c8c;
}

.amount-value {
	color: #262626;
}

.amount-total {
	display: flex;
	justify-content: space-between;
	font-weight: 600;
	border-top: 1px solid #d9d9d9;
	padding-top: 2px;
	margin-top: 2px;
}

.total-label {
	color: #262626;
}

.total-value {
	color: #1890ff;
}

.co-number {
	font-weight: 600;
	color: #52c41a;
}

.co-date {
	font-size: 11px;
	color: #8c8c8c;
}

.no-co {
	color: #8c8c8c;
	font-style: italic;
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

.accounting-summary {
	background: #fafafa;
	padding: 16px;
	margin: 16px;
	border-radius: 8px;
	border: 1px solid #d9d9d9;
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
