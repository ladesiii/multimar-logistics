<!--
Componente: ListadoOfertas
Descripción: Tabla principal de ofertas con visualización de detalle y acciones de crear, eliminar y cambio de estado.
-->
<template>
  <!-- Contenedor principal del listado de ofertas -->
  <section class="table-panel">
    <!-- Cabecera con botón de crear según permisos -->
    <header class="table-header">
      <h1>Ofertas</h1>
      <button
        v-if="puedeCrearOfertas"
        type="button"
        class="add-entity-btn"
        @click="abrirModalCrear"
      >
        Crear oferta
      </button>
    </header>

    <!-- Tabla con estados de carga/error/vacío y filas de ofertas -->
    <table class="data-table">
      <thead>
        <tr>
          <th>ID de oferta</th>
          <th v-if="mostrarColumnaCliente">Cliente</th>
          <th v-if="mostrarColumnaOperador">Operador</th>
          <th>Estado de la oferta</th>
          <th>Tipo de transporte</th>
          <th>Incoterm</th>
          <th>Fecha de creación</th>
          <th>Precio</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="estaCargando">
          <td :colspan="numeroColumnasTabla">Cargando ofertas...</td>
        </tr>
        <tr v-else-if="mensajeError">
          <td :colspan="numeroColumnasTabla">{{ mensajeError }}</td>
        </tr>
        <tr v-else-if="ofertas.length === 0">
          <td :colspan="numeroColumnasTabla">No hay ofertas para mostrar.</td>
        </tr>
        <tr v-else v-for="oferta in ofertas" :key="oferta.id">
          <td>{{ oferta.id }}</td>
          <td v-if="mostrarColumnaCliente">{{ oferta.client || '-' }}</td>
          <td v-if="mostrarColumnaOperador">{{ oferta.operador || '-' }}</td>
          <td>
            <span class="status-badge" :class="obtenerClaseEstadoOferta(oferta)">
              {{ obtenerEtiquetaEstadoOferta(oferta) }}
            </span>
          </td>
          <td>{{ oferta.tipus_transport || '-' }}</td>
          <td>{{ oferta.tipus_incoterm || '-' }}</td>
          <td>{{ oferta.data_creacio || '-' }}</td>
          <td>{{ oferta.preu ?? '-' }}</td>
          <td class="actions-cell">
            <button type="button" class="icon-btn view-btn" aria-label="Ver oferta" @click="abrirModalVer(oferta)">
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
            <button
              v-if="puedeEliminarOfertas"
              type="button"
              class="icon-btn delete-btn"
              aria-label="Eliminar oferta"
              @click="abrirModalEliminar(oferta)"
            >
              <svg viewBox="0 0 24 24" class="action-icon" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .92h6a1 1 0 0 0 1-.92L17 7" />
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal de detalle de oferta, con acciones de aceptar/rechazar -->
    <OfertaDetalleModal
      :is-open="modalVerAbierto"
      :offer="ofertaSeleccionada"
      :is-loading="estaCargandoDetalle"
      :error-message="mensajeErrorDetalle"
      :is-status-updating="actualizandoEstado"
      :status-action-error="errorAccionEstado"
      :can-manage-status="puedeGestionarEstadoOferta"
      :current-role="rolActual"
      @close="cerrarModalVer"
      @accept="actualizarEstadoOferta(2)"
      @reject="abrirModalRechazo"
    />

    <!-- Modal de confirmación para eliminar oferta -->
    <EliminarOfertaModal
      v-if="modalEliminarAbierto && ofertaAEliminar"
      :offer="ofertaAEliminar"
      @close="cerrarModalEliminar"
      @confirm="confirmarEliminarOferta"
    />

    <!-- Modal para registrar motivo de rechazo -->
    <RechazarOfertaModal
      v-if="modalRechazoAbierto && ofertaSeleccionada"
      :offer="ofertaSeleccionada"
      :is-submitting="actualizandoEstado"
      :error-message="errorModalRechazo"
      @close="cerrarModalRechazo"
      @submit="enviarRechazoOferta"
    />

    <!-- Modal de creación de oferta -->
    <NuevaOfertaModal
      v-if="modalCrearAbierto"
      :options="opcionesFormularioOferta"
      :is-loading="estaCargandoOpcionesFormulario"
      :error-message="errorOpcionesFormulario"
      @close="cerrarModalCrear"
      @submit="crearOferta"
    />

    <!-- Mensaje de error para operaciones de envío -->
    <p v-if="errorEnvio" class="submit-error">{{ errorEnvio }}</p>
  </section>
</template>

<script setup>
// Importaciones de Vue y modales asociados a esta vista.
import { computed, onMounted, ref } from 'vue'
import OfertaDetalleModal from './modals/OfertaDetalleModal.vue'
import EliminarOfertaModal from './modals/EliminarOfertaModal.vue'
import RechazarOfertaModal from './modals/RechazarOfertaModal.vue'
import NuevaOfertaModal from './modals/NuevaOfertaModal.vue'

// Estado local del listado y de todos sus modales/acciones.
const ofertas = ref([])
const estaCargando = ref(true)
const mensajeError = ref('')
const errorEnvio = ref('')
const modalVerAbierto = ref(false)
const modalEliminarAbierto = ref(false)
const modalRechazoAbierto = ref(false)
const ofertaSeleccionada = ref(null)
const ofertaAEliminar = ref(null)
const estaCargandoDetalle = ref(false)
const mensajeErrorDetalle = ref('')
const actualizandoEstado = ref(false)
const errorAccionEstado = ref('')
const errorModalRechazo = ref('')
const modalCrearAbierto = ref(false)
const estaCargandoOpcionesFormulario = ref(false)
const errorOpcionesFormulario = ref('')
const opcionesFormularioOferta = ref({})

// Detecta el rol del usuario guardado para habilitar acciones y columnas.
const rolActual = computed(() => {
  // El rol se resuelve tanto por id como por nombre para ser tolerante con datos antiguos.
  const usuarioEnTexto = localStorage.getItem('auth_user')

  if (!usuarioEnTexto) {
    return ''
  }

  try {
    const usuario = JSON.parse(usuarioEnTexto)
    const nombreRol = String(usuario?.rol || '').toLowerCase()
    const idRol = Number(usuario?.rol_id || 0)

    if (idRol === 1 || nombreRol.includes('admin')) {
      return 'admin'
    }

    if (idRol === 2 || nombreRol.includes('operador') || nombreRol.includes('operator')) {
      return 'operador'
    }

    if (idRol === 3 || nombreRol.includes('client')) {
      return 'cliente'
    }
  } catch {
    return ''
  }

  return ''
})

// Permisos derivados del rol.
const puedeEliminarOfertas = computed(() => ['admin', 'operador'].includes(rolActual.value))
const puedeGestionarEstadoOferta = computed(() => ['admin', 'cliente'].includes(rolActual.value))
const puedeCrearOfertas = computed(() => rolActual.value === 'operador')
const mostrarColumnaCliente = computed(() => rolActual.value !== 'cliente')
const mostrarColumnaOperador = computed(() => rolActual.value !== 'operador')
// Calcula el colspan de la tabla según columnas visibles por rol.
const numeroColumnasTabla = computed(() => {
  return 7 + (mostrarColumnaCliente.value ? 1 : 0) + (mostrarColumnaOperador.value ? 1 : 0)
})

// Carga el listado principal de ofertas.
const cargarOfertas = () => {
  estaCargando.value = true
  mensajeError.value = ''

  window.axios.get('/api/offers')
    .then(({ data }) => {
      ofertas.value = data.offers || []
    })
    .catch(() => {
      mensajeError.value = 'No se pudieron cargar las ofertas.'
    })
    .finally(() => {
      estaCargando.value = false
    })
}

// Carga opciones dinámicas del formulario de alta.
const cargarOpcionesFormulario = () => {
  estaCargandoOpcionesFormulario.value = true
  errorOpcionesFormulario.value = ''

  window.axios.get('/api/offers/form-options')
    .then(({ data }) => {
      opcionesFormularioOferta.value = data || {}
    })
    .catch(() => {
      errorOpcionesFormulario.value = 'No se pudieron cargar los campos para crear la oferta.'
    })
    .finally(() => {
      estaCargandoOpcionesFormulario.value = false
    })
}

// Convierte el id de estado a etiqueta visual.
const obtenerEtiquetaEstadoOferta = (oferta) => {
  const idEstado = Number(oferta?.estat_oferta_id)

  if (idEstado === 1) {
    return 'Pendiente'
  }

  if (idEstado === 2) {
    return 'Aceptada'
  }

  if (idEstado === 3) {
    return 'Rechazada'
  }

  return oferta?.estat || '-'
}

// Devuelve clase CSS según el estado de la oferta.
const obtenerClaseEstadoOferta = (oferta) => {
  const idEstado = Number(oferta?.estat_oferta_id)

  if (idEstado === 1) {
    return 'status-pending'
  }

  if (idEstado === 2) {
    return 'status-accepted'
  }

  if (idEstado === 3) {
    return 'status-rejected'
  }

  return ''
}

onMounted(() => {
  // Primera carga al montar la vista.
  cargarOfertas()
})

// Abre el modal de detalle y consulta información completa de la oferta.
const abrirModalVer = (oferta) => {
  modalVerAbierto.value = true
  estaCargandoDetalle.value = true
  mensajeErrorDetalle.value = ''
  errorAccionEstado.value = ''
  ofertaSeleccionada.value = null

  window.axios.get(`/api/offers/${oferta.id}`)
    .then(({ data }) => {
      ofertaSeleccionada.value = data.offer || null
    })
    .catch(() => {
      mensajeErrorDetalle.value = 'No se pudo cargar el detalle de la oferta.'
    })
    .finally(() => {
      estaCargandoDetalle.value = false
    })
}

// Cierra y limpia el modal de detalle.
const cerrarModalVer = () => {
  modalVerAbierto.value = false
  ofertaSeleccionada.value = null
  mensajeErrorDetalle.value = ''
  errorAccionEstado.value = ''
}

// Cambia el estado de la oferta (aceptar/rechazar) desde detalle.
const actualizarEstadoOferta = (idEstado) => {
  if (!ofertaSeleccionada.value?.id) {
    return
  }

  actualizandoEstado.value = true
  errorAccionEstado.value = ''

  window.axios.patch(`/api/offers/${ofertaSeleccionada.value.id}/status`, {
    estat_oferta_id: idEstado,
  })
    .then(() => {
      cerrarModalVer()
      cargarOfertas()
    })
    .catch((error) => {
      errorAccionEstado.value = error.response?.data?.message || 'No se pudo actualizar el estado de la oferta.'
    })
    .finally(() => {
      actualizandoEstado.value = false
    })
}

// Abre modal específico para capturar motivo de rechazo.
const abrirModalRechazo = () => {
  errorModalRechazo.value = ''
  modalRechazoAbierto.value = true
}

// Cierra modal de rechazo y limpia errores del mismo.
const cerrarModalRechazo = () => {
  modalRechazoAbierto.value = false
  errorModalRechazo.value = ''
}

// Envía rechazo con motivo y recarga listado.
const enviarRechazoOferta = ({ rao_rebuig }) => {
  if (!ofertaSeleccionada.value?.id) {
    return
  }

  actualizandoEstado.value = true
  errorModalRechazo.value = ''

  window.axios.patch(`/api/offers/${ofertaSeleccionada.value.id}/status`, {
    estat_oferta_id: 3,
    rao_rebuig,
  })
    .then(() => {
      cerrarModalRechazo()
      cerrarModalVer()
      cargarOfertas()
    })
    .catch((error) => {
      errorModalRechazo.value = error.response?.data?.message || 'No se pudo rechazar la oferta.'
    })
    .finally(() => {
      actualizandoEstado.value = false
    })
}

// Abre confirmación de eliminación.
const abrirModalEliminar = (oferta) => {
  errorEnvio.value = ''
  ofertaAEliminar.value = oferta
  modalEliminarAbierto.value = true
}

// Cierra confirmación de eliminación.
const cerrarModalEliminar = () => {
  modalEliminarAbierto.value = false
  ofertaAEliminar.value = null
}

// Abre modal de creación y precarga opciones si aún no existen.
const abrirModalCrear = () => {
  errorEnvio.value = ''
  modalCrearAbierto.value = true

  if (Object.keys(opcionesFormularioOferta.value || {}).length > 0) {
    return
  }

  cargarOpcionesFormulario()
}

// Cierra modal de creación.
const cerrarModalCrear = () => {
  modalCrearAbierto.value = false
}

// Envía alta de oferta al backend.
const crearOferta = (datosFormulario) => {
  errorEnvio.value = ''

  window.axios.post('/api/offers', {
    ...datosFormulario,
    data_creacio: new Date().toISOString().slice(0, 10),
  })
    .then(() => {
      cerrarModalCrear()
      cargarOfertas()
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        const mensajeApi = error.response?.data?.message
        const erroresValidacion = error.response?.data?.errors
        const primerErrorValidacion = erroresValidacion
          ? Object.values(erroresValidacion)[0]?.[0]
          : ''

        errorEnvio.value = primerErrorValidacion || mensajeApi || 'Revisa los datos del formulario de oferta.'
        return
      }

      errorEnvio.value = error.response?.data?.message || 'No se pudo crear la oferta.'
    })
}

// Elimina la oferta seleccionada y actualiza la tabla.
const confirmarEliminarOferta = () => {
  if (!ofertaAEliminar.value?.id) {
    return
  }

  errorEnvio.value = ''

  window.axios.delete(`/api/offers/${ofertaAEliminar.value.id}`)
    .then(() => {
      cerrarModalEliminar()
      cargarOfertas()
    })
    .catch(() => {
      errorEnvio.value = 'No se pudo eliminar la oferta.'
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
