<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Tracking</h1>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Ruta</th>
          <th>Medio</th>
          <th>Incoterm</th>
          <th>Estado</th>
          <th>Fecha creación</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="7">Cargando tracking...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td colspan="7">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="trackingOffers.length === 0">
          <td colspan="7">No hay ofertas en tracking.</td>
        </tr>
        <tr v-else v-for="offer in trackingOffers" :key="offer.id">
          <td>{{ offer.id }}</td>
          <td>{{ offer.ruta || '-' }}</td>
          <td>{{ offer.medio || '-' }}</td>
          <td>{{ offer.incoterm || '-' }}</td>
          <td>
            <span class="tracking-badge">{{ offer.estado || 'En tracking' }}</span>
          </td>
          <td>{{ offer.fecha_creacion || '-' }}</td>
          <td class="actions-cell">
            <button
              type="button"
              class="icon-btn view-btn"
              aria-label="Ver tracking"
              title="Ver tracking"
              @click="openTrackingDetail(offer)"
            >
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const trackingOffers = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

const loadTracking = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const { data } = await window.axios.get('/api/tracking')
    trackingOffers.value = Array.isArray(data?.tracking) ? data.tracking : []
  } catch {
    errorMessage.value = 'No se pudo cargar el tracking.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTracking()
})

const openTrackingDetail = (offer) => {
  // Placeholder for future navigation handled by another teammate.
  console.info('Abrir detalle tracking', offer?.id)
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

.tracking-badge {
  display: inline-flex;
  align-items: center;
  border: 1px solid #22c55e;
  background: #ecfdf3;
  color: #166534;
  border-radius: 999px;
  padding: 0.32rem 0.68rem;
  font-size: 0.86rem;
  font-weight: 700;
}

.actions-cell {
  display: flex;
  align-items: center;
}

.icon-btn {
  width: 32px;
  height: 32px;
  border: 1px solid #d9e4ee;
  border-radius: 8px;
  background: #f8fbfe;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.view-btn {
  color: #0d4a7b;
}

.action-icon {
  width: 16px;
  height: 16px;
}
</style>
