<!--
Componente: Dashboard
Descripción: Contenedor principal autenticado. Resuelve sesión/rol, construye el menú lateral y renderiza la sección activa.
-->
<template>
    <!-- Estado transitorio mientras se valida sesión y usuario -->
    <div v-if="comprobandoSesion" class="state-message">Cargando sesión...</div>

    <!-- Estado mostrado cuando el usuario no tiene rol válido para el panel -->
    <div v-else-if="!estaAutorizado" class="state-message">
        No tienes permisos para acceder al panel.
    </div>

    <!-- Layout completo del dashboard -->
    <div v-else class="main-layout">
        <NavbarVertical
            :menu-items="opcionesMenuDisponibles"
            :active-item="seccionSeleccionada"
            @section-selected="gestionarSeleccionSeccion"
        />
        <div class="view-container">
            <NavbarHorizontal />
            <!-- Render dinámico de la sección seleccionada en el menú -->
            <main class="page-content">
                <component :is="componenteSeccionActual" />
            </main>
        </div>
    </div>

</template>

<script setup>
// Importaciones de Vue, componentes de layout y vistas internas.
import { computed, onMounted, ref } from 'vue'
import NavbarVertical from '../navbar/NavbarVertical.vue'
import NavbarHorizontal from '../navbar/NavbarHorizontal.vue'
import DashboardVista from '../DashboardVista.vue'
import ListadoUsuarios from '../ListadoUsuarios.vue'
import ListadoClientes from '../ListadoClientes.vue'
import ListadoOfertas from '../ListadoOfertas.vue'
import ListadoTracking from '../ListadoTracking.vue'
import {
    Squares2X2Icon,
    UsersIcon,
    ClipboardDocumentListIcon,
    DocumentTextIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline'

// Estado de sesión y usuario autenticado.
const comprobandoSesion = ref(true)
const estaAutorizado = ref(true)
const usuarioActual = ref(null)

// Recupera de localStorage una versión cacheada del usuario.
const cargarUsuarioDesdeStorage = () => {
    // Recuperamos el usuario cacheado para pintar el menú antes de consultar la API.
    const usuarioGuardado = localStorage.getItem('auth_user')

    if (!usuarioGuardado) {
        return null
    }

    try {
        return JSON.parse(usuarioGuardado)
    } catch {
        return null
    }
}

// Normaliza el rol del usuario para controlar secciones y permisos.
const tipoRol = computed(() => {
    const idRol = Number(usuarioActual.value?.rol_id ?? 0)
    const nombreRol = String(usuarioActual.value?.rol || '').toLowerCase()

    if (idRol === 1 || nombreRol.includes('admin')) {
        return 'admin'
    }

    if (idRol === 2 || nombreRol.includes('operador') || nombreRol.includes('operator')) {
        return 'operador'
    }

    if (idRol === 3 || nombreRol.includes('client')) {
        return 'cliente'
    }

    return ''
})

// Opciones de menú por rol.
const opcionesMenuAdmin = [
    { text: 'Usuarios', icon: UsersIcon },
    { text: 'Clientes', icon: ClipboardDocumentListIcon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const opcionesMenuOperador = [
    { text: 'Dashboard', icon: Squares2X2Icon },
    { text: 'Clientes', icon: ClipboardDocumentListIcon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const opcionesMenuCliente = [
    { text: 'Dashboard', icon: Squares2X2Icon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const menuPorRol = {
    admin: opcionesMenuAdmin,
    operador: opcionesMenuOperador,
    cliente: opcionesMenuCliente,
}

const opcionesMenuDisponibles = computed(() => menuPorRol[tipoRol.value] || [])

// Sección activa actualmente en la vista.
const seccionSeleccionada = ref('Dashboard')

// Traduce nombre de sección a componente Vue.
const mapaComponentesSeccion = {
    Dashboard: DashboardVista,
    Usuarios: ListadoUsuarios,
    Clientes: ListadoClientes,
    Ofertas: ListadoOfertas,
    Tracking: ListadoTracking,
}

// Conjunto de secciones permitidas según rol.
const seccionesPermitidas = computed(() => opcionesMenuDisponibles.value.map((item) => item.text))

// Componente actual a renderizar en el área principal.
const componenteSeccionActual = computed(() => {
    const seccionPorDefecto = seccionesPermitidas.value[0] || 'Dashboard'
    const seccionObjetivo = seccionesPermitidas.value.includes(seccionSeleccionada.value)
        ? seccionSeleccionada.value
        : seccionPorDefecto

    return mapaComponentesSeccion[seccionObjetivo] || DashboardVista
})

// Cambia de sección solo si está permitida para el rol actual.
const gestionarSeleccionSeccion = (nombreSeccion) => {
    if (!seccionesPermitidas.value.includes(nombreSeccion)) {
        return
    }

    seccionSeleccionada.value = nombreSeccion
}

onMounted(() => {
    // Valida existencia de token antes de cargar el dashboard.
    const tokenSesion = localStorage.getItem('auth_token')

    if (!tokenSesion) {
        window.location.href = '/'
        return
    }

    // Se intenta mostrar algo de UI rápido con el usuario cacheado.
    usuarioActual.value = cargarUsuarioDesdeStorage()

    // La API confirma si el token sigue siendo válido y devuelve el usuario real.
    window.axios.get('/api/user')
        .then(({ data }) => {
            const usuarioApi = data?.user ?? null

            if (!usuarioApi) {
                throw new Error('Usuario no disponible')
            }

            usuarioActual.value = {
                id: usuarioApi.id,
                email: usuarioApi.correu,
                name: [usuarioApi.nom, usuarioApi.cognoms].filter(Boolean).join(' ').trim(),
                nom: usuarioApi.nom,
                cognoms: usuarioApi.cognoms,
                rol_id: usuarioApi.rol_id,
                rol: usuarioApi.rol?.rol,
            }

            localStorage.setItem('auth_user', JSON.stringify(usuarioActual.value))
        })
        .catch(() => {
            // Si falla la validación de sesión, limpiamos almacenamiento local.
            localStorage.removeItem('auth_token')
            localStorage.removeItem('auth_user')
            delete window.axios.defaults.headers.common.Authorization
            window.location.href = '/'
        })
        .finally(() => {
            // Si no hay usuario/rol válido se desactiva el panel para evitar inconsistencias.
            if (!usuarioActual.value) {
                return
            }

            if (!tipoRol.value) {
                estaAutorizado.value = false
                comprobandoSesion.value = false
                return
            }

            if (seccionesPermitidas.value.length > 0) {
                seccionSeleccionada.value = seccionesPermitidas.value[0]
            }

            comprobandoSesion.value = false
        })
})
</script>

<style scoped>
.state-message {
    padding: 2rem;
    text-align: center;
    font-weight: 700;
    color: #0a2540;
}
</style>
