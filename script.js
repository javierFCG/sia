const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');

registerBtn.addEventListener('click', () => {
    container.classList.add('active');
});

loginBtn.addEventListener('click', () => {
    container.classList.remove('active');
});
// Confirmar eliminación
function confirmarEliminacion(mensaje) {
    return confirm(mensaje || '¿Estás seguro de eliminar este elemento?');
}

// Mostrar/ocultar elementos
function toggleElement(id) {
    const element = document.getElementById(id);
    if (element) {
        element.classList.toggle('active');
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
});

// ==============================================
// FUNCIONES PARA MODALES
// ==============================================

function abrirModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
    }
}

function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
    }
}

// ==============================================
// FUNCIONES PARA TABLAS
// ==============================================

function mostrarTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.tab[onclick="mostrarTab('${tab}')"]`)?.classList.add('active');
    document.getElementById(`tab-${tab}`)?.classList.add('active');
}

// ==============================================
// FUNCIONES PARA VALIDACIÓN DE FORMULARIOS
// ==============================================

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validarPassword(password) {
    return password.length >= 6;
}

// ==============================================
// FUNCIONES PARA FECHAS
// ==============================================

function formatearFecha(fecha) {
    const d = new Date(fecha);
    return d.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatearFechaHora(fecha) {
    const d = new Date(fecha);
    return d.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ==============================================
// FUNCIONES PARA PDF (html2pdf)
// ==============================================

function descargarPDF(elementoId, nombreArchivo) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) return;
    
    const opciones = {
        margin: [0.3, 0.3, 0.3, 0.3],
        filename: nombreArchivo || 'documento.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    
    html2pdf().set(opciones).from(elemento).save();
}

// ==============================================
// FUNCIONES PARA DOCUMENTOS (Nuevo Ingreso)
// ==============================================

function mostrarCamposNuevoIngreso() {
    const rol = document.getElementById('rol')?.value;
    const campos = document.getElementById('camposNuevoIngreso');
    if (campos) {
        campos.style.display = (rol === 'NuevoIngreso') ? 'block' : 'none';
    }
}

// ==============================================
// FUNCIONES PARA SEGURIDAD
// ==============================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==============================================
// FUNCIONES PARA NOTIFICACIONES
// ==============================================

function mostrarMensaje(mensaje, tipo) {
    const contenedor = document.getElementById('mensajes');
    if (!contenedor) return;
    
    const div = document.createElement('div');
    div.className = `mensaje ${tipo}`;
    div.textContent = mensaje;
    contenedor.appendChild(div);
    
    setTimeout(() => {
        div.remove();
    }, 5000);
}

// ==============================================
// INICIALIZACIÓN
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
            `;
            this.appendChild(tooltip);
        });
        el.addEventListener('mouseleave', function() {
            this.querySelector('.tooltip')?.remove();
        });
    });
});