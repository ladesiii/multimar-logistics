<template>
    <div v-if="comprobandoSesion" class="state-message">Cargando sesión...</div>

    <div v-else-if="!estaAutorizado" class="state-message">
        No tienes permisos para acceder al panel.
    </div>

    <div v-else class="main-layout">
        <NavbarVertical
            :menu-items="opcionesMenuDisponibles"
            :active-item="seccionSeleccionada"
            @section-selected="gestionarSeleccionSeccion"
        />
        <div class="view-container">
            <NavbarHorizontal />
            <main class="page-content">
                <component :is="componenteSeccionActual" />
            </main>
        </div>
    </div>

</template>

<script setup>
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

const comprobandoSesion = ref(true)
const estaAutorizado = ref(true)
const usuarioActual = ref(null)

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

const seccionSeleccionada = ref('Dashboard')

const mapaComponentesSeccion = {
    Dashboard: DashboardVista,
    Usuarios: ListadoUsuarios,
    Clientes: ListadoClientes,
    Ofertas: ListadoOfertas,
    Tracking: ListadoTracking,
}

const seccionesPermitidas = computed(() => opcionesMenuDisponibles.value.map((item) => item.text))

const componenteSeccionActual = computed(() => {
    const seccionPorDefecto = seccionesPermitidas.value[0] || 'Dashboard'
    const seccionObjetivo = seccionesPermitidas.value.includes(seccionSeleccionada.value)
        ? seccionSeleccionada.value
        : seccionPorDefecto

    return mapaComponentesSeccion[seccionObjetivo] || DashboardVista
})

const gestionarSeleccionSeccion = (nombreSeccion) => {
    if (!seccionesPermitidas.value.includes(nombreSeccion)) {
        return
    }

    seccionSeleccionada.value = nombreSeccion
}

onMounted(() => {
    const tokenSesion = localStorage.getItem('auth_token')

    if (!tokenSesion) {
        window.location.href = '/'
        return
    }

    usuarioActual.value = cargarUsuarioDesdeStorage()

    // La API confirma que el token sigue siendo válido y devuelve el usuario real.
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
            localStorage.removeItem('auth_token')
            localStorage.removeItem('auth_user')
            delete window.axios.defaults.headers.common.Authorization
            window.location.href = '/'
        })
        .finally(() => {
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
