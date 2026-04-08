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
          <th>Nombre</th>
          <th>Email</th>
          <th>Empresa</th>
          <th>CIF/NIF</th>
          <th>Contacto</th>
          <th>Telefono</th>
          <th>Direccion</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="9">Cargando clientes...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td colspan="9">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="clients.length === 0">
          <td colspan="9">No hay clientes para mostrar.</td>
        </tr>
        <tr v-else v-for="client in clients" :key="client.id">
          <td>{{ client.id }}</td>
          <td>{{ client.nom_complet }}</td>
          <td>{{ client.email }}</td>
          <td>{{ client.nom_empresa }}</td>
          <td>{{ client.cif_nif }}</td>
          <td>{{ client.contacte || '-' }}</td>
          <td>{{ client.telefon || '-' }}</td>
          <td>{{ client.adreca || '-' }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn edit-btn" aria-label="Editar cliente" @click="openEditModal(client)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L9.23 17.06 5 18l.939-4.23 10.923-10.283Z" />
              </svg>
            </button>
            <button type="button" class="icon-btn delete-btn" aria-label="Eliminar cliente" @click="openDeleteModal(client)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <NuevoClienteModal
      v-if="isModalOpen"
      @close="isModalOpen = false"
      @submit="handleCreateClient"
    />

    <EditarClienteModal
      v-if="isEditModalOpen && selectedClient"
      :client="selectedClient"
      @close="isEditModalOpen = false"
      @submit="handleEditClient"
    />

    <EliminarClienteModal
      v-if="isDeleteModalOpen && clientToDelete"
      :client="clientToDelete"
      @close="closeDeleteModal"
      @confirm="confirmDeleteClient"
    />

    <p v-if="submitError" class="submit-error">{{ submitError }}</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import NuevoClienteModal from './NuevoClienteModal.vue'
import EditarClienteModal from './EditarClienteModal.vue'
import EliminarClienteModal from './EliminarClienteModal.vue'

const clients = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const selectedClient = ref(null)
const clientToDelete = ref(null)
const submitError = ref('')

const loadClients = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const { data } = await window.axios.get('/api/clients')
    clients.value = data.clients || []
  } catch {
    errorMessage.value = 'No se pudieron cargar los clientes.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadClients()
})

const handleCreateClient = async (clientData) => {
  submitError.value = ''

  try {
    await window.axios.post('/api/clients', clientData)
    isModalOpen.value = false
    await loadClients()
  } catch (error) {
    if (error.response?.status === 422) {
      const apiMessage = error.response?.data?.message
      const validationErrors = error.response?.data?.errors
      const firstValidationError = validationErrors
        ? Object.values(validationErrors)[0]?.[0]
        : ''

      submitError.value = firstValidationError || apiMessage || 'Revisa los datos del formulario.'
      return
    }

    submitError.value = 'No se pudo crear el cliente.'
  }
}

const openEditModal = (client) => {
  submitError.value = ''
  selectedClient.value = {
    id: client.id,
    nom: client.nom || '',
    cognoms: client.cognoms || '',
    email: client.email || '',
    nom_empresa: client.nom_empresa || '',
    cif_nif: client.cif_nif || '',
    adreca: client.adreca || '',
    contacte: client.contacte || '',
    telefon: client.telefon || '',
  }
  isEditModalOpen.value = true
}

const handleEditClient = async (clientData) => {
  if (!selectedClient.value?.id) {
    return
  }

  submitError.value = ''

  try {
    await window.axios.put(`/api/clients/${selectedClient.value.id}`, clientData)
    isEditModalOpen.value = false
    selectedClient.value = null
    await loadClients()
  } catch (error) {
    if (error.response?.status === 422) {
      const apiMessage = error.response?.data?.message
      const validationErrors = error.response?.data?.errors
      const firstValidationError = validationErrors
        ? Object.values(validationErrors)[0]?.[0]
        : ''

      submitError.value = firstValidationError || apiMessage || 'Revisa los datos del formulario.'
      return
    }

    submitError.value = 'No se pudo actualizar el cliente.'
  }
}

const openDeleteModal = (client) => {
  submitError.value = ''
  clientToDelete.value = client
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  clientToDelete.value = null
}

const confirmDeleteClient = async () => {
  if (!clientToDelete.value?.id) {
    return
  }

  submitError.value = ''

  try {
    await window.axios.delete(`/api/clients/${clientToDelete.value.id}`)
    closeDeleteModal()
    await loadClients()
  } catch {
    submitError.value = 'No se pudo eliminar el cliente.'
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

.edit-btn {
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
