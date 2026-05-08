<template>
  <div class="chat-wrapper">
    <!-- Toggle button -->
    <button class="chat-toggle" @click="toggleChat" :class="{ open: isOpen }">
      <span class="toggle-icon">{{ isOpen ? '✕' : '💬' }}</span>
      <span class="toggle-label" v-if="!isOpen">Consultar datos</span>
    </button>

    <!-- Chat panel -->
    <transition name="chat-slide">
      <div class="chat-panel" v-if="isOpen">
        <div class="chat-header">
          <div class="header-left">
            <div class="header-dot"></div>
            <div>
              <div class="header-title">Multimar Analytics</div>
              <div class="header-subtitle">IA · Comercio Internacional</div>
            </div>
          </div>
          <button class="header-close" @click="toggleChat">✕</button>
        </div>

        <div class="chat-messages" ref="messagesEl">
          <!-- Welcome message -->
          <div class="message bot" v-if="messages.length === 0">
            <div class="message-bubble">
              Hola, soy el asistente de <strong>Multimar Logistics</strong>. Puedo consultar datos del comercio internacional. Prueba con:
              <div class="suggestions">
                <button class="suggestion" @click="sendSuggestion(s)" v-for="s in suggestions" :key="s">{{ s }}</button>
              </div>
            </div>
          </div>

          <div
            v-for="(msg, i) in messages"
            :key="i"
            class="message"
            :class="msg.role"
          >
            <div class="message-bubble">
              <template v-if="msg.role === 'bot' && msg.data">
                <div class="answer-text">{{ msg.text }}</div>
                <div class="sql-badge" @click="msg.showSql = !msg.showSql">
                  <span>SQL</span> <span class="sql-toggle">{{ msg.showSql ? '▲' : '▼' }}</span>
                </div>
                <pre class="sql-code" v-if="msg.showSql">{{ msg.sql }}</pre>
                <div class="data-table" v-if="msg.data && msg.data.length">
                  <table>
                    <thead>
                      <tr>
                        <th v-for="col in Object.keys(msg.data[0])" :key="col">{{ col }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, ri) in msg.data" :key="ri">
                        <td v-for="col in Object.keys(msg.data[0])" :key="col">
                          {{ formatValue(row[col]) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="data-footer">{{ msg.data.length }} resultados</div>
                </div>
              </template>
              <template v-else>
                {{ msg.text }}
              </template>
            </div>
            <div class="message-time">{{ msg.time }}</div>
          </div>

          <!-- Typing indicator -->
          <div class="message bot" v-if="loading">
            <div class="message-bubble typing">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <div class="chat-input-area">
          <input
            class="chat-input"
            v-model="inputText"
            @keydown.enter="sendMessage"
            placeholder="Escribe tu consulta..."
            :disabled="loading"
          />
          <button class="send-btn" @click="sendMessage" :disabled="loading || !inputText.trim()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'ChatbotMultimar',
  data() {
    return {
      isOpen: false,
      inputText: '',
      loading: false,
      messages: [],
      suggestions: [
        '¿Los 5 productos más exportados?',
        '¿Qué países tienen mayor superávit?',
        '¿Cuál es la categoría más rentable?',
      ],
      // Cambia esta URL si tu app está en otro dominio
      webhookUrl: 'http://localhost:5678/webhook/chatbot',
    }
  },
  methods: {
    toggleChat() {
      this.isOpen = !this.isOpen
      if (this.isOpen) {
        this.$nextTick(() => this.scrollToBottom())
      }
    },

    sendSuggestion(text) {
      this.inputText = text
      this.sendMessage()
    },

    async sendMessage() {
      const text = this.inputText.trim()
      if (!text || this.loading) return

      this.inputText = ''
      this.messages.push({ role: 'user', text, time: this.now() })
      this.loading = true
      this.$nextTick(() => this.scrollToBottom())

      try {
        const res = await fetch(this.webhookUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ pregunta: text }),
        })

        if (!res.ok) throw new Error('Error en la consulta')
        const data = await res.json()

        const filas = Array.isArray(data.respuesta) ? data.respuesta : []
        const resumen = filas.length
          ? `Encontré ${filas.length} resultado${filas.length > 1 ? 's' : ''}.`
          : 'No encontré datos para esa consulta.'

        this.messages.push({
          role: 'bot',
          text: resumen,
          sql: data.sql || '',
          data: filas,
          showSql: false,
          time: this.now(),
        })
      } catch (e) {
        this.messages.push({
          role: 'bot',
          text: '⚠️ No pude conectar con el servidor. Asegúrate de que N8N está corriendo.',
          time: this.now(),
        })
      } finally {
        this.loading = false
        this.$nextTick(() => this.scrollToBottom())
      }
    },

    scrollToBottom() {
      const el = this.$refs.messagesEl
      if (el) el.scrollTop = el.scrollHeight
    },

    now() {
      return new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
    },

    formatValue(val) {
      if (val === null || val === undefined) return '—'
      if (typeof val === 'number') {
        return val > 999999
          ? '$' + (val / 1e9).toFixed(1) + 'B'
          : val.toLocaleString('es-ES')
      }
      return val
    },
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.chat-wrapper {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9999;
  font-family: 'DM Sans', sans-serif;
}

/* Toggle button */
.chat-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #0a2540;
  color: #fff;
  border: none;
  border-radius: 50px;
  padding: 14px 22px;
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  box-shadow: 0 4px 24px rgba(10,37,64,0.35);
  transition: all 0.25s ease;
  letter-spacing: 0.01em;
}
.chat-toggle:hover { background: #0d3060; transform: translateY(-2px); box-shadow: 0 8px 32px rgba(10,37,64,0.4); }
.chat-toggle.open { padding: 14px 18px; background: #1a3a5c; }
.toggle-icon { font-size: 18px; line-height: 1; }

/* Panel */
.chat-panel {
  position: absolute;
  bottom: 70px;
  right: 0;
  width: 400px;
  height: 560px;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 12px 60px rgba(10,37,64,0.22), 0 2px 8px rgba(10,37,64,0.08);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(10,37,64,0.08);
}

/* Header */
.chat-header {
  background: #0a2540;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-dot {
  width: 10px; height: 10px;
  background: #00d084;
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(0,208,132,0.25);
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 3px rgba(0,208,132,0.25); }
  50% { box-shadow: 0 0 0 6px rgba(0,208,132,0.1); }
}
.header-title { color: #fff; font-weight: 600; font-size: 15px; }
.header-subtitle { color: rgba(255,255,255,0.5); font-size: 11px; margin-top: 1px; letter-spacing: 0.05em; }
.header-close { background: rgba(255,255,255,0.1); border: none; color: #fff; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; font-size: 13px; transition: background 0.2s; }
.header-close:hover { background: rgba(255,255,255,0.2); }

/* Messages */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: #f7f9fc;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-track { background: transparent; }
.chat-messages::-webkit-scrollbar-thumb { background: #d0dae6; border-radius: 4px; }

.message { display: flex; flex-direction: column; max-width: 90%; }
.message.user { align-self: flex-end; align-items: flex-end; }
.message.bot { align-self: flex-start; align-items: flex-start; }

.message-bubble {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 13.5px;
  line-height: 1.55;
}
.message.user .message-bubble {
  background: #0a2540;
  color: #fff;
  border-bottom-right-radius: 4px;
}
.message.bot .message-bubble {
  background: #fff;
  color: #1a2a3a;
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 4px rgba(10,37,64,0.08);
  max-width: 100%;
}

.message-time { font-size: 10px; color: #a0b0c0; margin-top: 4px; padding: 0 4px; }

/* Suggestions */
.suggestions { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
.suggestion {
  background: #f0f4f8;
  border: 1px solid #d0dae6;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12.5px;
  color: #0a2540;
  cursor: pointer;
  text-align: left;
  font-family: 'DM Sans', sans-serif;
  transition: all 0.15s;
}
.suggestion:hover { background: #0a2540; color: #fff; border-color: #0a2540; }

/* SQL badge */
.sql-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #eef2f7;
  border: 1px solid #d0dae6;
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 600;
  color: #4a6080;
  cursor: pointer;
  margin: 8px 0 0;
  letter-spacing: 0.05em;
  transition: background 0.15s;
}
.sql-badge:hover { background: #dce5f0; }
.sql-toggle { font-size: 9px; }

.sql-code {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  background: #1a2a3a;
  color: #7dd3fc;
  padding: 10px 12px;
  border-radius: 8px;
  margin-top: 8px;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

/* Data table */
.answer-text { margin-bottom: 6px; }
.data-table { margin-top: 10px; overflow-x: auto; border-radius: 8px; border: 1px solid #e0e8f0; }
.data-table table { width: 100%; border-collapse: collapse; font-size: 12px; }
.data-table th {
  background: #0a2540;
  color: #fff;
  padding: 7px 10px;
  text-align: left;
  font-weight: 600;
  font-size: 11px;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.data-table td {
  padding: 6px 10px;
  border-bottom: 1px solid #eef2f7;
  color: #1a2a3a;
  white-space: nowrap;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:nth-child(even) td { background: #f7f9fc; }
.data-footer { padding: 5px 10px; font-size: 11px; color: #7090a0; text-align: right; background: #f7f9fc; border-top: 1px solid #e0e8f0; }

/* Typing indicator */
.typing { display: flex; align-items: center; gap: 5px; padding: 14px 18px; }
.typing span {
  width: 7px; height: 7px;
  background: #c0ccd8;
  border-radius: 50%;
  animation: bounce 1.2s infinite;
}
.typing span:nth-child(2) { animation-delay: 0.2s; }
.typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-6px); background: #0a2540; }
}

/* Input area */
.chat-input-area {
  padding: 14px 16px;
  background: #fff;
  border-top: 1px solid #e8eef5;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}
.chat-input {
  flex: 1;
  border: 1.5px solid #d0dae6;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13.5px;
  font-family: 'DM Sans', sans-serif;
  color: #1a2a3a;
  outline: none;
  background: #f7f9fc;
  transition: border 0.2s;
}
.chat-input:focus { border-color: #0a2540; background: #fff; }
.chat-input::placeholder { color: #a0b4c4; }
.chat-input:disabled { opacity: 0.6; }

.send-btn {
  background: #0a2540;
  border: none;
  border-radius: 10px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}
.send-btn:hover:not(:disabled) { background: #0d3060; transform: scale(1.05); }
.send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Transition */
.chat-slide-enter-active, .chat-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-slide-enter-from, .chat-slide-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

/* Mobile */
@media (max-width: 480px) {
  .chat-wrapper { bottom: 16px; right: 16px; left: 16px; }
  .chat-panel { width: 100%; right: 0; left: 0; bottom: 65px; }
}
</style>