<!--
Componente: ListadoUsuarios
Descripción: Muestra usuarios del sistema y gestiona altas, edición y eliminación mediante modales.
-->
<template>
  <!-- Panel principal de la sección de usuarios -->
  <section class="table-panel">
    <!-- Cabecera con acción para crear usuario -->
    <header class="table-header">
      <h1>Usuarios</h1>
      <button type="button" class="add-entity-btn" @click="modalNuevoAbierto = true">
        Añadir usuario
      </button>
    </header>

    <!-- Tabla con estados de carga, error, vacío y resultados -->
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
        <tr v-if="estaCargando">
          <td colspan="5">Cargando usuarios...</td>
        </tr>
        <tr v-else-if="mensajeError">
          <td colspan="5">{{ mensajeError }}</td>
        </tr>
        <tr v-else-if="usuarios.length === 0">
          <td colspan="5">No hay usuarios para mostrar.</td>
        </tr>
        <tr v-else v-for="usuario in usuarios" :key="usuario.id">
          <td>{{ usuario.id }}</td>
          <td>{{ usuario.nom_complet }}</td>
          <td>{{ usuario.email }}</td>
          <td>{{ usuario.rol }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn edit-btn" aria-label="Editar usuario" @click="abrirModalEditar(usuario)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L9.23 17.06 5 18l.939-4.23 10.923-10.283Z" />
              </svg>
            </button>
            <button type="button" class="icon-btn delete-btn" aria-label="Eliminar usuario" @click="abrirModalEliminar(usuario)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal de alta de usuario -->
    <NuevoUsuarioModal
      v-if="modalNuevoAbierto"
      @close="modalNuevoAbierto = false"
      @submit="crearUsuario"
    />

    <!-- Modal de edición de usuario -->
    <EditarUsuarioModal
      v-if="modalEditarAbierto && usuarioSeleccionado"
      :user="usuarioSeleccionado"
      @close="modalEditarAbierto = false"
      @submit="editarUsuario"
    />

    <!-- Modal de confirmación de borrado -->
    <EliminarUsuarioModal
      v-if="modalEliminarAbierto && usuarioAEliminar"
      :user="usuarioAEliminar"
      @close="cerrarModalEliminar"
      @confirm="confirmarEliminarUsuario"
    />

    <!-- Error de operaciones de envío -->
    <p v-if="errorEnvio" class="submit-error">{{ errorEnvio }}</p>
  </section>
</template>

<script setup>
// Importaciones de Vue y modales usados por esta pantalla.
import { onMounted, ref } from 'vue'
import NuevoUsuarioModal from './modals/NuevoUsuarioModal.vue'
import EditarUsuarioModal from './modals/EditarUsuarioModal.vue'
import EliminarUsuarioModal from './modals/EliminarUsuarioModal.vue'

// Estado de la vista.
const usuarios = ref([])
const estaCargando = ref(true)
const mensajeError = ref('')
const modalNuevoAbierto = ref(false)
const modalEditarAbierto = ref(false)
const modalEliminarAbierto = ref(false)
const usuarioSeleccionado = ref(null)
const usuarioAEliminar = ref(null)
const errorEnvio = ref('')

// Lee mensajes de validación devueltos por la API.
const obtenerMensajeValidacion = (error, mensajePorDefecto) => {
  const mensajeApi = error.response?.data?.message
  const erroresValidacion = error.response?.data?.errors
  const primerErrorValidacion = erroresValidacion ? Object.values(erroresValidacion)[0]?.[0] : ''

  return primerErrorValidacion || mensajeApi || mensajePorDefecto
}

// Carga usuarios desde backend.
const cargarUsuarios = () => {
  estaCargando.value = true
  mensajeError.value = ''

  window.axios.get('/api/users')
    .then(({ data }) => {
      usuarios.value = data.users || []
    })
    .catch(() => {
      mensajeError.value = 'No se pudieron cargar los usuarios.'
    })
    .finally(() => {
      estaCargando.value = false
    })
}

onMounted(() => {
  // Carga inicial al entrar en la vista.
  cargarUsuarios()
})

// Crea un nuevo usuario.
const crearUsuario = (datosUsuario) => {
  errorEnvio.value = ''

  window.axios.post('/api/users', datosUsuario)
    .then(() => {
      modalNuevoAbierto.value = false
      cargarUsuarios()
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        errorEnvio.value = obtenerMensajeValidacion(error, 'Revisa los datos del formulario.')
        return
      }

      errorEnvio.value = 'No se pudo crear el usuario.'
    })
}

// Abre el modal de edición con una copia de datos.
const abrirModalEditar = (usuario) => {
  errorEnvio.value = ''
  usuarioSeleccionado.value = {
    id: usuario.id,
    nom: usuario.nom || '',
    cognoms: usuario.cognoms || '',
    email: usuario.email || '',
    rol_id: usuario.rol_id || 2,
  }
  modalEditarAbierto.value = true
}

// Actualiza usuario existente.
const editarUsuario = (datosUsuario) => {
  if (!usuarioSeleccionado.value?.id) {
    return
  }

  errorEnvio.value = ''

  window.axios.put(`/api/users/${usuarioSeleccionado.value.id}`, datosUsuario)
    .then(() => {
      modalEditarAbierto.value = false
      usuarioSeleccionado.value = null
      cargarUsuarios()
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        errorEnvio.value = obtenerMensajeValidacion(error, 'Revisa los datos del formulario.')
        return
      }

      errorEnvio.value = 'No se pudo actualizar el usuario.'
    })
}

// Abre el modal de confirmación de borrado.
const abrirModalEliminar = (usuario) => {
  errorEnvio.value = ''
  usuarioAEliminar.value = usuario
  modalEliminarAbierto.value = true
}

// Cierra y limpia selección de borrado.
const cerrarModalEliminar = () => {
  modalEliminarAbierto.value = false
  usuarioAEliminar.value = null
}

// Elimina usuario seleccionado y refresca tabla.
const confirmarEliminarUsuario = () => {
  if (!usuarioAEliminar.value?.id) {
    return
  }

  errorEnvio.value = ''

  window.axios.delete(`/api/users/${usuarioAEliminar.value.id}`)
    .then(() => {
      cerrarModalEliminar()
      cargarUsuarios()
    })
    .catch(() => {
      errorEnvio.value = 'No se pudo eliminar el usuario.'
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
