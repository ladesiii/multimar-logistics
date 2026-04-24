
<template>
  
  <div v-if="isOpen" class="offer-modal-backdrop" @click.self="close">
    <div class="offer-modal" role="dialog" aria-modal="true" aria-label="Detalle de oferta">
      
      <header class="offer-modal-header">
        <div class="offer-header-main">
          <h2>
            Oferta
            <template v-if="offer">#{{ offer.id }}</template>
          </h2>
          <p class="offer-header-subtitle">Resumen completo de la propuesta logística</p>
        </div>

        <div class="offer-header-actions">
          <span v-if="offer" class="status-badge" :class="getOfferStatusClass(offer)">
            {{ getOfferStatusLabel(offer) }}
          </span>
          <button type="button" class="modal-close-btn" aria-label="Cerrar detalle" @click="close">X</button>
        </div>
      </header>

      
      <p v-if="isLoading" class="modal-status">Cargando detalle de la oferta...</p>
      <p v-else-if="errorMessage" class="modal-status error">{{ errorMessage }}</p>

      
      <div v-else-if="offer" class="offer-content">
        
        <section class="offer-highlights" aria-label="Resumen de la oferta">
          <article class="highlight-card">
            <span class="highlight-label">Cliente</span>
            <strong class="highlight-value">{{ offer.client || '-' }}</strong>
          </article>
          <article class="highlight-card">
            <span class="highlight-label">Transporte</span>
            <strong class="highlight-value">{{ offer.tipus_transport || '-' }}</strong>
          </article>
          <article class="highlight-card">
            <span class="highlight-label">Incoterm</span>
            <strong class="highlight-value">{{ offer.tipus_incoterm || '-' }}</strong>
          </article>
          <article class="highlight-card">
            <span class="highlight-label">Precio</span>
            <strong class="highlight-value">{{ offer.preu ?? '-' }}</strong>
          </article>
        </section>

        
        <div class="offer-columns">
        <div class="offer-column">
          <section class="offer-section-block">
            <h3 class="offer-section-title">1) Información principal</h3>
            <dl class="offer-details">
              <div class="offer-detail-row">
                <dt>Tipo de transporte</dt>
                <dd>{{ offer.tipus_transport || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Flujo</dt>
                <dd>{{ offer.tipus_fluxe || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Tipo de carga</dt>
                <dd>{{ offer.tipus_carrega || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Incoterm</dt>
                <dd>{{ offer.tipus_incoterm || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Tipo de validación</dt>
                <dd>{{ offer.tipus_validacio || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Estado de la oferta</dt>
                <dd>
                  <span class="status-badge" :class="getOfferStatusClass(offer)">
                    {{ getOfferStatusLabel(offer) }}
                  </span>
                </dd>
              </div>
            </dl>
          </section>

          <section class="offer-section-block">
            <h3 class="offer-section-title">3) Logística</h3>
            <dl class="offer-details">
              <div class="offer-detail-row">
                <dt>Transportista</dt>
                <dd>{{ offer.transportista || '-' }}</dd>
              </div>
              <div v-if="isMaritimeTransport" class="offer-detail-row">
                <dt>Línea transporte marítimo</dt>
                <dd>{{ offer.linia_transport_maritim || '-' }}</dd>
              </div>
              <div v-if="isMaritimeTransport" class="offer-detail-row">
                <dt>Puerto origen</dt>
                <dd>{{ offer.port_origen || '-' }}</dd>
              </div>
              <div v-if="isMaritimeTransport" class="offer-detail-row">
                <dt>Puerto destino</dt>
                <dd>{{ offer.port_desti || '-' }}</dd>
              </div>
              <div v-if="isAirTransport" class="offer-detail-row">
                <dt>Aeropuerto origen</dt>
                <dd>{{ offer.aeroport_origen || '-' }}</dd>
              </div>
              <div v-if="isAirTransport" class="offer-detail-row">
                <dt>Aeropuerto destino</dt>
                <dd>{{ offer.aeroport_desti || '-' }}</dd>
              </div>
              <div v-if="showContainerType" class="offer-detail-row">
                <dt>Tipo de contenedor</dt>
                <dd>{{ offer.tipus_contenidor || '-' }}</dd>
              </div>
            </dl>
          </section>
        </div>

        <div class="offer-column">
          <section class="offer-section-block">
            <h3 class="offer-section-title">2) Cliente</h3>
            <dl class="offer-details">
              <div class="offer-detail-row">
                <dt>Cliente</dt>
                <dd>{{ offer.client || '-' }}</dd>
              </div>
            </dl>
          </section>

          <section class="offer-section-block">
            <h3 class="offer-section-title">4) Detalles de la oferta</h3>
            <dl class="offer-details">
              <div class="offer-detail-row">
                <dt>Peso bruto</dt>
                <dd>{{ offer.pes_brut ?? '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Volumen</dt>
                <dd>{{ offer.volum ?? '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Precio</dt>
                <dd>{{ offer.preu ?? '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Comentarios</dt>
                <dd>{{ offer.comentaris || '-' }}</dd>
              </div>
              <div v-if="isRejectedOfferById" class="offer-detail-row">
                <dt>Razón de rechazo</dt>
                <dd>{{ offer.rao_rebuig || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Fecha creación</dt>
                <dd>{{ offer.data_creacio || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Fecha validez inicio</dt>
                <dd>{{ offer.data_validessa_inicial || '-' }}</dd>
              </div>
              <div class="offer-detail-row">
                <dt>Fecha validez fin</dt>
                <dd>{{ offer.data_validessa_final || '-' }}</dd>
              </div>
            </dl>
          </section>

          
          <div class="offer-state-panel">
            <div v-if="isPendingOffer && canManageStatus" class="offer-action-buttons">
              <button
                type="button"
                class="decision-btn accept-offer-btn"
                :disabled="isStatusUpdating"
                @click="$emit('accept')"
              >
                Aceptar oferta
              </button>
              <button
                type="button"
                class="decision-btn reject-offer-btn"
                :disabled="isStatusUpdating"
                @click="$emit('reject')"
              >
                Rechazar oferta
              </button>
            </div>

            <div v-else-if="isPendingOffer && currentRole === 'operador'" class="offer-resolution-message pending" role="status">
              <strong>Oferta pendiente de tramitacion.</strong>
              <span>Todavia no se ha tramitado la oferta.</span>
            </div>

            <div v-else-if="isAcceptedOffer" class="offer-resolution-message accepted" role="status">
              <strong>Oferta aceptada.</strong>
              <span>Esta oferta ya ha sido validada y no requiere más acciones.</span>
            </div>
            <div v-else-if="isRejectedOfferById" class="offer-resolution-message rejected" role="status">
              <strong>Oferta rechazada.</strong>
              <span>Esta oferta fue rechazada y queda cerrada para nuevas acciones.</span>
            </div>

            
            <p v-if="statusActionError" class="status-action-error">{{ statusActionError }}</p>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isOpen: Boolean,
  offer: Object,
  isLoading: Boolean,
  errorMessage: String,
  isStatusUpdating: Boolean,
  statusActionError: String,
  canManageStatus: Boolean,
  currentRole: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'accept', 'reject'])

const OFFER_STATUS = {
  pending: 1,
  accepted: 2,
  rejected: 3,
}

const normalizeText = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()

const isMaritimeTransport = computed(() => normalizeText(props.offer?.tipus_transport).includes('maritim'))
const isAirTransport = computed(() => {
  const transport = normalizeText(props.offer?.tipus_transport)
  return transport.includes('aeri') || transport.includes('aereo')
})
const showContainerType = computed(() => {
  const loadType = normalizeText(props.offer?.tipus_carrega)
  return loadType.includes('contenidor') || Boolean(props.offer?.tipus_contenidor_id)
})
const isPendingOffer = computed(() => Number(props.offer?.estat_oferta_id) === OFFER_STATUS.pending)
const isAcceptedOffer = computed(() => Number(props.offer?.estat_oferta_id) === OFFER_STATUS.accepted)
const isRejectedOfferById = computed(() => Number(props.offer?.estat_oferta_id) === OFFER_STATUS.rejected)

const close = () => {
  emit('close')
}

const getOfferStatusLabel = (offer) => {
  const statusId = Number(offer?.estat_oferta_id)

  if (statusId === OFFER_STATUS.pending) {
    return 'Pendiente'
  }

  if (statusId === OFFER_STATUS.accepted) {
    return 'Aceptada'
  }

  if (statusId === OFFER_STATUS.rejected) {
    return 'Rechazada'
  }

  return offer?.estat || '-'
}

const getOfferStatusClass = (offer) => {
  const statusId = Number(offer?.estat_oferta_id)

  if (statusId === OFFER_STATUS.pending) {
    return 'status-pending'
  }

  if (statusId === OFFER_STATUS.accepted) {
    return 'status-accepted'
  }

  if (statusId === OFFER_STATUS.rejected) {
    return 'status-rejected'
  }

  return ''
}
</script>

<style scoped>
.offer-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 21, 41, 0.35);
  display: grid;
  place-items: center;
  padding: 1rem;
  z-index: 30;
}

.offer-modal {
  width: min(980px, 100%);
  background: linear-gradient(180deg, #f5fbff 0%, #ffffff 42%);
  border-radius: 14px;
  box-shadow: 0 14px 32px rgba(0, 23, 41, 0.2);
  border: 1px solid #dbe9f5;
  padding: 1rem;
  max-height: 88vh;
  overflow: auto;
}

.offer-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  margin-bottom: 1rem;
  padding: 0.25rem 0.1rem 0.5rem;
  border-bottom: 1px solid #dbe9f5;
}

.offer-header-main {
  display: grid;
  gap: 0.15rem;
}

.offer-modal-header h2 {
  margin: 0;
  color: #002855;
  font-size: 1.18rem;
  font-weight: 800;
}

.offer-header-subtitle {
  margin: 0;
  color: #4a6784;
  font-size: 0.82rem;
  font-weight: 600;
}

.offer-header-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close-btn {
  border: 1px solid #d1dfeb;
  border-radius: 8px;
  background: #f8fbfe;
  color: #0d4a7b;
  width: 32px;
  height: 32px;
  font-weight: 700;
  cursor: pointer;
}

.offer-details {
  margin: 0;
}

.offer-content {
  display: grid;
  gap: 0.9rem;
}

.offer-highlights {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.6rem;
}

.highlight-card {
  border: 1px solid #dbe9f5;
  background: #ffffff;
  border-radius: 10px;
  padding: 0.6rem 0.7rem;
  display: grid;
  gap: 0.15rem;
  box-shadow: 0 4px 12px rgba(0, 40, 85, 0.05);
}

.highlight-label {
  color: #4c6d8e;
  font-size: 0.74rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.highlight-value {
  color: #082b4d;
  font-size: 0.92rem;
  font-weight: 800;
}

.offer-columns {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.85rem;
  align-items: start;
}

.offer-column {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.offer-section-block {
  border: 1px solid #dbe9f5;
  border-radius: 10px;
  padding: 0.75rem 0.8rem;
  background: #ffffff;
}

.offer-section-title {
  margin: 0 0 0.45rem;
  color: #002855;
  font-size: 0.98rem;
  font-weight: 800;
}

.offer-detail-row {
  display: grid;
  grid-template-columns: 160px 1fr;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0;
  border-bottom: 1px solid #e6edf3;
}

.offer-detail-row:last-child {
  border-bottom: none;
}

.offer-detail-row dt {
  margin: 0;
  color: #003b6f;
  font-weight: 700;
}

.offer-detail-row dd {
  margin: 0;
  display: flex;
  align-items: center;
  color: #0a2540;
  word-break: break-word;
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

.modal-status {
  margin: 0;
  color: #0a2540;
  font-size: 0.9rem;
  font-weight: 600;
}

.modal-status.error {
  color: #b42318;
}

.offer-state-panel {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  gap: 0.5rem;
  padding: 0;
}

.offer-action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  align-items: stretch;
  width: 100%;
}

.decision-btn {
  border: none;
  border-radius: 9px;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 700;
  padding: 0.52rem 0.75rem;
  width: 100%;
  cursor: pointer;
}

.decision-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.accept-offer-btn {
  background: #15803d;
}

.reject-offer-btn {
  background: #b42318;
}

.offer-resolution-message {
  margin: 0;
  display: grid;
  gap: 0.2rem;
  font-size: 0.9rem;
  line-height: 1.25;
  padding: 0.6rem 0.7rem;
  border-radius: 8px;
  border: 1px solid transparent;
  width: 100%;
  box-sizing: border-box;
}

.offer-resolution-message strong {
  font-size: 0.92rem;
  font-weight: 800;
}

.offer-resolution-message span {
  font-size: 0.86rem;
  font-weight: 600;
}

.offer-resolution-message.accepted {
  background: #ecfdf3;
  border-color: #22c55e;
  color: #166534;
}

.offer-resolution-message.rejected {
  background: #fef2f2;
  border-color: #ef4444;
  color: #991b1b;
}

.offer-resolution-message.pending {
  background: #fff7e6;
  border-color: #f5a524;
  color: #b45309;
}

.status-action-error {
  margin: 0;
  color: #b42318;
  font-size: 0.85rem;
  font-weight: 600;
}

@media (max-width: 900px) {
  .offer-highlights {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .offer-columns {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .offer-modal {
    padding: 0.8rem;
  }

  .offer-modal-header {
    align-items: flex-start;
  }

  .offer-header-actions {
    align-self: flex-start;
  }

  .offer-highlights {
    grid-template-columns: 1fr;
  }

  .offer-detail-row {
    grid-template-columns: 1fr;
    gap: 0.2rem;
    padding: 0.5rem 0;
  }
}
</style>

