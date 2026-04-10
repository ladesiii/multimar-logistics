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
          <th>Incoterm</th>
          <th>Fecha de creación</th>
          <th>Precio</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="9">Cargando ofertas...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td colspan="9">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="offers.length === 0">
          <td colspan="9">No hay ofertas para mostrar.</td>
        </tr>
        <tr v-else v-for="offer in offers" :key="offer.id">
          <td>{{ offer.id }}</td>
          <td>{{ offer.client || '-' }}</td>
          <td>{{ offer.operador || '-' }}</td>
          <td>
            <span class="status-badge" :class="getOfferStatusClass(offer)">
              {{ getOfferStatusLabel(offer) }}
            </span>
          </td>
          <td>{{ offer.tipus_transport || '-' }}</td>
          <td>{{ offer.tipus_incoterm || '-' }}</td>
          <td>{{ offer.data_creacio || '-' }}</td>
          <td>{{ offer.preu ?? '-' }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn view-btn" aria-label="Ver oferta" @click="openViewModal(offer)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
            <button type="button" class="icon-btn delete-btn" aria-label="Eliminar oferta" @click="openDeleteModal(offer)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <OfertaDetalleModal
      :is-open="isViewModalOpen"
      :offer="selectedOffer"
      :is-loading="isViewLoading"
      :error-message="viewErrorMessage"
      :is-status-updating="isStatusUpdating"
      :status-action-error="statusActionError"
      @close="closeViewModal"
      @accept="updateOfferStatus(2)"
      @reject="openRejectModal"
    />

    <EliminarOfertaModal
      v-if="isDeleteModalOpen && offerToDelete"
      :offer="offerToDelete"
      @close="closeDeleteModal"
      @confirm="confirmDeleteOffer"
    />

    <RechazarOfertaModal
      v-if="isRejectModalOpen && selectedOffer"
      :offer="selectedOffer"
      :is-submitting="isStatusUpdating"
      :error-message="rejectModalError"
      @close="closeRejectModal"
      @submit="submitRejectOffer"
    />

    <p v-if="submitError" class="submit-error">{{ submitError }}</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import OfertaDetalleModal from './OfertaDetalleModal.vue'
import EliminarOfertaModal from './EliminarOfertaModal.vue'
import RechazarOfertaModal from './RechazarOfertaModal.vue'

const offers = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const submitError = ref('')
const isViewModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isRejectModalOpen = ref(false)
const selectedOffer = ref(null)
const offerToDelete = ref(null)
const isViewLoading = ref(false)
const viewErrorMessage = ref('')
const isStatusUpdating = ref(false)
const statusActionError = ref('')
const rejectModalError = ref('')

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

const getOfferStatusLabel = (offer) => {
  const statusId = Number(offer?.estat_oferta_id)

  if (statusId === 1) {
    return 'Pendiente'
  }

  if (statusId === 2) {
    return 'Aceptada'
  }

  if (statusId === 3) {
    return 'Rechazada'
  }

  return offer?.estat || '-'
}

const getOfferStatusClass = (offer) => {
  const statusId = Number(offer?.estat_oferta_id)

  if (statusId === 1) {
    return 'status-pending'
  }

  if (statusId === 2) {
    return 'status-accepted'
  }

  if (statusId === 3) {
    return 'status-rejected'
  }

  return ''
}

onMounted(() => {
  loadOffers()
})

const openViewModal = async (offer) => {
  isViewModalOpen.value = true
  isViewLoading.value = true
  viewErrorMessage.value = ''
  statusActionError.value = ''
  selectedOffer.value = null

  try {
    const { data } = await window.axios.get(`/api/offers/${offer.id}`)
    selectedOffer.value = data.offer || null
  } catch {
    viewErrorMessage.value = 'No se pudo cargar el detalle de la oferta.'
  } finally {
    isViewLoading.value = false
  }

  if (!selectedOffer.value && !viewErrorMessage.value) {
    viewErrorMessage.value = 'No se encontró el detalle de la oferta.'
  }

  isViewModalOpen.value = true
}

const closeViewModal = () => {
  selectedOffer.value = null
  viewErrorMessage.value = ''
  statusActionError.value = ''
  rejectModalError.value = ''
  isViewLoading.value = false
  isStatusUpdating.value = false
  isRejectModalOpen.value = false
  isViewModalOpen.value = false
}

const updateOfferStatus = async (statusId) => {
  if (!selectedOffer.value?.id || isStatusUpdating.value) {
    return
  }

  statusActionError.value = ''
  isStatusUpdating.value = true

  try {
    const { data } = await window.axios.patch(`/api/offers/${selectedOffer.value.id}/status`, {
      estat_oferta_id: statusId,
    })

    selectedOffer.value = data.offer || selectedOffer.value
    await loadOffers()
  } catch (error) {
    statusActionError.value = error.response?.data?.message || 'No se pudo actualizar el estado de la oferta.'
  } finally {
    isStatusUpdating.value = false
  }
}

const openRejectModal = () => {
  rejectModalError.value = ''
  isRejectModalOpen.value = true
}

const closeRejectModal = () => {
  if (isStatusUpdating.value) {
    return
  }

  rejectModalError.value = ''
  isRejectModalOpen.value = false
}

const submitRejectOffer = async (reason) => {
  if (!reason) {
    rejectModalError.value = 'Debes indicar un motivo para rechazar la oferta.'
    return
  }

  if (!selectedOffer.value?.id || isStatusUpdating.value) {
    return
  }

  statusActionError.value = ''
  rejectModalError.value = ''
  isStatusUpdating.value = true

  try {
    const { data } = await window.axios.patch(`/api/offers/${selectedOffer.value.id}/status`, {
      estat_oferta_id: 3,
      rao_rebuig: reason,
    })

    selectedOffer.value = data.offer || selectedOffer.value
    isRejectModalOpen.value = false
    await loadOffers()
  } catch (error) {
    const message = error.response?.data?.message || 'No se pudo actualizar el estado de la oferta.'
    statusActionError.value = message
    rejectModalError.value = message
  } finally {
    isStatusUpdating.value = false
  }
}

const openDeleteModal = (offer) => {
  submitError.value = ''
  offerToDelete.value = offer
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  offerToDelete.value = null
}

const confirmDeleteOffer = async () => {
  if (!offerToDelete.value?.id) {
    return
  }

  submitError.value = ''

  try {
    await window.axios.delete(`/api/offers/${offerToDelete.value.id}`)
    closeDeleteModal()
    await loadOffers()
  } catch {
    submitError.value = 'No se pudo eliminar la oferta.'
  }
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
  vertical-align: middle;
  border-bottom: 1px solid #e6edf3;
}

.data-table thead th {
  color: #003b6f;
  font-weight: 700;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.72rem;
  border-radius: 999px;
  border: 1px solid #d9e4ee;
  background: #f8fbfe;
  font-size: 0.9rem;
  font-weight: 700;
  line-height: 1.1;
}

.status-pending {
  border-color: #f5a524;
  color: #b45309;
  background: #fff7e6;
}

.status-accepted {
  border-color: #22c55e;
  color: #166534;
  background: #ecfdf3;
}

.status-rejected {
  border-color: #ef4444;
  color: #991b1b;
  background: #fef2f2;
}

.actions-cell {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
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

.action-icon {
  width: 16px;
  height: 16px;
}

.view-btn {
  color: #0d4a7b;
}

.delete-btn {
  color: #b42318;
}

.submit-error {
  margin-top: 0.75rem;
  color: #b42318;
  font-size: 0.88rem;
  font-weight: 600;
}
</style>
