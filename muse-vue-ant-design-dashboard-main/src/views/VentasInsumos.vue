<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Ventas de Insumos</h2>
				<p class="subtitle">Gestión de ventas y control de inventario de insumos escolares</p>
			</div>

			<!-- Filtros y estadísticas -->
			<div class="filters-section">
				<a-row :gutter="16">
					<a-col :xs="24" :md="6">
						<a-form-item label="Fecha">
							<a-range-picker 
								v-model="dateRange" 
								@change="onDateChange"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="Categoría">
							<a-select v-model="filters.category" @change="fetchSales" placeholder="Todas" allowClear>
								<a-select-option v-for="cat in categories" :key="cat" :value="cat">
									{{ cat }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="Estado">
							<a-select v-model="filters.status" @change="fetchSales" placeholder="Todos" allowClear>
								<a-select-option value="completed">Completadas</a-select-option>
								<a-select-option value="pending">Pendientes</a-select-option>
								<a-select-option value="cancelled">Canceladas</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="&nbsp;">
							<a-button type="primary" @click="fetchSales" :loading="loading">
								<a-icon type="search" /> Buscar
							</a-button>
						</a-form-item>
					</a-col>
				</a-row>
			</div>

			<!-- Estadísticas -->
			<div class="stats-section">
				<a-row :gutter="16">
					<a-col :xs="24" :sm="12" :md="6">
						<a-statistic title="Ventas Totales" :value="statistics.totalSales" :value-style="{ color: '#3f8600' }" prefix="L" />
					</a-col>
					<a-col :xs="24" :sm="12" :md="6">
						<a-statistic title="Productos Vendidos" :value="statistics.totalItems" :value-style="{ color: '#1890ff' }" />
					</a-col>
					<a-col :xs="24" :sm="12" :md="6">
						<a-statistic title="Ventas Hoy" :value="statistics.todaySales" :value-style="{ color: '#722ed1' }" prefix="L" />
					</a-col>
					<a-col :xs="24" :sm="12" :md="6">
						<a-statistic title="Promedio por Venta" :value="statistics.averageSale" :value-style="{ color: '#fa8c16' }" prefix="L" />
					</a-col>
				</a-row>
			</div>

			<!-- Botones de acción -->
			<div class="actions-section">
				<a-space>
					<a-button type="primary" @click="showSaleModal">
						<a-icon type="plus" /> Nueva Venta
					</a-button>
					<a-button @click="exportSales" :disabled="salesData.length === 0">
						<a-icon type="download" /> Exportar
					</a-button>
					<a-button @click="showInventoryModal">
						<a-icon type="shop" /> Ver Inventario
					</a-button>
				</a-space>
			</div>

			<!-- Tabla de ventas -->
			<a-table
				:columns="columns"
				:data-source="salesData"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				row-key="id"
			>
				<!-- Columna de Fecha -->
				<template #date="text">
					{{ formatDate(text) }}
				</template>

				<!-- Columna de Cliente -->
				<template #customer="text, record">
					<div>
						<div class="customer-name">{{ record.customer_name }}</div>
						<div class="customer-contact">{{ record.customer_contact }}</div>
					</div>
				</template>

				<!-- Columna de Items -->
				<template #items="text, record">
					<a-tag v-for="item in record.items" :key="item.id" color="blue" style="margin: 2px;">
						{{ item.name }} x{{ item.quantity }}
					</a-tag>
				</template>

				<!-- Columna de Total -->
				<template #total="text">
					<span class="total-amount">L {{ formatCurrency(text) }}</span>
				</template>

				<!-- Columna de Estado -->
				<template #status="text">
					<a-tag :color="getStatusColor(text)">
						{{ getStatusLabel(text) }}
					</a-tag>
				</template>

				<!-- Columna de Acciones -->
				<template #actions="text, record">
					<a-space>
						<a-button size="small" @click="viewSale(record)">
							<a-icon type="eye" />
						</a-button>
						<a-button size="small" @click="printSale(record)">
							<a-icon type="printer" />
						</a-button>
						<a-popconfirm
							v-if="record.status === 'pending'"
							title="¿Cancelar esta venta?"
							ok-text="Sí"
							cancel-text="No"
							@confirm="cancelSale(record)"
						>
							<a-button size="small" type="danger">
								<a-icon type="close" />
							</a-button>
						</a-popconfirm>
					</a-space>
				</template>
			</a-table>
		</a-card>

		<!-- Modal de nueva venta -->
		<a-modal
			title="Nueva Venta"
			:visible="saleModalVisible"
			@ok="processSale"
			@cancel="closeSaleModal"
			:confirmLoading="processing"
			width="900px"
		>
			<a-form :form="saleForm" layout="vertical">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Nombre del Cliente">
							<a-input 
								v-decorator="['customer_name', { rules: [{ required: true, message: 'El nombre es requerido' }] }]"
								placeholder="Nombre completo"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Contacto">
							<a-input 
								v-decorator="['customer_contact', { rules: [{ required: true, message: 'El contacto es requerido' }] }]"
								placeholder="Teléfono o correo"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Productos">
					<div class="sale-items">
						<div v-for="(item, index) in saleItems" :key="index" class="sale-item">
							<a-row :gutter="8">
								<a-col :span="10">
									<a-select 
										v-model="item.product_id" 
										@change="onProductChange(index)"
										placeholder="Seleccione producto"
										style="width: 100%"
									>
										<a-select-option v-for="product in availableProducts" :key="product.id" :value="product.id">
											{{ product.name }} (Stock: {{ product.stock }}) - L{{ product.price }}
										</a-select-option>
									</a-select>
								</a-col>
								<a-col :span="4">
									<a-input-number 
										v-model="item.quantity" 
										:min="1" 
										:max="item.max_quantity || 999"
										@change="calculateTotal"
										style="width: 100%"
									/>
								</a-col>
								<a-col :span="4">
									<a-input-number 
										v-model="item.price" 
										:min="0" 
										:precision="2"
										@change="calculateTotal"
										style="width: 100%"
										disabled
									/>
								</a-col>
								<a-col :span="4">
									<a-input-number 
										v-model="item.subtotal" 
										:min="0" 
										:precision="2"
										style="width: 100%"
										disabled
									/>
								</a-col>
								<a-col :span="2">
									<a-button type="danger" size="small" @click="removeItem(index)" :disabled="saleItems.length === 1">
										<a-icon type="minus" />
									</a-button>
								</a-col>
							</a-row>
						</div>
						<a-button type="dashed" @click="addItem" style="width: 100%; margin-top: 8px;">
							<a-icon type="plus" /> Agregar Producto
						</a-button>
					</div>
				</a-form-item>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Método de Pago">
							<a-select v-decorator="['payment_method', { rules: [{ required: true, message: 'El método de pago es requerido' }] }]" placeholder="Seleccione">
								<a-select-option value="cash">Efectivo</a-select-option>
								<a-select-option value="card">Tarjeta</a-select-option>
								<a-select-option value="transfer">Transferencia</a-select-option>
								<a-select-option value="mixed">Mixto</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Notas">
							<a-textarea v-decorator="['notes']" :rows="1" placeholder="Notas adicionales" />
						</a-form-item>
					</a-col>
				</a-row>

				<div class="sale-summary">
					<a-row :gutter="16">
						<a-col :span="8">
							<strong>Subtotal:</strong> L {{ formatCurrency(subtotal) }}
						</a-col>
						<a-col :span="8">
							<strong>IVA (15%):</strong> L {{ formatCurrency(tax) }}
						</a-col>
						<a-col :span="8">
							<strong>Total:</strong> L {{ formatCurrency(total) }}
						</a-col>
					</a-row>
				</div>
			</a-form>
		</a-modal>

		<!-- Modal de inventario -->
		<a-modal
			title="Inventario Disponible"
			:visible="inventoryModalVisible"
			@cancel="closeInventoryModal"
			:footer="null"
			width="800px"
		>
			<a-table
				:columns="inventoryColumns"
				:data-source="inventoryData"
				:loading="inventoryLoading"
				:pagination="false"
				row-key="id"
			>
				<template #stock="text, record">
					<a-tag :color="record.stock > 10 ? 'green' : record.stock > 5 ? 'orange' : 'red'">
						{{ text }}
					</a-tag>
				</template>
				<template #price="text">
					L {{ formatCurrency(text) }}
				</template>
			</a-table>
		</a-modal>

		<!-- Modal de detalles de venta -->
		<a-modal
			title="Detalles de Venta"
			:visible="detailsModalVisible"
			@cancel="closeDetailsModal"
			:footer="null"
			width="600px"
		>
			<div v-if="selectedSale">
				<a-descriptions :column="1" bordered>
					<a-descriptions-item label="ID Venta">#{{ selectedSale.id }}</a-descriptions-item>
					<a-descriptions-item label="Fecha">{{ formatDate(selectedSale.date) }}</a-descriptions-item>
					<a-descriptions-item label="Cliente">{{ selectedSale.customer_name }}</a-descriptions-item>
					<a-descriptions-item label="Contacto">{{ selectedSale.customer_contact }}</a-descriptions-item>
					<a-descriptions-item label="Método Pago">{{ getPaymentMethodLabel(selectedSale.payment_method) }}</a-descriptions-item>
					<a-descriptions-item label="Estado">
						<a-tag :color="getStatusColor(selectedSale.status)">
							{{ getStatusLabel(selectedSale.status) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Total">L {{ formatCurrency(selectedSale.total) }}</a-descriptions-item>
					<a-descriptions-item label="Notas">{{ selectedSale.notes || 'N/A' }}</a-descriptions-item>
				</a-descriptions>

				<h4 style="margin-top: 16px;">Productos</h4>
				<a-table
					:columns="detailsColumns"
					:data-source="selectedSale.items"
					:pagination="false"
					row-key="id"
					size="small"
				>
					<template #price="text">
						L {{ formatCurrency(text) }}
					</template>
					<template #subtotal="text">
						L {{ formatCurrency(text) }}
					</template>
				</a-table>
			</div>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'VentasInsumos',
	data() {
		return {
			loading: false,
			processing: false,
			inventoryLoading: false,
			saleModalVisible: false,
			inventoryModalVisible: false,
			detailsModalVisible: false,
			selectedSale: null,
			dateRange: [moment().startOf('month'), moment().endOf('month')],
			filters: {
				category: null,
				status: null,
			},
			categories: ['Uniformes', 'Útiles', 'Libros', 'Materiales', 'Otros'],
			salesData: [],
			inventoryData: [],
			availableProducts: [],
			saleItems: [
				{ product_id: null, quantity: 1, price: 0, subtotal: 0, max_quantity: 999 }
			],
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
			},
			statistics: {
				totalSales: 0,
				totalItems: 0,
				todaySales: 0,
				averageSale: 0,
			},
			columns: [
				{
					title: 'Fecha',
					dataIndex: 'date',
					key: 'date',
					scopedSlots: { customRender: 'date' },
					width: 100,
				},
				{
					title: 'Cliente',
					key: 'customer',
					scopedSlots: { customRender: 'customer' },
					width: 200,
				},
				{
					title: 'Productos',
					key: 'items',
					scopedSlots: { customRender: 'items' },
					width: 300,
				},
				{
					title: 'Total',
					dataIndex: 'total',
					key: 'total',
					scopedSlots: { customRender: 'total' },
					width: 100,
				},
				{
					title: 'Estado',
					dataIndex: 'status',
					key: 'status',
					scopedSlots: { customRender: 'status' },
					width: 100,
				},
				{
					title: 'Acciones',
					key: 'actions',
					scopedSlots: { customRender: 'actions' },
					width: 120,
				},
			],
			inventoryColumns: [
				{
					title: 'Producto',
					dataIndex: 'name',
					key: 'name',
				},
				{
					title: 'Categoría',
					dataIndex: 'category',
					key: 'category',
				},
				{
					title: 'Stock',
					dataIndex: 'stock',
					key: 'stock',
					scopedSlots: { customRender: 'stock' },
				},
				{
					title: 'Precio',
					dataIndex: 'price',
					key: 'price',
					scopedSlots: { customRender: 'price' },
				},
			],
			detailsColumns: [
				{
					title: 'Producto',
					dataIndex: 'name',
					key: 'name',
				},
				{
					title: 'Cantidad',
					dataIndex: 'quantity',
					key: 'quantity',
				},
				{
					title: 'Precio',
					dataIndex: 'price',
					key: 'price',
					scopedSlots: { customRender: 'price' },
				},
				{
					title: 'Subtotal',
					dataIndex: 'subtotal',
					key: 'subtotal',
					scopedSlots: { customRender: 'subtotal' },
				},
			],
		};
	},
	computed: {
		subtotal() {
			return this.saleItems.reduce((sum, item) => sum + (item.subtotal || 0), 0);
		},
		tax() {
			return this.subtotal * 0.15;
		},
		total() {
			return this.subtotal + this.tax;
		},
	},
	mounted() {
		this.fetchSales();
		this.fetchInventory();
		this.fetchStatistics();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchSales() {
			this.loading = true;
			const params = {
				start_date: this.dateRange[0].format('YYYY-MM-DD'),
				end_date: this.dateRange[1].format('YYYY-MM-DD'),
				category: this.filters.category,
				status: this.filters.status,
				page: this.pagination.current,
				pageSize: this.pagination.pageSize,
			};

			axios
				.get('http://localhost:8000/api/sales', { 
					headers: this.apiHeaders(),
					params 
				})
				.then((res) => {
					this.salesData = res.data.data || [];
					this.pagination.total = res.data.total || 0;
				})
				.catch((err) => {
					console.error('Error cargando ventas:', err);
					this.$message.error('No se pudieron cargar las ventas');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		fetchInventory() {
			this.inventoryLoading = true;
			axios
				.get('http://localhost:8000/api/inventory/available', { headers: this.apiHeaders() })
				.then((res) => {
					this.inventoryData = res.data || [];
					this.availableProducts = res.data.filter(item => item.stock > 0) || [];
				})
				.catch((err) => {
					console.error('Error cargando inventario:', err);
					this.$message.error('No se pudo cargar el inventario');
				})
				.finally(() => {
					this.inventoryLoading = false;
				});
		},
		fetchStatistics() {
			axios
				.get('http://localhost:8000/api/sales/statistics', { headers: this.apiHeaders() })
				.then((res) => {
					this.statistics = res.data || {
						totalSales: 0,
						totalItems: 0,
						todaySales: 0,
						averageSale: 0,
					};
				})
				.catch((err) => {
					console.error('Error cargando estadísticas:', err);
				});
		},
		onDateChange() {
			this.fetchSales();
		},
		showSaleModal() {
			this.saleModalVisible = true;
			this.fetchInventory();
		},
		closeSaleModal() {
			this.saleModalVisible = false;
			this.saleItems = [{ product_id: null, quantity: 1, price: 0, subtotal: 0, max_quantity: 999 }];
			this.saleForm.resetFields();
		},
		showInventoryModal() {
			this.inventoryModalVisible = true;
			this.fetchInventory();
		},
		closeInventoryModal() {
			this.inventoryModalVisible = false;
		},
		addItem() {
			this.saleItems.push({ 
				product_id: null, 
				quantity: 1, 
				price: 0, 
				subtotal: 0, 
				max_quantity: 999 
			});
		},
		removeItem(index) {
			this.saleItems.splice(index, 1);
			this.calculateTotal();
		},
		onProductChange(index) {
			const item = this.saleItems[index];
			const product = this.availableProducts.find(p => p.id === item.product_id);
			
			if (product) {
				item.price = product.price;
				item.max_quantity = product.stock;
				item.quantity = Math.min(item.quantity, product.stock);
				item.subtotal = item.quantity * item.price;
			}
			
			this.calculateTotal();
		},
		calculateTotal() {
			this.saleItems.forEach(item => {
				item.subtotal = item.quantity * item.price;
			});
		},
		processSale() {
			this.saleForm.validateFields((err, values) => {
				if (err) return;

				// Validar que todos los productos estén seleccionados
				const invalidItems = this.saleItems.filter(item => !item.product_id);
				if (invalidItems.length > 0) {
					this.$message.error('Seleccione todos los productos');
					return;
				}

				this.processing = true;
				const saleData = {
					...values,
					items: this.saleItems.map(item => ({
						product_id: item.product_id,
						quantity: item.quantity,
						price: item.price,
						subtotal: item.subtotal,
					})),
					subtotal: this.subtotal,
					tax: this.tax,
					total: this.total,
					date: moment().format('YYYY-MM-DD HH:mm:ss'),
				};

				axios
					.post('http://localhost:8000/api/sales', saleData, { headers: this.apiHeaders() })
					.then(() => {
						this.$message.success('Venta procesada correctamente');
						this.closeSaleModal();
						this.fetchSales();
						this.fetchStatistics();
					})
					.catch((err) => {
						console.error('Error procesando venta:', err);
						this.$message.error('No se pudo procesar la venta');
					})
					.finally(() => {
						this.processing = false;
					});
			});
		},
		viewSale(record) {
			this.selectedSale = record;
			this.detailsModalVisible = true;
		},
		closeDetailsModal() {
			this.detailsModalVisible = false;
			this.selectedSale = null;
		},
		printSale(record) {
			// Implementar función de impresión
			this.$message.info('Función de impresión en desarrollo');
		},
		cancelSale(record) {
			axios
				.put(`http://localhost:8000/api/sales/${record.id}/cancel`, {}, { headers: this.apiHeaders() })
				.then(() => {
					this.$message.success('Venta cancelada');
					this.fetchSales();
					this.fetchStatistics();
				})
				.catch((err) => {
					console.error('Error cancelando venta:', err);
					this.$message.error('No se pudo cancelar la venta');
				});
		},
		exportSales() {
			// Implementar función de exportación
			this.$message.info('Función de exportación en desarrollo');
		},
		handleTableChange(pagination) {
			this.pagination.current = pagination.current;
			this.pagination.pageSize = pagination.pageSize;
			this.fetchSales();
		},
		formatDate(date) {
			return moment(date).format('DD/MM/YYYY HH:mm');
		},
		formatCurrency(amount) {
			return new Intl.NumberFormat('es-HN', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2,
			}).format(amount);
		},
		getStatusLabel(status) {
			const labels = {
				pending: 'Pendiente',
				completed: 'Completada',
				cancelled: 'Cancelada',
			};
			return labels[status] || status;
		},
		getStatusColor(status) {
			const colors = {
				pending: 'orange',
				completed: 'green',
				cancelled: 'red',
			};
			return colors[status] || 'default';
		},
		getPaymentMethodLabel(method) {
			const labels = {
				cash: 'Efectivo',
				card: 'Tarjeta',
				transfer: 'Transferencia',
				mixed: 'Mixto',
			};
			return labels[method] || method;
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.header {
	margin-bottom: 24px;

	.title {
		margin: 0;
		font-size: 22px;
		font-weight: 700;
		color: #111827;
	}

	.subtitle {
		margin: 6px 0 0 0;
		color: #6b7280;
	}
}

.filters-section {
	margin-bottom: 16px;
	padding: 16px;
	background: #f9fafb;
	border-radius: 8px;
}

.stats-section {
	margin-bottom: 16px;
	padding: 16px;
	background: #f0f9ff;
	border-radius: 8px;
}

.actions-section {
	margin-bottom: 16px;
}

.customer-name {
	font-weight: 500;
	color: #111827;
}

.customer-contact {
	font-size: 12px;
	color: #6b7280;
}

.total-amount {
	font-weight: 600;
	color: #111827;
}

.sale-items {
	border: 1px solid #d9d9d9;
	border-radius: 4px;
	padding: 8px;
	background: #fafafa;
}

.sale-item {
	margin-bottom: 8px;
	padding: 8px;
	background: white;
	border-radius: 4px;
	border: 1px solid #e8e8e8;
}

.sale-summary {
	margin-top: 16px;
	padding: 16px;
	background: #f6f8fa;
	border-radius: 4px;
	border: 1px solid #e8e8e8;
}
</style>
