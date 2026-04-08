<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Clientes</h1>
      <button type="button" class="add-client-btn" @click="isModalOpen = true">
        Anadir cliente
      </button>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Empresa</th>
          <th>CIF/NIF</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="4">Aquí irá la tabla de clientes.</td>
        </tr>
      </tbody>
    </table>

    <NuevoClienteModal
      v-if="isModalOpen"
      @close="isModalOpen = false"
      @submit="handleCreateClient"
    />
  </section>
</template>

<script setup>
import { ref } from 'vue'
import NuevoClienteModal from './NuevoClienteModal.vue'

const isModalOpen = ref(false)

const handleCreateClient = (clientData) => {
  console.log('Nuevo cliente:', clientData)
  isModalOpen.value = false
}
</script>

<style scoped>
.table-panel {
  background: #ffffff;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 6px 18px rgba(0, 30, 51, 0.08);
}

.table-header h1 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #002855;
  margin: 0;
}

.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.add-client-btn {
  border: none;
  border-radius: 10px;
  background-color: #09253b;
  color: #ffffff;
  font-weight: 700;
  padding: 0.55rem 0.9rem;
  cursor: pointer;
}

.add-client-btn:hover {
  opacity: 0.9;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  text-align: left;
  padding: 0.75rem;
  border-bottom: 1px solid #e6edf3;
}

.data-table thead th {
  color: #003b6f;
  font-weight: 700;
}
</style>
