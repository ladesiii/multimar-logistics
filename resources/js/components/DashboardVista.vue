<!--
Componente: DashboardVista
Descripción: Vista principal del dashboard que muestra un resumen de las ofertas y el estado del tracking.
-->
<template>
  <!-- Sección principal del dashboard -->
  <section class="dashboard-overview">
    <!-- Cabecera con título y descripción -->
    <header class="overview-header">
      <h1>Dashboard</h1>
      <p>Resumen de tu logistica.</p>
    </header>

    <!-- Muestra un mensaje de error si existe -->
    <p v-if="mensajeError" class="state-message error">{{ mensajeError }}</p>

    <!-- Contenedor de las tarjetas de resumen, se muestra si no está cargando y no hay errores -->
    <div v-if="!estaCargando && !mensajeError" class="overview-cards">
      <!-- Tarjeta para el total de ofertas -->
      <article class="overview-card total">
        <div class="card-top">
          <h2>Total de ofertas</h2>
          <!-- Icono representativo -->
          <svg viewBox="0 0 24 24" class="card-icon" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 12h8M8 15h5" />
          </svg>
        </div>
        <p class="overview-value">{{ estadisticas.total }}</p>
      </article>

      <!-- Tarjeta para las ofertas pendientes -->
      <article class="overview-card pending">
        <div class="card-top">
          <h2>Ofertas pendientes</h2>
          <!-- Icono representativo -->
          <svg viewBox="0 0 24 24" class="card-icon" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
          </svg>
        </div>
        <p class="overview-value">{{ estadisticas.pending }}</p>
      </article>

      <!-- Tarjeta para las ofertas aceptadas -->
      <article class="overview-card accepted">
        <div class="card-top">
          <h2>Ofertas aceptadas</h2>
          <!-- Icono representativo -->
          <svg viewBox="0 0 24 24" class="card-icon" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9" />
          </svg>
        </div>
        <p class="overview-value">{{ estadisticas.accepted }}</p>
      </article>

      <!-- Tarjeta para las ofertas rechazadas -->
      <article class="overview-card rejected">
        <div class="card-top">
          <h2>Ofertas rechazadas</h2>
          <!-- Icono representativo -->
          <svg viewBox="0 0 24 24" class="card-icon" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 6 6m0-6-6 6" />
          </svg>
        </div>
        <p class="overview-value">{{ estadisticas.rejected }}</p>
      </article>
    </div>

    <!-- Sección para el listado de tracking -->
    <section class="tracking-section">
      <ListadoTracking />
    </section>
  </section>
</template>

<script setup>
// Importaciones de Vue y componentes
import { onMounted, reactive, ref } from 'vue'
import ListadoTracking from './ListadoTracking.vue'

// --- ESTADO DEL COMPONENTE ---

// Ref para controlar el estado de carga
const estaCargando = ref(true)
// Ref para almacenar mensajes de error
const mensajeError = ref('')
// Objeto reactivo para almacenar las estadísticas de las ofertas
const estadisticas = reactive({
  total: 0,
  pending: 0,
  accepted: 0,
  rejected: 0,
})

// --- MÉTODOS ---

/**
 * Cuenta el número de ofertas que coinciden con un estado específico.
 * @param {Array} ofertas - El array de ofertas a filtrar.
 * @param {number} idEstado - El ID del estado de la oferta a contar.
 * @returns {number} El número de ofertas en el estado especificado.
 */
const contarOfertasPorEstado = (ofertas, idEstado) => {
  return ofertas.filter((oferta) => Number(oferta?.estat_oferta_id) === idEstado).length
}

/**
 * Carga las estadísticas de las ofertas desde la API.
 * Realiza una petición GET a /api/offers y actualiza el estado del componente.
 */
const cargarEstadisticasDashboard = () => {
  estaCargando.value = true
  mensajeError.value = ''

  window.axios.get('/api/offers')
    .then(({ data }) => {
      // Asegura que 'data.offers' es un array antes de procesarlo
      const ofertas = Array.isArray(data?.offers) ? data.offers : []

      // Actualiza las estadísticas con los datos recibidos
      estadisticas.total = ofertas.length
      estadisticas.pending = contarOfertasPorEstado(ofertas, 1) // ID 1 para 'Pendiente'
      estadisticas.accepted = contarOfertasPorEstado(ofertas, 2) // ID 2 para 'Aceptada'
      estadisticas.rejected = contarOfertasPorEstado(ofertas, 3) // ID 3 para 'Rechazada'
    })
    .catch(() => {
      // Manejo de errores en la petición
      mensajeError.value = 'No se pudo cargar el resumen de ofertas.'
    })
    .finally(() => {
      // Se ejecuta siempre al finalizar la petición
      estaCargando.value = false
    })
}

// --- HOOKS DEL CICLO DE VIDA ---

// Hook que se ejecuta cuando el componente se ha montado en el DOM
onMounted(() => {
  cargarEstadisticasDashboard()
})
</script>

<style scoped>
/* Estilos específicos para el componente DashboardVista */
.dashboard-overview {
  background: #ffffff;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 6px 18px rgba(0, 30, 51, 0.08);
}

.overview-header h1 {
  margin: 0;
  color: #002855;
  font-size: 1.2rem;
  font-weight: 800;
}

.overview-header p {
  margin: 0.35rem 0 0;
  color: #31516b;
  font-size: 0.92rem;
  font-weight: 600;
}

.state-message {
  margin: 1rem 0 0;
  color: #31516b;
  font-size: 0.95rem;
  font-weight: 700;
}

.state-message.error {
  color: #b42318;
}

.overview-cards {
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.85rem;
}

.overview-card {
  border: 1px solid #d8e5f0;
  border-radius: 12px;
  padding: 0.95rem;
  background: #f9fcff;
}

.overview-card.pending {
  background: #fff7e6;
  border-color: #f5a524;
}

.overview-card.accepted {
  background: #ecfdf3;
  border-color: #22c55e;
}

.overview-card.rejected {
  background: #fef2f2;
  border-color: #ef4444;
}

.overview-card.total {
  background: #eef5ff;
  border-color: #3b82f6;
}

.overview-card h2 {
  margin: 0;
  color: #003b6f;
  font-size: 0.96rem;
  font-weight: 800;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.card-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
}

.overview-card.pending .card-icon {
  color: #b45309;
}

.overview-card.accepted .card-icon {
  color: #166534;
}

.overview-card.rejected .card-icon {
  color: #991b1b;
}

.overview-card.total .card-icon {
  color: #1d4ed8;
}

.overview-value {
  margin: 0.45rem 0 0;
  color: #0a2540;
  font-size: 2rem;
  line-height: 1;
  font-weight: 900;
}

@media (max-width: 900px) {
  .overview-cards {
    grid-template-columns: 1fr;
  }
}

.tracking-section {
  margin-top: 1.2rem;
}
</style>
