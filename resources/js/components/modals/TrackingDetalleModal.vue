
<template>
  <div v-if="isOpen" class="tracking-modal-backdrop" @click.self="$emit('close')">
    <div class="tracking-modal" role="dialog" aria-modal="true" aria-label="Detalle de tracking">

      <header class="tracking-modal-header">
        <div>
          <h2>Tracking — Oferta #{{ oferta?.id }}</h2>
          <p class="tracking-modal-subtitle">Estado actual del envío</p>
        </div>
        <button type="button" class="modal-close-btn" aria-label="Cerrar" @click="$emit('close')">X</button>
      </header>

      <p v-if="isLoading" class="tracking-status-msg">Cargando pasos de tracking...</p>
      <p v-else-if="errorMessage" class="tracking-status-msg error">{{ errorMessage }}</p>

      <div v-else class="tracking-modal-body">

        <ol class="tracking-stepper">
          <li
            v-for="step in steps"
            :key="step.id"
            class="tracking-step"
            :class="{
              'step-done': isStepDone(step),
              'step-current': isStepCurrent(step),
            }"
          >
            <span class="step-dot">
              <svg v-if="isStepDone(step)" viewBox="0 0 16 16" class="step-check-icon" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="2.5,8.5 6,12 13.5,4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span v-else-if="isStepCurrent(step)" class="step-dot-inner active"></span>
              <span v-else class="step-dot-inner"></span>
            </span>
            <span class="step-label">{{ step.nom }}</span>
          </li>
        </ol>

        <div v-if="isAdmin" class="tracking-edit-panel">
          <h3 class="edit-panel-title">Cambiar paso de tracking</h3>
          <div class="edit-panel-row">
            <select v-model="selectedStepId" class="step-select" :disabled="isSaving">
              <option v-for="step in steps" :key="step.id" :value="step.id">
                {{ step.nom }}
              </option>
            </select>
            <button
              type="button"
              class="save-step-btn"
              :disabled="isSaving || selectedStepId === oferta?.tracking_step_id"
              @click="$emit('save', selectedStepId)"
            >
              {{ isSaving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
          <p v-if="saveError" class="save-error">{{ saveError }}</p>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isOpen: Boolean,
  oferta: Object,
  steps: { type: Array, default: () => [] },
  isLoading: Boolean,
  errorMessage: String,
  isAdmin: Boolean,
  isSaving: Boolean,
  saveError: String,
})

defineEmits(['close', 'save'])

const selectedStepId = ref(null)

watch(
  () => props.oferta?.tracking_step_id,  // vigila el paso actual de la oferta
  (val) => { selectedStepId.value = val ?? null },  // cuando cambia, actualiza selectedStepId
  { immediate: true },  // también lo ejecuta nada más montar el componente
)

// Devuelve el número de orden del paso actual de la oferta
const currentStepOrder = () => {
  const current = props.steps.find(s => s.id === props.oferta?.tracking_step_id)
  return current ? Number(current.ordre) : 0
}

// Devuelve true si el paso ya fue superado (su orden es menor al actual)
const isStepDone = (step) => Number(step.ordre) < currentStepOrder()

// Devuelve true si este paso es el que tiene asignado la oferta ahora mismo
const isStepCurrent = (step) => step.id === props.oferta?.tracking_step_id
</script>

<style scoped>
.tracking-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 21, 41, 0.35);
  display: grid;
  place-items: center;
  padding: 1rem;
  z-index: 30;
}

.tracking-modal {
  width: min(520px, 100%);
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 14px 32px rgba(0, 23, 41, 0.2);
  border: 1px solid #dbe9f5;
  padding: 1.25rem;
  max-height: 88vh;
  overflow: auto;
}

.tracking-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.8rem;
  margin-bottom: 1.2rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #dbe9f5;
}

.tracking-modal-header h2 {
  margin: 0;
  color: #002855;
  font-size: 1.1rem;
  font-weight: 800;
}

.tracking-modal-subtitle {
  margin: 0.15rem 0 0;
  color: #4a6784;
  font-size: 0.82rem;
  font-weight: 600;
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
  flex-shrink: 0;
}

.tracking-status-msg {
  margin: 0;
  color: #0a2540;
  font-size: 0.9rem;
  font-weight: 600;
}

.tracking-status-msg.error {
  color: #b42318;
}

.tracking-modal-body {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.tracking-stepper {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.tracking-step {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  position: relative;
  padding-bottom: 1rem;
}

.tracking-step:last-child {
  padding-bottom: 0;
}

.tracking-step::before {
  content: '';
  position: absolute;
  left: 11px;
  top: 24px;
  bottom: 0;
  width: 2px;
  background: #e2eaf2;
}

.tracking-step:last-child::before {
  display: none;
}

.step-done::before {
  background: #22c55e;
}

.step-current::before {
  background: linear-gradient(to bottom, #0d4a7b 60%, #e2eaf2);
}

.step-dot {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #d1dfeb;
  background: #f8fbfe;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
}

.step-done .step-dot {
  border-color: #22c55e;
  background: #ecfdf3;
  color: #166534;
}

.step-current .step-dot {
  border-color: #0d4a7b;
  background: #0d4a7b;
}

.step-check-icon {
  width: 12px;
  height: 12px;
}

.step-dot-inner {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #c5d7e8;
}

.step-dot-inner.active {
  background: #ffffff;
}

.step-label {
  padding-top: 0.2rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #0a2540;
  line-height: 1.3;
}

.step-done .step-label {
  color: #166534;
}

.step-current .step-label {
  color: #0d4a7b;
  font-weight: 800;
}

.tracking-edit-panel {
  border-top: 1px solid #dbe9f5;
  padding-top: 1rem;
}

.edit-panel-title {
  margin: 0 0 0.6rem;
  font-size: 0.92rem;
  font-weight: 800;
  color: #002855;
}

.edit-panel-row {
  display: flex;
  gap: 0.6rem;
  align-items: center;
}

.step-select {
  flex: 1;
  border: 1px solid #c5d7e8;
  border-radius: 8px;
  padding: 0.5rem 0.65rem;
  font-size: 0.88rem;
  font-weight: 600;
  color: #0a2540;
  background: #f8fbfe;
  cursor: pointer;
}

.step-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.save-step-btn {
  border: none;
  border-radius: 9px;
  background: #09253b;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 700;
  padding: 0.5rem 1rem;
  cursor: pointer;
  white-space: nowrap;
}

.save-step-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.save-error {
  margin: 0.5rem 0 0;
  color: #b42318;
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
