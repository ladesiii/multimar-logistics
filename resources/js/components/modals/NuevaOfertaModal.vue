
<template>
  
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>NUEVA OFERTA</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      
      <p v-if="isLoading" class="form-status">Cargando opciones del formulario...</p>
      <p v-else-if="errorMessage" class="form-status error">{{ errorMessage }}</p>

      
      <form v-else class="modal-form" @submit.prevent="enviarFormulario">
        
        <section class="form-section">
          <h3>1) Informacion principal</h3>
          <div class="fields-grid">
            <div class="field">
              <label for="tipus-transport">Tipo de transporte</label>
              <select id="tipus-transport" v-model="form.tipus_transport_id" required>
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_transports || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div class="field">
              <label for="tipus-fluxe">Flujo</label>
              <select id="tipus-fluxe" v-model="form.tipus_fluxe_id" required>
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_fluxes || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div class="field">
              <label for="tipus-carrega">Tipo de carga</label>
              <select id="tipus-carrega" v-model="form.tipus_carrega_id" required>
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_carrega || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div class="field">
              <label for="tipus-incoterm">Incoterm</label>
              <select id="tipus-incoterm" v-model="form.tipus_incoterm_id" required>
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_incoterms || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div class="field">
              <label for="tipus-validacio">Tipo de validacion</label>
              <select id="tipus-validacio" v-model="form.tipus_validacio_id" required>
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_validacions || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div class="field">
              <label for="estat-oferta">Estado de la oferta</label>
              <select id="estat-oferta" v-model="form.estat_oferta_id" required disabled>
                <option v-for="item in options.estats_ofertes || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
              <small class="field-help">Se asigna automaticamente como Pendiente al crear.</small>
            </div>
          </div>
        </section>

        
        <section class="form-section">
          <h3>2) Cliente</h3>
          <div class="fields-grid one-col">
            <div class="field">
              <label for="client">Cliente</label>
              <select id="client" v-model="form.client_id" required>
                <option value="">Selecciona un cliente</option>
                <option v-for="item in options.clients || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>
          </div>
        </section>

        
        <section class="form-section">
          <h3>3) Logistica</h3>
          <div class="fields-grid">
            <div class="field">
              <label for="transportista">Transportista (opcional)</label>
              <select id="transportista" v-model="form.transportista_id">
                <option value="">Sin transportista</option>
                <option v-for="item in options.transportistes || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="isMaritimeTransport" class="field">
              <label for="linia-maritim">Linea transporte maritimo</label>
              <select id="linia-maritim" v-model="form.linia_transport_maritim_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.linies_transport_maritim || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="isMaritimeTransport" class="field">
              <label for="port-origen">Puerto origen</label>
              <select id="port-origen" v-model="form.port_origen_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.ports || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="isMaritimeTransport" class="field">
              <label for="port-desti">Puerto destino</label>
              <select id="port-desti" v-model="form.port_desti_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.ports || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="isAirTransport" class="field">
              <label for="aeroport-origen">Aeropuerto origen</label>
              <select id="aeroport-origen" v-model="form.aeroport_origen_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.aeroports || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="isAirTransport" class="field">
              <label for="aeroport-desti">Aeropuerto destino</label>
              <select id="aeroport-desti" v-model="form.aeroport_desti_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.aeroports || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>

            <div v-if="showContainerType" class="field">
              <label for="tipus-contenidor">Tipo de contenedor</label>
              <select id="tipus-contenidor" v-model="form.tipus_contenidor_id">
                <option value="">Selecciona una opcion</option>
                <option v-for="item in options.tipus_contenidors || []" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
              </select>
            </div>
          </div>
        </section>

        
        <section class="form-section">
          <h3>4) Detalles de la oferta</h3>
          <div class="fields-grid">
            <div class="field">
              <label for="pes-brut">Peso bruto</label>
              <input id="pes-brut" v-model="form.pes_brut" type="number" step="0.01" min="0">
            </div>

            <div class="field">
              <label for="volum">Volumen</label>
              <input id="volum" v-model="form.volum" type="number" step="0.01" min="0">
            </div>

            <div class="field">
              <label for="preu">Precio</label>
              <input id="preu" v-model="form.preu" type="number" step="1" min="0">
            </div>

            <div class="field full-width">
              <label for="comentaris">Comentarios</label>
              <textarea id="comentaris" v-model="form.comentaris" rows="3"></textarea>
            </div>

            <div v-if="isRejectedStatus" class="field full-width">
              <label for="rao-rebuig">Razon de rechazo</label>
              <textarea id="rao-rebuig" v-model="form.rao_rebuig" rows="2"></textarea>
            </div>

            <div class="field">
              <label for="data-inici">Fecha validez inicio</label>
              <input id="data-inici" v-model="form.data_validessa_inicial" type="date">
            </div>

            <div class="field">
              <label for="data-fi">Fecha validez fin</label>
              <input id="data-fi" v-model="form.data_validessa_final" type="date">
            </div>
          </div>
        </section>

        
        <div class="actions-row">
          <button type="button" class="cancel-btn" @click="$emit('close')">Cancelar</button>
          <button type="submit" class="submit-btn">Crear oferta</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  options: {
    type: Object,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: ''
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  tipus_transport_id: '',
  tipus_fluxe_id: '',
  tipus_carrega_id: '',
  tipus_incoterm_id: '',
  tipus_validacio_id: '',
  estat_oferta_id: '',
  client_id: '',
  transportista_id: '',
  linia_transport_maritim_id: '',
  port_origen_id: '',
  port_desti_id: '',
  aeroport_origen_id: '',
  aeroport_desti_id: '',
  tipus_contenidor_id: '',
  pes_brut: '',
  volum: '',
  preu: '',
  comentaris: '',
  rao_rebuig: '',
  data_validessa_inicial: '',
  data_validessa_final: '',
})

const normalizeText = (value) => String(value || '')
  .toLowerCase()
  .replace(/[àá]/g, 'a')
  .replace(/[èé]/g, 'e')
  .replace(/[ìí]/g, 'i')
  .replace(/[òó]/g, 'o')
  .replace(/[ùúü]/g, 'u')
  .replace(/ñ/g, 'n')

const obtenerEtiquetaSeleccionada = (lista, id) => {
  const elementoSeleccionado = (lista || []).find((item) => String(item.id) === String(id))
  return elementoSeleccionado?.label || ''
}

const isMaritimeTransport = computed(() => {
  const etiqueta = normalizeText(obtenerEtiquetaSeleccionada(props.options.tipus_transports, form.tipus_transport_id))
  return etiqueta.includes('maritim')
})

const isAirTransport = computed(() => {
  const etiqueta = normalizeText(obtenerEtiquetaSeleccionada(props.options.tipus_transports, form.tipus_transport_id))
  return etiqueta.includes('aeri') || etiqueta.includes('aereo')
})

const showContainerType = computed(() => {
  const etiqueta = normalizeText(obtenerEtiquetaSeleccionada(props.options.tipus_carrega, form.tipus_carrega_id))
  return etiqueta.includes('contenidor')
})

const isRejectedStatus = computed(() => {
  const rejectedId = props.options?.status_defaults?.rejected_id
  return rejectedId && Number(form.estat_oferta_id) === Number(rejectedId)
})

watch(
  () => props.options,
  (options) => {
    const pendingId = options?.status_defaults?.pending_id
    const firstStatusId = options?.estats_ofertes?.[0]?.id

    if (pendingId && !form.estat_oferta_id) {
      form.estat_oferta_id = String(pendingId)
      return
    }

    if (!form.estat_oferta_id && firstStatusId) {
      form.estat_oferta_id = String(firstStatusId)
    }
  },
  { immediate: true }
)

watch(isMaritimeTransport, (active) => {
  if (active) {
    form.aeroport_origen_id = ''
    form.aeroport_desti_id = ''
  }
})

watch(isAirTransport, (active) => {
  if (active) {
    form.linia_transport_maritim_id = ''
    form.port_origen_id = ''
    form.port_desti_id = ''
  }
})

watch(showContainerType, (active) => {
  if (!active) {
    form.tipus_contenidor_id = ''
  }
})

watch(isRejectedStatus, (active) => {
  if (!active) {
    form.rao_rebuig = ''
  }
})

const convertirANumeroONulo = (value, permitirDecimales = false) => {
  if (value === '' || value === null || value === undefined) {
    return null
  }

  const numeroParseado = permitirDecimales ? Number.parseFloat(value) : Number.parseInt(value, 10)
  return Number.isNaN(numeroParseado) ? null : numeroParseado
}

const enviarFormulario = () => {
  const datosOferta = {
    tipus_transport_id: convertirANumeroONulo(form.tipus_transport_id),
    tipus_fluxe_id: convertirANumeroONulo(form.tipus_fluxe_id),
    tipus_carrega_id: convertirANumeroONulo(form.tipus_carrega_id),
    tipus_incoterm_id: convertirANumeroONulo(form.tipus_incoterm_id),
    tipus_validacio_id: convertirANumeroONulo(form.tipus_validacio_id),
    estat_oferta_id: convertirANumeroONulo(form.estat_oferta_id),
    client_id: convertirANumeroONulo(form.client_id),
    transportista_id: convertirANumeroONulo(form.transportista_id),
    linia_transport_maritim_id: convertirANumeroONulo(form.linia_transport_maritim_id),
    port_origen_id: convertirANumeroONulo(form.port_origen_id),
    port_desti_id: convertirANumeroONulo(form.port_desti_id),
    aeroport_origen_id: convertirANumeroONulo(form.aeroport_origen_id),
    aeroport_desti_id: convertirANumeroONulo(form.aeroport_desti_id),
    tipus_contenidor_id: convertirANumeroONulo(form.tipus_contenidor_id),
    pes_brut: convertirANumeroONulo(form.pes_brut, true),
    volum: convertirANumeroONulo(form.volum, true),
    preu: convertirANumeroONulo(form.preu),
    comentaris: form.comentaris?.trim() || null,
    rao_rebuig: form.rao_rebuig?.trim() || null,
    data_validessa_inicial: form.data_validessa_inicial || null,
    data_validessa_final: form.data_validessa_final || null,
  }

  emit('submit', datosOferta)
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 30, 51, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 60;
}

.modal-card {
  width: min(1100px, 100%);
  max-height: 92vh;
  overflow: auto;
  border-radius: 12px;
  background: #eaf3f8;
  border: 1px solid #89c4f5;
  padding: 0.9rem;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25);
}

.modal-header {
  background: #09253b;
  color: #ffffff;
  border-radius: 7px;
  padding: 0.45rem 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.8rem;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.close-btn {
  border: none;
  background: transparent;
  color: #ffffff;
  font-size: 1.4rem;
  font-weight: 800;
  cursor: pointer;
  line-height: 1;
}

.form-status {
  margin: 0.8rem 0;
  font-weight: 700;
  color: #0a2134;
}

.form-status.error {
  color: #b42318;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.form-section {
  border: 1px solid #c7dceb;
  border-radius: 10px;
  padding: 0.8rem;
  background: #f5f9fc;
}

.form-section h3 {
  margin: 0 0 0.6rem;
  color: #0a2134;
  font-size: 1rem;
  font-weight: 800;
}

.fields-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}

.fields-grid.one-col {
  grid-template-columns: 1fr;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field.full-width {
  grid-column: 1 / -1;
}

.field label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0a2134;
}

.field-help {
  color: #36566f;
  font-size: 0.78rem;
  font-weight: 600;
}

.field input,
.field select,
.field textarea {
  border: 1px solid #89c4f5;
  background: #ffffff;
  border-radius: 6px;
  min-height: 38px;
  padding: 0 0.6rem;
  font-family: inherit;
}

.field textarea {
  padding: 0.55rem 0.6rem;
}

.actions-row {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 0.25rem;
}

.cancel-btn,
.submit-btn {
  border: none;
  border-radius: 8px;
  height: 40px;
  padding: 0 1rem;
  font-size: 0.95rem;
  font-weight: 800;
  cursor: pointer;
}

.cancel-btn {
  background: #d3dde6;
  color: #0a2134;
}

.submit-btn {
  background: #09253b;
  color: #ffffff;
}

@media (max-width: 900px) {
  .fields-grid {
    grid-template-columns: 1fr;
  }
}
</style>

