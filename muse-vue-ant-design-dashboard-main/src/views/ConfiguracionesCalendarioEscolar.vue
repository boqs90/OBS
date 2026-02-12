<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Calendario escolar</h2>
				<p class="subtitle">Administra fechas importantes: inicio/fin de clases, feriados, eventos y evaluaciones</p>
			</div>

			<a-row :gutter="16" style="margin-bottom: 16px">
				<a-col :xs="24" :md="10">
					<a-range-picker
						v-model="range"
						style="width: 100%"
						format="YYYY-MM-DD"
						:placeholder="['Inicio', 'Fin']"
					/>
				</a-col>
				<a-col :xs="24" :md="6">
					<a-select v-model="type" style="width: 100%" placeholder="Tipo">
						<a-select-option value="holiday">Feriado</a-select-option>
						<a-select-option value="event">Evento</a-select-option>
						<a-select-option value="exam">Evaluación</a-select-option>
						<a-select-option value="term">Periodo</a-select-option>
					</a-select>
				</a-col>
				<a-col :xs="24" :md="8">
					<a-input v-model="title" placeholder="Título" />
				</a-col>
			</a-row>

			<div class="actions">
				<a-button type="primary" :loading="saving" @click="addItem">Agregar</a-button>
				<a-button style="margin-left: 8px" @click="resetForm">Limpiar</a-button>
			</div>

			<a-divider />

			<a-spin :spinning="loading">
				<a-table
					:columns="columns"
					:data-source="items"
					:row-key="record => record.id"
					:pagination="{ pageSize: 10 }"
				>
					<template slot="type" slot-scope="text">
						<a-tag :color="typeColor(text)">{{ typeLabel(text) }}</a-tag>
					</template>
					<template slot="date" slot-scope="text, record">
						<span>{{ record.start_date }} - {{ record.end_date }}</span>
					</template>
					<template slot="actions" slot-scope="text, record">
						<a-button size="small" type="danger" @click="removeItem(record)">
							<a-icon type="delete" />
						</a-button>
					</template>
				</a-table>
			</a-spin>

			<div class="footer-actions">
				<a-button type="primary" :loading="savingAll" @click="saveAll">Guardar calendario</a-button>
			</div>
		</a-card>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'ConfiguracionesCalendarioEscolar',
	data() {
		return {
			loading: false,
			saving: false,
			savingAll: false,
			range: [],
			type: 'holiday',
			title: '',
			items: [],
			columns: [
				{ title: 'Tipo', dataIndex: 'type', key: 'type', width: 120, scopedSlots: { customRender: 'type' } },
				{ title: 'Fechas', key: 'date', scopedSlots: { customRender: 'date' } },
				{ title: 'Título', dataIndex: 'title', key: 'title' },
				{ title: 'Acciones', key: 'actions', width: 120, align: 'right', scopedSlots: { customRender: 'actions' } },
			],
		};
	},
	mounted() {
		this.fetchCalendar();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchCalendar() {
			this.loading = true;
			axios
				.get('http://localhost:8000/api/settings/school-calendar', { headers: this.apiHeaders() })
				.then((res) => {
					this.items = res.data?.items || [];
				})
				.catch((err) => {
					console.error('Error cargando calendario:', err.response?.data || err);
					this.$message.error('No se pudo cargar el calendario');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		addItem() {
			if (!this.range || this.range.length !== 2) {
				this.$message.warning('Selecciona un rango de fechas');
				return;
			}
			if (!this.title || !this.title.trim()) {
				this.$message.warning('Ingresa un título');
				return;
			}

			this.saving = true;
			const item = {
				id: `tmp_${Date.now()}`,
				type: this.type,
				title: this.title.trim(),
				start_date: moment(this.range[0]).format('YYYY-MM-DD'),
				end_date: moment(this.range[1]).format('YYYY-MM-DD'),
			};
			this.items = [item, ...this.items];
			this.resetForm();
			this.saving = false;
		},
		removeItem(record) {
			this.items = this.items.filter((x) => x.id !== record.id);
		},
		saveAll() {
			this.savingAll = true;
			axios
				.put('http://localhost:8000/api/settings/school-calendar', { items: this.items }, { headers: this.apiHeaders() })
				.then(() => {
					this.$message.success('Calendario guardado');
					this.fetchCalendar();
				})
				.catch((e) => {
					console.error('Error guardando calendario:', e.response?.data || e);
					this.$message.error('No se pudo guardar el calendario');
				})
				.finally(() => {
					this.savingAll = false;
				});
		},
		resetForm() {
			this.range = [];
			this.type = 'holiday';
			this.title = '';
		},
		typeLabel(type) {
			const map = { holiday: 'Feriado', event: 'Evento', exam: 'Evaluación', term: 'Periodo' };
			return map[type] || type;
		},
		typeColor(type) {
			const map = { holiday: 'red', event: 'blue', exam: 'orange', term: 'green' };
			return map[type] || 'default';
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.header {
	margin-bottom: 16px;

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

.actions {
	display: flex;
	justify-content: flex-start;
	margin-bottom: 16px;
}

.footer-actions {
	margin-top: 16px;
	display: flex;
	justify-content: flex-end;
}
</style>
