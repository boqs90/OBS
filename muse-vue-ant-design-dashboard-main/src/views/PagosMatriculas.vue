<template>
	<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
		<template #title>
			<div class="table-header">
				<div class="table-header-top">
					<h6 class="mb-0">Pagos de Matrículas</h6>
				</div>
				<div class="table-header-controls">
					<a-input-search
						v-model="searchText"
						placeholder="Buscar por nombre del padre, nombre del alumno, identidad o número de matrícula..."
						style="width: 300px"
						allow-clear
						@search="handleSearch"
					/>
					<a-button type="primary" @click="showAddPaymentModal">
						<a-icon type="plus" /> Nuevo Pago
					</a-button>
				</div>
			</div>
		</template>

		<!-- Tabla de Pagos -->
		<a-table
			:columns="columns"
			:data-source="filteredPayments"
			:loading="loading"
			:pagination="pagination"
			@change="handleTableChange"
			row-key="id"
		>
			<!-- Columna de Estado -->
			<template #status="text, record">
				<a-tag :color="getStatusColor(record.status)">
					{{ getStatusText(record.status) }}
				</a-tag>
			</template>

			<!-- Columna de Monto -->
			<template #amount="text">
				<span class="font-weight-bold">
					{{ formatCurrency(text) }}
				</span>
			</template>

			<!-- Columna de Fecha -->
			<template #date="text">
				{{ formatDate(text) }}
			</template>

			<!-- Columna de Acciones -->
			<template #action="text, record">
				<div class="table-actions">
					<a-button size="small" type="primary" ghost @click="viewPayment(record)">
						<a-icon type="eye" /> Ver
					</a-button>
					<a-button 
						size="small" 
						type="primary" 
						ghost 
						@click="editPayment(record)"
						:disabled="record.status === 'completed'"
					>
						<a-icon type="edit" /> Editar
					</a-button>
					<a-popconfirm
						title="¿Estás seguro de eliminar este pago?"
						ok-text="Sí"
						cancel-text="No"
						@confirm="deletePayment(record)"
					>
						<a-button size="small" type="danger" ghost>
							<a-icon type="delete" /> Eliminar
						</a-button>
					</a-popconfirm>
				</div>
			</template>
		</a-table>

		<!-- Modal de Nuevo/Editar Pago -->
		<a-modal
			v-model="paymentModalVisible"
			:title="editingPayment ? 'Editar Pago' : 'Nuevo Pago de Matrícula'"
			:width="800"
			@ok="handlePaymentSubmit"
			@cancel="resetPaymentForm"
		>
			<a-form :form="paymentForm" layout="vertical">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Alumno">
							<a-select
								v-decorator="[
									'student_id',
									{ rules: [{ required: true, message: 'Por favor seleccione un alumno' }] }
								]"
								placeholder="Seleccione un alumno"
								show-search
								:filter-option="filterStudentOption"
								@change="handleStudentChange"
							>
								<a-select-option v-for="student in students" :key="student.id" :value="student.id">
									{{ student.name }} - {{ student.enrollment_number }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Padre/Madre/Tutor">
							<a-input
								v-decorator="[
									'parent_name',
									{ rules: [{ required: true, message: 'Por favor ingrese el nombre del padre/madre/tutor' }] }
								]"
								placeholder="Nombre completo del padre/madre/tutor"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Identidad del Padre/Madre/Tutor">
							<a-input
								v-decorator="[
									'parent_identity',
									{ rules: [{ required: true, message: 'Por favor ingrese la identidad' }] }
								]"
								placeholder="Número de identidad"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Monto">
							<a-input-number
								v-decorator="[
									'amount',
									{ 
										rules: [{ required: true, message: 'Por favor ingrese el monto' }],
										initialValue: 0
									}
								]"
								:min="0"
								:precision="2"
								style="width: 100%"
								formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
								parser="value => value.replace(/\$\s?|(,*)/g, '')"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Método de Pago">
							<a-select
								v-decorator="[
									'payment_method',
									{ rules: [{ required: true, message: 'Por favor seleccione el método de pago' }] }
								]"
								placeholder="Seleccione método de pago"
								@change="handlePaymentMethodChange"
							>
								<a-select-option value="cash">Efectivo</a-select-option>
								<a-select-option value="card">Tarjeta (Débito Automático)</a-select-option>
								<a-select-option value="transfer">Transferencia</a-select-option>
								<a-select-option value="check">Cheque</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Fecha de Pago">
							<a-date-picker
								v-decorator="[
									'payment_date',
									{ rules: [{ required: true, message: 'Por favor seleccione la fecha' }] }
								]"
								style="width: 100%"
								format="DD/MM/YYYY"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<!-- Información de Transferencia -->
				<div v-if="showTransferInfo" class="transfer-info-section">
					<a-divider><a-icon type="bank" /> Información de Transferencia</a-divider>
					
					<a-row :gutter="16">
						<a-col :span="12">
							<a-form-item label="Banco Origen">
								<a-select
									v-decorator="[
										'transfer_source_bank',
										{ rules: [{ required: true, message: 'Por favor seleccione el banco de origen' }] }
									]"
									placeholder="Seleccione el banco de origen"
								>
									<a-select-option value="bac">BAC Credomatic</a-select-option>
									<a-select-option value="atlantida">Banco Atlántida</a-select-option>
									<a-select-option value="ficensa">Banco Ficensa</a-select-option>
									<a-select-option value="honduras">Banco de Honduras</a-select-option>
									<a-select-option value="azteca">Banco Azteca</a-select-option>
									<a-select-option value="occidente">Banco Occidente</a-select-option>
									<a-select-option value="other">Otro</a-select-option>
								</a-select>
							</a-form-item>
						</a-col>
						<a-col :span="12">
							<a-form-item label="Número de Referencia">
								<a-input
									v-decorator="[
										'transfer_reference',
										{ rules: [{ required: true, message: 'Por favor ingrese el número de referencia' }] }
									]"
									placeholder="Número de referencia de la transferencia"
								/>
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :span="12">
							<a-form-item label="Fecha de Transferencia">
								<a-date-picker
									v-decorator="[
										'transfer_date',
										{ rules: [{ required: true, message: 'Por favor seleccione la fecha de transferencia' }] }
									]"
									style="width: 100%"
									format="DD/MM/YYYY"
								/>
							</a-form-item>
						</a-col>
						<a-col :span="12">
							<a-form-item label="Cuenta Origen">
								<a-input
									v-decorator="[
										'transfer_account',
										{ rules: [{ required: true, message: 'Por favor ingrese el número de cuenta' }] }
									]"
									placeholder="Número de cuenta de origen"
								/>
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :span="24">
							<a-form-item label="Comprobante de Transferencia">
								<a-upload
									name="transfer_receipt"
									list-type="picture-card"
									class="receipt-uploader"
									:show-upload-list="true"
									:before-upload="beforeUploadReceipt"
									:custom-request="handleReceiptUpload"
									@change="handleReceiptChange"
									@preview="handlePreview"
									:file-list="receiptFileList"
								>
									<div v-if="receiptFileList.length < 1">
										<a-icon type="camera" />
										<div class="ant-upload-text">Subir Comprobante</div>
										<div class="ant-upload-hint">
											JPG, PNG o PDF (Máx. 5MB)
										</div>
									</div>
								</a-upload>
								
								<!-- Modal para vista previa del comprobante -->
								<a-modal
									v-model="previewVisible"
									:title="previewTitle"
									:footer="null"
									:width="800"
								>
									<img alt="preview" style="width: 100%" :src="previewImage" />
								</a-modal>
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :span="24">
							<a-form-item label="Notas de Transferencia">
								<a-textarea
									v-decorator="['transfer_notes']"
									placeholder="Notas adicionales sobre la transferencia"
									:rows="2"
								/>
							</a-form-item>
						</a-col>
					</a-row>
				</div>

				<!-- Información de Tarjeta para Débito Automático -->
				<div v-if="showCardInfo" class="card-info-section">
					<a-divider><a-icon type="credit-card" /> Información de Tarjeta (Débito Automático)</a-divider>
					
					<a-row :gutter="16">
						<a-col :span="24">
							<div class="credit-card-container">
								<div class="credit-card">
									<div class="card-header">
										<div class="card-chip">
											<svg width="40" height="30" viewBox="0 0 40 30" fill="none">
												<rect width="40" height="30" rx="4" fill="#FFD700"/>
												<rect x="8" y="10" width="24" height="2" fill="#333"/>
												<rect x="8" y="18" width="16" height="2" fill="#333"/>
											</svg>
										</div>
										<div class="card-type">
											<span v-if="cardType">{{ cardType }}</span>
											<span v-else>CARD</span>
										</div>
									</div>
									<div class="card-body">
										<div class="card-number">
											{{ formatCardNumber(cardNumber) || '#### #### #### ####' }}
										</div>
										<div class="card-info">
											<div class="card-holder">
												<span class="label">CARD HOLDER</span>
												<span class="value">{{ cardHolderName || 'NAME ON CARD' }}</span>
											</div>
											<div class="card-expiry">
												<span class="label">EXPIRES</span>
												<span class="value">{{ cardExpiry || 'MM/YY' }}</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</a-col>
					</a-row>

					<a-row :gutter="16" style="margin-top: 20px;">
						<a-col :span="16">
							<a-form-item label="Número de Tarjeta">
								<a-input
									v-decorator="[
										'card_number',
										{ 
											rules: [
												{ required: true, message: 'Por favor ingrese el número de tarjeta' },
												{ len: 19, message: 'El número de tarjeta debe tener 16 dígitos' }
											]
										}
									]"
									placeholder="#### #### #### ####"
									:max-length="19"
									@input="formatCardInput"
								/>
							</a-form-item>
						</a-col>
						<a-col :span="8">
							<a-form-item label="CVV">
								<a-input
									v-decorator="[
										'card_cvv',
										{ 
											rules: [
												{ required: true, message: 'Por favor ingrese el CVV' },
												{ len: 3, message: 'El CVV debe tener 3 dígitos' }
											]
										}
									]"
									placeholder="###"
									:max-length="3"
									type="password"
								/>
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :span="12">
							<a-form-item label="Nombre del Titular">
								<a-input
									v-decorator="[
										'card_holder_name',
										{ rules: [{ required: true, message: 'Por favor ingrese el nombre del titular' }] }
									]"
									placeholder="Nombre como aparece en la tarjeta"
									@input="updateCardHolder"
								/>
							</a-form-item>
						</a-col>
						<a-col :span="12">
							<a-form-item label="Fecha de Vencimiento">
								<a-input
									v-decorator="[
										'card_expiry',
										{ 
											rules: [
												{ required: true, message: 'Por favor ingrese la fecha de vencimiento' },
												{ pattern: /^(0[1-9]|1[0-2])\/\d{2}$/, message: 'Formato MM/AA' }
											]
										}
									]"
									placeholder="MM/AA"
									:max-length="5"
									@input="formatExpiryInput"
								/>
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :span="12">
							<a-form-item label="Banco Emisor">
								<a-select
									v-decorator="[
										'card_bank',
										{ rules: [{ required: true, message: 'Por favor seleccione el banco' }] }
									]"
									placeholder="Seleccione el banco"
								>
									<a-select-option value="bac">BAC Credomatic</a-select-option>
									<a-select-option value="atlantida">Banco Atlántida</a-select-option>
									<a-select-option value="ficensa">Banco Ficensa</a-select-option>
									<a-select-option value="honduras">Banco de Honduras</a-select-option>
									<a-select-option value="azteca">Banco Azteca</a-select-option>
									<a-select-option value="occidente">Banco Occidente</a-select-option>
									<a-select-option value="other">Otro</a-select-option>
								</a-select>
							</a-form-item>
						</a-col>
						<a-col :span="12">
							<a-form-item label="Autorización Débito Automático">
								<a-checkbox
									v-decorator="[
										'automatic_debit_authorization',
										{ 
											rules: [{ required: true, message: 'Debe autorizar el débito automático' }],
											valuePropName: 'checked'
										}
									]"
								>
									Autorizo débitos automáticos mensuales
								</a-checkbox>
							</a-form-item>
						</a-col>
					</a-row>
				</div>

				<a-form-item label="Notas">
					<a-textarea
						v-decorator="['notes']"
						placeholder="Notas adicionales sobre el pago"
						:rows="3"
					/>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal de Ver Detalles -->
		<a-modal
			v-model="detailsModalVisible"
			title="Detalles del Pago"
			:width="600"
			:footer="null"
		>
			<div v-if="selectedPayment">
				<a-descriptions :column="2" bordered>
					<a-descriptions-item label="Alumno">
						{{ selectedPayment.student_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Número de Matrícula">
						{{ selectedPayment.enrollment_number }}
					</a-descriptions-item>
					<a-descriptions-item label="Padre/Madre/Tutor">
						{{ selectedPayment.parent_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Identidad">
						{{ selectedPayment.parent_identity }}
					</a-descriptions-item>
					<a-descriptions-item label="Monto">
						<span class="font-weight-bold">{{ formatCurrency(selectedPayment.amount) }}</span>
					</a-descriptions-item>
					<a-descriptions-item label="Estado">
						<a-tag :color="getStatusColor(selectedPayment.status)">
							{{ getStatusText(selectedPayment.status) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Método de Pago">
						{{ getPaymentMethodText(selectedPayment.payment_method) }}
					</a-descriptions-item>
					<a-descriptions-item label="Fecha de Pago">
						{{ formatDate(selectedPayment.payment_date) }}
					</a-descriptions-item>
					<a-descriptions-item label="Fecha de Registro">
						{{ formatDate(selectedPayment.created_at) }}
					</a-descriptions-item>
					<a-descriptions-item label="Registrado por">
						{{ selectedPayment.created_by_name }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div v-if="selectedPayment.notes" style="margin-top: 20px;">
					<h6>Notas:</h6>
					<p>{{ selectedPayment.notes }}</p>
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
			payments: [],
			students: [],
			searchText: '',
			showCardInfo: false,
			showTransferInfo: false,
			cardNumber: '',
			cardHolderName: '',
			cardExpiry: '',
			cardType: '',
			receiptFileList: [],
			previewVisible: false,
			previewImage: '',
			previewTitle: '',
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} registros`
			},
			paymentModalVisible: false,
			detailsModalVisible: false,
			editingPayment: null,
			selectedPayment: null,
			paymentForm: this.$form.createForm(this),
			columns: [
				{
					title: 'Alumno',
					dataIndex: 'student_name',
					key: 'student_name',
					width: 200,
					sorter: true
				},
				{
					title: 'Matrícula',
					dataIndex: 'enrollment_number',
					key: 'enrollment_number',
					width: 120,
					sorter: true
				},
				{
					title: 'Padre/Madre/Tutor',
					dataIndex: 'parent_name',
					key: 'parent_name',
					width: 200,
					sorter: true
				},
				{
					title: 'Identidad',
					dataIndex: 'parent_identity',
					key: 'parent_identity',
					width: 150
				},
				{
					title: 'Monto',
					dataIndex: 'amount',
					key: 'amount',
					width: 120,
					sorter: true,
					scopedSlots: { customRender: 'amount' }
				},
				{
					title: 'Estado',
					dataIndex: 'status',
					key: 'status',
					width: 100,
					scopedSlots: { customRender: 'status' }
				},
				{
					title: 'Fecha',
					dataIndex: 'payment_date',
					key: 'payment_date',
					width: 120,
					sorter: true,
					scopedSlots: { customRender: 'date' }
				},
				{
					title: 'Acciones',
					key: 'action',
					width: 200,
					scopedSlots: { customRender: 'action' }
				}
			]
		}
	},
	computed: {
		filteredPayments() {
			if (!this.searchText) return this.payments;
			
			const searchLower = this.searchText.toLowerCase();
			return this.payments.filter(payment => 
				payment.student_name.toLowerCase().includes(searchLower) ||
				payment.enrollment_number.toLowerCase().includes(searchLower) ||
				payment.parent_name.toLowerCase().includes(searchLower) ||
				payment.parent_identity.toLowerCase().includes(searchLower)
			);
		}
	},
	created() {
		this.fetchPayments();
		this.fetchStudents();
	},
	methods: {
		authHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		
		async fetchPayments() {
			this.loading = true;
			try {
				const response = await axios.get('http://localhost:8000/api/payments/matriculas', { 
					headers: this.authHeaders() 
				});
				this.payments = response.data || [];
				this.pagination.total = this.payments.length;
			} catch (error) {
				console.error('Error fetching payments:', error);
				this.$message.error('Error al cargar los pagos');
			} finally {
				this.loading = false;
			}
		},
		
		async fetchStudents() {
			try {
				const response = await axios.get('http://localhost:8000/api/students', { 
					headers: this.authHeaders() 
				});
				this.students = response.data || [];
			} catch (error) {
				console.error('Error fetching students:', error);
			}
		},
		
		handleSearch() {
			this.pagination.current = 1;
		},
		
		handleTableChange(pagination, filters, sorter) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		
		showAddPaymentModal() {
			this.editingPayment = null;
			this.paymentModalVisible = true;
			this.$nextTick(() => {
				this.paymentForm.resetFields();
			});
		},
		
		editPayment(record) {
			this.editingPayment = record;
			this.paymentModalVisible = true;
			this.$nextTick(() => {
				this.paymentForm.setFieldsValue({
					student_id: record.student_id,
					parent_name: record.parent_name,
					parent_identity: record.parent_identity,
					amount: record.amount,
					payment_method: record.payment_method,
					payment_date: this.$moment(record.payment_date),
					notes: record.notes
				});
			});
		},
		
		viewPayment(record) {
			this.selectedPayment = record;
			this.detailsModalVisible = true;
		},
		
		async handlePaymentSubmit() {
			this.paymentForm.validateFields(async (err, values) => {
				if (!err) {
					try {
						const payload = {
							...values,
							payment_date: values.payment_date.format('YYYY-MM-DD'),
							// Incluir información de tarjeta si aplica
							card_info: this.showCardInfo ? {
								card_number: values.card_number,
								card_cvv: values.card_cvv,
								card_holder_name: values.card_holder_name,
								card_expiry: values.card_expiry,
								card_bank: values.card_bank,
								automatic_debit_authorization: values.automatic_debit_authorization
							} : null,
							// Incluir información de transferencia si aplica
							transfer_info: this.showTransferInfo ? {
								transfer_source_bank: values.transfer_source_bank,
								transfer_reference: values.transfer_reference,
								transfer_date: values.transfer_date.format('YYYY-MM-DD'),
								transfer_account: values.transfer_account,
								transfer_notes: values.transfer_notes,
								transfer_receipt: this.receiptFileList.length > 0 ? this.receiptFileList[0].response?.url || this.receiptFileList[0].url : null
							} : null
						};
						
						if (this.editingPayment) {
							await axios.put(`http://localhost:8000/api/payments/matriculas/${this.editingPayment.id}`, payload, {
								headers: this.authHeaders()
							});
							this.$message.success('Pago actualizado exitosamente');
						} else {
							await axios.post('http://localhost:8000/api/payments/matriculas', payload, {
								headers: this.authHeaders()
							});
							this.$message.success('Pago registrado exitosamente');
						}
						
						this.paymentModalVisible = false;
						this.fetchPayments();
					} catch (error) {
						console.error('Error saving payment:', error);
						this.$message.error('Error al guardar el pago');
					}
				}
			});
		},
		
		async deletePayment(record) {
			try {
				await axios.delete(`http://localhost:8000/api/payments/matriculas/${record.id}`, {
					headers: this.authHeaders()
				});
				this.$message.success('Pago eliminado exitosamente');
				this.fetchPayments();
			} catch (error) {
				console.error('Error deleting payment:', error);
				this.$message.error('Error al eliminar el pago');
			}
		},
		
		resetPaymentForm() {
			this.paymentForm.resetFields();
			this.editingPayment = null;
			this.showCardInfo = false;
			this.showTransferInfo = false;
			this.cardNumber = '';
			this.cardHolderName = '';
			this.cardExpiry = '';
			this.cardType = '';
			this.receiptFileList = [];
		},
		
		handlePaymentMethodChange(value) {
			this.showCardInfo = value === 'card';
			this.showTransferInfo = value === 'transfer';
			
			if (!this.showCardInfo) {
				// Limpiar campos de tarjeta
				this.paymentForm.resetFields(['card_number', 'card_cvv', 'card_holder_name', 'card_expiry', 'card_bank', 'automatic_debit_authorization']);
				this.cardNumber = '';
				this.cardHolderName = '';
				this.cardExpiry = '';
				this.cardType = '';
			}
			
			if (!this.showTransferInfo) {
				// Limpiar campos de transferencia
				this.paymentForm.resetFields(['transfer_source_bank', 'transfer_reference', 'transfer_date', 'transfer_account', 'transfer_notes']);
				this.receiptFileList = [];
			}
		},
		
		// Funciones para manejo de comprobantes
		beforeUploadReceipt(file) {
			const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'application/pdf';
			if (!isJpgOrPng) {
				this.$message.error('Solo puedes subir archivos JPG, PNG o PDF!');
				return false;
			}
			const isLt5M = file.size / 1024 / 1024 < 5;
			if (!isLt5M) {
				this.$message.error('El archivo debe ser menor a 5MB!');
				return false;
			}
			return false; // Prevenir upload automático
		},
		
		handleReceiptUpload({ file, onSuccess, onError }) {
			const formData = new FormData();
			formData.append('file', file);
			formData.append('type', 'transfer_receipt');
			
			axios.post('http://localhost:8000/api/upload/receipt', formData, {
				headers: {
					...this.authHeaders(),
					'Content-Type': 'multipart/form-data'
				}
			})
			.then(response => {
				onSuccess(response.data, file);
				this.$message.success('Comprobante subido exitosamente');
			})
			.catch(error => {
				onError(error);
				this.$message.error('Error al subir el comprobante');
			});
		},
		
		handleReceiptChange({ fileList }) {
			this.receiptFileList = fileList;
		},
		
		handlePreview(file) {
			this.previewImage = file.url || file.response?.url;
			this.previewVisible = true;
			this.previewTitle = file.name || file.response?.name;
		},
		
		formatCardInput(e) {
			let value = e.target.value.replace(/\s/g, '');
			let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
			this.cardNumber = formattedValue;
			this.paymentForm.setFieldsValue({ card_number: formattedValue });
			
			// Detectar tipo de tarjeta
			this.detectCardType(value);
		},
		
		formatExpiryInput(e) {
			let value = e.target.value.replace(/\D/g, '');
			if (value.length >= 2) {
				value = value.slice(0, 2) + '/' + value.slice(2, 4);
			}
			this.cardExpiry = value;
			this.paymentForm.setFieldsValue({ card_expiry: value });
		},
		
		updateCardHolder(e) {
			this.cardHolderName = e.target.value.toUpperCase();
		},
		
		detectCardType(cardNumber) {
			const patterns = {
				visa: /^4/,
				mastercard: /^5[1-5]/,
				amex: /^3[47]/,
				discover: /^6(?:011|5)/,
				diners: /^3(?:0[0-5]|[68])/,
				jcb: /^(?:2131|1800|35\d{3})/,
				maestro: /^(?:5018|5020|5038|6304|6759|676[1-3])/
			};
			
			for (const [type, pattern] of Object.entries(patterns)) {
				if (pattern.test(cardNumber)) {
					this.cardType = type.toUpperCase();
					return;
				}
			}
			this.cardType = '';
		},
		
		formatCardNumber(number) {
			if (!number) return '';
			return number.replace(/\s/g, '').match(/.{1,4}/g)?.join(' ') || number;
		},
		
		handleStudentChange(studentId) {
			const student = this.students.find(s => s.id === studentId);
			if (student) {
				this.paymentForm.setFieldsValue({
					parent_name: student.parent_name,
					parent_identity: student.parent_identity
				});
			}
		},
		
		filterStudentOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
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
				completed: 'green',
				cancelled: 'red',
				refunded: 'purple'
			};
			return colors[status] || 'default';
		},
		
		getStatusText(status) {
			const texts = {
				pending: 'Pendiente',
				completed: 'Completado',
				cancelled: 'Cancelado',
				refunded: 'Reembolsado'
			};
			return texts[status] || status;
		},
		
		getPaymentMethodText(method) {
			const methods = {
				cash: 'Efectivo',
				card: 'Tarjeta',
				transfer: 'Transferencia',
				check: 'Cheque'
			};
			return methods[method] || method;
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

.table-search {
	width: 300px;
	flex: 1 1 300px;
	min-width: 200px;
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

@media (max-width: 768px) {
	.table-header-controls {
		justify-content: flex-start;
	}
	.table-search {
		width: 100%;
		min-width: 0;
	}
}

/* Estilos de Tarjeta de Crédito */
.credit-card-container {
	display: flex;
	justify-content: center;
	margin: 20px 0;
}

.credit-card {
	width: 350px;
	height: 220px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border-radius: 20px;
	padding: 20px;
	color: white;
	box-shadow: 0 10px 30px rgba(0,0,0,0.3);
	position: relative;
	overflow: hidden;
}

.credit-card::before {
	content: '';
	position: absolute;
	top: -50%;
	right: -50%;
	width: 200%;
	height: 200%;
	background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
	animation: shine 3s infinite;
}

@keyframes shine {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.card-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 30px;
}

.card-chip {
	width: 40px;
	height: 30px;
}

.card-type {
	font-size: 14px;
	font-weight: bold;
	letter-spacing: 1px;
}

.card-body {
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	height: calc(100% - 70px);
}

.card-number {
	font-size: 20px;
	letter-spacing: 2px;
	margin-bottom: 20px;
	font-family: 'Courier New', monospace;
}

.card-info {
	display: flex;
	justify-content: space-between;
	align-items: flex-end;
}

.card-holder, .card-expiry {
	display: flex;
	flex-direction: column;
}

.card-holder .label, .card-expiry .label {
	font-size: 8px;
	opacity: 0.8;
	margin-bottom: 4px;
	letter-spacing: 1px;
}

.card-holder .value, .card-expiry .value {
	font-size: 14px;
	font-weight: bold;
	letter-spacing: 1px;
}

.card-info-section {
	background: #f5f5f5;
	padding: 20px;
	border-radius: 8px;
	margin: 20px 0;
}

/* Estilos de Transferencia */
.transfer-info-section {
	background: #f0f8ff;
	padding: 20px;
	border-radius: 8px;
	margin: 20px 0;
	border: 1px solid #d4edda;
}

.receipt-uploader {
	width: 100%;
}

.receipt-uploader .ant-upload {
	border: 2px dashed #d9d9d9;
	border-radius: 8px;
	background: #fafafa;
	text-align: center;
	transition: border-color 0.3s;
}

.receipt-uploader .ant-upload:hover {
	border-color: #1890ff;
}

.receipt-uploader .ant-upload-drag {
	border: 2px dashed #d9d9d9;
	border-radius: 8px;
	background: #fafafa;
}
</style>
