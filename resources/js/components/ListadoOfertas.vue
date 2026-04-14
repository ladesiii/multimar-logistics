<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Ofertas</h1>
      <button
        v-if="canCreateOffers"
        type="button"
        class="add-entity-btn"
        @click="openCreateModal"
      >
        Crear oferta
      </button>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID de oferta</th>
          <th v-if="showClientColumn">Cliente</th>
          <th v-if="showOperatorColumn">Operador</th>
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
          <td :colspan="tableColumnsCount">Cargando ofertas...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td :colspan="tableColumnsCount">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="offers.length === 0">
          <td :colspan="tableColumnsCount">No hay ofertas para mostrar.</td>
        </tr>
        <tr v-else v-for="offer in offers" :key="offer.id">
          <td>{{ offer.id }}</td>
          <td v-if="showClientColumn">{{ offer.client || '-' }}</td>
          <td v-if="showOperatorColumn">{{ offer.operador || '-' }}</td>
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
            <button
              v-if="canDeleteOffers"
              type="button"
              class="icon-btn delete-btn"
              aria-label="Eliminar oferta"
              @click="openDeleteModal(offer)"
            >
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
      :can-manage-status="canManageOfferStatus"
      :current-role="currentRole"
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

    <NuevaOfertaModal
      v-if="isCreateModalOpen"
      :options="offerFormOptions"
      :is-loading="isFormOptionsLoading"
      :error-message="formOptionsError"
      @close="closeCreateModal"
      @submit="handleCreateOffer"
    />

    <p v-if="submitError" class="submit-error">{{ submitError }}</p>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import OfertaDetalleModal from './modals/OfertaDetalleModal.vue'
import EliminarOfertaModal from './modals/EliminarOfertaModal.vue'
import RechazarOfertaModal from './modals/RechazarOfertaModal.vue'
import NuevaOfertaModal from './modals/NuevaOfertaModal.vue'

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
const isCreateModalOpen = ref(false)
const isFormOptionsLoading = ref(false)
const formOptionsError = ref('')
const offerFormOptions = ref({})

const currentRole = computed(() => {
  const rawUser = localStorage.getItem('auth_user')

  if (!rawUser) {
    return ''
  }

  try {
    const user = JSON.parse(rawUser)
    const roleName = String(user?.rol || '').toLowerCase()
    const roleId = Number(user?.rol_id || 0)

    if (roleId === 1 || roleName.includes('admin')) {
      return 'admin'
    }

    if (roleId === 2 || roleName.includes('operador') || roleName.includes('operator')) {
      return 'operador'
    }

    if (roleId === 3 || roleName.includes('client')) {
      return 'cliente'
    }
  } catch {
    return ''
  }

  return ''
})

const canDeleteOffers = computed(() => ['admin', 'operador'].includes(currentRole.value))
const canManageOfferStatus = computed(() => ['admin', 'cliente'].includes(currentRole.value))
const canCreateOffers = computed(() => currentRole.value === 'operador')
const showClientColumn = computed(() => currentRole.value !== 'cliente')
const showOperatorColumn = computed(() => currentRole.value !== 'operador')
const tableColumnsCount = computed(() => {
  return 7 + (showClientColumn.value ? 1 : 0) + (showOperatorColumn.value ? 1 : 0)
})

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

const loadFormOptions = async () => {
  isFormOptionsLoading.value = true
  formOptionsError.value = ''

  try {
    const { data } = await window.axios.get('/api/offers/form-options')
    offerFormOptions.value = data || {}
  } catch {
    formOptionsError.value = 'No se pudieron cargar los campos para crear la oferta.'
  } finally {
    isFormOptionsLoading.value = false
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
}

const closeViewModal = () => {
  isViewModalOpen.value = false
  selectedOffer.value = null
  viewErrorMessage.value = ''
  statusActionError.value = ''
}

const updateOfferStatus = async (statusId) => {
  if (!selectedOffer.value?.id) {
    return
  }

  isStatusUpdating.value = true
  statusActionError.value = ''

  try {
    await window.axios.patch(`/api/offers/${selectedOffer.value.id}/status`, {
      estat_oferta_id: statusId,
    })
    closeViewModal()
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
  isRejectModalOpen.value = false
  rejectModalError.value = ''
}

const submitRejectOffer = async ({ rao_rebuig }) => {
  if (!selectedOffer.value?.id) {
    return
  }

  isStatusUpdating.value = true
  rejectModalError.value = ''

  try {
    await window.axios.patch(`/api/offers/${selectedOffer.value.id}/status`, {
      estat_oferta_id: 3,
      rao_rebuig,
    })
    closeRejectModal()
    closeViewModal()
    await loadOffers()
  } catch (error) {
    rejectModalError.value = error.response?.data?.message || 'No se pudo rechazar la oferta.'
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

const openCreateModal = async () => {
  submitError.value = ''
  isCreateModalOpen.value = true

  if (Object.keys(offerFormOptions.value || {}).length > 0) {
    return
  }

  await loadFormOptions()
}

const closeCreateModal = () => {
  isCreateModalOpen.value = false
}

const handleCreateOffer = async (payload) => {
  submitError.value = ''

  try {
    await window.axios.post('/api/offers', {
      ...payload,
      data_creacio: new Date().toISOString().slice(0, 10),
    })

    closeCreateModal()
    await loadOffers()
  } catch (error) {
    if (error.response?.status === 422) {
      const apiMessage = error.response?.data?.message
      const validationErrors = error.response?.data?.errors
      const firstValidationError = validationErrors
        ? Object.values(validationErrors)[0]?.[0]
        : ''

      submitError.value = firstValidationError || apiMessage || 'Revisa los datos del formulario de oferta.'
      return
    }

    submitError.value = error.response?.data?.message || 'No se pudo crear la oferta.'
  }
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
  margin: 0;
}

.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.add-entity-btn {
  border: none;
  border-radius: 10px;
  background-color: #09253b;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.9rem;
  padding: 0.55rem 0.9rem;
  line-height: 1;
  cursor: pointer;
}

.add-entity-btn:hover {
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

.actions-cell {
  display: flex;
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
  margin-top: 1rem;
  color: #b42318;
  font-weight: 700;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 0.3rem 0.7rem;
  font-size: 0.85rem;
  font-weight: 700;
}

.status-pending {
  background: #fff7e6;
  border: 1px solid #f5a524;
  color: #b45309;
}

.status-accepted {
  background: #ecfdf3;
  border: 1px solid #22c55e;
  color: #166534;
}

.status-rejected {
  background: #fef2f2;
  border: 1px solid #ef4444;
  color: #991b1b;
}
</style>
