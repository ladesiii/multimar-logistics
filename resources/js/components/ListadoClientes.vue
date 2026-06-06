
<template>

  <section class="table-panel">

    <header class="table-header">
      <h1>Clientes</h1>
      <button type="button" class="add-entity-btn" @click="modalNuevoAbierto = true">
        Añadir cliente
      </button>
    </header>


    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>ID Usuario</th>
          <th>Empresa</th>
          <th>CIF/NIF</th>
          <th>Telefono</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="estaCargando">
          <td colspan="8">Cargando clientes...</td>
        </tr>
        <tr v-else-if="mensajeError">
          <td colspan="8">{{ mensajeError }}</td>
        </tr>
        <tr v-else-if="clientes.length === 0">
          <td colspan="8">No hay clientes para mostrar.</td>
        </tr>
        <tr v-else v-for="cliente in clientes" :key="cliente.id">
          <td>{{ cliente.id }}</td>
          <td>{{ cliente.nom_complet }}</td>
          <td>{{ cliente.email }}</td>
          <td>{{ cliente.usuari_id }}</td>
          <td>{{ cliente.nom_empresa }}</td>
          <td>{{ cliente.cif_nif }}</td>
          <td>{{ cliente.telefon || '-' }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn edit-btn" aria-label="Editar cliente" @click="abrirModalEditar(cliente)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L9.23 17.06 5 18l.939-4.23 10.923-10.283Z" />
              </svg>
            </button>
            <button type="button" class="icon-btn delete-btn" aria-label="Eliminar cliente" @click="abrirModalEliminar(cliente)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>


    <NuevoClienteModal
      v-if="modalNuevoAbierto"
      @close="modalNuevoAbierto = false"
      @submit="crearCliente"
    />


    <EditarClienteModal
      v-if="modalEditarAbierto && clienteSeleccionado"
      :client="clienteSeleccionado"
      @close="modalEditarAbierto = false"
      @submit="editarCliente"
    />


    <EliminarClienteModal
      v-if="modalEliminarAbierto && clienteAEliminar"
      :client="clienteAEliminar"
      @close="cerrarModalEliminar"
      @confirm="confirmarEliminarCliente"
    />


    <p v-if="errorEnvio" class="submit-error">{{ errorEnvio }}</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import NuevoClienteModal from './modals/NuevoClienteModal.vue'
import EditarClienteModal from './modals/EditarClienteModal.vue'
import EliminarClienteModal from './modals/EliminarClienteModal.vue'
import axios from 'axios'

const clientes = ref([])
const estaCargando = ref(true)
const mensajeError = ref('')
const modalNuevoAbierto = ref(false)
const modalEditarAbierto = ref(false)
const modalEliminarAbierto = ref(false)
const clienteSeleccionado = ref(null)
const clienteAEliminar = ref(null)
const errorEnvio = ref('')

const obtenerMensajeValidacion = (error, mensajePorDefecto) => {
  const mensajeApi = error.response?.data?.message
  const erroresValidacion = error.response?.data?.errors
  const primerErrorValidacion = erroresValidacion ? Object.values(erroresValidacion)[0]?.[0] : ''

  return primerErrorValidacion || mensajeApi || mensajePorDefecto
}

const cargarClientes = () => {
  estaCargando.value = true
  mensajeError.value = ''

  axios.get('/api/clients')
    .then(({ data }) => {
      clientes.value = data.clients || []
    })
    .catch(() => {
      mensajeError.value = 'No se pudieron cargar los clientes.'
    })
    .finally(() => {
      estaCargando.value = false
    })
}

onMounted(() => {
  cargarClientes()
})

const crearCliente = (datosCliente) => {
  errorEnvio.value = ''

  axios.post('/api/clients', datosCliente)
    .then(() => {
      modalNuevoAbierto.value = false
      cargarClientes()
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        errorEnvio.value = obtenerMensajeValidacion(error, 'Revisa los datos del formulario.')
        return
      }

      errorEnvio.value = 'No se pudo crear el cliente.'
    })
}

const abrirModalEditar = (cliente) => {
  errorEnvio.value = ''
  clienteSeleccionado.value = {
    id: cliente.id,
    nom: cliente.nom || '',
    cognoms: cliente.cognoms || '',
    email: cliente.email || '',
    nom_empresa: cliente.nom_empresa || '',
    cif_nif: cliente.cif_nif || '',
    telefon: cliente.telefon || '',
  }
  modalEditarAbierto.value = true
}

const editarCliente = (datosCliente) => {
  if (!clienteSeleccionado.value?.id) {
    return
  }

  errorEnvio.value = ''

  axios.put(`/api/clients/${clienteSeleccionado.value.id}`, datosCliente)
    .then(() => {
      modalEditarAbierto.value = false
      clienteSeleccionado.value = null
      cargarClientes()
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        errorEnvio.value = obtenerMensajeValidacion(error, 'Revisa los datos del formulario.')
        return
      }

      errorEnvio.value = 'No se pudo actualizar el cliente.'
    })
}

const abrirModalEliminar = (cliente) => {
  errorEnvio.value = ''
  clienteAEliminar.value = cliente
  modalEliminarAbierto.value = true
}

const cerrarModalEliminar = () => {
  modalEliminarAbierto.value = false
  clienteAEliminar.value = null
}

const confirmarEliminarCliente = () => {
  if (!clienteAEliminar.value?.id) {
    return
  }

  errorEnvio.value = ''

  axios.delete(`/api/clients/${clienteAEliminar.value.id}`)
    .then(() => {
      cerrarModalEliminar()
      cargarClientes()
    })
    .catch(() => {
      errorEnvio.value = 'No se pudo eliminar el cliente.'
    })
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

.edit-btn {
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
</style>

