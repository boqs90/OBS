<template>
  <div class="inventory-control">
    <a-card title="Control de Inventario" :loading="loading">
      <!-- Barra de herramientas y búsqueda -->
      <div class="toolbar-section">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-input-search
              v-model="searchText"
              placeholder="Buscar por nombre, código o escanear código de barra"
              @search="handleSearch"
              style="width: 100%"
              allowClear
            >
              <a-button slot="enterButton" @click="showBarcodeScanner">
                <a-icon type="scan" /> Escanear
              </a-button>
            </a-input-search>
          </a-col>
          <a-col :span="4">
            <a-select
              v-model="filterCategory"
              placeholder="Categoría"
              style="width: 100%"
              @change="fetchInventory"
              allowClear
            >
              <a-select-option value="office">Oficina</a-select-option>
              <a-select-option value="classroom">Salón</a-select-option>
              <a-select-option value="library">Biblioteca</a-select-option>
              <a-select-option value="lab">Laboratorio</a-select-option>
              <a-select-option value="sports">Deportes</a-select-option>
              <a-select-option value="maintenance">Mantenimiento</a-select-option>
              <a-select-option value="other">Otros</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="4">
            <a-select
              v-model="filterStatus"
              placeholder="Estado"
              style="width: 100%"
              @change="fetchInventory"
              allowClear
            >
              <a-select-option value="available">Disponible</a-select-option>
              <a-select-option value="low">Stock Bajo</a-select-option>
              <a-select-option value="out">Agotado</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="8">
            <a-space style="float: right;">
              <a-button type="primary" @click="showAddItemModal">
                <a-icon type="plus" /> Nuevo Artículo
              </a-button>
              <a-button @click="showMovementModal">
                <a-icon type="swap" /> Movimiento
              </a-button>
              <a-button @click="exportInventory">
                <a-icon type="download" /> Exportar
              </a-button>
            </a-space>
          </a-col>
        </a-row>
      </div>

      <!-- Estadísticas de Inventario -->
      <div class="stats-section">
        <a-row :gutter="16">
          <a-col :span="6">
            <a-statistic
              title="Total Artículos"
              :value="statistics.total"
              :value-style="{ color: '#1890ff' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Stock Total"
              :value="statistics.totalStock"
              :value-style="{ color: '#52c41a' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Stock Bajo"
              :value="statistics.lowStock"
              :value-style="{ color: '#fa8c16' }"
            />
          </a-col>
          <a-col :span="6">
            <a-statistic
              title="Agotados"
              :value="statistics.outOfStock"
              :value-style="{ color: '#f5222d' }"
            />
          </a-col>
        </a-row>
      </div>

      <!-- Tabla de Inventario -->
      <div class="table-section">
        <a-table
          :columns="inventoryColumns"
          :data-source="inventoryItems"
          :loading="loading"
          :pagination="pagination"
          @change="handleTableChange"
          :scroll="{ x: 1400 }"
          row-key="id"
        >
          <!-- Columna de imagen -->
          <template slot="image" slot-scope="text, record">
            <div class="item-image">
              <a-avatar
                :src="record.image"
                :size="40"
                shape="square"
                @click="previewImage(record.image)"
                style="cursor: pointer;"
              >
                <a-icon type="picture" v-if="!record.image" />
              </a-avatar>
            </div>
          </template>

          <!-- Columna de código de barra -->
          <template slot="barcode" slot-scope="text, record">
            <div class="barcode-info">
              <code>{{ record.barcode }}</code>
              <a-button type="link" size="small" @click="printBarcode(record)">
                <a-icon type="printer" />
              </a-button>
            </div>
          </template>

          <!-- Columna de nombre -->
          <template slot="name" slot-scope="text, record">
            <div class="item-info">
              <div class="item-name">{{ record.name }}</div>
              <div class="item-description">{{ record.description }}</div>
            </div>
          </template>

          <!-- Columna de categoría -->
          <template slot="category" slot-scope="text, record">
            <a-tag :color="getCategoryColor(record.category)">
              {{ getCategoryText(record.category) }}
            </a-tag>
          </template>

          <!-- Columna de stock -->
          <template slot="stock" slot-scope="text, record">
            <div class="stock-info">
              <a-progress
                :percent="getStockPercentage(record)"
                :status="getStockStatus(record)"
                :format="() => `${record.stock} / ${record.minStock}`"
                size="small"
              />
              <div class="stock-details">
                <span class="current-stock">{{ record.stock }}</span>
                <span class="min-stock">Min: {{ record.minStock }}</span>
              </div>
            </div>
          </template>

          <!-- Columna de estado -->
          <template slot="status" slot-scope="text, record">
            <a-tag :color="getStatusColor(record)">
              {{ getStatusText(record) }}
            </a-tag>
          </template>

          <!-- Columna de acciones -->
          <template slot="actions" slot-scope="text, record">
            <a-space>
              <a-button type="link" size="small" @click="editItem(record)">
                <a-icon type="edit" /> Editar
              </a-button>
              <a-dropdown>
                <a-button type="link" size="small">
                  <a-icon type="more" /> Más
                </a-button>
                <a-menu slot="overlay">
                  <a-menu-item @click="showItemHistory(record)">
                    <a-icon type="history" /> Historial
                  </a-menu-item>
                  <a-menu-item @click="duplicateItem(record)">
                    <a-icon type="copy" /> Duplicar
                  </a-menu-item>
                  <a-menu-divider />
                  <a-menu-item @click="deleteItem(record)" style="color: #f5222d;">
                    <a-icon type="delete" /> Eliminar
                  </a-menu-item>
                </a-menu>
              </a-dropdown>
            </a-space>
          </template>
        </a-table>
      </div>
    </a-card>

    <!-- Modal de Agregar/Editar Artículo -->
    <a-modal
      :title="itemModal.title"
      :visible="itemModal.visible"
      @ok="saveItem"
      @cancel="closeItemModal"
      :confirmLoading="itemModal.loading"
      width="800px"
    >
      <a-form :form="itemForm" layout="vertical">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Nombre del Artículo">
              <a-input
                v-decorator="['name', { 
                  rules: [{ required: true, message: 'Ingresa el nombre del artículo.' }] 
                }]"
                placeholder="Nombre del artículo"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="Código de Barras">
              <a-input
                v-decorator="['barcode', { 
                  rules: [{ required: true, message: 'Ingresa el código de barras.' }] 
                }]"
                placeholder="Código de barras"
                addonAfter="Generate"
                @click="generateBarcode"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Categoría">
              <a-select
                v-decorator="['category', { 
                  rules: [{ required: true, message: 'Selecciona una categoría.' }] 
                }]"
                placeholder="Selecciona categoría"
              >
                <a-select-option value="office">Oficina</a-select-option>
                <a-select-option value="classroom">Salón</a-select-option>
                <a-select-option value="library">Biblioteca</a-select-option>
                <a-select-option value="lab">Laboratorio</a-select-option>
                <a-select-option value="sports">Deportes</a-select-option>
                <a-select-option value="maintenance">Mantenimiento</a-select-option>
                <a-select-option value="other">Otros</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="Ubicación">
              <a-input
                v-decorator="['location']"
                placeholder="Ej: Bodega A, Estantería 3"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="Stock Actual">
              <a-input-number
                v-decorator="['stock', { 
                  rules: [{ required: true, message: 'Ingresa el stock actual.' }] 
                }]"
                :min="0"
                style="width: 100%"
              />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="Stock Mínimo">
              <a-input-number
                v-decorator="['minStock', { 
                  rules: [{ required: true, message: 'Ingresa el stock mínimo.' }] 
                }]"
                :min="0"
                style="width: 100%"
              />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="Unidad">
              <a-select
                v-decorator="['unit', { 
                  initialValue: 'units',
                  rules: [{ required: true, message: 'Selecciona la unidad.' }] 
                }]"
                placeholder="Unidad"
              >
                <a-select-option value="units">Unidades</a-select-option>
                <a-select-option value="boxes">Cajas</a-select-option>
                <a-select-option value="kg">Kilogramos</a-select-option>
                <a-select-option value="liters">Litros</a-select-option>
                <a-select-option value="meters">Metros</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>

        <a-form-item label="Descripción">
          <a-textarea
            v-decorator="['description']"
            placeholder="Descripción del artículo"
            :rows="3"
          />
        </a-form-item>

        <a-form-item label="Imagen del Artículo">
          <a-upload
            name="image"
            list-type="picture-card"
            class="image-uploader"
            :show-upload-list="false"
            :before-upload="beforeUploadImage"
            @change="handleImageChange"
          >
            <img v-if="itemModal.imageUrl" :src="itemModal.imageUrl" alt="avatar" style="width: 100%; height: 100%; object-fit: cover;" />
            <div v-else>
              <a-icon :type="itemModal.loading ? 'loading' : 'plus'" />
              <div class="ant-upload-text">Subir Imagen</div>
            </div>
          </a-upload>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal de Movimiento de Inventario -->
    <a-modal
      title="Movimiento de Inventario"
      :visible="movementModal.visible"
      @ok="saveMovement"
      @cancel="closeMovementModal"
      :confirmLoading="movementModal.loading"
      width="600px"
    >
      <a-form :form="movementForm" layout="vertical">
        <a-form-item label="Tipo de Movimiento">
          <a-radio-group
            v-decorator="['movementType', { 
              rules: [{ required: true, message: 'Selecciona el tipo de movimiento.' }] 
            }]"
          >
            <a-radio value="in">Entrada</a-radio>
            <a-radio value="out">Salida</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item label="Artículo">
          <a-select
            v-decorator="['itemId', { 
              rules: [{ required: true, message: 'Selecciona un artículo.' }] 
            }]"
            placeholder="Selecciona artículo"
            show-search
            :filter-option="filterItemOption"
            @change="handleItemSelect"
          >
            <a-select-option 
              v-for="item in inventoryItems" 
              :key="item.id"
              :value="item.id"
            >
              {{ item.name }} - {{ item.barcode }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Cantidad">
              <a-input-number
                v-decorator="['quantity', { 
                  rules: [{ required: true, message: 'Ingresa la cantidad.' }] 
                }]"
                :min="1"
                style="width: 100%"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="Stock Actual">
              <a-input
                :value="selectedItem.stock"
                disabled
                style="background: #f5f5f5;"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-form-item label="Motivo del Movimiento">
          <a-textarea
            v-decorator="['reason', { 
              rules: [{ required: true, message: 'Ingresa el motivo del movimiento.' }] 
            }]"
            placeholder="Describe el motivo del movimiento..."
            :rows="3"
          />
        </a-form-item>

        <a-form-item label="Responsable">
          <a-input
            v-decorator="['responsible']"
            placeholder="Nombre del responsable"
          />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal de Escáner de Código de Barras -->
    <a-modal
      title="Escanear Código de Barras"
      :visible="scannerModal.visible"
      @cancel="closeScannerModal"
      :footer="null"
      width="500px"
    >
      <div class="scanner-section">
        <div class="scanner-area">
          <video ref="scannerVideo" style="width: 100%; height: 300px; background: #000;"></video>
        </div>
        <div class="scanner-controls">
          <a-button type="primary" @click="startScanner" :loading="scannerModal.scanning">
            <a-icon type="camera" /> Iniciar Escáner
          </a-button>
          <a-button @click="stopScanner" :disabled="!scannerModal.scanning">
            <a-icon type="stop" /> Detener
          </a-button>
        </div>
        <div v-if="scannerModal.scannedCode" class="scanned-result">
          <a-alert
            :message="`Código Escaneado: ${scannerModal.scannedCode}`"
            type="success"
            show-icon
          />
        </div>
      </div>
    </a-modal>

    <!-- Modal de Historial de Movimientos -->
    <a-modal
      title="Historial de Movimientos"
      :visible="historyModal.visible"
      @cancel="closeHistoryModal"
      :footer="null"
      width="800px"
    >
      <div v-if="historyModal.item">
        <h3>{{ historyModal.item.name }}</h3>
        <a-table
          :columns="historyColumns"
          :data-source="historyModal.movements"
          :loading="historyModal.loading"
          :pagination="false"
          size="small"
          row-key="id"
        >
          <template slot="type" slot-scope="text">
            <a-tag :color="text === 'in' ? 'green' : 'red'">
              {{ text === 'in' ? 'Entrada' : 'Salida' }}
            </a-tag>
          </template>
          <template slot="date" slot-scope="text">
            {{ formatDate(text) }}
          </template>
        </a-table>
      </div>
    </a-modal>

    <!-- Modal de Vista Previa de Imagen -->
    <a-modal
      title="Vista Previa de Imagen"
      :visible="imageModal.visible"
      @cancel="closeImageModal"
      :footer="null"
      width="600px"
    >
      <img :src="imageModal.imageUrl" style="width: 100%;" />
    </a-modal>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'InventoryControl',
  data() {
    return {
      loading: false,
      searchText: '',
      filterCategory: '',
      filterStatus: '',
      inventoryItems: [],
      pagination: {
        current: 1,
        pageSize: 20,
        total: 0,
      },
      statistics: {
        total: 0,
        totalStock: 0,
        lowStock: 0,
        outOfStock: 0,
      },
      itemModal: {
        visible: false,
        loading: false,
        title: 'Nuevo Artículo',
        imageUrl: '',
        loading: false,
        editingItem: null,
      },
      movementModal: {
        visible: false,
        loading: false,
      },
      scannerModal: {
        visible: false,
        scanning: false,
        scannedCode: '',
      },
      historyModal: {
        visible: false,
        loading: false,
        item: null,
        movements: [],
      },
      imageModal: {
        visible: false,
        imageUrl: '',
      },
      selectedItem: {
        stock: 0,
      },
    };
  },
  beforeCreate() {
    this.itemForm = this.$form.createForm(this, { name: 'item_form' });
    this.movementForm = this.$form.createForm(this, { name: 'movement_form' });
  },
  mounted() {
    this.fetchInventory();
    this.fetchStatistics();
  },
  methods: {
    fetchInventory() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        per_page: this.pagination.pageSize,
        search: this.searchText || undefined,
        category: this.filterCategory || undefined,
        status: this.filterStatus || undefined,
      };

      axios.get('/api/inventory', { params })
        .then(response => {
          this.inventoryItems = response.data.data || [];
          this.pagination.total = response.data.total || 0;
        })
        .catch(error => {
          this.$message.error('Error al cargar el inventario');
          console.error('Error fetching inventory:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchStatistics() {
      axios.get('/api/inventory/statistics')
        .then(response => {
          this.statistics = response.data || { total: 0, totalStock: 0, lowStock: 0, outOfStock: 0 };
        })
        .catch(error => {
          console.error('Error fetching statistics:', error);
        });
    },
    handleTableChange(pagination) {
      this.pagination.current = pagination.current;
      this.fetchInventory();
    },
    handleSearch() {
      this.pagination.current = 1;
      this.fetchInventory();
    },
    showAddItemModal() {
      this.itemModal.title = 'Nuevo Artículo';
      this.itemModal.visible = true;
      this.itemModal.editingItem = null;
      this.itemModal.imageUrl = '';
      
      this.$nextTick(() => {
        this.itemForm.resetFields();
      });
    },
    editItem(item) {
      this.itemModal.title = 'Editar Artículo';
      this.itemModal.visible = true;
      this.itemModal.editingItem = item;
      this.itemModal.imageUrl = item.image;
      
      this.$nextTick(() => {
        this.itemForm.setFieldsValue({
          name: item.name,
          barcode: item.barcode,
          category: item.category,
          location: item.location,
          stock: item.stock,
          minStock: item.minStock,
          unit: item.unit,
          description: item.description,
        });
      });
    },
    closeItemModal() {
      this.itemModal.visible = false;
      this.itemModal.loading = false;
      this.itemModal.editingItem = null;
      this.itemModal.imageUrl = '';
      this.itemForm.resetFields();
    },
    saveItem() {
      this.itemForm.validateFields((err, values) => {
        if (!err) {
          this.itemModal.loading = true;
          
          const formData = new FormData();
          Object.keys(values).forEach(key => {
            formData.append(key, values[key]);
          });
          
          if (this.itemModal.imageUrl && typeof this.itemModal.imageUrl !== 'string') {
            formData.append('image', this.itemModal.imageUrl);
          }
          
          if (this.itemModal.editingItem) {
            formData.append('id', this.itemModal.editingItem.id);
          }

          const url = this.itemModal.editingItem ? '/api/inventory/update' : '/api/inventory/create';
          const method = this.itemModal.editingItem ? 'put' : 'post';

          axios[method](url, formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          })
            .then(() => {
              this.$message.success(this.itemModal.editingItem ? 'Artículo actualizado correctamente' : 'Artículo creado correctamente');
              this.closeItemModal();
              this.fetchInventory();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al guardar el artículo');
              console.error('Error saving item:', error);
            })
            .finally(() => {
              this.itemModal.loading = false;
            });
        }
      });
    },
    deleteItem(item) {
      axios.delete(`/api/inventory/${item.id}`)
        .then(() => {
          this.$message.success('Artículo eliminado correctamente');
          this.fetchInventory();
          this.fetchStatistics();
        })
        .catch(error => {
          this.$message.error('Error al eliminar el artículo');
          console.error('Error deleting item:', error);
        });
    },
    duplicateItem(item) {
      this.showAddItemModal();
      this.$nextTick(() => {
        this.itemForm.setFieldsValue({
          name: `${item.name} (Copia)`,
          barcode: this.generateRandomBarcode(),
          category: item.category,
          location: item.location,
          stock: 0,
          minStock: item.minStock,
          unit: item.unit,
          description: item.description,
        });
      });
    },
    showMovementModal() {
      this.movementModal.visible = true;
      this.$nextTick(() => {
        this.movementForm.resetFields();
      });
    },
    closeMovementModal() {
      this.movementModal.visible = false;
      this.movementModal.loading = false;
      this.movementForm.resetFields();
      this.selectedItem = { stock: 0 };
    },
    saveMovement() {
      this.movementForm.validateFields((err, values) => {
        if (!err) {
          this.movementModal.loading = true;
          
          axios.post('/api/inventory/movement', values)
            .then(() => {
              this.$message.success('Movimiento registrado correctamente');
              this.closeMovementModal();
              this.fetchInventory();
              this.fetchStatistics();
            })
            .catch(error => {
              this.$message.error('Error al registrar el movimiento');
              console.error('Error saving movement:', error);
            })
            .finally(() => {
              this.movementModal.loading = false;
            });
        }
      });
    },
    handleItemSelect(itemId) {
      const item = this.inventoryItems.find(i => i.id === itemId);
      this.selectedItem = item || { stock: 0 };
    },
    showBarcodeScanner() {
      this.scannerModal.visible = true;
    },
    closeScannerModal() {
      this.scannerModal.visible = false;
      this.stopScanner();
      this.scannerModal.scannedCode = '';
    },
    startScanner() {
      this.scannerModal.scanning = true;
      // Implementar lógica de escáner
      this.$message.info('Función de escáner en desarrollo');
    },
    stopScanner() {
      this.scannerModal.scanning = false;
    },
    generateBarcode() {
      const barcode = this.generateRandomBarcode();
      this.itemForm.setFieldsValue({ barcode });
    },
    generateRandomBarcode() {
      return Math.random().toString(36).substring(2, 15).toUpperCase();
    },
    showItemHistory(item) {
      this.historyModal.visible = true;
      this.historyModal.loading = true;
      this.historyModal.item = item;
      
      axios.get(`/api/inventory/${item.id}/movements`)
        .then(response => {
          this.historyModal.movements = response.data || [];
        })
        .catch(error => {
          this.$message.error('Error al cargar el historial');
          console.error('Error fetching history:', error);
        })
        .finally(() => {
          this.historyModal.loading = false;
        });
    },
    closeHistoryModal() {
      this.historyModal.visible = false;
      this.historyModal.loading = false;
      this.historyModal.item = null;
      this.historyModal.movements = [];
    },
    printBarcode(item) {
      // Implementar impresión de código de barras
      this.$message.info('Función de impresión en desarrollo');
    },
    previewImage(imageUrl) {
      this.imageModal.visible = true;
      this.imageModal.imageUrl = imageUrl;
    },
    closeImageModal() {
      this.imageModal.visible = false;
      this.imageModal.imageUrl = '';
    },
    exportInventory() {
      axios.get('/api/inventory/export', {
        responseType: 'blob',
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `inventario_${moment().format('YYYY-MM-DD')}.xlsx`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          this.$message.error('Error al exportar el inventario');
          console.error('Error exporting inventory:', error);
        });
    },
    beforeUploadImage(file) {
      const isImage = file.type.startsWith('image/');
      if (!isImage) {
        this.$message.error('Solo se permiten archivos de imagen');
        return false;
      }
      const isLt2M = file.size / 1024 / 1024 < 2;
      if (!isLt2M) {
        this.$message.error('La imagen debe ser menor a 2MB');
        return false;
      }
      return false; // Prevenir upload automático
    },
    handleImageChange({ file }) {
      this.itemModal.imageUrl = file;
    },
    filterItemOption(input, option) {
      return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
    },
    getStockPercentage(item) {
      return item.minStock > 0 ? Math.round((item.stock / item.minStock) * 100) : 100;
    },
    getStockStatus(item) {
      if (item.stock === 0) return 'exception';
      if (item.stock <= item.minStock) return 'normal';
      return 'success';
    },
    getStatusColor(item) {
      if (item.stock === 0) return 'red';
      if (item.stock <= item.minStock) return 'orange';
      return 'green';
    },
    getStatusText(item) {
      if (item.stock === 0) return 'Agotado';
      if (item.stock <= item.minStock) return 'Stock Bajo';
      return 'Disponible';
    },
    getCategoryColor(category) {
      const colors = {
        office: 'blue',
        classroom: 'green',
        library: 'purple',
        lab: 'orange',
        sports: 'cyan',
        maintenance: 'red',
        other: 'default',
      };
      return colors[category] || 'default';
    },
    getCategoryText(category) {
      const categories = {
        office: 'Oficina',
        classroom: 'Salón',
        library: 'Biblioteca',
        lab: 'Laboratorio',
        sports: 'Deportes',
        maintenance: 'Mantenimiento',
        other: 'Otros',
      };
      return categories[category] || category;
    },
    formatDate(dateString) {
      return moment(dateString).format('DD/MM/YYYY HH:mm');
    },
  },
  computed: {
    inventoryColumns() {
      return [
        {
          title: 'Imagen',
          key: 'image',
          scopedSlots: { customRender: 'image' },
          width: 80,
        },
        {
          title: 'Código de Barras',
          key: 'barcode',
          scopedSlots: { customRender: 'barcode' },
          width: 150,
        },
        {
          title: 'Artículo',
          key: 'name',
          scopedSlots: { customRender: 'name' },
          width: 250,
        },
        {
          title: 'Categoría',
          key: 'category',
          scopedSlots: { customRender: 'category' },
          width: 120,
        },
        {
          title: 'Ubicación',
          dataIndex: 'location',
          key: 'location',
          width: 120,
        },
        {
          title: 'Stock',
          key: 'stock',
          scopedSlots: { customRender: 'stock' },
          width: 150,
        },
        {
          title: 'Estado',
          key: 'status',
          scopedSlots: { customRender: 'status' },
          width: 100,
        },
        {
          title: 'Acciones',
          key: 'actions',
          scopedSlots: { customRender: 'actions' },
          width: 120,
          fixed: 'right',
        },
      ];
    },
    historyColumns() {
      return [
        {
          title: 'Tipo',
          dataIndex: 'type',
          key: 'type',
          scopedSlots: { customRender: 'type' },
          width: 100,
        },
        {
          title: 'Cantidad',
          dataIndex: 'quantity',
          key: 'quantity',
          width: 80,
        },
        {
          title: 'Motivo',
          dataIndex: 'reason',
          key: 'reason',
        },
        {
          title: 'Responsable',
          dataIndex: 'responsible',
          key: 'responsible',
          width: 120,
        },
        {
          title: 'Fecha',
          dataIndex: 'date',
          key: 'date',
          scopedSlots: { customRender: 'date' },
          width: 150,
        },
      ];
    },
  },
};
</script>

<style scoped>
.inventory-control {
  padding: 24px;
}

.toolbar-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.stats-section {
  margin: 24px 0;
  padding: 16px;
  background: #f0f2f5;
  border-radius: 8px;
}

.table-section {
  margin-top: 24px;
}

.item-image img {
  border-radius: 4px;
}

.barcode-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.barcode-info code {
  background: #f0f0f0;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: monospace;
}

.item-info {
  display: flex;
  flex-direction: column;
}

.item-name {
  font-weight: 500;
  font-size: 14px;
}

.item-description {
  font-size: 12px;
  color: #666;
}

.stock-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stock-details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.current-stock {
  font-weight: 500;
}

.min-stock {
  color: #666;
}

.image-uploader {
  text-align: center;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  padding: 20px;
  cursor: pointer;
  transition: border-color 0.3s;
}

.image-uploader:hover {
  border-color: #1890ff;
}

.scanner-section {
  text-align: center;
}

.scanner-area {
  margin-bottom: 16px;
  border: 2px dashed #d9d9d9;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
}

.scanner-controls {
  margin-bottom: 16px;
}

.scanner-controls .ant-btn {
  margin: 0 8px;
}

.scanned-result {
  margin-top: 16px;
}
</style>
