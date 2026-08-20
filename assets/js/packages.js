// packages.js - improved, robust
document.addEventListener('DOMContentLoaded', function(){
  // Countdown timers
  document.querySelectorAll('.countdown').forEach(function(el){
    const end = el.dataset.end;
    if(!end) return;
    const endTime = new Date(end).getTime();
    function tick(){
      const now = Date.now();
      const diff = endTime - now;
      if(diff <= 0){ el.textContent = 'Offer ended'; return; }
      const d = Math.floor(diff/86400000);
      const h = Math.floor((diff%86400000)/3600000);
      const m = Math.floor((diff%3600000)/60000);
      const s = Math.floor((diff%60000)/1000);
      el.textContent = (d?d+'d ':'') + h+'h '+m+'m '+s+'s';
    }
    tick(); setInterval(tick,1000);
  });

  // Compare tool
  const compareSet = new Set();
  const compareModal = document.getElementById('compareModal');
  const compareArea = document.getElementById('compareArea');
  document.querySelectorAll('.compare-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      if(compareSet.has(id)){ compareSet.delete(id); this.textContent='Compare'; }
      else { if(compareSet.size<3) { compareSet.add(id); this.textContent='Selected'; } else { alert('Max 3 packages'); } }
      renderCompare();
    });
  });
  function renderCompare(){
    if(compareSet.size===0){ compareModal.setAttribute('aria-hidden','true'); return; }
    const ids = Array.from(compareSet);
    let html = '<div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><tr>';
    ids.forEach(id=>{
      const card = document.querySelector('.package-card[data-id="'+id+'"]');
      if(!card) return;
      const name = card.querySelector('h3').textContent;
      html += '<th style="border:1px solid rgba(255,255,255,0.04);padding:12px;background:rgba(255,255,255,0.02)">'+name+'</th>';
    });
    html += '</tr>';
    // speed row
    html += '<tr>';
    ids.forEach(id=>{
      const card = document.querySelector('.package-card[data-id="'+id+'"]');
      const speed = card.querySelector('.speed').textContent;
      html += '<td style="border:1px solid rgba(255,255,255,0.04);padding:12px">Speed: '+speed+'</td>';
    });
    html += '</tr>';
    // price row
    html += '<tr>';
    ids.forEach(id=>{
      const card = document.querySelector('.package-card[data-id="'+id+'"]');
      const priceEl = card.querySelector('.price-now');
      const price = priceEl ? priceEl.textContent : card.querySelector('.price-now')?.textContent || '';
      html += '<td style="border:1px solid rgba(255,255,255,0.04);padding:12px">Price: '+price+'</td>';
    });
    html += '</tr></table></div>';
    compareArea.innerHTML = html;
    compareModal.setAttribute('aria-hidden','false');
  }
  document.querySelectorAll('.modal-close').forEach(b=>b.addEventListener('click', ()=>compareModal.setAttribute('aria-hidden','true')));

  // Calculator: update total when addons toggled
  document.querySelectorAll('.package-card').forEach(card=>{
    const base = parseFloat(card.querySelector('.add-to-cart').dataset.base) || 0;
    const totalEl = card.querySelector('.calc-total');
    const checkboxes = card.querySelectorAll('.addon-checkbox');
    function recalc(){
      let total = base;
      checkboxes.forEach(cb=>{
        if(cb.checked) total += parseFloat(cb.dataset.price || 0);
      });
      totalEl.textContent = total.toFixed(2);
    }
    checkboxes.forEach(cb=>cb.addEventListener('change', recalc));
    recalc();
  });

  // Add-to-cart redirect with addons
  document.querySelectorAll('.add-to-cart').forEach(btn=>{
    btn.addEventListener('click', function(){
      const packageId = this.dataset.package;
      const card = document.querySelector('.package-card[data-id="'+packageId+'"]');
      const addonIds = Array.from(card.querySelectorAll('.addon-checkbox:checked')).map(i=>i.dataset.id);
      const url = "/pages/checkout.php?package_id="+encodeURIComponent(packageId)+(addonIds.length?('&addons='+encodeURIComponent(addonIds.join(','))):'');
      window.location.href = url;
    });
  });
});
// Availability UX: smooth scroll to results
document.addEventListener('DOMContentLoaded', function(){
  const form = document.querySelector('form[action="/pages/availability.php"], form[action="/pages/availability.php"]');
  if(form){
    form.addEventListener('submit', function(){ setTimeout(()=>{ const el = document.querySelector('.packages-grid, .card'); if(el) el.scrollIntoView({behavior:'smooth'}); }, 300); });
  }
});

// Live search + price slider + speed filter (client-side, instant - no page reload)
document.addEventListener('DOMContentLoaded', function(){
  const searchBox = document.getElementById('pkgSearch');
  const priceSlider = document.getElementById('pkgMaxPrice');
  const priceLabel = document.getElementById('pkgMaxPriceLabel');
  const speedFilter = document.getElementById('pkgSpeedFilter');
  const noResults = document.getElementById('pkgNoResults');
  const cards = Array.from(document.querySelectorAll('.package-card'));
  if (!cards.length) return;

  const maxPrice = Math.max(...cards.map(c => parseFloat(c.dataset.price) || 0), 1000);
  if (priceSlider) { priceSlider.max = Math.ceil(maxPrice / 100) * 100; priceSlider.value = priceSlider.max; }

  function applyFilters(){
    const q = (searchBox?.value || '').trim().toLowerCase();
    const maxP = parseFloat(priceSlider?.value || Infinity);
    const minSpeed = parseFloat(speedFilter?.value || 0);
    let visibleCount = 0;

    cards.forEach(card => {
      const name = card.dataset.name || '';
      const price = parseFloat(card.dataset.price) || 0;
      const speed = parseFloat(card.dataset.speed) || 0;
      const matches = (!q || name.includes(q)) && price <= maxP && speed >= minSpeed;
      card.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    if (priceLabel) priceLabel.textContent = (priceSlider.value == priceSlider.max) ? 'Any' : (priceSlider.value + ' BDT');
    if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
  }

  searchBox?.addEventListener('input', applyFilters);
  priceSlider?.addEventListener('input', applyFilters);
  speedFilter?.addEventListener('change', applyFilters);
  applyFilters();
});
