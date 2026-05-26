<?php

use App\Core\Config;
use App\Core\Helpers;
use App\Helpers\CompanyConfigHelper;
// Cargar configuración de empresa
$companyName = CompanyConfigHelper::get('company_name', 'Travel Mayan World');
$contactInfo = CompanyConfigHelper::getContactInfo();

$title = 'Contacto | ' . $companyName;
$metaDescription = 'Escríbenos para resolver dudas y coordinar tu viaje';
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="mb-3" id="contacto-titulo">Contacto</h1>
            <p class="text-muted mb-4">¿Tienes dudas o necesitas ayuda? Escríbenos y te responderemos en menos de 24 horas.</p>

            <form method="POST" action="<?= Config::getBaseUrl() ?>?route=contact" class="row g-3" aria-labelledby="contacto-titulo">
        <input type="hidden" name="csrf_token" value="<?= Helpers::generateCsrfToken() ?>">
        <div class="col-md-6">
            <label class="form-label" for="contact_nombre">Nombre</label>
            <input type="text" id="contact_nombre" name="nombre" class="form-control" required aria-required="true" autocomplete="name" placeholder="Tu nombre completo">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contact_email">Email</label>
            <input type="email" id="contact_email" name="email" class="form-control" required aria-required="true" autocomplete="email" placeholder="tu@email.com">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contact_tel">Teléfono</label>
            <input type="tel" id="contact_tel" name="telefono" class="form-control" autocomplete="tel" placeholder="Ej. +52 55 1234 5678" pattern="[\+0-9\s\-]{7,}" aria-describedby="tel_hint">
            <small id="tel_hint" class="form-text text-muted">Opcional. Incluye código de país.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contact_asunto">Asunto</label>
            <input type="text" id="contact_asunto" name="asunto" class="form-control" required aria-required="true" placeholder="Tema del mensaje">
        </div>
        <div class="col-12">
            <label class="form-label" for="contact_mensaje">Mensaje</label>
            <textarea id="contact_mensaje" name="mensaje" class="form-control" rows="5" required aria-required="true" minlength="10" aria-describedby="contact_hint"></textarea>
            <small id="contact_hint" class="form-text text-muted">Incluye fechas tentativas y número de personas.</small>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary" aria-label="Enviar formulario de contacto"><i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Enviar mensaje</button>
        </div>
    </form>
        </div>

        <!-- Información de Contacto -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="fas fa-info-circle me-2"></i>Información de Contacto</h5>

                    <?php if (!empty($contactInfo['address'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Dirección</h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($contactInfo['address']) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contactInfo['phone'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="fas fa-phone me-2 text-primary"></i>Teléfono</h6>
                        <a href="<?= CompanyConfigHelper::getPhoneLink() ?>" class="text-decoration-none">
                            <?= htmlspecialchars($contactInfo['phone']) ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contactInfo['whatsapp'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp</h6>
                        <a href="<?= CompanyConfigHelper::getWhatsAppLink('Hola, necesito información') ?>"
                           target="_blank"
                           class="text-decoration-none">
                            <?= htmlspecialchars($contactInfo['whatsapp']) ?>
                        </a>
                        <br>
                        <small class="text-muted">Click para chatear</small>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contactInfo['email'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="fas fa-envelope me-2 text-primary"></i>Email</h6>
                        <a href="<?= CompanyConfigHelper::getEmailLink('Consulta desde web') ?>" class="text-decoration-none">
                            <?= htmlspecialchars($contactInfo['email']) ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php
                    $horarios = CompanyConfigHelper::get('business_hours');
                    if (!empty($horarios)):
                    ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="fas fa-clock me-2 text-primary"></i>Horario</h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($horarios) ?></p>
                    </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3">Síguenos</h6>
                    <?php
                    $socialMedia = CompanyConfigHelper::getSocialMedia();
                    ?>
                    <div class="d-flex gap-3">
                        <?php if (!empty($socialMedia['facebook'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['facebook']) ?>" target="_blank" class="btn btn-outline-primary btn-sm" aria-label="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($socialMedia['instagram'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['instagram']) ?>" target="_blank" class="btn btn-outline-danger btn-sm" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($socialMedia['twitter'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['twitter']) ?>" target="_blank" class="btn btn-outline-info btn-sm" aria-label="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.querySelector('form[action$="?route=contact"]');
  if (!form) return;
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const fields = ['contact_nombre','contact_email','contact_asunto','contact_mensaje'];
  function setValidity(el, valid){ el.classList.toggle('is-invalid', !valid); el.classList.toggle('is-valid', valid); el.setAttribute('aria-invalid', String(!valid)); }
  fields.forEach(id=>{ const el = document.getElementById(id); el?.addEventListener('blur', ()=>{ let ok = el.value.trim().length>0; if(id==='contact_email' && ok) ok = emailRe.test(el.value.trim()); if(id==='contact_mensaje' && ok) ok = el.value.trim().length>=10; setValidity(el, ok); }); });
  form.addEventListener('submit', function(e){ let firstInvalid=null; fields.forEach(id=>{ const el=document.getElementById(id); if(!el) return; let ok=el.value.trim().length>0; if(id==='contact_email' && ok) ok = emailRe.test(el.value.trim()); if(id==='contact_mensaje' && ok) ok = el.value.trim().length>=10; setValidity(el, ok); if(!ok && !firstInvalid) firstInvalid=el; }); if(firstInvalid){ e.preventDefault(); firstInvalid.focus(); }});
});
</script>
