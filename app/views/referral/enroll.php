<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Únete al Programa de Referidos - Travel Mayan World</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .enroll-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .enroll-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .benefit-item {
            padding: 1.5rem;
            text-align: center;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .benefit-item:hover {
            transform: translateY(-10px);
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.3);
        }
        
        .benefit-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #28a745, #20c997);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .benefit-item:hover .benefit-icon {
            -webkit-text-fill-color: white;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-enroll {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 50px;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-enroll:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-enroll::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-enroll:hover::before {
            left: 100%;
        }
        
        .testimonial-card {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Procesando...</span>
        </div>
        <div>Procesando tu inscripción...</div>
    </div>
</div>

<div class="enroll-hero">
    <div class="container">
        <div class="enroll-card">
            <div class="row g-0">
                <!-- Panel Izquierdo - Información -->
                <div class="col-lg-5 p-5" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                    <h2 class="mb-4">
                        <i class="fas fa-users me-3"></i>
                        Programa de Referidos
                    </h2>
                    
                    <p class="lead mb-4">
                        Únete a nuestro programa de referidos y convierte tu pasión por los viajes mayas en ganancias reales.
                    </p>
                    
                    <!-- Beneficios -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle fa-lg me-3 text-success"></i>
                            <div>
                                <strong>10% de comisión</strong><br>
                                <small class="opacity-75">Por cada reserva exitosa</small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-gift fa-lg me-3 text-warning"></i>
                            <div>
                                <strong>Descuentos para ti</strong><br>
                                <small class="opacity-75">5% extra en todos tus tours</small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-trophy fa-lg me-3 text-info"></i>
                            <div>
                                <strong>Sistema de niveles</strong><br>
                                <small class="opacity-75">Gana más con cada nivel</small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-mobile-alt fa-lg me-3 text-light"></i>
                            <div>
                                <strong>Tracking en tiempo real</strong><br>
                                <small class="opacity-75">Ve tus ganancias al instante</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Testimonial -->
                    <div class="testimonial-card bg-white text-dark">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b47c?w=50&h=50&fit=crop&crop=face&auto=format" 
                                 class="rounded-circle me-3" width="50" height="50" alt="María">
                            <div>
                                <strong>María Gonzalez</strong><br>
                                <small class="text-muted">Referrer Top</small>
                            </div>
                        </div>
                        <p class="mb-0">
                            <i class="fas fa-quote-left text-primary me-2"></i>
                            "En 6 meses he ganado más de $2,000 compartiendo tours con mis amigos. ¡Es increíble!"
                        </p>
                    </div>
                </div>
                
                <!-- Panel Derecho - Formulario -->
                <div class="col-lg-7 p-5">
                    <h3 class="mb-4 text-center">Únete Ahora</h3>
                    
                    <!-- Formulario de Inscripción -->
                    <form id="enrollForm" novalidate>
                        <input type="hidden" name="csrf_token" id="csrfToken" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="firstName" value="<?= htmlspecialchars(Auth::getUserName() ?? '') ?>" readonly>
                                    <label for="firstName">
                                        <i class="fas fa-user me-2"></i>Nombre
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars(Auth::getUserEmail() ?? '') ?>" readonly>
                                    <label for="email">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Teléfono" required>
                                <label for="phone">
                                    <i class="fas fa-phone me-2"></i>Teléfono de Contacto *
                                </label>
                                <div class="invalid-feedback">
                                    Por favor ingresa un número de teléfono válido.
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">
                            <i class="fas fa-share-alt me-2 text-primary"></i>
                            Redes Sociales (Opcional)
                        </h5>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="url" class="form-control" id="facebook" name="facebook" placeholder="Facebook">
                                <label for="facebook">
                                    <i class="fab fa-facebook me-2 text-primary"></i>Perfil de Facebook
                                </label>
                                <div class="form-text">
                                    Ayuda a personalizar tus enlaces de compartición
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="url" class="form-control" id="instagram" name="instagram" placeholder="Instagram">
                                <label for="instagram">
                                    <i class="fab fa-instagram me-2 text-danger"></i>Perfil de Instagram
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="whatsapp" name="whatsapp" placeholder="WhatsApp">
                                <label for="whatsapp">
                                    <i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp
                                </label>
                                <div class="form-text">
                                    Para compartir más fácilmente con contactos
                                </div>
                            </div>
                        </div>
                        
                        <!-- Términos y Condiciones -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Acepto los 
                                <a href="?route=referral/terms" target="_blank" rel="noopener" class="text-primary">términos y condiciones</a> 
                                del programa de referidos y las 
                                <a href="?route=privacy" target="_blank" rel="noopener" class="text-primary">políticas de privacidad</a>
                            </label>
                            <div class="invalid-feedback">
                                Debes aceptar los términos y condiciones para continuar.
                            </div>
                        </div>
                        
                        <!-- Marketing Opt-in -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="marketing" name="marketing" checked>
                            <label class="form-check-label" for="marketing">
                                Deseo recibir consejos de marketing, actualizaciones del programa y ofertas especiales por email
                            </label>
                        </div>
                        
                        <!-- Botón de Inscripción -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-enroll">
                                <i class="fas fa-rocket me-2"></i>
                                Inscribirme Ahora
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-2"></i>
                                Tu información está 100% protegida
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Información Adicional -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-center mb-5 text-white">¿Cómo Funciona?</h3>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="text-center text-white">
                    <div class="step-number">1</div>
                    <h5>Inscríbete</h5>
                    <p class="opacity-75">
                        Completa el formulario y obtén tu código único de referido al instante.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="text-center text-white">
                    <div class="step-number">2</div>
                    <h5>Comparte</h5>
                    <p class="opacity-75">
                        Comparte tu código con amigos a través de redes sociales, WhatsApp o email.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="text-center text-white">
                    <div class="step-number">3</div>
                    <h5>Gana</h5>
                    <p class="opacity-75">
                        Recibe 10% de comisión por cada reserva exitosa. ¡Sin límites!
                    </p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Rápido -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="card bg-white">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">
                            <i class="fas fa-question-circle me-2 text-primary"></i>
                            Preguntas Frecuentes
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>¿Cuándo recibo mi pago?</strong>
                                    <p class="text-muted mb-0">Los pagos se procesan semanalmente los viernes.</p>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>¿Hay límite de referidos?</strong>
                                    <p class="text-muted mb-0">No hay límite. Puedes referir tantas personas como desees.</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>¿Cuesta algo inscribirse?</strong>
                                    <p class="text-muted mb-0">No, la inscripción es completamente gratuita.</p>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>¿Puedo referir internacionalmente?</strong>
                                    <p class="text-muted mb-0">Sí, aceptamos referidos de cualquier país.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="?route=referral/faq" class="btn btn-outline-primary">
                                Ver todas las preguntas
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('enrollForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Validaciones adicionales
        if (!validatePhoneNumber()) {
            return;
        }
        
        if (!document.getElementById('terms').checked) {
            showAlert('Debes aceptar los términos y condiciones', 'danger');
            return;
        }
        
        submitEnrollment();
    });
    
    // Validación en tiempo real del teléfono
    document.getElementById('phone').addEventListener('input', function() {
        validatePhoneNumber();
    });
});

function validatePhoneNumber() {
    const phone = document.getElementById('phone');
    const phoneRegex = /^[\+]?[(]?[\d\s\-\(\)]{10,}$/;
    
    if (!phoneRegex.test(phone.value.trim())) {
        phone.setCustomValidity('Ingresa un número de teléfono válido');
        return false;
    } else {
        phone.setCustomValidity('');
        return true;
    }
}

function submitEnrollment() {
    const formData = new FormData(document.getElementById('enrollForm'));
    
    // Mostrar loading
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    fetch('?route=referral/enroll', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.referral_code);
            
            // Redirigir después de 3 segundos
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }, 3000);
        } else {
            showAlert(data.message || 'Error en la inscripción', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión. Inténtalo de nuevo.', 'danger');
    })
    .finally(() => {
        document.getElementById('loadingOverlay').style.display = 'none';
    });
}

function showSuccessModal(referralCode) {
    const modalHtml = `
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                        </div>
                        <h3 class="mb-3">¡Inscripción Exitosa!</h3>
                        <p class="lead mb-4">
                            Bienvenido al programa de referidos de Travel Mayan World. 
                            Tu código único es:
                        </p>
                        <div class="alert alert-info">
                            <h4 class="mb-3">Tu Código: <strong>${referralCode}</strong></h4>
                            <button class="btn btn-primary" onclick="copyToClipboard('${referralCode}')">
                                <i class="fas fa-copy me-2"></i>Copiar Código
                            </button>
                        </div>
                        <p class="text-muted">
                            Serás redirigido a tu dashboard en unos segundos...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Código copiado al portapapeles', 'success');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Código copiado al portapapeles', 'success');
    });
}

// Animaciones de entrada
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.benefit-item, .testimonial-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});
</script>

</body>
</html>
