<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Ofertas</h1>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID de oferta</th>
          <th>Cliente</th>
          <th>Operador</th>
          <th>Estado de la oferta</th>
          <th>Tipo de transporte</th>
          <th>Fecha de creación</th>
          <th>Precio</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="7">Cargando ofertas...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td colspan="7">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="offers.length === 0">
          <td colspan="7">No hay ofertas para mostrar.</td>
        </tr>
        <tr v-else v-for="offer in offers" :key="offer.id">
          <td>{{ offer.id }}</td>
          <td>{{ offer.client || '-' }}</td>
          <td>{{ offer.operador || '-' }}</td>
          <td>{{ offer.estat || '-' }}</td>
          <td>{{ offer.tipus_transport || '-' }}</td>
          <td>{{ offer.data_creacio || '-' }}</td>
          <td>{{ offer.preu ?? '-' }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const offers = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

const loadOffers = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const { data } = await window.axios.get('/api/offers')
    offers.value = data.offers || []
  } catch {
    errorMessage.value = 'No se pudieron cargar las ofertas.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadOffers()
})
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
  margin-bottom: 1rem;
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
