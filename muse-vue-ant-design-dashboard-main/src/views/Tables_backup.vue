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

						<a-alert
							type="info"
							show-icon
							message="Los permisos se heredan del rol del usuario. Puedes sobreescribirlos marcando o desmarcando las casillas. Los permisos específicos del usuario tienen prioridad sobre los del rol."
							style="margin-bottom: 16px;"
						/>

						<!-- Estructura simplificada: Secciones -> Pantallas -> Acciones -->
						<div class="permissions-structure">
							<div 
								v-for="section in hierarchicalScreens" 
								:key="section.key"
								class="permission-section"
							>
								<!-- Header de la sección -->
								<div class="section-header">
									<div class="section-title">
										<h3>{{ section.label }}</h3>
										<div class="section-actions">
											<a-button 
												size="small" 
												type="primary" 
												ghost
												@click="toggleUserSectionPermissions(section)"
											>
												{{ isUserSectionFullyEnabled(section) ? 'Deshabilitar todo' : 'Habilitar todo' }}
											</a-button>
											<a-button 
												size="small" 
												type="default"
												@click="resetUserSectionToRole(section)"
											>
												Restaurar rol
											</a-button>
										</div>
									</div>
								</div>

								<!-- Pantallas dentro de la sección -->
								<div class="screens-container">
									<div 
										v-for="screen in getAllScreensFromSection(section)" 
										:key="screen.key"
										class="screen-item"
										:class="{ 'screen-item--override': hasUserOverride(screen.key) }"
									>
										<div class="screen-main">
											<a-checkbox
												:checked="isUserScreenEnabled(screen.key)"
												@change="onToggleUserScreen(screen.key, $event)"
											>
												<span class="screen-label">{{ screen.label }}</span>
												<span v-if="hasUserOverride(screen.key)" class="override-badge">
													{{ isUserScreenEnabled(screen.key) ? '✓ Habilitado' : '✗ Bloqueado' }}
												</span>
											</a-checkbox>
										</div>

										<div class="screen-actions">
											<a-checkbox
												class="action-checkbox"
												:checked="getUserScreenPerm(screen.key).can_create"
												:disabled="isUserScreenDisabled(screen.key)"
												@change="onToggleUserScreenAction(screen.key, 'can_create', $event)"
											>
												Agregar
											</a-checkbox>
											<a-checkbox
												class="action-checkbox"
												:checked="getUserScreenPerm(screen.key).can_edit"
												:disabled="isUserScreenDisabled(screen.key)"
												@change="onToggleUserScreenAction(screen.key, 'can_edit', $event)"
											>
												Editar
											</a-checkbox>
											<a-checkbox
												class="action-checkbox"
												:checked="getUserScreenPerm(screen.key).can_delete"
												:disabled="isUserScreenDisabled(screen.key)"
												@change="onToggleUserScreenAction(screen.key, 'can_delete', $event)"
											>
												Eliminar
											</a-checkbox>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</a-modal>
			</a-col>
		</a-row>

	</div>
</template>

<style scoped>
.role-screens-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px 16px;
}

@media (max-width: 767px) {
	.role-screens-grid {
		grid-template-columns: 1fr;
	}
}

.role-screens-group {
	border: 1px solid rgba(17, 24, 39, 0.08);
	border-radius: 12px;
	padding: 10px 12px;
	background: rgba(17, 24, 39, 0.02);
}

.role-screens-group--general {
	border-color: rgba(124, 58, 237, 0.25);
	background: linear-gradient(135deg, rgba(124, 58, 237, 0.06), rgba(6, 182, 212, 0.04), rgba(34, 197, 94, 0.04));
	box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.role-screens-group__title {
	font-weight: 800;
	color: #111827;
	margin-bottom: 8px;
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.role-screens-group__title::before {
	content: "";
	display: inline-block;
	width: 8px;
	height: 8px;
	border-radius: 999px;
	background: rgba(17, 24, 39, 0.25);
}

.role-screens-group--general .role-screens-group__title::before {
	background: linear-gradient(180deg, #7c3aed, #06b6d4, #22c55e);
}

.role-screens-group__items {
	display: grid;
	grid-template-columns: 1fr;
	gap: 8px 10px;
}

.role-screen-label {
	display: block;
	min-width: 0;
	white-space: normal;
	word-break: break-word;
	color: rgba(17, 24, 39, 0.88);
}

/* Estilos para la nueva estructura jerárquica de permisos */
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

.screen-item--override {
	border-color: #faad14;
	background: #fffbe6;
}

.screen-item--override:hover {
	background: #fff7e6;
	border-color: #d48806;
}

.screen-main {
	flex: 1;
	min-width: 0;
	display: flex;
	align-items: center;
	gap: 8px;
}

.screen-label {
	font-size: 14px;
	color: #595959;
	font-weight: 500;
}

.override-badge {
	font-size: 11px;
	padding: 2px 6px;
	border-radius: 10px;
	background: #faad14;
	color: #fff;
	font-weight: 600;
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

<script>
import axios from 'axios';
import { getToken, getUser } from '@/utils/auth';
import UsersTable from '../components/Users/UsersTable.vue';
import UserForm from '../components/Users/UserForm.vue';
import { h } from 'vue'; // Agregar importación de h (createElement) para el modal de contraseña temporal

export default ({
	components: {
		UsersTable,
		UserForm,
	},
	data() {
		return {
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
					width: 170,
					align: 'right',
				},
			],

			// Permisos por usuario (overrides)
			showUserScreensModal: false,
			savingUserScreens: false,
			selectedUserForScreens: null,
			screens: [],
			roleScreenKeys: [],
			overrideAllowKeys: [],
			overrideDenyKeys: [],
			effectiveScreenKeys: [],
		}
	},
	created() {
		console.log('=== COMPONENTE CREADO ===');
	},
	mounted() {
		console.log('=== COMPONENTE MONTADO ===');
		this.fetchUsers();
		this.fetchRoles();
		this.fetchScreens();
		
		// Debug: Mostrar información del usuario actual
		const debugInfo = this.debugUserInfo;
		console.log('=== DEBUG INFO USUARIO ===');
		console.log('Usuario completo:', debugInfo.user);
		console.log('Rol:', debugInfo.role);
		console.log('Rol string:', debugInfo.roleString);
		console.log('Es Super usuario:', debugInfo.isSuper);
		console.log('Puede editar permisos:', debugInfo.canEdit);
		console.log('========================');
	},
	computed: {
		isCurrentUserSuperUser() {
			return isCurrentUserSuperUser();
		},
		canEditUserPermissions() {
			// Solo Super usuario puede editar permisos de usuarios de sistema
			return this.isCurrentUserSuperUser;
		},
		isSelectedUserSystem() {
			if (!this.selectedUserForScreens) return false;
			return String(this.selectedUserForScreens.role || '').trim().toLowerCase() === 'sistema';
		},
		debugUserInfo() {
			const user = getUser();
			return {
				user: user,
				role: user?.role,
				isSuper: this.isCurrentUserSuperUser,
				canEdit: this.canEditUserPermissions,
				roleString: String(user?.role || '').trim().toLowerCase()
			};
		},
		groupedScreens() {
			const screens = (this.screens || []).slice().sort((a, b) => {
				const ga = String(a.group || '');
				const gb = String(b.group || '');
				if (ga !== gb) return ga.localeCompare(gb, 'es', { sensitivity: 'base' });
				return (a.sort_order || 0) - (b.sort_order || 0);
			});

			const groupsMap = new Map();
			screens.forEach((s) => {
				const groupName = String(s.group || '').trim() || 'General';
				if (!groupsMap.has(groupName)) groupsMap.set(groupName, []);
				groupsMap.get(groupName).push({
					key: s.key,
					label: s.label,
					sort_order: s.sort_order || 0,
				});
			});

			return Array.from(groupsMap.entries()).map(([name, items]) => ({ name, items }));
		},
		// Nueva estructura jerárquica igual que en Roles.vue
		hierarchicalScreens() {
			const screens = (this.screens || []).slice().sort((a, b) => {
				const ga = String(a.section || a.group || '');
				const gb = String(b.section || b.group || '');
				if (ga !== gb) return ga.localeCompare(gb, 'es', { sensitivity: 'base' });
				return (a.sort_order || 0) - (b.sort_order || 0);
			});

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

			return Array.from(sectionsMap.values()).map(section => ({
				...section,
				categories: Array.from(section.categories.values())
			}));
			this.fetchUsers();
			this.fetchRoles();
			this.fetchScreens();
			
			// Debug: Mostrar información del usuario actual
			const debugInfo = this.debugUserInfo;
			console.log('=== DEBUG INFO USUARIO ===');
			console.log('Usuario completo:', debugInfo.user);
			console.log('Rol:', debugInfo.role);
			console.log('Rol string:', debugInfo.roleString);
			console.log('Es Super usuario:', debugInfo.isSuper);
			console.log('Puede editar permisos:', debugInfo.canEdit);
			console.log('========================');
		},
		computed: {
			isCurrentUserSuperUser() {
				return isCurrentUserSuperUser();
			},
			canEditUserPermissions() {
				// Solo Super usuario puede editar permisos de usuarios de sistema
				return this.isCurrentUserSuperUser;
			},
			isSelectedUserSystem() {
				if (!this.selectedUserForScreens) return false;
				return String(this.selectedUserForScreens.role || '').trim().toLowerCase() === 'sistema';
			},
			debugUserInfo() {
				const user = getUser();
				return {
					user: user,
					role: user?.role,
					isSuper: this.isCurrentUserSuperUser,
					canEdit: this.canEditUserPermissions,
					roleString: String(user?.role || '').trim().toLowerCase()
				};
			},
			groupedScreens() {
				const screens = (this.screens || []).slice().sort((a, b) => {
					const ga = String(a.group || '');
					const gb = String(b.group || '');
					if (ga !== gb) return ga.localeCompare(gb, 'es', { sensitivity: 'base' });
					return (a.sort_order || 0) - (b.sort_order || 0);
				});

				const groupsMap = new Map();
				screens.forEach((s) => {
					const groupName = String(s.group || '').trim() || 'General';
					if (!groupsMap.has(groupName)) groupsMap.set(groupName, []);
					groupsMap.get(groupName).push({
						key: s.key,
						label: s.label,
						sort_order: s.sort_order || 0,
					});
				});

				return Array.from(groupsMap.entries()).map(([name, items]) => ({ name, items }));
			},
			// Nueva estructura jerárquica igual que en Roles.vue
			hierarchicalScreens() {
				const screens = (this.screens || []).slice().sort((a, b) => {
					const ga = String(a.section || a.group || '');
					const gb = String(b.section || b.group || '');
					if (ga !== gb) return ga.localeCompare(gb, 'es', { sensitivity: 'base' });
					return (a.sort_order || 0) - (b.sort_order || 0);
				});

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

				return Array.from(sectionsMap.values()).map(section => ({
					...section,
					categories: Array.from(section.categories.values())
				}));
			},
		},
		methods: {
			apiHeaders() {
				const token = getToken();
				return { Authorization: `Bearer ${token}` };
			},
			fetchScreens() {
				// Intentar usar el nuevo endpoint jerárquico primero
				const authToken = getToken();
				if (!authToken) return;
				
				axios.get('http://localhost:8000/api/roles/screens-hierarchy', { headers: this.apiHeaders() })
					.then((res) => { 
						this.screens = res.data || [];
					})
					.catch((err) => {
						// Si falla, usar el endpoint original
						axios.get('http://localhost:8000/api/screens', { headers: { Authorization: `Bearer ${authToken}` } })
							.then((res) => { this.screens = res.data || []; })
							.catch((err) => {
								console.error('Error al obtener pantallas:', err.response?.data || err);
								this.screens = [];
							});
					});
			},
			fetchRoles() {
				const authToken = getToken();
				axios.get('http://localhost:8000/api/roles', {
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

				// Limpiar primero el usuario en edición
				this.editingUser = null;
				this.showUserModal = false;
				
				// Pequeño retraso para asegurar que el componente se reinicie
				this.$nextTick(() => {
					this.editingUser = {
						...raw,
						photo_url: raw.photo_url || null,
					};
					// Otro $nextTick para asegurar que el UserForm reciba los datos
					this.$nextTick(() => {
						this.showUserModal = true;
					});
				});
			},
			recomputeEffectiveKeys() {
				const roleSet = new Set((this.roleScreenKeys || []).map(String));
				const allowSet = new Set((this.overrideAllowKeys || []).map(String));
				const denySet = new Set((this.overrideDenyKeys || []).map(String));

				const merged = new Set([...roleSet, ...allowSet]);
				for (const k of denySet) merged.delete(String(k));

				this.effectiveScreenKeys = Array.from(merged);
			},
			openUserPermissions(row) {
				const raw = row && row._raw ? row._raw : null;
				if (!raw) return;

				console.log('=== DEBUG OPEN PERMISSIONS ===');
				console.log('Usuario seleccionado:', raw);
				console.log('Rol del usuario seleccionado:', raw.role);
				console.log('Usuario actual:', this.debugUserInfo.user);
				console.log('Rol del usuario actual:', this.debugUserInfo.role);
				console.log('Es Super usuario actual:', this.debugUserInfo.isSuper);
				console.log('Puede editar permisos:', this.debugUserInfo.canEdit);
				console.log('===============================');

				this.selectedUserForScreens = raw;
				this.showUserScreensModal = true;
				this.savingUserScreens = false;

				// cargar screens si aún no están
				if (!this.screens || this.screens.length === 0) this.fetchScreens();

				// Verificar si es usuario de sistema
				const isSystemUser = String(raw.role || '').trim().toLowerCase() === 'sistema';
				const isCurrentUserSuper = isCurrentUserSuperUser();

				console.log('¿Es usuario de sistema?', isSystemUser);
				console.log('¿Es usuario actual Super?', isCurrentUserSuper);

				// Si es usuario de sistema, darle todos los permisos automáticamente
				if (isSystemUser) {
					console.log('Tratando como usuario de sistema...');
					// Obtener todas las pantallas disponibles y dar todos los permisos
					axios.get('http://localhost:8000/api/roles/screens-hierarchy', { headers: this.apiHeaders() })
						.then((res) => {
							console.log('Respuesta de screens-hierarchy:', res.data);
							const allScreens = [];
							if (res.data && Array.isArray(res.data)) {
								res.data.forEach(section => {
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
							}
							
							console.log('Todas las pantallas encontradas:', allScreens);
							
							// Dar todos los permisos al usuario de sistema
							this.roleScreenKeys = allScreens.map(String);
							this.overrideAllowKeys = [];
							this.overrideDenyKeys = [];
							this.effectiveScreenKeys = allScreens.map(String);
							console.log('Permisos asignados:', this.effectiveScreenKeys);
						})
						.catch((err) => {
							console.error('Error obteniendo pantallas para usuario de sistema:', err);
							// Fallback: usar el endpoint normal
							axios.get(`http://localhost:8000/api/user/${raw.id}/screens`, {
								headers: { Authorization: `Bearer ${getToken()}` },
							})
								.then((res) => {
									console.log('Fallback - respuesta de user screens:', res.data);
									const keys = (res.data?.effective_screen_keys || []).map(String);
									this.roleScreenKeys = keys;
									this.overrideAllowKeys = [];
									this.overrideDenyKeys = [];
									this.effectiveScreenKeys = keys;
								});
						});
					return;
				}

				// Para otros usuarios, comportamiento normal
				console.log('Tratando como usuario normal...');
				const authToken = getToken();
				axios.get(`http://localhost:8000/api/user/${raw.id}/screens`, {
					headers: { Authorization: `Bearer ${authToken}` },
				})
					.then((res) => {
						console.log('Respuesta normal de user screens:', res.data);
						this.roleScreenKeys = (res.data?.role_screen_keys || []).map(String);
						this.overrideAllowKeys = (res.data?.override_allow_keys || []).map(String);
						this.overrideDenyKeys = (res.data?.override_deny_keys || []).map(String);
						this.effectiveScreenKeys = (res.data?.effective_screen_keys || []).map(String);
						console.log('Permisos normales asignados:', this.effectiveScreenKeys);
					})
					.catch((err) => {
						console.error('Error cargando permisos del usuario:', err.response?.data || err);
						this.roleScreenKeys = [];
						this.overrideAllowKeys = [];
						this.overrideDenyKeys = [];
						this.effectiveScreenKeys = [];
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
			onToggleUserScreen(key, evt) {
				const checked = !!evt?.target?.checked;
				const k = String(key);
				const roleSet = new Set((this.roleScreenKeys || []).map(String));
				const allowSet = new Set((this.overrideAllowKeys || []).map(String));
				const denySet = new Set((this.overrideDenyKeys || []).map(String));

				if (checked) {
					// permitir: quitar deny; si rol ya lo permite, no necesitamos allow override
					denySet.delete(k);
					if (roleSet.has(k)) allowSet.delete(k);
					else allowSet.add(k);
				} else {
					// bloquear: quitar allow; si rol lo permite, necesitamos deny override
					allowSet.delete(k);
					if (roleSet.has(k)) denySet.add(k);
					else denySet.delete(k);
				}

				this.overrideAllowKeys = Array.from(allowSet);
				this.overrideDenyKeys = Array.from(denySet);
				this.recomputeEffectiveKeys();
			},
			saveUserPermissions() {
				const u = this.selectedUserForScreens;
				if (!u || !u.id) return;

				this.savingUserScreens = true;
				const authToken = getToken();

				axios.put(`http://localhost:8000/api/user/${u.id}/screens`, {
					allow_keys: this.overrideAllowKeys,
					deny_keys: this.overrideDenyKeys,
				}, {
					headers: { Authorization: `Bearer ${authToken}`, 'Content-Type': 'application/json' },
				})
					.then((res) => {
						this.roleScreenKeys = (res.data?.role_screen_keys || []).map(String);
						this.overrideAllowKeys = (res.data?.override_allow_keys || []).map(String);
						this.overrideDenyKeys = (res.data?.override_deny_keys || []).map(String);
						this.effectiveScreenKeys = (res.data?.effective_screen_keys || []).map(String);

						// limpiar cache del guard para que se recargue en la próxima navegación
						localStorage.removeItem('allowedScreens');
						sessionStorage.removeItem('allowedScreens');

						// si estamos editando permisos del usuario actual, también conviene limpiar el cache de sidebar/guard
						const me = getUser();
						if (me && String(me.id) === String(u.id)) {
							// no forzamos reload, pero al navegar se refrescará
						}

						if (this.$message) this.$message.success('Permisos del usuario guardados.');
					})
					.catch((err) => {
						const msg = err?.response?.data?.message || 'No se pudieron guardar los permisos del usuario.';
						console.error('Error guardando permisos del usuario:', err.response?.data || err);
						if (this.$message) this.$message.error(msg);
					})
					.finally(() => { this.savingUserScreens = false; });
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

			isUserScreenEnabled(key) {
				const k = String(key);
				return this.effectiveScreenKeys.includes(k);
			},

			isUserScreenDisabled(key) {
				// Si el usuario seleccionado es de sistema y el usuario actual no es Super usuario, deshabilitar
				if (this.isSelectedUserSystem && !this.canEditUserPermissions) {
					return true;
				}
				// Si no está habilitado, también deshabilitar
				return !this.isUserScreenEnabled(key);
			},

			hasUserOverride(key) {
				const k = String(key);
				const allowSet = new Set((this.overrideAllowKeys || []).map(String));
				const denySet = new Set((this.overrideDenyKeys || []).map(String));
				const roleSet = new Set((this.roleScreenKeys || []).map(String));
				
				// Tiene override si está en allow o deny y no coincide con el rol
				return (allowSet.has(k) || denySet.has(k));
			},

			getUserScreenPerm(key) {
				const k = String(key);
				// Por ahora, si la pantalla está habilitada, dar todos los permisos
				// TODO: Implementar permisos granulares por acción como en roles
				return {
					can_create: this.isUserScreenEnabled(k),
					can_edit: this.isUserScreenEnabled(k),
					can_delete: this.isUserScreenEnabled(k)
				};
			},

			isUserSectionFullyEnabled(section) {
				const allScreens = this.getAllScreensFromSection(section);
				return allScreens.length > 0 && allScreens.every(screen => this.isUserScreenEnabled(screen.key));
			},

			toggleUserSectionPermissions(section) {
				const allScreens = this.getAllScreensFromSection(section);
				const enable = !this.isUserSectionFullyEnabled(section);
				
				allScreens.forEach(screen => {
					const evt = { target: { checked: enable } };
					this.onToggleUserScreen(screen.key, evt);
				});
			},

			resetUserSectionToRole(section) {
				const allScreens = this.getAllScreensFromSection(section);
				const allowSet = new Set((this.overrideAllowKeys || []).map(String));
				const denySet = new Set((this.overrideDenyKeys || []).map(String));
				
				allScreens.forEach(screen => {
					const k = String(screen.key);
					allowSet.delete(k);
					denySet.delete(k);
				});
				
				this.overrideAllowKeys = Array.from(allowSet);
				this.overrideDenyKeys = Array.from(denySet);
				this.recomputeEffectiveKeys();
			},

			onToggleUserScreenAction(key, action, evt) {
				// TODO: Implementar permisos granulares por acción
				// Por ahora, no hacer nada ya que el sistema actual maneja permisos por pantalla
				console.log('Toggle action:', key, action, evt?.target?.checked);
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
			fetchUsers() {
				this.loadingUsers = true;
				const authToken = getToken();
				axios.get('http://localhost:8000/api/user', {
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
							// _raw: mantener el status REAL del backend (Activo/Inactivo) para el formulario de edición
							_raw: { ...user, role, status: (user.status || 'Activo'), statusLabel, isDeleted, isInactive },
							userName: {
								name: user.name,
								email: user.email,
								avatar: user.photo_url || null,
							},
							email: user.email,
							role,
							// status (columna): mostramos etiqueta amigable
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
					? `http://localhost:8000/api/user/${this.editingUser.id}`
					: 'http://localhost:8000/api/user';
				const method = this.editingUser ? 'post' : 'post'; // Usar POST para FormData con _method

				// Si hay archivo, usar FormData
				if (userData.photoFile) {
					const formData = new FormData();
					
					// Agregar todos los campos del formulario
					Object.keys(userData).forEach(key => {
						if (key !== 'photoFile') {
							formData.append(key, userData[key] || '');
						}
					});
					
					// Agregar archivo de foto
					formData.append('photo', userData.photoFile);
					
					// Agregar método para edición (PUT simulado)
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
					// Sin archivo, enviar como JSON normal
					const payload = this.editingUser
						? {
							name: userData.name,
							role: userData.role,
							status: userData.status,
							_method: 'PUT', // Para edición
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

				axios.delete(`http://localhost:8000/api/user/${id}`, {
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

				axios.patch(`http://localhost:8000/api/user/${id}/deactivate`, {}, {
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

				axios.patch(`http://localhost:8000/api/user/${id}/activate`, {}, {
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
				const id = row && (row.key || row.id);
				if (!id) return;

				// Generar contraseña temporal localmente
				const tempPassword = this.generateTempPassword();
				
				// Mostrar la contraseña temporal al administrador
				this.$message.success(`Contraseña temporal generada: ${tempPassword}`, 10);
				
				// Opcional: Copiar al portapapeles
				this.copyToClipboard(tempPassword);
				
				// Aquí podrías guardar la contraseña temporal en el estado del usuario
				// o mostrarla en un modal/detalle del usuario
				this.showTempPasswordModal(row, tempPassword);
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
				// Crear un modal para mostrar la contraseña temporal
				this.$modal.info({
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
								'Esta contraseña temporal se mostrará en el detalle del usuario hasta que sea cambiada.')
						]);
					},
					width: 500,
					okText: 'Entendido'
				});
			},
			closeUserModal() {
				this.showUserModal = false;
				this.editingUser = null;
			},
		},
	})

</script>

<style lang="scss">
</style>