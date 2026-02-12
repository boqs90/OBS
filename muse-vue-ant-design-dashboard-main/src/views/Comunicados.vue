<template>
	<div class="layout">
		<a-card class="communications-card" :bordered="false">
			<div class="card-header">
				<div class="header-content">
					<div class="header-info">
						<h2 class="page-title">
							<span class="icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M2 5C2 3.89543 2.89543 3 4 3H16C17.1046 3 18 3.89543 18 5V11C18 11.5523 17.5523 12 17 12H3C2.44772 12 2 11.5523 2 11V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M3 14C2.44772 14 2 14.4477 2 15C2 15.5523 2.44772 16 3 16H17C17.5523 16 18 15.5523 18 15V14H3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M8 7C8 6.44772 8.44772 6 9 6H11C11.5523 6 12 6.44772 12 7C12 7.55228 11.5523 8 11 8H9C8.44772 8 8 7.55228 8 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							Comunicados
						</h2>
						<p class="page-description">Envía mensajes masivos a padres, docentes y empleados por WhatsApp y correo electrónico</p>
					</div>
					<div class="header-actions">
						<a-button type="primary" @click="showCreateModal = true">
							<a-icon type="plus" />
							Nuevo Comunicado
						</a-button>
					</div>
				</div>
			</div>

			<!-- Filtros y búsqueda -->
			<div class="filters-section">
				<a-row :gutter="16" type="flex" align="middle">
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-input-search
							v-model="searchText"
							placeholder="Buscar comunicado..."
							@search="handleSearch"
							allow-clear
						/>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-select
							v-model="statusFilter"
							placeholder="Filtrar por estado"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los estados</a-select-option>
							<a-select-option value="draft">Borrador</a-select-option>
							<a-select-option value="sent">Enviado</a-select-option>
							<a-select-option value="scheduled">Programado</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-select
							v-model="recipientFilter"
							placeholder="Filtrar por destinatario"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los destinatarios</a-select-option>
							<a-select-option value="parents">Padres</a-select-option>
							<a-select-option value="teachers">Docentes</a-select-option>
							<a-select-option value="employees">Empleados</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="8" :lg="6">
						<a-button @click="resetFilters">
							<a-icon type="reload" />
							Limpiar filtros
						</a-button>
					</a-col>
				</a-row>
			</div>

			<!-- Tabla de comunicados -->
			<a-table
				:columns="columns"
				:data-source="filteredCommunications"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				:row-key="record => record.id"
			>
				<template slot="title" slot-scope="text, record">
					<div class="communication-title">
						<span class="title">{{ text }}</span>
						<span class="priority" :class="record.priority">{{ getPriorityLabel(record.priority) }}</span>
					</div>
				</template>

				<template slot="recipient_type" slot-scope="text">
					<a-tag :color="getRecipientColor(text)">
						{{ getRecipientLabel(text) }}
					</a-tag>
				</template>

				<template slot="status" slot-scope="text">
					<a-badge :status="getStatusBadge(text)" :text="getStatusLabel(text)" />
				</template>

				<template slot="scheduled_at" slot-scope="text">
					<span v-if="text">{{ formatDateTime(text) }}</span>
					<span v-else>-</span>
				</template>

				<template slot="actions" slot-scope="text, record">
					<div class="table-actions">
						<a-button size="small" @click="viewCommunication(record)">
							<a-icon type="eye" />
						</a-button>
						<a-button size="small" @click="editCommunication(record)" v-if="record.status !== 'sent'">
							<a-icon type="edit" />
						</a-button>
						<a-button size="small" type="primary" @click="sendNow(record)" v-if="record.status === 'draft' || record.status === 'scheduled'">
							<a-icon type="send" />
						</a-button>
						<a-button size="small" type="danger" @click="deleteCommunication(record)">
							<a-icon type="delete" />
						</a-button>
					</div>
				</template>
			</a-table>
		</a-card>

		<!-- Modal para crear/editar comunicado -->
		<a-modal
			:title="editingCommunication ? 'Editar Comunicado' : 'Nuevo Comunicado'"
			:visible="showCreateModal"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirm-loading="saving"
			width="800px"
		>
			<a-form :form="form" :layout="'vertical'">
				<a-form-item label="Título del Comunicado">
					<a-input
						v-decorator="[
							'title',
							{ rules: [{ required: true, message: 'El título es requerido' }] }
						]"
						placeholder="Ej: Reunión de padres de familia"
					/>
				</a-form-item>

				<a-form-item label="Tipo de Destinatario">
					<a-select
						v-decorator="[
							'recipient_type',
							{ rules: [{ required: true, message: 'El tipo de destinatario es requerido' }] }
						]"
						placeholder="Selecciona el tipo de destinatario"
						@change="onRecipientTypeChange"
					>
						<a-select-option value="parents">Padres</a-select-option>
						<a-select-option value="teachers">Docentes</a-select-option>
						<a-select-option value="employees">Empleados</a-select-option>
						<a-select-option value="all">Todos</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Grados Específicos (opcional)">
					<a-select
						v-decorator="['grades']"
						mode="multiple"
						placeholder="Selecciona los grados (deja vacío para todos)"
						style="width: 100%"
					>
						<a-select-option v-for="grade in gradesOptions" :key="grade.value" :value="grade.value">
							{{ grade.label }}
						</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Mensaje">
					<a-textarea
						v-decorator="[
							'message',
							{ rules: [{ required: true, message: 'El mensaje es requerido' }] }
						]"
						placeholder="Escribe el mensaje del comunicado..."
						:rows="6"
					/>
				</a-form-item>

				<a-form-item label="Canales de Envío">
					<a-checkbox-group v-decorator="['channels']">
						<a-checkbox value="whatsapp">WhatsApp</a-checkbox>
						<a-checkbox value="email">Correo Electrónico</a-checkbox>
					</a-checkbox-group>
				</a-form-item>

				<a-form-item label="Usar Destinatarios Personalizados">
					<a-switch 
						v-model="useCustomRecipients" 
						@change="onCustomRecipientsToggle"
					/>
					<div class="help-text">
						<small>Activa para enviar a contactos específicos</small>
					</div>
				</a-form-item>

				<a-form-item label="Destinatarios Personalizados" v-if="useCustomRecipients">
					<a-textarea
						v-decorator="['custom_recipients']"
						placeholder="Ingresa números de WhatsApp o correos electrónicos (uno por línea)"
						:rows="4"
					/>
					<div class="help-text">
						<small>Ejemplo: +50412345678 o padre@ejemplo.com (uno por línea)</small>
					</div>
				</a-form-item>

				<a-form-item label="Prioridad" v-if="!useCustomRecipients">
					<a-select
						v-decorator="[
							'priority',
							{ rules: [{ required: true, message: 'La prioridad es requerida' }] }
						]"
						placeholder="Selecciona la prioridad"
					>
						<a-select-option value="low">Baja</a-select-option>
						<a-select-option value="normal">Normal</a-select-option>
						<a-select-option value="high">Alta</a-select-option>
						<a-select-option value="urgent">Urgente</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Programar Envío">
					<a-date-time-picker
						v-decorator="['scheduled_at']"
						placeholder="Selecciona fecha y hora (opcional)"
						style="width: 100%"
						format="YYYY-MM-DD HH:mm"
						:disabled-date="disabledDate"
						:disabled-time="disabledTime"
					/>
				</a-form-item>

				<a-form-item label="Archivos Adjuntos">
					<a-upload
						:file-list="fileList"
						:before-upload="beforeUpload"
						@remove="handleRemove"
						multiple
					>
						<a-button>
							<a-icon type="upload" />
							Seleccionar Archivos
						</a-button>
					</a-upload>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal para ver detalles -->
		<a-modal
			title="Detalles del Comunicado"
			:visible="showDetailsModal"
			@cancel="showDetailsModal = false"
			:footer="null"
			width="700px"
		>
			<div v-if="selectedCommunication" class="communication-details">
				<a-descriptions :column="1" bordered>
					<a-descriptions-item label="Título">
						{{ selectedCommunication.title }}
					</a-descriptions-item>
					<a-descriptions-item label="Tipo de Destinatario">
						<a-tag :color="getRecipientColor(selectedCommunication.recipient_type)">
							{{ getRecipientLabel(selectedCommunication.recipient_type) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Prioridad">
						<span class="priority" :class="selectedCommunication.priority">
							{{ getPriorityLabel(selectedCommunication.priority) }}
						</span>
					</a-descriptions-item>
					<a-descriptions-item label="Canales">
						<a-tag v-for="channel in selectedCommunication.channels" :key="channel" color="blue">
							{{ channel === 'whatsapp' ? 'WhatsApp' : 'Email' }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Estado">
						<a-badge :status="getStatusBadge(selectedCommunication.status)" :text="getStatusLabel(selectedCommunication.status)" />
					</a-descriptions-item>
					<a-descriptions-item label="Fecha Programada" v-if="selectedCommunication.scheduled_at">
						{{ formatDateTime(selectedCommunication.scheduled_at) }}
					</a-descriptions-item>
					<a-descriptions-item label="Fecha de Envío" v-if="selectedCommunication.sent_at">
						{{ formatDateTime(selectedCommunication.sent_at) }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div class="message-section">
					<h4>Mensaje</h4>
					<div class="message-content" v-html="formatMessage(selectedCommunication.message)"></div>
				</div>
				
				<div class="attachments-section" v-if="selectedCommunication.attachments && selectedCommunication.attachments.length > 0">
					<h4>Archivos Adjuntos</h4>
					<div class="attachments-list">
						<a-tag v-for="attachment in selectedCommunication.attachments" :key="attachment.id" color="default" class="attachment-tag">
							<a-icon type="paper-clip" />
							{{ attachment.name }}
						</a-tag>
					</div>
				</div>
			</div>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'Comunicados',
	data() {
		return {
			loading: false,
			communicationsData: [],
			searchText: '',
			statusFilter: 'all',
			recipientFilter: 'all',
			showCreateModal: false,
			showDetailsModal: false,
			saving: false,
			editingCommunication: null,
			selectedCommunication: null,
			useCustomRecipients: false,
			fileList: [],
			form: this.$form.createForm(this, { name: 'communication_form' }),
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} comunicados`,
			},
			gradesOptions: [
				{ label: 'Pre-Kínder', value: 'pre-kinder' },
				{ label: 'Kínder', value: 'kinder' },
				{ label: '1er Grado', value: '1ro' },
				{ label: '2do Grado', value: '2do' },
				{ label: '3er Grado', value: '3ro' },
				{ label: '4to Grado', value: '4to' },
				{ label: '5to Grado', value: '5to' },
				{ label: '6to Grado', value: '6to' },
				{ label: '7mo Grado', value: '7mo' },
				{ label: '8vo Grado', value: '8vo' },
				{ label: '9no Grado', value: '9no' },
				{ label: '10mo Grado', value: '10mo' },
				{ label: '11vo Grado', value: '11vo' },
			],
			columns: [
				{ title: 'Título', dataIndex: 'title', key: 'title', scopedSlots: { customRender: 'title' } },
				{ title: 'Destinatario', dataIndex: 'recipient_type', key: 'recipient_type', width: 120, scopedSlots: { customRender: 'recipient_type' } },
				{ title: 'Estado', dataIndex: 'status', key: 'status', width: 120, scopedSlots: { customRender: 'status' } },
				{ title: 'Programado', dataIndex: 'scheduled_at', key: 'scheduled_at', width: 150, scopedSlots: { customRender: 'scheduled_at' } },
				{ title: 'Acciones', key: 'actions', width: 200, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
		};
	},
	computed: {
		filteredCommunications() {
			let filtered = this.communicationsData;

			// Filtrar por texto de búsqueda
			if (this.searchText && this.searchText.trim() !== '') {
				const search = this.searchText.toLowerCase();
				filtered = filtered.filter(comm => 
					comm.title.toLowerCase().includes(search) ||
					comm.message.toLowerCase().includes(search)
				);
			}

			// Filtrar por estado
			if (this.statusFilter !== 'all') {
				filtered = filtered.filter(comm => comm.status === this.statusFilter);
			}

			// Filtrar por destinatario
			if (this.recipientFilter !== 'all') {
				filtered = filtered.filter(comm => comm.recipient_type === this.recipientFilter);
			}

			return filtered;
		},
	},
	mounted() {
		this.fetchCommunications();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchCommunications() {
			this.loading = true;
			axios.get('http://localhost:8000/api/communications', { headers: this.apiHeaders() })
				.then((res) => {
					this.communicationsData = res.data || [];
					this.pagination.total = this.communicationsData.length;
				})
				.catch((err) => {
					console.error('Error al obtener comunicados:', err.response?.data || err);
					this.$message.error('No se pudieron cargar los comunicados');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		handleSearch() {
			this.pagination.current = 1;
		},
		handleFilter() {
			this.pagination.current = 1;
		},
		resetFilters() {
			this.searchText = '';
			this.statusFilter = 'all';
			this.recipientFilter = 'all';
			this.pagination.current = 1;
		},
		handleTableChange(pagination) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		getRecipientColor(type) {
			const colors = {
				parents: 'green',
				teachers: 'blue',
				employees: 'orange',
				all: 'purple',
			};
			return colors[type] || 'default';
		},
		getRecipientLabel(type) {
			const labels = {
				parents: 'Padres',
				teachers: 'Docentes',
				employees: 'Empleados',
				all: 'Todos',
			};
			return labels[type] || type;
		},
		getStatusBadge(status) {
			const badges = {
				draft: 'default',
				scheduled: 'processing',
				sent: 'success',
				failed: 'error',
			};
			return badges[status] || 'default';
		},
		getStatusLabel(status) {
			const labels = {
				draft: 'Borrador',
				scheduled: 'Programado',
				sent: 'Enviado',
				failed: 'Fallido',
			};
			return labels[status] || status;
		},
		getPriorityLabel(priority) {
			const labels = {
				low: 'Baja',
				normal: 'Normal',
				high: 'Alta',
				urgent: 'Urgente',
			};
			return labels[priority] || priority;
		},
		formatDateTime(dateStr) {
			return moment(dateStr).format('DD/MM/YYYY HH:mm');
		},
		formatMessage(message) {
			return message.replace(/\n/g, '<br>');
		},
		disabledDate(current) {
			return current && current < moment().startOf('day');
		},
		disabledTime() {
			return {
				disabledHours: () => [0, 1, 2, 3, 4, 5],
			};
		},
		onRecipientTypeChange(value) {
			// Limpiar selección de grados si cambia el tipo de destinatario
			if (value === 'all') {
				this.form.setFieldsValue({ grades: [] });
			}
		},
		onCustomRecipientsToggle(checked) {
			if (checked) {
				// Si se activa destinatarios personalizados, limpiar campos de destinatarios masivos
				this.form.setFieldsValue({ 
					recipient_type: null,
					grades: [],
					priority: 'normal'
				});
			} else {
				// Si se desactiva, limpiar destinatarios personalizados
				this.form.setFieldsValue({ 
					custom_recipients: null 
				});
			}
		},
		viewCommunication(record) {
			this.selectedCommunication = record;
			this.showDetailsModal = true;
		},
		editCommunication(record) {
			this.editingCommunication = record;
			this.showCreateModal = true;
			this.form.setFieldsValue({
				title: record.title,
				recipient_type: record.recipient_type,
				grades: record.grades || [],
				message: record.message,
				channels: record.channels || ['whatsapp', 'email'],
				priority: record.priority,
				scheduled_at: record.scheduled_at ? moment(record.scheduled_at) : null,
			});
		},
		closeModal() {
			this.showCreateModal = false;
			this.saving = false;
			this.editingCommunication = null;
			this.useCustomRecipients = false;
			this.fileList = [];
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				this.saving = true;
				
				// Procesar destinatarios personalizados
				let customRecipients = [];
				if (values.custom_recipients) {
					const lines = values.custom_recipients.split('\n').filter(line => line.trim());
					customRecipients = lines.map(line => {
						const trimmed = line.trim();
						// Validar si es WhatsApp o correo
						if (trimmed.startsWith('+') || /^\d+$/.test(trimmed.replace(/[^\d]/g, ''))) {
							// Es número de WhatsApp
							return {
								type: 'whatsapp',
								value: trimmed.startsWith('+') ? trimmed : `+${trimmed.replace(/[^\d]/g, '')}`,
							};
						} else if (trimmed.includes('@')) {
							// Es correo electrónico
							return {
								type: 'email',
								value: trimmed,
							};
						}
						return null;
					}).filter(item => item !== null);
				}

				const payload = {
					title: values.title,
					recipient_type: values.recipient_type,
					grades: values.grades || [],
					message: values.message,
					channels: values.channels || ['whatsapp', 'email'],
					priority: values.priority,
					scheduled_at: values.scheduled_at ? values.scheduled_at.format('YYYY-MM-DD HH:mm:ss') : null,
					custom_recipients: customRecipients,
					attachments: this.fileList.map(file => ({
						name: file.name,
						url: file.url || file.response?.url,
						size: file.size,
					})),
				};

				const request = this.editingCommunication
					? axios.put(`http://localhost:8000/api/communications/${this.editingCommunication.id}`, payload, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/communications', payload, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchCommunications();
						this.$message.success(this.editingCommunication ? 'Comunicado actualizado correctamente' : 'Comunicado creado correctamente');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar el comunicado';
						console.error('Error guardando comunicado:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		sendNow(record) {
			this.$confirm({
				title: 'Enviar Comunicado',
				content: `¿Estás seguro de enviar el comunicado "${record.title}" ahora?`,
				okText: 'Enviar',
				okType: 'primary',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.post(`http://localhost:8000/api/communications/${record.id}/send`, {}, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchCommunications();
							this.$message.success('Comunicado enviado correctamente');
						})
						.catch((error) => {
							console.error('Error enviando comunicado:', error.response?.data || error);
							this.$message.error('No se pudo enviar el comunicado');
						});
				},
			});
		},
		deleteCommunication(record) {
			this.$confirm({
				title: 'Eliminar Comunicado',
				content: `¿Estás seguro de eliminar el comunicado "${record.title}"?`,
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.delete(`http://localhost:8000/api/communications/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchCommunications();
							this.$message.success('Comunicado eliminado correctamente');
						})
						.catch((error) => {
							console.error('Error eliminando comunicado:', error.response?.data || error);
							this.$message.error('No se pudo eliminar el comunicado');
						});
				},
			});
		},
		beforeUpload(file) {
			const isValidType = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type);
			if (!isValidType) {
				this.$message.error('Solo se permiten archivos PDF, Word e imágenes');
				return false;
			}
			const isLt10M = file.size / 1024 / 1024 < 10;
			if (!isLt10M) {
				this.$message.error('El archivo debe ser menor a 10MB');
				return false;
			}
			return false; // Prevenir upload automático
		},
		handleRemove(file) {
			this.fileList = this.fileList.filter(item => item.uid !== file.uid);
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.communications-card {
	.card-header {
		margin-bottom: 24px;
		
		.header-content {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 16px;
			flex-wrap: wrap;

			.header-info {
				flex: 1;
				min-width: 0;

				.page-title {
					display: flex;
					align-items: center;
					gap: 12px;
					margin: 0 0 8px 0;
					font-size: 24px;
					font-weight: 700;
					color: #1f2937;

					.icon {
						color: #7c3aed;
					}
				}

				.page-description {
					margin: 0;
					color: #6b7280;
					font-size: 14px;
					line-height: 1.5;
				}
			}

			.header-actions {
				flex-shrink: 0;
			}
		}
	}
}

.filters-section {
	margin-bottom: 24px;
	padding: 20px;
	background: #f8fafc;
	border-radius: 8px;
	border: 1px solid #e2e8f0;
}

.communication-title {
	display: flex;
	flex-direction: column;
	gap: 4px;

	.title {
		font-weight: 600;
		color: #1f2937;
	}

	.priority {
		font-size: 11px;
		padding: 2px 6px;
		border-radius: 4px;
		font-weight: 600;
		
		&.low {
			background: #f3f4f6;
			color: #6b7280;
		}
		
		&.normal {
			background: #dbeafe;
			color: #1e40af;
		}
		
		&.high {
			background: #fed7d7;
			color: #b91c1c;
		}
		
		&.urgent {
			background: #fee2e2;
			color: #dc2626;
		}
	}
}

.table-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	flex-wrap: wrap;
}

.communication-details {
	.message-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.message-content {
			background: #f9fafb;
			padding: 16px;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			line-height: 1.6;
		}
	}
	
	.attachments-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.attachments-list {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			
			.attachment-tag {
				display: flex;
				align-items: center;
				gap: 4px;
			}
		}
	}
}

.help-text {
	margin-top: 4px;
	color: #6b7280;
	font-size: 12px;
	line-height: 1.4;
}

// Responsive
@media (max-width: 768px) {
	.layout {
		padding: 16px;
	}

	.header-content {
		flex-direction: column;
		align-items: stretch;
	}

	.header-actions {
		width: 100%;
		
		.ant-btn {
			width: 100%;
		}
	}
}

@media (max-width: 576px) {
	.filters-section {
		.ant-row {
			.ant-col {
				margin-bottom: 12px;
			}
		}
	}
}
</style>
