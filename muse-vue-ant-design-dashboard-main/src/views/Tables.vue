<!-- 
	This is the tables page, it uses the dashboard layout in: 
	"./layouts/Dashboard.vue" .
-->

<template>
	<div>

		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<UsersTable
					:data="usersData"
					:columns="usersColumns"
					:loading="loadingUsers"
					@openAddUserModal="openCreateUser"
					@editUser="openEditUser"
					@openUserPermissions="openUserPermissions"
					@activateUser="activateUser"
					@deactivateUser="deactivateUser"
					@deleteUser="deleteUser"
					@resetUserPassword="resetUserPassword"
				/>

				<a-modal
					:title="editingUser ? 'Editar Usuario' : 'Agregar Nuevo Usuario'"
					:visible="showUserModal"
					:footer="null"
					@cancel="closeUserModal"
				>
					<UserForm
						:initialValues="editingUser"
						:roles="rolesOptions"
						:submitText="editingUser ? 'Actualizar' : 'Guardar'"
						:resetAfterSubmit="!editingUser"
						@submitForm="handleSubmitUser"
					/>
				</a-modal>

				<a-modal
					title="Permisos del usuario"
					:visible="showUserScreensModal"
					:confirm-loading="savingUserScreens"
					:ok-button-props="{ style: { display: isSuperUserRole(selectedUserForScreens) ? 'none' : 'inline-block' } }"
					@ok="saveUserPermissions"
					@cancel="closeUserPermissions"
					width="1000px"
					:bodyStyle="{ padding: '20px', maxHeight: '70vh', overflowY: 'auto' }"
				>
					<div v-if="!selectedUserForScreens" class="text-muted">Selecciona un usuario.</div>

					<div v-else>
						<div class="text-muted" style="margin-bottom: 16px;">
							Usuario: <strong>{{ selectedUserForScreens.name }}</strong>
							<span v-if="selectedUserForScreens.email">({{ selectedUserForScreens.email }})</span>
							<span v-if="selectedUserForScreens.role" class="user-role-badge">Rol: {{ selectedUserForScreens.role }}</span>
						</div>

						<!-- Show message for Super usuario -->
						<a-alert
							v-if="isSuperUserRole(selectedUserForScreens)"
							type="info"
							show-icon
							message="Este usuario es Super usuario"
							description="Siempre tiene todas las pantallas y no se le pueden modificar los permisos."
							style="margin-bottom: 16px;"
						/>

						<!-- Show permissions interface for other users (same as Roles.vue) -->
						<div v-else>
							<!-- Spinner de carga -->
							<div v-if="loadingUserScreens" style="text-align: center; padding: 40px;">
								<a-spin size="large" tip="Cargando permisos..." />
							</div>

							<!-- Contenido cuando no está cargando -->
							<div v-else>
								<a-alert
									v-if="isImmutableRole(selectedUserForScreens)"
									type="info"
									show-icon
									:message="immutableRoleMessage(selectedUserForScreens)"
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
														:disabled="isImmutableRole(selectedUserForScreens)"
														@change="toggleAllSectionChecks(section, $event)"
													>
														Todos
													</a-checkbox>
													<a-button 
														size="small" 
														type="primary" 
														ghost
														@click="toggleSectionPermissions(section)"
														:disabled="isImmutableRole(selectedUserForScreens)"
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
														:disabled="isImmutableRole(selectedUserForScreens)"
														@change="onToggleScreenAction(screen.key, 'can_create', $event)"
													>
														Agregar
													</a-checkbox>
													<a-checkbox
														class="action-checkbox"
														:checked="getScreenPerm(screen.key).can_edit"
														:disabled="isImmutableRole(selectedUserForScreens)"
														@change="onToggleScreenAction(screen.key, 'can_edit', $event)"
													>
														Editar
													</a-checkbox>
													<a-checkbox
														class="action-checkbox"
														:checked="getScreenPerm(screen.key).can_delete"
														:disabled="isImmutableRole(selectedUserForScreens)"
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
import UsersTable from '../components/Users/UsersTable.vue';
import UserForm from '../components/Users/UserForm.vue';
import { h } from 'vue';

export default ({
	components: {
		UsersTable,
		UserForm,
	},
	data() {
		return {
			apiBaseUrl: 'http://localhost:8000/api', // Base URL for API calls
			showUserModal: false,
			editingUser: null,
			loadingUsers: false,
			usersData: [],
			rolesOptions: [],
			usersColumns: [
				{
					title: 'Nombre',
					dataIndex: 'userName',
					scopedSlots: { customRender: 'userName' },
					width: 320,
				},
				{
					title: 'Rol',
					dataIndex: 'role',
					class: 'text-muted',
					width: 160,
				},
				{
					title: 'Estado',
					dataIndex: 'status',
					scopedSlots: { customRender: 'status' },
					width: 140,
				},
				{
					title: 'Acciones',
					scopedSlots: { customRender: 'actions' },
					width: 220,
					align: 'right',
				},
			],

			// Permisos por usuario (overrides)
			showUserScreensModal: false,
			savingUserScreens: false,
			loadingUserScreens: false, // Nuevo: estado de carga
			selectedUserForScreens: null,
			screens: [],
			activeSections: [],
			selectedScreenPerms: {},
			roleScreenKeys: [],
			overrideAllowKeys: [],
			overrideDenyKeys: [],
			effectiveScreenKeys: [],
		};
	},
	computed: {
		hierarchicalScreens() {
			return this.screens || [];
		},
	},
	created() {
		console.log('=== COMPONENTE CREADO ===');
	},
	mounted() {
		console.log('=== COMPONENTE MONTADO ===');
		this.fetchUsers();
		this.fetchRoles();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchRoles() {
			const authToken = getToken();
			axios.get(`${this.apiBaseUrl}/roles`, {
				headers: { 'Authorization': `Bearer ${authToken}` }
			})
			.then((response) => {
				const list = (response.data || []).map((r) => r?.name).filter(Boolean);
				this.rolesOptions = list;
			})
			.catch((error) => {
				console.error('Error al obtener roles:', error.response?.data || error);
				this.rolesOptions = [];
			});
		},
		openCreateUser() {
			this.editingUser = null;
			this.showUserModal = true;
		},
		openEditUser(row) {
			const raw = row && row._raw ? row._raw : null;
			if (raw && raw.isDeleted) {
				if (this.$message) this.$message.info('Este usuario está eliminado y solo se mantiene como registro.');
				return;
			}
			if (raw && raw.isInactive) {
				if (this.$message) this.$message.info('Este usuario está desactivado. Solo se permite volver a activarlo.');
				return;
			}

			this.editingUser = null;
			this.showUserModal = false;
			
			this.$nextTick(() => {
				this.editingUser = {
					...raw,
					photo_url: raw.photo_url || null,
				};
				this.$nextTick(() => {
					this.showUserModal = true;
				});
			});
		},
		fetchUsers() {
			this.loadingUsers = true;
			const authToken = getToken();
			axios.get(`${this.apiBaseUrl}/user`, {
				headers: { 'Authorization': `Bearer ${authToken}` }
			})
			.then((response) => {
				this.usersData = (response.data || []).map((user) => {
					const role = user.role || 'Usuario';
					const isDeleted = !!user.deleted_at;
					const isInactive = !isDeleted && user.status === 'Inactivo';
					const statusLabel = isDeleted
						? 'Eliminado'
						: (user.status === 'Inactivo' ? 'Desactivado' : (user.status || (user.email_verified_at ? 'Activo' : 'Desactivado')));

					return {
						key: user.id,
						_raw: { ...user, role, status: (user.status || 'Activo'), statusLabel, isDeleted, isInactive },
						userName: {
							name: user.name,
							email: user.email,
							avatar: user.photo_url || null,
						},
						email: user.email,
						role,
						status: statusLabel,
						createdAt: this.formatDate(user.created_at),
						endedAt: this.formatDate(user.ended_at),
					};
				});
			})
			.catch((error) => {
				console.error('Error al obtener usuarios:', error.response?.data || error);
			})
			.finally(() => {
				this.loadingUsers = false;
			});
		},
		handleSubmitUser(userData) {
			const authToken = getToken();
			const url = this.editingUser
				? `${this.apiBaseUrl}/user/${this.editingUser.id}`
				: `${this.apiBaseUrl}/user`;
			const method = this.editingUser ? 'post' : 'post';

			if (userData.photoFile) {
				const formData = new FormData();
				
				Object.keys(userData).forEach(key => {
					if (key !== 'photoFile') {
						formData.append(key, userData[key] || '');
					}
				});
				
				formData.append('photo', userData.photoFile);
				
				if (this.editingUser) {
					formData.append('_method', 'PUT');
				}

				axios({ method, url, data: formData, headers: { ...{ 'Authorization': `Bearer ${authToken}` }, 'Content-Type': 'multipart/form-data' } })
					.then(() => {
						this.closeUserModal();
						this.fetchUsers();
					})
					.catch((error) => {
						console.error('Error guardando usuario:', error.response?.data || error);
					});
			} else {
				const payload = this.editingUser
					? {
						name: userData.name,
						role: userData.role,
						status: userData.status,
						_method: 'PUT',
					}
					: userData;

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
					this.closeUserModal();
					this.fetchUsers();
				})
				.catch((error) => {
					console.error('Error guardando usuario:', error.response?.data || error);
				});
			}
		},
		deleteUser(row) {
			const authToken = getToken();
			const id = row && (row.key || row.id);
			if (!id) return;

			axios.delete(`${this.apiBaseUrl}/user/${id}`, {
				headers: { 'Authorization': `Bearer ${authToken}` }
			})
			.then(() => {
				this.fetchUsers();
			})
			.catch((error) => {
				const msg = error?.response?.data?.message || 'No se pudo eliminar el usuario.';
				console.error('Error eliminando usuario:', error.response?.data || error);
				if (this.$message) this.$message.error(msg);
			});
		},
		deactivateUser(row) {
			const authToken = getToken();
			const id = row && (row.key || row.id);
			if (!id) return;

			axios.patch(`${this.apiBaseUrl}/user/${id}/deactivate`, {}, {
				headers: { 'Authorization': `Bearer ${authToken}` },
			})
			.then(() => {
				this.fetchUsers();
			})
			.catch((error) => {
				const msg = error?.response?.data?.message || 'No se pudo desactivar el usuario.';
				console.error('Error desactivando usuario:', error.response?.data || error);
				if (this.$message) this.$message.error(msg);
			});
		},
		activateUser(row) {
			const authToken = getToken();
			const id = row && (row.key || row.id);
			if (!id) return;

			axios.patch(`${this.apiBaseUrl}/user/${id}/activate`, {}, {
				headers: { 'Authorization': `Bearer ${authToken}` },
			})
			.then(() => {
				this.fetchUsers();
			})
			.catch((error) => {
				const msg = error?.response?.data?.message || 'No se pudo activar el usuario.';
				console.error('Error activando usuario:', error.response?.data || error);
				if (this.$message) this.$message.error(msg);
			});
		},
		resetUserPassword(row) {
			console.log('🔄 BOTÓN RESETEAR CONTRASEÑA PRESIONADO');
			
			const id = row && (row.key || row.id);
			if (!id) return;

			// Generar contraseña temporal localmente
			const tempPassword = this.generateTempPassword();
			
			// Obtener los datos actuales del usuario
			const user = row._raw || row;
			
			// Guardar la contraseña temporal en el backend
			const authToken = getToken();
			
			// Mostrar mensaje de que se está procesando
			this.$message.loading('Generando contraseña temporal...', 0);
			
			// Usar PUT con JSON que funcionó, guardando en el campo password
			axios({
				method: 'PUT',
				url: `${this.apiBaseUrl}/user/${id}`,
				data: {
					name: user.name || '',
					email: user.email || user.userName?.email,
					role: user.role || '',
					status: user.status || 'Activo',
					password: tempPassword // Guardar en campo password que sí existe
				},
				headers: {
					'Authorization': `Bearer ${authToken}`,
					'Content-Type': 'application/json',
				},
			})
			.then((response) => {
				this.$message.destroy();
				console.log('✅ CONTRASEÑA GUARDADA:', tempPassword);
				
				// Mostrar éxito
				this.$message.success(`Contraseña temporal generada: ${tempPassword}`, 10);
				
				// Copiar al portapapeles
				this.copyToClipboard(tempPassword);
				
				// Mostrar modal con la contraseña
				this.showTempPasswordModal(row, tempPassword);
				
				// Actualizar lista de usuarios
				this.fetchUsers();
			})
			.catch((error) => {
				this.$message.destroy();
				console.error('❌ ERROR GUARDANDO:', error.response?.data);
				this.$message.error('No se pudo guardar la contraseña temporal', 10);
			});
		},

		generateTempPassword() {
			// Generar contraseña temporal de 8 caracteres alfanuméricos
			const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			let password = '';
			for (let i = 0; i < 8; i++) {
				password += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			return password;
		},

		copyToClipboard(text) {
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).catch(err => {
					console.error('Error al copiar al portapapeles:', err);
				});
			}
		},

		showTempPasswordModal(user, tempPassword) {
			// Crear un modal para mostrar la contraseña temporal usando $info de Ant Design
			this.$info({
				title: 'Contraseña Temporal Generada',
				content: h => {
					return h('div', { style: 'text-align: center; padding: 20px;' }, [
						h('p', { style: 'margin-bottom: 16px; font-size: 16px;' }, `Usuario: ${user.userName?.name || user.name}`),
						h('p', { style: 'margin-bottom: 16px; font-size: 14px; color: #666;' }, `Email: ${user.userName?.email || user.email}`),
						h('div', { 
							style: 'background: #f0f0f0; padding: 16px; border-radius: 8px; margin: 16px 0;' 
						}, [
							h('p', { style: 'margin: 0; font-weight: bold; color: #333;' }, 'Contraseña Temporal:'),
							h('p', { 
								style: 'margin: 8px 0 0 0; font-size: 18px; font-weight: bold; color: #1890ff; font-family: monospace;' 
							}, tempPassword)
						]),
						h('p', { style: 'margin-top: 16px; font-size: 12px; color: #999;' }, 
							'Esta contraseña temporal está activa y el usuario podrá iniciar sesión con ella.'
						)
					]);
				},
				width: 500,
				okText: 'Entendido'
			});
		},

		// Helper methods for role detection (same as Roles.vue)
		isSuperUserRole(role) {
			if (!role) return false;
			// If role is a user object, get the role property
			const roleName = role.role || role.name || role || '';
			const roleNameStr = String(roleName).trim();
			return roleNameStr.toLowerCase() === 'super usuario';
		},
		isImmutableRole(role) {
			return this.isSuperUserRole(role);
		},
		immutableRoleMessage(role) {
			if (this.isSuperUserRole(role)) {
				return 'Este rol es Super usuario. Siempre tiene todas las pantallas y no se le pueden quitar permisos.';
			}
			return 'Este rol no se puede modificar.';
		},

		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},

		convertToHierarchical(flatScreens) {
			const sectionsMap = new Map();
			
			flatScreens.forEach(screen => {
				// Si no hay sección o categoría, crear una sección individual para cada pantalla
				if (!screen.section && !screen.category) {
					const sectionName = screen.label || 'General';
					if (!sectionsMap.has(screen.key)) {
						sectionsMap.set(screen.key, {
							key: screen.key,
							label: sectionName,
							categories: [
								{
									key: 'main',
									label: 'Principal',
									links: [{
										key: screen.key,
										label: screen.label,
										sort_order: screen.sort_order || 0,
										link_type: screen.link_type || 'link'
									}]
								}
							]
						});
					}
				} else {
					// Lógica original para pantallas con sección/categoría
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
				}
			});
			
			const result = Array.from(sectionsMap.values()).map(section => {
				// Convertir categories Map a Array si es necesario
				if (section.categories && section.categories instanceof Map) {
					return {
						...section,
						categories: Array.from(section.categories.values())
					};
				}
				return section;
			});
			
			return result;
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

		closeUserModal() {
			this.showUserModal = false;
			this.editingUser = null;
		},
		openUserPermissions(row) {
			const raw = row && row._raw ? row._raw : null;
			if (!raw) return;

			this.selectedUserForScreens = raw;
			this.showUserScreensModal = true;
			this.loadingUserScreens = true; // Activar spinner de carga
			this.selectedScreenPerms = {};

			// Si es Super usuario, mostrar mensaje y no dejar editar
			if (this.isSuperUserRole(raw)) {
				this.loadingUserScreens = false;
				return;
			}

			// Para todos los demás usuarios, cargar permisos del rol del usuario
			const roleName = raw.role;
			if (!roleName) {
				this.loadingUserScreens = false;
				return;
			}

			// Buscar el rol por nombre para obtener sus permisos
			axios.get(`${this.apiBaseUrl}/roles`, { headers: this.apiHeaders() })
				.then((rolesRes) => {
					const roles = rolesRes.data || [];
					const role = roles.find(r => r.name.toLowerCase() === roleName.toLowerCase());
					
					if (!role) {
						this.loadingUserScreens = false;
						return;
					}

					// Cargar permisos del rol
					axios.get(`${this.apiBaseUrl}/roles/${role.id}/screens`, { headers: this.apiHeaders() })
						.then((res) => {
							const perms = (res && res.data && res.data.screen_permissions) ? res.data.screen_permissions : null;
							const keys = (res && res.data && res.data.screen_keys) ? res.data.screen_keys : [];

							// Cargar todas las pantallas disponibles
							axios.get(`${this.apiBaseUrl}/roles/screens-hierarchy`, { headers: this.apiHeaders() })
								.then((screensRes) => {
									if (!screensRes.data || !Array.isArray(screensRes.data) || screensRes.data.length === 0) {
										return axios.get(`${this.apiBaseUrl}/screens`, { headers: this.apiHeaders() });
									}
									
									return { data: screensRes.data };
								})
								.then((screensRes) => {
									let allScreens = [];
									let processedScreens = [];
									
									if (Array.isArray(screensRes.data)) {
										if (screensRes.data.length > 0 && screensRes.data[0].categories) {
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
											processedScreens = this.convertToHierarchical(screensRes.data);
											allScreens = screensRes.data.map(screen => screen.key);
										}
									}
									
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
										(keys || []).map(String).forEach((k) => {
											out[k] = { can_create: true, can_edit: true, can_delete: true };
										});
									}

									// Luego, agregar todas las pantallas disponibles sin permisos
									allScreens.forEach(screenKey => {
										const key = String(screenKey);
										if (!out[key]) {
											out[key] = { can_create: false, can_edit: false, can_delete: false };
										}
									});
									
									// Cargar permisos individuales del usuario (overrides)
									axios.get(`${this.apiBaseUrl}/user/${this.selectedUserForScreens.id}/permissions`, { headers: this.apiHeaders() })
										.then((userPermsRes) => {
											const userPermissions = userPermsRes.data || {};
											
											// Aplicar overrides del usuario (tienen prioridad sobre los del rol)
											Object.keys(userPermissions).forEach(screenKey => {
												const key = String(screenKey);
												if (userPermissions[screenKey]) {
													out[key] = {
														can_create: !!userPermissions[screenKey].can_create,
														can_edit: !!userPermissions[screenKey].can_edit,
														can_delete: !!userPermissions[screenKey].can_delete,
													};
												}
											});
											
											this.selectedScreenPerms = out;
											this.loadingUserScreens = false; // Desactivar spinner
										})
										.catch((err) => {
											// Si no hay permisos individuales, usar los del rol
											this.selectedScreenPerms = out;
											this.loadingUserScreens = false; // Desactivar spinner
										});
								})
								.catch((err) => {
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
									}
									this.loadingUserScreens = false; // Desactivar spinner
								});
						})
						.catch((err) => {
							this.selectedScreenPerms = {};
							this.loadingUserScreens = false; // Desactivar spinner
						});
				})
				.catch((err) => {
					this.selectedScreenPerms = {};
					this.loadingUserScreens = false; // Desactivar spinner
				});
		},
		closeUserPermissions() {
			this.showUserScreensModal = false;
			this.savingUserScreens = false;
			this.selectedUserForScreens = null;
			this.roleScreenKeys = [];
			this.overrideAllowKeys = [];
			this.overrideDenyKeys = [];
			this.effectiveScreenKeys = [];
		},
		saveUserPermissions() {
			if (!this.selectedUserForScreens || this.isSuperUserRole(this.selectedUserForScreens)) {
				this.closeUserPermissions();
				return;
			}

			this.savingUserScreens = true;

			// Preparar los datos de permisos para enviar
			const screenPermissions = {};
			
			// Solo enviar permisos que estén activos (al menos una acción en true)
			Object.keys(this.selectedScreenPerms).forEach(screenKey => {
				const perms = this.selectedScreenPerms[screenKey];
				if (perms.can_create || perms.can_edit || perms.can_delete) {
					screenPermissions[screenKey] = {
						can_create: perms.can_create,
						can_edit: perms.can_edit,
						can_delete: perms.can_delete
					};
				}
			});

			// Enviar al backend
			axios.post(`${this.apiBaseUrl}/user/${this.selectedUserForScreens.id}/permissions`, {
				screen_permissions: screenPermissions
			}, {
				headers: this.apiHeaders()
			})
			.then((response) => {
				this.$message.success(response.data.message || 'Permisos guardados correctamente');
				this.closeUserPermissions();
			})
			.catch((error) => {
				console.error('Error guardando permisos:', error.response?.data || error);
				const message = error.response?.data?.message || 'Error al guardar los permisos';
				this.$message.error(message);
			})
			.finally(() => {
				this.savingUserScreens = false;
			});
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
	},
})

</script>

<style scoped>
.user-role-badge {
	display: inline-block;
	padding: 2px 8px;
	background: #1890ff;
	color: #fff;
	border-radius: 10px;
	font-size: 11px;
	font-weight: 600;
	margin-left: 8px;
}

/* Estilos para la estructura de permisos (copiados de Roles.vue) */
.screens-container {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.screen-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 8px 12px;
	background: #fff;
	border: 1px solid #e8e8e8;
	border-radius: 6px;
	transition: all 0.2s ease;
}

.screen-item:hover {
	border-color: #1890ff;
	box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);
}

.screen-main {
	flex: 1;
}

.screen-label {
	font-weight: 500;
	color: #262626;
}

.screen-actions {
	display: flex;
	gap: 12px;
	align-items: center;
}

.action-checkbox {
	margin: 0;
}
</style>
