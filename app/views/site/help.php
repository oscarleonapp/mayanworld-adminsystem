<?php 
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-8">
      <h1 class="mb-4">Centro de Ayuda</h1>

      <h4 class="mb-3">Preguntas frecuentes</h4>
      <div class="input-group mb-1">
        <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
        <input type="text" class="form-control" id="faqSearch" placeholder="Busca por palabras clave (p. ej., pago, cancelación)" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="faqSuggestions">
      </div>
      <ul id="faqSuggestions" class="list-group mb-3" style="display:none;" role="listbox" aria-label="Sugerencias"></ul>
      <div class="accordion mb-4" id="faq">
        <div class="accordion-item">
          <h2 class="accordion-header" id="q1">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">¿Cómo reservo un tour?</button>
          </h2>
          <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#faq">
            <div class="accordion-body">Elige tu destino, selecciona fecha y personas, y completa tus datos. Puedes pagar con transferencia, efectivo o tarjeta (Stripe).</div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="q2">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">¿Puedo pagar un anticipo?</button>
          </h2>
          <div id="c2" class="accordion-collapse collapse" data-bs-parent="#faq">
            <div class="accordion-body">Sí. Puedes pagar un anticipo del <?= (int)(Config::DEPOSIT_RATE*100) ?>% con tarjeta y completar el saldo después.</div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="q3">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">¿Cuál es la política de cancelación?</button>
          </h2>
          <div id="c3" class="accordion-collapse collapse" data-bs-parent="#faq">
            <div class="accordion-body">Las cancelaciones y reembolsos dependen del tour. Revisa la sección "Políticas de Cancelación" en el detalle del tour.</div>
          </div>
        </div>
      </div>

      <h4 class="mb-3">Políticas y seguridad</h4>
      <ul class="list-group mb-4" id="policiesList">
        <li class="list-group-item"><i class="fas fa-shield-alt text-success me-2"></i> Pagos seguros con Stripe (3DS cuando aplica)</li>
        <li class="list-group-item"><i class="fas fa-rotate-left text-primary me-2"></i> Cancelación flexible en tours elegibles</li>
        <li class="list-group-item"><i class="fas fa-user-lock text-secondary me-2"></i> Protección de datos personales</li>
      </ul>

      <h4 class="mb-3">¿Necesitas ayuda?</h4>
      <p>
        <i class="fas fa-envelope me-2"></i>
        <a href="mailto:<?= Config::COMPANY_EMAIL ?>"><?= Config::COMPANY_EMAIL ?></a>
        <br>
        <i class="fab fa-whatsapp me-2 text-success"></i>
        <a target="_blank" rel="noopener" href="https://wa.me/<?= preg_replace('/\D+/', '', Config::SOCIAL_WHATSAPP) ?>">WhatsApp</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('faqSearch');
  const acc = document.getElementById('faq');
  const policies = document.getElementById('policiesList');
  const sugg = document.getElementById('faqSuggestions');
  let suggIndex = -1;
  function filter(){
    const q = (input.value || '').toLowerCase();
    // FAQs
    acc.querySelectorAll('.accordion-item').forEach(item=>{
      const txt = item.textContent.toLowerCase();
      item.style.display = txt.includes(q) ? '' : 'none';
    });
    // Policies
    policies.querySelectorAll('.list-group-item').forEach(li=>{
      const txt = li.textContent.toLowerCase();
      li.style.display = txt.includes(q) ? '' : 'none';
    });
    buildSuggestions(q);
  }
  function buildSuggestions(q){
    if (!sugg) return;
    sugg.innerHTML = '';
    if (!q) { sugg.style.display='none'; return; }
    const items = [];
    // Collect FAQ headings
    acc.querySelectorAll('.accordion-item').forEach(item=>{
      const btn = item.querySelector('.accordion-button');
      const panel = item.querySelector('.accordion-collapse');
      if (!btn || !panel) return;
      const text = btn.textContent.trim();
      if (text.toLowerCase().includes(q)) {
        items.push({ text, action: ()=>{ 
          // open the panel and scroll
          const bsCollapse = new bootstrap.Collapse(panel, {toggle:false});
          bsCollapse.show();
          item.scrollIntoView({behavior:'smooth', block:'start'});
        }});
      }
    });
    // Collect policies
    policies.querySelectorAll('.list-group-item').forEach(li=>{
      const text = li.textContent.trim();
      if (text.toLowerCase().includes(q)) {
        items.push({ text, action: ()=>{ li.scrollIntoView({behavior:'smooth', block:'center'}); }});
      }
    });
    // Render top 5
    items.slice(0,5).forEach((it, idx)=>{
      const li = document.createElement('li');
      li.className='list-group-item list-group-item-action';
      li.style.cursor='pointer';
      li.textContent = it.text;
      li.setAttribute('role','option');
      li.dataset.index = idx;
      li.onclick = ()=>{ it.action(); sugg.style.display='none'; };
      sugg.appendChild(li);
    });
    sugg.style.display = items.length ? '' : 'none';
    const expanded = items.length ? 'true' : 'false';
    input.setAttribute('aria-expanded', expanded);
    suggIndex = -1;
  }
  // Debounce helper
  function debounce(fn, wait){
    let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), wait); };
  }
  const debouncedFilter = debounce(filter, 200);
  input?.addEventListener('input', debouncedFilter);
  // Hide suggestions with ESC
  document.addEventListener('keydown', function(e){ if (e.key==='Escape' && sugg){ sugg.style.display='none'; }});
  // Hide suggestions on outside click
  document.addEventListener('click', function(e){
    if (!sugg) return;
    const inside = sugg.contains(e.target) || input.contains(e.target);
    if (!inside) sugg.style.display = 'none';
  });

  // Keyboard navigation for suggestions
  input?.addEventListener('keydown', function(e){
    if (!sugg || sugg.style.display==='none') return;
    const children = Array.from(sugg.children).filter(el=>el.matches('.list-group-item'));
    if (!children.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      suggIndex = (suggIndex + 1) % children.length;
      highlight(children);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      suggIndex = (suggIndex - 1 + children.length) % children.length;
      highlight(children);
    } else if (e.key === 'Enter') {
      if (suggIndex >= 0 && suggIndex < children.length) {
        e.preventDefault();
        children[suggIndex].click();
      }
    }
  });

  function highlight(children){
    children.forEach((el, i)=>{
      if (i === suggIndex) {
        el.classList.add('active');
        el.scrollIntoView({ block: 'nearest' });
      } else {
        el.classList.remove('active');
      }
    });
  }
});
</script>
