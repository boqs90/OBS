<template>
  <div class="import-page">
    <!-- Botón de regreso -->
    <div class="back-button-container">
      <a-button @click="goBack" type="link" style="padding: 0; margin-bottom: 16px;">
        <a-icon type="arrow-left" style="margin-right: 8px;" />
        Regresar a Registro de Alumnos
      </a-button>
    </div>
    
    <a-row :gutter="24" type="flex">
      <a-col :span="24" class="mb-24">
        <ImportStudents />
      </a-col>
    </a-row>
  </div>
</template>

<script>
import ImportStudents from '@/components/Students/ImportStudents.vue';

export default {
  name: 'ImportarAlumnos',
  components: {
    ImportStudents
  },
  methods: {
    goBack() {
      this.$router.push('/registro/alumnos');
    }
  }
};
</script>

<style scoped>
.import-page {
  padding: 24px;
}

.back-button-container {
  margin-bottom: 16px;
}

.back-button-container .ant-btn-link {
  color: #1890ff;
  font-size: 14px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
}

.back-button-container .ant-btn-link:hover {
  color: #40a9ff;
}
</style>
