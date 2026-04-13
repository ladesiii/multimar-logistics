<template>
  <div class="tracking-app">
    <!-- Header -->
    <header class="op-header">
      <div class="header-inner">
        <div class="op-badge">OP</div>
        <div class="header-text">
          <p class="header-label">Seguimiento de Operación</p>
          <h1 class="header-title">#OP-2020-1 <span class="route-arrow">Shanghai → Barcelona</span></h1>
        </div>
      </div>
    </header>

    <main class="content">

      <!-- Datos de la Operación -->
      <section class="card datos-card">
        <h2 class="section-title">Datos de la Operación</h2>
        <div class="datos-grid">
          <div class="dato-item">
            <span class="dato-label">ID Operación</span>
            <span class="dato-value mono">OP-2020-1</span>
          </div>
          <div class="dato-item">
            <span class="dato-label">Ruta</span>
            <span class="dato-value">Shanghai, CN → Barcelona, ES</span>
          </div>
          <div class="dato-item">
            <span class="dato-label">Incoterm</span>
            <span class="dato-value">
              <span class="tag tag-blue">FOB</span>
            </span>
          </div>
          <div class="dato-item">
            <span class="dato-label">Medio</span>
            <span class="dato-value">
              <span class="tag tag-teal">🚢 SEA</span>
            </span>
          </div>
          <div class="dato-item">
            <span class="dato-label">Última Actualización</span>
            <span class="dato-value mono">9 Marzo 2026</span>
          </div>
        </div>
      </section>

      <!-- Pedido tracking -->
      <section class="card pedido-card">
        <div class="pedido-header">
          <div>
            <p class="pedido-num">#Pedido 1</p>
            <p class="pedido-ruta">Shanghai, CN → Barcelona, ES</p>
          </div>
          <div class="pedido-meta">
            <span class="fase-label">Fase: <strong>Aduana</strong></span>
            <span class="fecha-estimada">Fecha estimada: 15 Marzo 2026</span>
          </div>
        </div>

        <!-- Timeline de estados -->
        <div class="timeline">
          <div
            v-for="(step, index) in steps"
            :key="index"
            class="timeline-step"
            :class="step.status"
          >
            <div class="step-connector" v-if="index < steps.length - 1"></div>
            <div class="step-icon">
              <span v-if="step.status === 'done'">✓</span>
              <span v-else-if="step.status === 'active'">●</span>
              <span v-else>○</span>
            </div>
            <div class="step-content">
              <p class="step-name">{{ step.name }}</p>
              <div class="step-dates">
                <div class="step-date-row">
                  <span class="date-label">Fecha prevista</span>
                  <span class="date-value">{{ step.prevista }}</span>
                </div>
                <div class="step-date-row">
                  <span class="date-label">Fecha real</span>
                  <span class="date-value" :class="{ 'no-data': step.real === 'Sin asignar' }">
                    {{ step.real }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Bottom section: Actualizaciones + Mapa -->
      <div class="bottom-grid">

        <!-- Últimas Actualizaciones -->
        <section class="card actualizaciones-card">
          <h2 class="section-title">Últimas Actualizaciones</h2>
          <ul class="updates-list">
            <li
              v-for="(update, i) in updates"
              :key="i"
              class="update-item"
              :style="{ animationDelay: `${i * 0.08}s` }"
            >
              <div class="update-dot" :class="i === 0 ? 'dot-active' : ''"></div>
              <div class="update-body">
                <span class="update-time">{{ update.date }}</span>
                <span class="update-sep">|</span>
                <span class="update-event">{{ update.event }}</span>
              </div>
            </li>
          </ul>
        </section>

        <!-- Mapa -->
        <section class="card mapa-card">
          <div class="map-container">
            <svg viewBox="0 0 400 240" class="map-svg" xmlns="http://www.w3.org/2000/svg">
              <!-- Fondo océano -->
              <rect width="400" height="240" fill="#c8dff5" rx="8"/>

              <!-- Continentes simplificados -->
              <!-- Asia/China -->
              <path d="M230,20 L320,15 L360,30 L370,60 L350,90 L320,100 L290,80 L260,70 L240,50 Z" fill="#b8c9a3" stroke="#8fa880" stroke-width="0.8"/>
              <!-- Europa -->
              <path d="M60,20 L110,15 L130,30 L135,55 L120,65 L100,60 L80,70 L60,65 L50,45 Z" fill="#b8c9a3" stroke="#8fa880" stroke-width="0.8"/>
              <!-- Africa -->
              <path d="M70,80 L110,75 L120,95 L115,130 L100,145 L80,140 L65,120 L60,100 Z" fill="#b8c9a3" stroke="#8fa880" stroke-width="0.8"/>
              <!-- India -->
              <path d="M270,80 L295,78 L300,110 L285,125 L268,108 Z" fill="#b8c9a3" stroke="#8fa880" stroke-width="0.8"/>

              <!-- Ruta marítima -->
              <path
                d="M305,65 Q270,100 220,120 Q160,140 120,100 Q105,85 98,55"
                fill="none"
                stroke="white"
                stroke-width="2.5"
                stroke-dasharray="6 4"
                opacity="0.85"
              />

              <!-- Punto origen: Shanghai -->
              <circle cx="305" cy="65" r="10" fill="white" opacity="0.9"/>
              <circle cx="305" cy="65" r="6" fill="#2563eb"/>
              <text x="305" y="88" text-anchor="middle" font-size="8" fill="#1e3a5f" font-weight="600">Shanghai</text>

              <!-- Barco en ruta -->
              <g transform="translate(200,112)">
                <circle r="14" fill="#2563eb" opacity="0.15"/>
                <circle r="10" fill="#2563eb"/>
                <text x="0" y="4" text-anchor="middle" font-size="11">🚢</text>
              </g>

              <!-- Punto destino: Barcelona -->
              <circle cx="98" cy="55" r="10" fill="white" opacity="0.9"/>
              <circle cx="98" cy="55" r="6" fill="#94a3b8"/>
              <text x="98" y="78" text-anchor="middle" font-size="8" fill="#1e3a5f" font-weight="600">Barcelona</text>
            </svg>
          </div>
        </section>

      </div>
    </main>
  </div>
</template>

<script>
export default {
  name: 'OperacionTracking',
  data() {
    return {
      steps: [
        {
          name: 'Preparación',
          status: 'done',
          prevista: '15 Marzo 2026',
          real: '14 Marzo 2026',
        },
        {
          name: 'Enviado',
          status: 'done',
          prevista: '15 Marzo 2026',
          real: '14 Marzo 2026',
        },
        {
          name: 'En tránsito',
          status: 'done',
          prevista: '15 Marzo 2026',
          real: '14 Marzo 2026',
        },
        {
          name: 'Aduana',
          status: 'active',
          prevista: '15 Marzo 2026',
          real: 'Sin asignar',
        },
        {
          name: 'Entregado',
          status: 'pending',
          prevista: '15 Marzo 2026',
          real: 'Sin asignar',
        },
      ],
      updates: [
        { date: '9 Marzo 2026, 09:00h', event: 'En Aduanas' },
        { date: '8 Marzo 2026, 19:00h', event: 'Entrada en Terminal' },
        { date: '6 Marzo 2026, 09:00h', event: 'Entrada en Terminal' },
        { date: '5 Marzo 2026, 14:00h', event: 'Pedido Enviado' },
        { date: '3 Marzo 2026, 12:00h', event: 'Pedido preparado' },
      ],
    }
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.tracking-app {
  font-family: 'Sora', sans-serif;
  background: #f0f4f8;
  min-height: 100vh;
  color: #1e2d3d;
}

/* ── HEADER ── */
.op-header {
  background: #1a2e4a;
  padding: 20px 32px;
  border-bottom: 3px solid #2563eb;
}

.header-inner {
  display: flex;
  align-items: center;
  gap: 16px;
  max-width: 960px;
  margin: 0 auto;
}

.op-badge {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  font-weight: 700;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-label {
  color: #94a3b8;
  font-size: 11px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.header-title {
  color: #f8fafc;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.2;
}

.route-arrow {
  color: #60a5fa;
  font-weight: 400;
}

/* ── LAYOUT ── */
.content {
  max-width: 960px;
  margin: 0 auto;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e2d3d;
  letter-spacing: 0.03em;
  margin-bottom: 18px;
  text-transform: uppercase;
}

/* ── DATOS ── */
.datos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px 24px;
  border-top: 1px solid #e2e8f0;
  padding-top: 16px;
}

.dato-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dato-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #94a3b8;
  font-weight: 600;
}

.dato-value {
  font-size: 13px;
  font-weight: 600;
  color: #1e2d3d;
}

.mono {
  font-family: 'IBM Plex Mono', monospace;
}

.tag {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.tag-blue {
  background: #dbeafe;
  color: #1d4ed8;
}

.tag-teal {
  background: #ccfbf1;
  color: #0f766e;
}

/* ── PEDIDO HEADER ── */
.pedido-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 10px;
}

.pedido-num {
  font-size: 13px;
  font-weight: 700;
  color: #1e2d3d;
}

.pedido-ruta {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.pedido-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.fase-label {
  font-size: 12px;
  color: #64748b;
}

.fase-label strong {
  color: #2563eb;
}

.fecha-estimada {
  font-size: 11px;
  color: #94a3b8;
  font-family: 'IBM Plex Mono', monospace;
}

/* ── TIMELINE ── */
.timeline {
  display: flex;
  align-items: flex-start;
  gap: 0;
  overflow-x: auto;
  padding-bottom: 4px;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
  min-width: 130px;
}

/* connector line */
.step-connector {
  position: absolute;
  top: 16px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: #e2e8f0;
  z-index: 0;
}

.timeline-step.done .step-connector,
.timeline-step.active .step-connector {
  background: #2563eb;
}

.step-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2.5px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  z-index: 1;
  position: relative;
  transition: all 0.3s;
}

.done .step-icon {
  border-color: #16a34a;
  background: #16a34a;
  color: white;
  font-weight: 700;
}

.active .step-icon {
  border-color: #2563eb;
  background: #2563eb;
  color: white;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
}

.pending .step-icon {
  border-color: #cbd5e1;
  color: #94a3b8;
}

.step-content {
  margin-top: 10px;
  text-align: center;
  padding: 0 4px;
}

.step-name {
  font-size: 12px;
  font-weight: 700;
  color: #1e2d3d;
  margin-bottom: 8px;
}

.pending .step-name {
  color: #94a3b8;
}

.step-dates {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.step-date-row {
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.date-label {
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #94a3b8;
}

.date-value {
  font-size: 11px;
  color: #475569;
  font-family: 'IBM Plex Mono', monospace;
}

.date-value.no-data {
  color: #94a3b8;
  font-style: italic;
}

/* ── BOTTOM GRID ── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

/* ── ACTUALIZACIONES ── */
.updates-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.update-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
  animation: fadeIn 0.4s ease both;
}

.update-item:last-child {
  border-bottom: none;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.update-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
}

.update-dot.dot-active {
  background: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.update-body {
  font-size: 12.5px;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.update-time {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 11px;
  color: #94a3b8;
}

.update-sep {
  color: #cbd5e1;
}

.update-event {
  color: #1e2d3d;
  font-weight: 600;
}

/* ── MAPA ── */
.mapa-card {
  padding: 0;
  overflow: hidden;
}

.map-container {
  width: 100%;
  height: 100%;
  min-height: 220px;
}

.map-svg {
  width: 100%;
  height: 100%;
  display: block;
}

/* ── RESPONSIVE ── */
@media (max-width: 680px) {
  .bottom-grid {
    grid-template-columns: 1fr;
  }

  .timeline {
    flex-direction: column;
    gap: 16px;
  }

  .timeline-step {
    flex-direction: row;
    align-items: flex-start;
    min-width: unset;
  }

  .step-connector {
    display: none;
  }

  .step-content {
    text-align: left;
    margin-top: 0;
    margin-left: 12px;
  }

  .pedido-meta {
    align-items: flex-start;
  }

  .datos-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>