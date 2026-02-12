<template>
	<div>
		<!-- Administración de Roles (CRUD) -->
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
					<template #title>
						<a-row type="flex" align="middle" class="table-header-row">
							<a-col :span="24" :md="12">
								<div class="table-header-left">
									<h5 class="font-semibold m-0">Administrar roles</h5>
								</div>
							</a-col>
							<a-col :span="24" :md="12" class="table-header-right">
								<a-input-search
									v-model="searchText"
									:maxLength="200"
									allowClear
									placeholder="Buscar..."
									class="header-search"
								/>
								<a-button class="btn-add-outline" @click="openCreate">
									<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
										<path d="M12 5v14M5 12h14" fill="none" />
									</svg>
									Agregar rol
								</a-button>
							</a-col>
						</a-row>
					</template>

					<a-table
						rowKey="id"
						:columns="columns"
						:data-source="filteredRoles"
						:loading="loading"
						:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
						:locale="{ emptyText: 'No hay datos' }"
						:scroll="{ x: 700 }"
					>
						<template slot="type" slot-scope="value, row">
							<a-tag :class="typeTagClass(row)">
								{{ displayUserType(row) }}
							</a-tag>
						</template>

						<template slot="actions" slot-scope="_, row">
							<div class="table-actions">
								<a-button size="small" type="primary" ghost :disabled="isImmutableRole(row)" @click="openEdit(row)">
									Editar
								</a-button>
								<a-button size="small" type="primary" ghost @click="openPermissions(row)">
									Permisos
								</a-button>
								<a-popconfirm
									title="¿Seguro que deseas eliminar este rol?"
									ok-text="Sí"
									cancel-text="No"
									@confirm="deleteRole(row)"
									:disabled="isImmutableRole(row)"
								>
									<a-button size="small" type="danger" ghost :disabled="isImmutableRole(row)">
										Eliminar
									</a-button>
								</a-popconfirm>
							</div>
						</template>
					</a-table>
				</a-card>

				<a-modal
					:title="editingRole ? 'Editar rol' : 'Agregar rol'"
					:visible="showModal"
					:forceRender="true"
					:footer="null"
					@cancel="closeModal"
				>
					<a-form :form="form" @submit="handleSubmit">
						<a-form-item label="Nombre del rol">
							<a-input
								:maxLength="200"
								v-decorator="['name', { rules: [{ required: true, message: 'Ingresa el nombre del rol.' }] }]"
								placeholder="Ej: Secretaría"
								:disabled="!!editingRole"
							/>
						</a-form-item>

						<a-form-item label="Tipo de usuario">
							<a-select
								placeholder="Selecciona un tipo"
								v-decorator="['user_type', { rules: [{ required: true, message: 'Selecciona el tipo de usuario.' }] }]"
								:disabled="editingRole && isImmutableRole(editingRole)"
							>
								<a-select-option value="Sistema">Sistema</a-select-option>
								<a-select-option value="Usuario normal">Usuario normal</a-select-option>
								<a-select-option value="Super usuario">Super usuario</a-select-option>
							</a-select>
						</a-form-item>

						<a-form-item>
							<a-button type="primary" html-type="submit" :loading="saving">
								Guardar
							</a-button>
						</a-form-item>
					</a-form>
				</a-modal>

				<a-modal
					title="Permisos del rol"
					:visible="showScreensModal"
					:confirm-loading="savingScreens"
					:ok-button-props="{ style: { display: isSuperUserRole(selectedRoleForScreens) ? 'none' : 'inline-block' } }"
					@ok="savePermissions"
					@cancel="closeScreensModal"
					width="1000px"
					:bodyStyle="{ padding: '20px', maxHeight: '70vh', overflowY: 'auto' }"
				>
					<div v-if="!selectedRoleForScreens" class="text-muted">Selecciona un rol.</div>

					<div v-else>
						<div class="text-muted" style="margin-bottom: 16px;">
							Rol: <strong>{{ selectedRoleForScreens.name }}</strong>
						</div>

						<!-- Spinner de carga -->
						<div v-if="savingScreens" style="text-align: center; padding: 40px;">
							<a-spin size="large" tip="Cargando permisos..." />
						</div>

						<!-- Contenido cuando no está cargando -->
						<div v-else>
							<a-alert
								v-if="isSuperUserRole(selectedRoleForScreens) || String(selectedRoleForScreens.name || '').trim().toLowerCase() === 'super usuario'"
								type="info"
								show-icon
								message="Este rol es Super usuario. Siempre tiene todas las pantallas y no se le pueden quitar permisos."
								style="margin-bottom: 16px;"
							/>

							<!-- Mostrar permisos solo si NO es Super usuario -->
							<div v-if="!isSuperUserRole(selectedRoleForScreens) && String(selectedRoleForScreens.name || '').trim().toLowerCase() !== 'super usuario'">
								<a-alert
									v-if="isImmutableRole(selectedRoleForScreens)"
									type="info"
									show-icon
									:message="immutableRoleMessage(selectedRoleForScreens)"
									style="margin-bottom: 16px;"
								/>

								<!-- Estructura simplificada: Secciones -> Pantallas -> Acciones -->
								<a-collapse 
									v-model="activeSections" 
									:bordered="false"
									:expand-icon-position="'right'"
									style="background: transparent; border: none;"
								>
									<a-collapse-panel 
										v-for="section in hierarchicalScreens" 
										:key="section.key"
										style="background: #f8f9fa; margin-bottom: 8px; border-radius: 6px; border: 1px solid #e8e8e8;"
									>
										<template #header>
											<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
												<span>{{ section.label }}</span>
												<div style="display: flex; align-items: center; gap: 12px;" @click.stop>
													<a-checkbox
														:checked="isSectionFullyChecked(section)"
														:indeterminate="isSectionPartiallyChecked(section)"
														:disabled="isImmutableRole(selectedRoleForScreens)"
														@change="toggleAllSectionChecks(section, $event)"
													>
														Todos
													</a-checkbox>
													<a-button 
														size="small" 
														type="primary" 
														ghost
														@click="toggleSectionPermissions(section)"
														:disabled="isImmutableRole(selectedRoleForScreens)"
													>
														{{ isSectionFullyEnabled(section) ? 'Deshabilitar todo' : 'Habilitar todo' }}
													</a-button>
												</div>
											</div>
										</template>

										<!-- Pantallas dentro de la sección -->
										<div class="screens-container">
											<div 
												v-for="screen in getAllScreensFromSection(section)" 
												:key="screen.key"
												class="screen-item"
											>
												<div class="screen-main">
													<span class="screen-label">{{ screen.label }}</span>
												</div>

												<div class="screen-actions">
													<a-checkbox
														class="action-checkbox"
														:checked="getScreenPerm(screen.key).can_create"
														:disabled="isImmutableRole(selectedRoleForScreens)"
														@change="onToggleScreenAction(screen.key, 'can_create', $event)"
													>
														Agregar
													</a-checkbox>
													<a-checkbox
														class="action-checkbox"
														:checked="getScreenPerm(screen.key).can_edit"
														:disabled="isImmutableRole(selectedRoleForScreens)"
														@change="onToggleScreenAction(screen.key, 'can_edit', $event)"
													>
														Editar
													</a-checkbox>
													<a-checkbox
														class="action-checkbox"
														:checked="getScreenPerm(screen.key).can_delete"
														:disabled="isImmutableRole(selectedRoleForScreens)"
														@change="onToggleScreenAction(screen.key, 'can_delete', $event)"
													>
														Eliminar
													</a-checkbox>
												</div>
											</div>
										</div>
									</a-collapse-panel>
								</a-collapse>
							</div>
						</div>
					</div>
				</a-modal>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken, getUser } from '@/utils/auth';

// Función para detectar si el usuario actual es Super usuario
function isCurrentUserSuperUser() {
	const user = getUser();
	if (!user || !user.role) return false;
	return String(user.role).trim().toLowerCase() === 'super usuario';
}

export default ({
	name: 'Roles',
	data() {
		return {
			roles: [],
			searchText: '',
			loading: false,
			saving: false,
			showModal: false,
			editingRole: null,
			form: null,
			screens: [],
			showScreensModal: false,
			savingScreens: false,
			selectedRoleForScreens: null,
			selectedScreenPerms: {},
			activeSections: [], // Para controlar qué secciones están expandidas
			activeTab: '0', // Para las pestañas
			columns: [
				{ title: 'Rol', dataIndex: 'name', width: 260 },
				{ title: 'Tipo', dataIndex: 'user_type', scopedSlots: { customRender: 'type' }, width: 160 },
				{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 240, align: 'right' },
			],
		};
	},
	computed: {
		isCurrentUserSuperUser() {
			return isCurrentUserSuperUser();
		},
		canEditRolePermissions() {
			// Solo Super usuario puede editar permisos de roles inmutables
			return this.isCurrentUserSuperUser;
		},
		debugUserInfo() {
			const user = getUser();
			return {
				user: user,
				role: user?.role,
				isSuper: this.isCurrentUserSuperUser,
				canEdit: this.canEditRolePermissions,
				roleString: String(user?.role || '').trim().toLowerCase()
			};
		},
		filteredRoles() {
			const q = String(this.searchText || '').trim().toLowerCase();
			if (!q) return this.roles;
			return (this.roles || []).filter((r) => {
				const parts = [
					(r && r.name) ? r.name : '',
					this.displayUserType(r),
				].filter(Boolean).join(' ').toLowerCase();
				return parts.includes(q);
			});
		},
		hierarchicalScreens() {
			// Si los datos ya vienen en formato jerárquico (con categories), usarlos directamente
			if (this.screens && this.screens.length > 0 && this.screens[0].categories) {
				return this.screens;
			}
			
			// Si no, procesar como formato plano
			const screens = (this.screens || []).slice().sort((a, b) => {
				const ga = String(a.section || a.group || '');
				const gb = String(b.section || b.group || '');
				if (ga !== gb) return ga.localeCompare(gb, 'es', { sensitivity: 'base' });
				return (a.sort_order || 0) - (b.sort_order || 0);
			});

			// Agrupar por sección
			const sectionsMap = new Map();
			screens.forEach((s) => {
				const sectionName = String(s.section || s.group || 'General');
				if (!sectionsMap.has(sectionName)) {
					sectionsMap.set(sectionName, {
						key: sectionName.toLowerCase().replace(/\s+/g, '_'),
						label: sectionName,
						categories: new Map()
					});
				}
				
				const section = sectionsMap.get(sectionName);
				const categoryName = String(s.category || 'General');
				
				if (!section.categories.has(categoryName)) {
					section.categories.set(categoryName, {
						key: categoryName.toLowerCase().replace(/\s+/g, '_'),
						label: categoryName,
						links: []
					});
				}
				
				section.categories.get(categoryName).links.push({
					key: s.key,
					label: s.label,
					sort_order: s.sort_order || 0,
					link_type: s.link_type || 'link'
				});
			});

			// Convertir Maps a Arrays
			return Array.from(sectionsMap.values()).map(section => ({
				...section,
				categories: Array.from(section.categories.values())
			}));
		},
	},
	beforeCreate() {
		this.form = this.$form.createForm(this, { name: 'role_form' });
	},
	mounted() {
		this.fetchRoles();
		this.fetchScreens();
	},
	methods: {
		isSuperUserRole(role) {
			const name = role && role.name != null ? String(role.name) : '';
			return name.trim().toLowerCase() === 'super usuario';
		},
		isSystemRole(role) {
			const t = String((role && role.user_type) ? role.user_type : '').trim().toLowerCase();
			if (t === 'sistema') return true;
			// fallback por compatibilidad
			return !!(role && role.is_system) && !this.isSuperUserRole(role);
		},
		isImmutableRole(role) {
			// Solo Super usuario es inmutable (no se puede editar)
			if (!role) return false;
			if (this.isSuperUserRole(role)) return true;
			
			// Todos los demás roles son editables
			return false;
		},
		immutableRoleMessage(role) {
			if (this.isSuperUserRole(role) || String(this.displayUserType(role)).toLowerCase() === 'super usuario') {
				return 'Este rol es Super usuario. Siempre tiene todas las pantallas y no se le pueden quitar permisos.';
			}
			const roleName = String(role.name || '').trim().toLowerCase();
			if (roleName === 'administrador') {
				return 'Este rol es Administrador. Tiene todos los permisos habilitados y solo puede ser modificado por un Super usuario.';
			}
			return 'Este rol es del sistema. Sus permisos no se pueden modificar.';
		},
		displayUserType(role) {
			if (!role) return 'Usuario normal';
			// Por nombre (rol fijo)
			if (this.isSuperUserRole(role)) return 'Super usuario';
			// Por columna
			const t = String((role && role.user_type) ? role.user_type : '').trim();
			if (t) return t;
			// Compatibilidad: roles viejos marcados como system
			if (role && role.is_system) return 'Sistema';
			return 'Usuario normal';
		},
		typeTagClass(role) {
			const t = String(this.displayUserType(role) || '').toLowerCase();
			if (t === 'sistema') return 'ant-tag-system';
			if (t === 'super usuario') return 'ant-tag-super';
			return 'ant-tag-muted';
		},
		ensureForm() {
			if (!this.form) {
				this.form = this.$form.createForm(this, { name: 'role_form' });
			}
		},
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchRoles() {
			this.loading = true;
			axios.get('http://localhost:8000/api/roles', { headers: this.apiHeaders() })
				.then((res) => { this.roles = res.data || []; })
				.catch((err) => console.error('Error al obtener roles:', err.response?.data || err))
				.finally(() => { this.loading = false; });
		},
		fetchScreens() {
			// Intentar usar el nuevo endpoint jerárquico primero
			axios.get('http://localhost:8000/api/roles/screens-hierarchy', { headers: this.apiHeaders() })
				.then((res) => { 
					this.screens = res.data || [];
				})
				.catch((err) => {
					// Si falla, usar el endpoint original
					axios.get('http://localhost:8000/api/screens', { headers: this.apiHeaders() })
						.then((res) => { this.screens = res.data || []; })
						.catch((err) => {
							console.error('Error al obtener pantallas:', err.response?.data || err);
							this.screens = [];
						});
				});
		},
		openCreate() {
			this.ensureForm();
			this.editingRole = null;
			this.showModal = true;
			this.$nextTick(() => {
				if (this.form && typeof this.form.resetFields === 'function') this.form.resetFields();
				if (this.form && typeof this.form.setFieldsValue === 'function') {
					this.form.setFieldsValue({ user_type: 'Usuario normal' });
				}
			});
		},
		openEdit(row) {
			this.ensureForm();
			this.editingRole = row;
			this.showModal = true;
			this.$nextTick(() => {
				setTimeout(() => {
					if (!this.form) return;
					// reset primero para evitar valores anteriores (y para campos disabled)
					if (typeof this.form.resetFields === 'function') this.form.resetFields();
					if (typeof this.form.setFieldsValue === 'function') {
						this.form.setFieldsValue({
							name: (row && row.name) ? row.name : '',
							user_type: this.displayUserType(row),
						});
					}
				}, 0);
			});
		},
		closeModal() {
			this.showModal = false;
			this.editingRole = null;
			if (this.form && typeof this.form.resetFields === 'function') this.form.resetFields();
		},
		handleSubmit(e) {
			e.preventDefault();
			this.ensureForm();
			if (!this.form || typeof this.form.validateFields !== 'function') return;
			this.form.validateFields((err, values) => {
				if (err) return;
				this.saving = true;

				const url = this.editingRole
					? `http://localhost:8000/api/roles/${this.editingRole.id}`
					: 'http://localhost:8000/api/roles';
				const method = this.editingRole ? 'put' : 'post';

				const payload = this.editingRole
					? { user_type: values.user_type }
					: values;

				axios({
					method,
					url,
					data: payload,
					headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' },
				})
					.then(() => {
						this.closeModal();
						this.fetchRoles();
					})
					.catch((error) => console.error('Error guardando rol:', error.response?.data || error))
					.finally(() => { this.saving = false; });
			});
		},
		deleteRole(row) {
			if (!row || !row.id) return;
			axios.delete(`http://localhost:8000/api/roles/${row.id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchRoles())
				.catch((error) => {
					const msg = (error && error.response && error.response.data && error.response.data.message)
						? error.response.data.message
						: 'No se pudo eliminar el rol.';
					console.error('Error eliminando rol:', (error && error.response && error.response.data) ? error.response.data : error);
					this.$message.error(msg);
				});
		},
		openPermissions(role) {
			if (!role || !role.id) return;
			
			this.selectedRoleForScreens = role;
			this.showScreensModal = true;
			this.savingScreens = true; // Activar spinner
			this.selectedScreenPerms = {};

			// Si es Super usuario, mostrar mensaje y no dejar editar
			if (this.isSuperUserRole(role)) {
				this.savingScreens = false; // Desactivar spinner
				// No cargar permisos, solo mostrar el mensaje de inmutabilidad
				return;
			}

			// Para todos los demás roles, cargar permisos del rol Y todas las pantallas disponibles
			// Cargar permisos del rol
			axios.get(`http://localhost:8000/api/roles/${role.id}/screens`, { headers: this.apiHeaders() })
				.then((res) => {
					const perms = (res && res.data && res.data.screen_permissions) ? res.data.screen_permissions : null;
					const keys = (res && res.data && res.data.screen_keys) ? res.data.screen_keys : [];

					// Cargar todas las pantallas disponibles para mostrarlas en el modal
					// Primero intentar con el endpoint jerárquico, si no funciona usar el normal
					axios.get('http://localhost:8000/api/roles/screens-hierarchy', { headers: this.apiHeaders() })
						.then((screensRes) => {
							// Si el endpoint jerárquico devuelve datos vacíos, usar el endpoint normal
							if (!screensRes.data || !Array.isArray(screensRes.data) || screensRes.data.length === 0) {
								return axios.get('http://localhost:8000/api/screens', { headers: this.apiHeaders() });
							}
							
							return { data: screensRes.data };
						})
						.then((screensRes) => {
							let allScreens = [];
							let processedScreens = [];
							
							// Procesar según el formato de datos
							if (Array.isArray(screensRes.data)) {
								// Verificar si es formato jerárquico o plano
								if (screensRes.data.length > 0 && screensRes.data[0].categories) {
									// Formato jerárquico
									processedScreens = screensRes.data;
									screensRes.data.forEach(section => {
										if (section.categories) {
											section.categories.forEach(category => {
												if (category.links) {
													category.links.forEach(link => {
														allScreens.push(link.key);
													});
												}
											});
										}
									});
								} else {
									// Formato plano - convertir a jerárquico
									processedScreens = this.convertToHierarchical(screensRes.data);
									allScreens = screensRes.data.map(screen => screen.key);
								}
							}
							
							// Actualizar this.screens con los datos procesados
							this.screens = processedScreens;

							// Crear objeto de permisos combinando los del rol con todas las pantallas disponibles
							const out = {};
							
							// Primero, agregar los permisos existentes del rol
							if (perms && typeof perms === 'object') {
								Object.keys(perms).forEach((k) => {
									out[String(k)] = {
										can_create: !!perms[k]?.can_create,
										can_edit: !!perms[k]?.can_edit,
										can_delete: !!perms[k]?.can_delete,
									};
								});
							} else {
								// Si solo vienen keys, dar todos los permisos para esas keys
								(keys || []).map(String).forEach((k) => {
									out[k] = { can_create: true, can_edit: true, can_delete: true };
								});
							}

							// Luego, agregar todas las pantallas disponibles sin permisos (para que se puedan editar)
							allScreens.forEach(screenKey => {
								const key = String(screenKey);
								if (!out[key]) {
									// Si es un rol de sistema, dar todos los permisos por defecto
									const isSystemRole = String(role.name || '').trim().toLowerCase() === 'sistema' || 
													  String(role.user_type || '').trim().toLowerCase() === 'sistema';
									
									if (isSystemRole) {
										out[key] = { can_create: true, can_edit: true, can_delete: true };
									} else {
										out[key] = { can_create: false, can_edit: false, can_delete: false };
									}
								}
							});
							
							this.selectedScreenPerms = out;
							this.savingScreens = false; // Desactivar spinner
						})
						.catch((err) => {
							// Si falla, usar solo los permisos del rol
							if (perms && typeof perms === 'object') {
								const out = {};
								Object.keys(perms).forEach((k) => {
									out[String(k)] = {
										can_create: !!perms[k]?.can_create,
										can_edit: !!perms[k]?.can_edit,
										can_delete: !!perms[k]?.can_delete,
									};
								});
								this.selectedScreenPerms = out;
							} else {
								const out = {};
								(keys || []).map(String).forEach((k) => {
									out[k] = { can_create: true, can_edit: true, can_delete: true };
								});
								this.selectedScreenPerms = out;
							}
							this.savingScreens = false; // Desactivar spinner
						});
				})
				.catch((err) => {
					this.selectedScreenPerms = {};
					this.savingScreens = false; // Desactivar spinner
				});
		},
		convertToHierarchical(flatScreens) {
			// Agrupar por sección/categoría
			const sectionsMap = new Map();
			
			flatScreens.forEach(screen => {
				const sectionName = screen.section || screen.group || 'General';
				const categoryName = screen.category || 'General';
				
				if (!sectionsMap.has(sectionName)) {
					sectionsMap.set(sectionName, {
						key: sectionName.toLowerCase().replace(/\s+/g, '_'),
						label: sectionName,
						categories: new Map()
					});
				}
				
				const section = sectionsMap.get(sectionName);
				
				if (!section.categories.has(categoryName)) {
					section.categories.set(categoryName, {
						key: categoryName.toLowerCase().replace(/\s+/g, '_'),
						label: categoryName,
						links: []
					});
				}
				
				section.categories.get(categoryName).links.push({
					key: screen.key,
					label: screen.label,
					sort_order: screen.sort_order || 0,
					link_type: screen.link_type || 'link'
				});
			});
			
			// Convertir Maps a Arrays
			const result = Array.from(sectionsMap.values()).map(section => ({
				...section,
				categories: Array.from(section.categories.values())
			}));
			
			return result;
		},
		closeScreensModal() {
			this.showScreensModal = false;
			this.savingScreens = false;
			this.selectedRoleForScreens = null;
			this.selectedScreenPerms = {};
		},
		savePermissions() {
			const role = this.selectedRoleForScreens;
			if (!role || !role.id) return;
			if (this.isImmutableRole(role)) return;

			this.savingScreens = true;
			const payload = {
				screen_permissions: Object.keys(this.selectedScreenPerms || {}).map((k) => ({
					key: String(k),
					can_create: !!this.selectedScreenPerms[k]?.can_create,
					can_edit: !!this.selectedScreenPerms[k]?.can_edit,
					can_delete: !!this.selectedScreenPerms[k]?.can_delete,
				})),
			};
			axios.put(`http://localhost:8000/api/roles/${role.id}/screens`, {
				...payload,
			}, { headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
				.then(() => {
					this.$message.success('Pantallas actualizadas.');
					
					// Limpiar caché de pantallas para que el menú se actualice
					localStorage.removeItem('allowedScreens');
					sessionStorage.removeItem('allowedScreens');
					
					// Si estamos editando permisos del usuario actual, también conviene limpiar el cache de sidebar/guard
					const user = getUser();
					if (user && String(user.role) === String(role.name)) {
						// Forzar recarga de la página para que se actualice el menú
						window.location.reload();
					}
					
					this.closeScreensModal();
				})
				.catch((err) => {
					const msg = (err && err.response && err.response.data && err.response.data.message)
						? err.response.data.message
						: 'No se pudieron guardar las pantallas.';
					console.error('Error guardando pantallas:', (err && err.response && err.response.data) ? err.response.data : err);
					this.$message.error(msg);
				})
				.finally(() => {
					this.savingScreens = false;
				});
		},
		isScreenEnabled(key) {
			const k = String(key);
			return !!(this.selectedScreenPerms && this.selectedScreenPerms[k]);
		},
		getScreenPerm(key) {
			const k = String(key);
			const current = (this.selectedScreenPerms && this.selectedScreenPerms[k]) ? this.selectedScreenPerms[k] : null;
			return current || { can_create: false, can_edit: false, can_delete: false };
		},
		onToggleScreen(key, e) {
			const k = String(key);
			const checked = !!(e && e.target && e.target.checked);
			const perms = { ...(this.selectedScreenPerms || {}) };

			if (checked) {
				if (!perms[k]) perms[k] = { can_create: true, can_edit: true, can_delete: true };
				this.selectedScreenPerms = perms;
				return;
			}

			delete perms[k];
			this.selectedScreenPerms = perms;
		},
		onToggleScreenAction(key, field, e) {
			const k = String(key);
			const f = String(field);
			const checked = !!(e && e.target && e.target.checked);

			if (!this.selectedScreenPerms || !this.selectedScreenPerms[k]) return;

			const perms = { ...(this.selectedScreenPerms || {}) };
			const cur = { ...(perms[k] || { can_create: true, can_edit: true, can_delete: true }) };
			cur[f] = checked;

			// Permitir que las 3 acciones queden en false (sin acceso a la pantalla)
			perms[k] = cur;
			this.selectedScreenPerms = perms;
		},

		// Métodos para la estructura jerárquica
		isSectionFullyEnabled(section) {
			if (!section.categories || section.categories.length === 0) return false;
			return section.categories.every(category => 
				this.isCategoryFullyEnabled(category)
			);
		},

		isCategoryFullyEnabled(category) {
			if (!category.links || category.links.length === 0) return false;
			return category.links.every(link => this.isScreenEnabled(link.key));
		},

		toggleSectionPermissions(section) {
			const enable = !this.isSectionFullyEnabled(section);
			section.categories.forEach(category => {
				this.toggleCategoryPermissions(category, enable);
			});
		},

		toggleCategoryPermissions(category, forceEnable = null) {
			const enable = forceEnable !== null ? forceEnable : !this.isCategoryFullyEnabled(category);
			const perms = { ...(this.selectedScreenPerms || {}) };

			category.links.forEach(link => {
				if (enable) {
					if (!perms[link.key]) {
						perms[link.key] = { can_create: true, can_edit: true, can_delete: true };
					}
				} else {
					delete perms[link.key];
				}
			});

			this.selectedScreenPerms = perms;
		},

		editSection(section) {
			this.$message.info(`Editar sección: ${section.label}`);
			// TODO: Implementar modal para editar sección
		},

		editCategory(category) {
			this.$message.info(`Editar categoría: ${category.label}`);
			// TODO: Implementar modal para editar categoría
		},

		deleteCategory(category) {
			this.$confirm({
				title: '¿Eliminar categoría?',
				content: `¿Estás seguro de que deseas eliminar la categoría "${category.label}" y todos sus enlaces?`,
				okText: 'Sí',
				okType: 'danger',
				cancelText: 'No',
				onOk: () => {
					this.$message.success(`Categoría "${category.label}" eliminada`);
					// TODO: Implementar eliminación en backend
				}
			});
		},

		deleteLink(link) {
			this.$confirm({
				title: '¿Eliminar enlace?',
				content: `¿Estás seguro de que deseas eliminar el enlace "${link.label}"?`,
				okText: 'Sí',
				okType: 'danger',
				cancelText: 'No',
				onOk: () => {
					const perms = { ...(this.selectedScreenPerms || {}) };
					delete perms[link.key];
					this.selectedScreenPerms = perms;
					this.$message.success(`Enlace "${link.label}" eliminado`);
				}
			});
		},

		addLink(category) {
			this.$message.info(`Agregar enlace a categoría: ${category.label}`);
			// TODO: Implementar modal para agregar nuevo enlace
		},

		addCategory(section) {
			this.$message.info(`Agregar categoría a sección: ${section.label}`);
			// TODO: Implementar modal para agregar nueva categoría
		},

		// Método simplificado para obtener todas las pantallas de una sección
		getAllScreensFromSection(section) {
			const allScreens = [];
			if (section.categories) {
				section.categories.forEach(category => {
					if (category.links) {
						allScreens.push(...category.links);
					}
				});
			}
			return allScreens.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
		},

		// Métodos para el checkbox "Todos" de cada sección
		isSectionFullyChecked(section) {
			const screens = this.getAllScreensFromSection(section);
			if (screens.length === 0) return false;
			
			return screens.every(screen => {
				const perm = this.getScreenPerm(screen.key);
				return perm.can_create && perm.can_edit && perm.can_delete;
			});
		},

		isSectionPartiallyChecked(section) {
			const screens = this.getAllScreensFromSection(section);
			if (screens.length === 0) return false;
			
			const fullyChecked = this.isSectionFullyChecked(section);
			const hasAnyChecked = screens.some(screen => {
				const perm = this.getScreenPerm(screen.key);
				return perm.can_create || perm.can_edit || perm.can_delete;
			});
			
			return !fullyChecked && hasAnyChecked;
		},

		toggleAllSectionChecks(section, e) {
			const checked = !!(e && e.target && e.target.checked);
			const screens = this.getAllScreensFromSection(section);
			
			const perms = { ...(this.selectedScreenPerms || {}) };
			
			screens.forEach(screen => {
				const key = String(screen.key);
				if (!perms[key]) {
					perms[key] = { can_create: false, can_edit: false, can_delete: false };
				}
				
				perms[key] = {
					can_create: checked,
					can_edit: checked,
					can_delete: checked
				};
			});
			
			this.selectedScreenPerms = perms;
		},
	},
})
</script>

<style scoped>
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

.header-search {
	width: 240px;
	min-width: 180px;
}

.table-actions {
	display: inline-flex;
	gap: 8px;
	align-items: center;
	justify-content: flex-end;
	white-space: nowrap;
	margin-right: 16px;
}

/* Estilos para la estructura simplificada de permisos */
.permissions-structure {
	display: flex;
	flex-direction: column;
	gap: 24px;
}

.permission-section {
	border: 1px solid #e8e8e8;
	border-radius: 8px;
	background: #fff;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.section-header {
	background: linear-gradient(135deg, #f5f5f5, #fafafa);
	padding: 16px 20px;
	border-bottom: 1px solid #e8e8e8;
}

.section-title {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.section-title h3 {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
	color: #262626;
}

.section-actions {
	display: flex;
	gap: 8px;
}

.screens-container {
	padding: 16px 20px;
}

.screen-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 16px;
	margin-bottom: 8px;
	border: 1px solid #f0f0f0;
	border-radius: 6px;
	background: #fafafa;
	transition: all 0.2s ease;
}

.screen-item:hover {
	background: #f0f8ff;
	border-color: #d6e4ff;
	box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);
}

.screen-item:last-child {
	margin-bottom: 0;
}

.screen-main {
	flex: 1;
	min-width: 0;
}

.screen-label {
	font-size: 14px;
	color: #595959;
	font-weight: 500;
}

.screen-actions {
	display: flex;
	align-items: center;
	gap: 16px;
	flex-shrink: 0;
}

.action-checkbox {
	display: flex;
	align-items: center;
}

.action-checkbox >>> .ant-checkbox + span {
	font-size: 12px;
	color: #8c8c8c;
	font-weight: 500;
}

/* Estados activos */
.screen-item:has(.ant-checkbox-wrapper-checked) {
	background: #e6f7ff;
	border-color: #91d5ff;
}

/* Responsive */
@media (max-width: 768px) {
	.permissions-structure {
		gap: 16px;
	}
	
	.section-title {
		flex-direction: column;
		align-items: flex-start;
		gap: 12px;
	}
	
	.screen-item {
		flex-direction: column;
		align-items: flex-start;
		gap: 12px;
	}
	
	.screen-actions {
		width: 100%;
		justify-content: flex-start;
		padding-left: 24px;
	}
}

/* Mejoras visuales */
::v-deep .ant-checkbox-wrapper {
	display: flex;
	align-items: center;
}

::v-deep .ant-checkbox-wrapper:hover {
	background: rgba(24, 144, 255, 0.05);
	border-radius: 4px;
	padding: 4px 8px;
	margin: -4px -8px;
}

::v-deep .ant-checkbox-wrapper-checked {
	background: rgba(24, 144, 255, 0.1);
	border-radius: 4px;
	padding: 4px 8px;
	margin: -4px -8px;
}

::v-deep .ant-checkbox-wrapper-checked .ant-checkbox-inner {
	border-color: #1890ff;
	background-color: #1890ff;
}

/* Botones */
::v-deep .ant-btn-primary.ant-btn-ghost {
	border-color: #1890ff;
	color: #1890ff;
}

::v-deep .ant-btn-primary.ant-btn-ghost:hover {
	background: #1890ff;
	color: #fff;
}

/* Scroll para el modal */
::v-deep .ant-modal-body {
	max-height: 70vh;
	overflow-y: auto;
}
</style>

