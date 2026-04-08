<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Usuarios</h1>
      <button type="button" class="add-user-btn" @click="isModalOpen = true">
        Añadir usuario
      </button>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="5">Cargando usuarios...</td>
        </tr>
        <tr v-else-if="errorMessage">
          <td colspan="5">{{ errorMessage }}</td>
        </tr>
        <tr v-else-if="users.length === 0">
          <td colspan="5">No hay usuarios para mostrar.</td>
        </tr>
        <tr v-else v-for="user in users" :key="user.id">
          <td>{{ user.id }}</td>
          <td>{{ user.nom_complet }}</td>
          <td>{{ user.email }}</td>
          <td>{{ user.rol }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn edit-btn" aria-label="Editar usuario" @click="openEditModal(user)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L9.23 17.06 5 18l.939-4.23 10.923-10.283Z" />
              </svg>
            </button>
            <button type="button" class="icon-btn delete-btn" aria-label="Eliminar usuario" @click="openDeleteModal(user)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <NuevoUsuarioModal
      v-if="isModalOpen"
      @close="isModalOpen = false"
      @submit="handleCreateUser"
    />

    <EditarUsuarioModal
      v-if="isEditModalOpen && selectedUser"
      :user="selectedUser"
      @close="isEditModalOpen = false"
      @submit="handleEditUser"
    />

    <EliminarUsuarioModal
      v-if="isDeleteModalOpen && userToDelete"
      :user="userToDelete"
      @close="closeDeleteModal"
      @confirm="confirmDeleteUser"
    />

    <p v-if="submitError" class="submit-error">{{ submitError }}</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import NuevoUsuarioModal from './NuevoUsuarioModal.vue'
import EditarUsuarioModal from './EditarUsuarioModal.vue'
import EliminarUsuarioModal from './EliminarUsuarioModal.vue'

const users = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const selectedUser = ref(null)
const userToDelete = ref(null)
const submitError = ref('')

const loadUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const { data } = await window.axios.get('/api/users')
    users.value = data.users || []
  } catch {
    errorMessage.value = 'No se pudieron cargar los usuarios.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadUsers()
})

const handleCreateUser = async (userData) => {
  submitError.value = ''

  try {
    await window.axios.post('/api/users', userData)
    isModalOpen.value = false
    await loadUsers()
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

    submitError.value = 'No se pudo crear el usuario.'
  }
}

const openEditModal = (user) => {
  submitError.value = ''
  selectedUser.value = {
    id: user.id,
    nom: user.nom || '',
    cognoms: user.cognoms || '',
    email: user.email || '',
    rol_id: user.rol_id || 2,
  }
  isEditModalOpen.value = true
}

const handleEditUser = async (userData) => {
  if (!selectedUser.value?.id) {
    return
  }

  submitError.value = ''

  try {
    await window.axios.put(`/api/users/${selectedUser.value.id}`, userData)
    isEditModalOpen.value = false
    selectedUser.value = null
    await loadUsers()
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

    submitError.value = 'No se pudo actualizar el usuario.'
  }
}

const openDeleteModal = (user) => {
  submitError.value = ''
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  userToDelete.value = null
}

const confirmDeleteUser = async () => {
  if (!userToDelete.value?.id) {
    return
  }

  submitError.value = ''

  try {
    await window.axios.delete(`/api/users/${userToDelete.value.id}`)
    closeDeleteModal()
    await loadUsers()
  } catch {
    submitError.value = 'No se pudo eliminar el usuario.'
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

.add-user-btn {
  border: none;
  border-radius: 10px;
  background-color: #09253B;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.9rem;
  padding: 0.6rem 0.8rem;
  line-height: 1;
  cursor: pointer;
}

.add-user-btn:hover {
  opacity: 90%;
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
