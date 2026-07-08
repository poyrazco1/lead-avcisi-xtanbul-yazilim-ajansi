const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => [...r.querySelectorAll(s)];
let stopFlag = false;
let lastLeads = [];
let teamMembers = window.LEAD_APP?.teamMembers || [];
let packagePrices = window.LEAD_APP?.packagePrices || {};
const OPT = window.LEAD_APP || {};

function toast(msg, type='ok') {
  const t = $('#toast');
  if(!t) return alert(msg);
  t.textContent = msg;
  t.className = `toast ${type}`;
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => t.classList.add('hidden'), 5200);
}
function moneyFmt(v){return new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY',maximumFractionDigits:0}).format(Number(v||0));}
function escapeHtml(v){return String(v ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function normalizeWa(phone){let d=String(phone||'').replace(/\D+/g,''); if(d.startsWith('0090')) d=d.slice(2); if(d.startsWith('90')) return d; if(d.startsWith('0')) return '90'+d.slice(1); if(d.length===10) return '90'+d; return d;}
function waUrl(phone,msg){return `https://wa.me/${encodeURIComponent(normalizeWa(phone))}?text=${encodeURIComponent(msg||'')}`;}
function uniqueBy(arr, keyFn){const m=new Map(); arr.forEach(x=>{const k=keyFn(x); if(k && !m.has(k)) m.set(k,x);}); return [...m.values()];}
function selectedValues(el){return [...(el?.selectedOptions || [])].map(o=>o.value).filter(Boolean);}
function optionBaseLabel(o){return o?.dataset?.baseLabel || String(o?.textContent || '').replace(/^[✓□]\s+/, '');}
function selectedLabels(el){return [...(el?.selectedOptions || [])].map(o=>({value:o.value,label:optionBaseLabel(o)})).filter(x=>x.value);}
function refreshSelectTicks(el){
  if(!el || !el.multiple) return;
  [...el.options].forEach(o=>{
    if(!o.dataset.baseLabel) o.dataset.baseLabel = String(o.textContent || '').replace(/^[✓□]\s+/, '');
    o.textContent = `${o.selected ? '✓' : '□'} ${o.dataset.baseLabel}`;
  });
  el.classList.toggle('has-selection', selectedValues(el).length > 0);
}
function refreshAllSelectTicks(){['mainCategorySelect','subCategorySelect','microSectorSelect','citySelect','districtSelect'].forEach(id=>refreshSelectTicks($('#'+id)));}
function enableTapMulti(el){
  if(!el || el.dataset.tapMulti==='1') return;
  el.dataset.tapMulti='1';
  const toggle = e => {
    if(e.target && e.target.tagName==='OPTION'){
      e.preventDefault();
      e.target.selected=!e.target.selected;
      refreshSelectTicks(el);
      el.dispatchEvent(new Event('change',{bubbles:true}));
    }
  };
  el.addEventListener('mousedown', toggle);
  el.addEventListener('touchstart', toggle, {passive:false});
}
function deselectValue(selectId, value){
  const el = $('#'+selectId); if(!el) return;
  [...el.options].forEach(o=>{ if(o.value===value) o.selected=false; });
  refreshSelectTicks(el);
  el.dispatchEvent(new Event('change',{bubbles:true}));
}
function splitText(v){return String(v||'').split(/[\n,;]+/).map(x=>x.trim()).filter(Boolean);}
function packagePriceMap(){const m={}; $$('.package-price').forEach(i=>m[i.dataset.package]=i.value.trim()); return m;}
function packagePrice(key){return packagePriceMap()[key] || packagePrices[key] || '';}
function fillSelect(el, items, opts={}) {
  if (!el) return;
  el.innerHTML = '';
  if (opts.placeholder && !el.multiple) el.insertAdjacentHTML('beforeend', `<option value="">${escapeHtml(opts.placeholder)}</option>`);
  items.forEach(i => {
    const value = typeof i === 'object' ? i.value : i;
    const label = typeof i === 'object' ? i.label : i;
    el.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(value)}" data-base-label="${escapeHtml(label)}">${escapeHtml(label)}</option>`);
  });
  refreshSelectTicks(el);
}
function selectOption(el, value){[...(el?.options||[])].forEach(o=>{if(o.value===value)o.selected=true;}); refreshSelectTicks(el);}
function initFilters(){
  const tree = window.SECTOR_TREE || {};
  const mains = Object.keys(tree);
  fillSelect($('#mainCategorySelect'), mains);
  selectOption($('#mainCategorySelect'), 'Teknik Servis & Tamir');
  fillSubCategories();
  const cities = Object.keys(window.TR_LOCATIONS || {}).sort((a,b)=>a.localeCompare(b,'tr'));
  fillSelect($('#citySelect'), cities);
  selectOption($('#citySelect'), 'İstanbul');
  fillDistricts();
  $('#mainCategorySelect')?.addEventListener('change', fillSubCategories);
  $('#subCategorySelect')?.addEventListener('change', fillMicroSectors);
  $('#citySelect')?.addEventListener('change', fillDistricts);
  $('#selectAllDistricts')?.addEventListener('click', () => { $$('#districtSelect option').forEach(o=>o.selected=true); refreshSelectTicks($('#districtSelect')); updateSelectionSummary(); });
  $('#clearDistricts')?.addEventListener('click', () => { $$('#districtSelect option').forEach(o=>o.selected=false); refreshSelectTicks($('#districtSelect')); updateSelectionSummary(); });
  $('#clearSectors')?.addEventListener('click', () => { $$('#mainCategorySelect option,#subCategorySelect option,#microSectorSelect option').forEach(o=>o.selected=false); refreshAllSelectTicks(); fillSubCategories(); updateSelectionSummary(); });
  ['mainCategorySelect','subCategorySelect','microSectorSelect','citySelect','districtSelect'].forEach(id=>enableTapMulti($('#'+id)));
  ['mainCategorySelect','subCategorySelect','microSectorSelect','citySelect','districtSelect','manualDistricts','customKeywords'].forEach(id=>{
    const el = $('#'+id); if(el) ['change','input'].forEach(ev=>el.addEventListener(ev, ()=>{ refreshSelectTicks(el); updateSelectionSummary(); }));
  });
  document.addEventListener('click', e=>{
    const chip = e.target.closest('.select-chip'); if(!chip) return;
    e.preventDefault();
    deselectValue(chip.dataset.selectId, chip.dataset.value);
  });
  updateSelectionSummary();
}
function fillSubCategories(){
  const tree = window.SECTOR_TREE || {};
  const mains = selectedValues($('#mainCategorySelect'));
  const items = [];
  mains.forEach(main => Object.keys(tree[main] || {}).forEach(sub => items.push({value:`${main}||${sub}`, label:`${main} › ${sub}`})));
  fillSelect($('#subCategorySelect'), items);
  fillMicroSectors();
}
function fillMicroSectors(){
  const tree = window.SECTOR_TREE || {};
  const selectedSubs = selectedValues($('#subCategorySelect'));
  const mains = selectedValues($('#mainCategorySelect'));
  const items = [];
  if (selectedSubs.length) {
    selectedSubs.forEach(v => { const [main, sub] = v.split('||'); (tree[main]?.[sub] || []).forEach(micro => items.push({value:`${main}||${sub}||${micro}`, label:`${sub} › ${micro}`})); });
  } else if (mains.length === 1) {
    const main = mains[0]; Object.keys(tree[main] || {}).forEach(sub => (tree[main][sub] || []).forEach(micro => items.push({value:`${main}||${sub}||${micro}`, label:`${sub} › ${micro}`})));
  }
  fillSelect($('#microSectorSelect'), items);
  updateSelectionSummary();
}
function fillDistricts(){
  const cities = selectedValues($('#citySelect'));
  const items = [];
  cities.forEach(city => (window.TR_LOCATIONS[city] || []).forEach(d => items.push({value:`${city}||${d}`, label:`${city} / ${d}`})));
  fillSelect($('#districtSelect'), items);
  updateSelectionSummary();
}
function buildSectorQueries(){
  const tree = window.SECTOR_TREE || {};
  const out = [];
  selectedValues($('#microSectorSelect')).forEach(v => { const [category, sub, micro] = v.split('||'); if (micro) out.push({category, sub_sector:`${sub} / ${micro}`, search_term: micro}); });
  if (!out.length) selectedValues($('#subCategorySelect')).forEach(v => { const [category, sub] = v.split('||'); if (sub) out.push({category, sub_sector: sub, search_term: sub}); });
  if (!out.length) selectedValues($('#mainCategorySelect')).forEach(category => { const subs = Object.keys(tree[category] || {}); if (subs.length) subs.forEach(sub => out.push({category, sub_sector: sub, search_term: sub})); else out.push({category, sub_sector: category, search_term: category}); });
  splitText($('#customKeywords')?.value).forEach(keyword => out.push({category:'Serbest Arama', sub_sector: keyword, search_term: keyword}));
  return uniqueBy(out, x => `${x.search_term}|${x.category}|${x.sub_sector}`);
}
function buildLocations(){
  const selectedCities = selectedValues($('#citySelect'));
  const out = [];
  selectedValues($('#districtSelect')).forEach(v => { const [city, district] = v.split('||'); if (city) out.push({city, district: district || ''}); });
  splitText($('#manualDistricts')?.value).forEach(v => {
    const clean = v.replace(/\s+/g,' ').trim(); if (!clean) return;
    let parts = clean.split(/[\/|>]+/).map(x=>x.trim()).filter(Boolean);
    if (parts.length >= 2) out.push({city: parts[0], district: parts.slice(1).join(' ')});
    else selectedCities.forEach(city => out.push({city, district: clean}));
  });
  if (!out.length) selectedCities.forEach(city => out.push({city, district: ''}));
  return uniqueBy(out, x => `${x.city}|${x.district}`);
}
function chipList(title, selectId, items, empty='Seçim yok'){
  const chips = items.map(x=>`<button type="button" class="select-chip" data-select-id="${escapeHtml(selectId)}" data-value="${escapeHtml(x.value)}" title="Seçimi kaldır">✓ ${escapeHtml(x.label)} <span>×</span></button>`).join('');
  return `<div class="summary-block"><strong>${escapeHtml(title)}</strong><div class="summary-chips">${chips || `<em>${escapeHtml(empty)}</em>`}</div></div>`;
}
function textChipList(title, items, empty='Yok'){
  const chips = items.map(x=>`<span class="text-chip">${escapeHtml(x)}</span>`).join('');
  return `<div class="summary-block"><strong>${escapeHtml(title)}</strong><div class="summary-chips">${chips || `<em>${escapeHtml(empty)}</em>`}</div></div>`;
}
function updateSelectionSummary(){
  const q = buildSectorQueries(); const loc = buildLocations(); const el = $('#selectionSummary'); if (!el) return;
  const combos = q.length * loc.length;
  const mains = selectedLabels($('#mainCategorySelect'));
  const subs = selectedLabels($('#subCategorySelect'));
  const micros = selectedLabels($('#microSectorSelect'));
  const cities = selectedLabels($('#citySelect'));
  const districts = selectedLabels($('#districtSelect'));
  const keywords = splitText($('#customKeywords')?.value);
  const manual = splitText($('#manualDistricts')?.value);
  el.innerHTML = `
    <div class="summary-count"><b>${q.length}</b> arama kelimesi × <b>${loc.length}</b> bölge = <b>${combos}</b> sorgu kombinasyonu</div>
    <div class="summary-grid">
      ${chipList('Ana kategoriler', 'mainCategorySelect', mains, 'Ana kategori seçilmedi')}
      ${chipList('Alt kategoriler', 'subCategorySelect', subs, 'Alt kategori seçilmedi')}
      ${chipList('Alt alt kategori / meslek', 'microSectorSelect', micros, 'Meslek seçilmedi')}
      ${chipList('Şehirler', 'citySelect', cities, 'Şehir seçilmedi')}
      ${chipList('İlçeler', 'districtSelect', districts, 'İlçe seçilmedi; şehir geneli aranır')}
      ${textChipList('Serbest kelimeler', keywords, 'Yok')}
      ${textChipList('Manuel bölgeler', manual, 'Yok')}
    </div>
    <div class="summary-preview"><b>Aranacak ilk 10 kelime:</b> ${(q.slice(0,10).map(x=>`<span>${escapeHtml(x.search_term)}</span>`).join('') || '<em>Serbest kelime veya kategori seç.</em>')} ${q.length>10?`<small>+${q.length-10} daha</small>`:''}</div>
  `;
}
async function api(url, payload=null){
  const opt = payload ? {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({...payload, csrf: window.LEAD_APP.csrf})} : {credentials:'same-origin',headers:{'Accept':'application/json'}};
  const res = await fetch(url, opt); const txt = await res.text(); let data;
  try { data = JSON.parse(txt); } catch(e) { const raw = (txt || '').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,300); throw new Error(`Sunucu JSON yerine okunamayan cevap verdi. HTTP ${res.status}. ${raw || 'Boş cevap'}`); }
  if(!res.ok || data.ok===false) throw new Error(data.error || `İşlem başarısız. HTTP ${res.status}`);
  return data;
}
function setProgress(p, msg){ $('#progressBox')?.classList.remove('hidden'); $('#progressBar').style.width = `${Math.max(0,Math.min(100,p))}%`; $('#progressText').textContent = msg; }
async function startSearch(){
  stopFlag = false;
  const queries = buildSectorQueries(); const locations = buildLocations(); const targetLimit = Math.min(5000, Math.max(10, parseInt($('#limitInput').value || '100',10)));
  const packageType = $('#packageSelect').value; const price = packagePrice(packageType); const payment = $('#paymentInput').value.trim(); const assigned_to = '';
  const consulting = $$('.consulting-check:checked').map(x=>x.value); const multiLang = $('#multiLangCheck').checked; const package_prices = packagePriceMap();
  if(!queries.length) return toast('En az bir kategori/alt kategori seç veya serbest arama kelimesi yaz.', 'bad');
  if(!locations.length || locations.some(x=>!x.city)) return toast('En az bir şehir seç. Manuel ilçede şehir/ilçe formatı da kullanabilirsin.', 'bad');
  $('#startSearch').classList.add('hidden'); $('#stopSearch').classList.remove('hidden');
  let savedTotal=0, duplicateTotal=0, checked=0, skippedWebsite=0, skippedPhone=0, blacklisted=0, done=0; const totalJobs = queries.length * locations.length;
  try{
    outer: for (const q of queries) for (const loc of locations) {
      if(stopFlag || savedTotal >= targetLimit) break outer;
      done++; const remaining = Math.max(1, targetLimit - savedTotal); const perRequestLimit = Math.min(80, remaining); const region = [loc.district, loc.city].filter(Boolean).join(' / ');
      setProgress((done-1)/totalJobs*100, `${q.search_term} × ${region} aranıyor... Kaydedilen: ${savedTotal}`);
      const data = await api('api/search.php', {category:q.category,sub_sector:q.sub_sector,search_term:q.search_term,custom_keyword:'',city:loc.city,district:loc.district,limit:perRequestLimit,price,payment,package:packageType,consulting,multi_lang:multiLang,assigned_to,package_prices});
      savedTotal += data.saved || 0; duplicateTotal += data.duplicates || 0; checked += data.checked || 0; skippedWebsite += data.skippedWebsite || 0; skippedPhone += data.skippedPhone || 0; blacklisted += data.blacklisted || 0;
      setProgress(done/totalJobs*100, `${q.search_term} × ${region} bitti. Yeni: ${savedTotal}, tekrar: ${duplicateTotal}, kara liste: ${blacklisted}, web sitesi var diye elenen: ${skippedWebsite}`);
      await loadLeads(false);
    }
    toast(stopFlag ? 'Arama durduruldu.' : `Tarama bitti. Yeni lead: ${savedTotal}, tekrar: ${duplicateTotal}, kontrol edilen: ${checked}, kara liste: ${blacklisted}`, 'ok');
  }catch(err){ toast(err.message, 'bad'); setProgress(100, 'Hata: '+err.message); }
  finally{ $('#startSearch').classList.remove('hidden'); $('#stopSearch').classList.add('hidden'); setTimeout(()=>$('#progressBox').classList.add('hidden'), 3000); await loadLeads(false); await refreshStats(); }
}
async function refreshStats(){
  try{
    const d=await api('api/stats.php'); const s=d.stats;
    const set=(id,v)=>{const el=$('#'+id); if(el) el.textContent=v;};
    set('statTotal',s.total); set('statToday',s.today); set('statNew',s.new); set('statOffer',s.offer);
    set('statPayment',s.payment||0); set('statCustomer',s.customer||0); set('statConversion',(s.conversion||0)+'%'); set('statDb',s.db);
    set('statRevenue',moneyFmt(s.revenue_total||0)); set('statPaid',moneyFmt(s.revenue_paid||0)); set('statPendingRevenue',moneyFmt(s.revenue_pending||0));
    set('statContracts',s.contracts||0); set('statActiveOrders',s.active_orders||0); set('statOverdueDelivery',s.overdue_delivery||0); set('statRenewalDue',s.renewal_due||0);
    $('#statusBreakdown').innerHTML = (s.by_status||[]).map(x=>`<span><b>${escapeHtml(x.status)}</b>${escapeHtml(x.c)}</span>`).join('') || '<p class="muted">Henüz veri yok.</p>';
    $('#topSectors').innerHTML = (s.top_sectors||[]).map((x,i)=>`<div><b>${i+1}. ${escapeHtml(x.sector||'-')}</b><span>${escapeHtml(x.c)} lead ${x.avg_score?`· skor ${Math.round(x.avg_score)}`:''}</span></div>`).join('') || '<p class="muted">Henüz veri yok.</p>';
  }catch(e){}
}

/* ============ Lead listesi + filtreler ============ */
function currentFilters(){
  return {
    q: $('#quickSearch')?.value || $('#topSearch')?.value || '',
    status: $('#statusFilter')?.value || '',
    min_score: $('#scoreFilter')?.value || '',
    city: $('#cityFilter')?.value || '',
    sector: $('#sectorFilter')?.value || '',
    assigned: $('#assignedFilter')?.value || '',
    priority: $('#priorityFilter')?.value || '',
    website: $('#websiteFilter')?.value || '',
    wa: $('#waFilter')?.value || '',
    offer: $('#offerFilter')?.value || '',
    follow: $('#followFilter')?.value || ''
  };
}
function applyClientFilters(rows, f){
  const today = new Date().toISOString().slice(0,10);
  return rows.filter(r=>{
    if(f.priority && (r.priority||'') !== f.priority) return false;
    if(f.website==='no' && (r.website||'').trim()!=='') return false;
    if(f.website==='yes' && (r.website||'').trim()==='') return false;
    if(f.wa==='sent' && !(Number(r.message_count||0)>0)) return false;
    if(f.wa==='not' && Number(r.message_count||0)>0) return false;
    if(f.offer==='sent' && !(Number(r.offer_sent||0)>0)) return false;
    if(f.offer==='not' && Number(r.offer_sent||0)>0) return false;
    if(f.follow){ const nf=(r.next_followup_at||'').slice(0,10); if(f.follow==='today' && nf!==today) return false; if(f.follow==='overdue' && !(nf && nf<today)) return false; }
    return true;
  });
}
const FILTER_LABELS = {status:'Durum',min_score:'Skor',city:'Şehir',sector:'Sektör',priority:'Öncelik',assigned:'Temsilci',website:'Web',wa:'WhatsApp',offer:'Teklif',follow:'Takip'};
const FILTER_VALTXT = {min_score:{'80':'80+ sıcak','60':'60+ orta'},website:{no:'Site yok',yes:'Site var'},wa:{sent:'Gönderildi',not:'Gönderilmedi'},offer:{sent:'Teklif var',not:'Teklif yok'},follow:{today:'Bugün',overdue:'Gecikmiş'}};
function renderFilterChips(f){
  const box=$('#filterChips'); if(!box) return;
  const chips=[];
  Object.keys(FILTER_LABELS).forEach(k=>{ const v=f[k]; if(v){ const txt=(FILTER_VALTXT[k]&&FILTER_VALTXT[k][v])||v; chips.push(`<span class="chip">${escapeHtml(FILTER_LABELS[k])}: ${escapeHtml(txt)} <span class="x" data-clear="${k}">×</span></span>`); } });
  box.innerHTML = chips.join('');
}
async function loadLeads(showToast=false){
  const f = currentFilters();
  const qs = new URLSearchParams({q:f.q,status:f.status,min_score:f.min_score,city:f.city,sector:f.sector,assigned_to:f.assigned});
  const d = await api(`api/leads.php?${qs.toString()}`);
  let rows = d.leads || [];
  rows = applyClientFilters(rows, f);
  lastLeads = rows; renderLeads(rows);
  const c=$('#leadCount'); if(c) c.textContent = `${rows.length} kayıt`;
  renderFilterChips(f);
  if(showToast) toast(`${rows.length} kayıt listelendi.`);
}
function scoreClass(score){score=Number(score||0); return score>=80?'hot':score>=60?'warm':'cold';}
function prioClass(p){return 'prio-'+String(p||'Ilık').toLowerCase().replace(/ç/g,'c').replace(/ı/g,'i').replace(/\s+/g,'-');}
function digitalCell(r){ const has=(r.website||'').trim()!==''; const top = has ? escapeHtml(r.website_quality||'Site var') : 'Site yok'; const sub = r.rating ? `★${escapeHtml(r.rating)}${r.review_count?` · ${escapeHtml(r.review_count)}`:''}` : ''; return `<span class="cell-sub" style="color:${has?'var(--ink-soft)':'var(--ok)'};font-weight:600">${top}</span>${sub?`<span class="cell-sub">${sub}</span>`:''}`; }
function actionGroup(r){
  return `<div class="action-group">
    <button class="icon-btn wa" title="WhatsApp gönder" onclick="sendWhatsApp(${Number(r.id)},'first')">💬</button>
    <button class="icon-btn detail" title="Detay" onclick="openDetail(${Number(r.id)})">👁</button>
    <button class="icon-btn more" title="Diğer işlemler" onclick="openFloatMenu(this,${Number(r.id)},event)">⋯</button>
  </div>`;
}
let floatMenuEl=null;
function closeFloatMenu(){ if(floatMenuEl){ floatMenuEl.remove(); floatMenuEl=null; } }
function openFloatMenu(btn, id, ev){
  ev.stopPropagation();
  if(floatMenuEl){ closeFloatMenu(); return; }
  const r = lastLeads.find(x=>Number(x.id)===Number(id)) || {};
  const items = [
    ['Ara (script)', `openCall(${id})`],
    ['Teklif mesajı', `sendWhatsApp(${id},'detail')`],
    ['Ödeme mesajı', `sendWhatsApp(${id},'payment')`],
    ['Takip mesajı', `sendWhatsApp(${id},'followup')`],
    ['Takip linki', `sendWhatsApp(${id},'tracking')`],
    ['Gönderildi işaretle', `markSentManual(${id})`],
  ];
  const links = [
    ['Sözleşme', `contract.php?lead_id=${id}`],
    ['Teklif (A4)', `quote.php?id=${id}`],
  ];
  if(r.maps_url) links.push(['Haritada aç', r.maps_url]);
  const menu = document.createElement('div');
  menu.className='float-menu';
  menu.innerHTML = items.map(([t,fn])=>`<button type="button" onclick="closeFloatMenu();${fn}">${escapeHtml(t)}</button>`).join('') +
    links.map(([t,href])=>`<a href="${escapeHtml(href)}" target="_blank" onclick="closeFloatMenu()">${escapeHtml(t)}</a>`).join('');
  document.body.appendChild(menu);
  const rect = btn.getBoundingClientRect();
  const mw = 190; let left = rect.right - mw; if(left<8) left=8;
  let top = rect.bottom + 6;
  menu.style.left = left+'px'; menu.style.top = top+'px'; menu.style.minWidth = mw+'px';
  // Alt tarafta yer yoksa yukarı aç
  const mh = menu.offsetHeight;
  if(top + mh > window.innerHeight - 8){ menu.style.top = Math.max(8, rect.top - mh - 6)+'px'; }
  floatMenuEl = menu;
}
document.addEventListener('click', closeFloatMenu);
window.addEventListener('scroll', closeFloatMenu, true);
function renderLeads(rows){
  const tbody = $('#leadRows'); const cards = $('#mobileCards'); tbody.innerHTML=''; cards.innerHTML='';
  if(!rows.length){ tbody.innerHTML='<tr><td colspan="8"><div class="empty-state"><div class="ico">📭</div>Kayıt yok. Filtreleri temizleyin ya da yeni lead toplayın.</div></td></tr>'; cards.innerHTML='<div class="empty-state"><div class="ico">📭</div>Kayıt yok.</div>'; return; }
  rows.forEach(r => {
    const sectorText = [r.sector, r.sub_sector].filter(Boolean).join(' / '); const regionText = [r.district, r.city].filter(Boolean).join(' / ');
    const contactName = r.contact_name ? `<span class="cell-sub">${escapeHtml(r.contact_name)}${r.contact_position?' · '+escapeHtml(r.contact_position):''}</span>` : (r.district?`<span class="cell-sub">${escapeHtml(r.district)}</span>`:'');
    const lastContact = (r.last_contact_at||'').slice(0,10);
    const follow = (r.next_followup_at||'').slice(0,10);
    const email = r.email ? `<span class="cell-sub" title="${escapeHtml(r.email)}">✉ ${escapeHtml(r.email)}</span>` : '';
    tbody.insertAdjacentHTML('beforeend', `<tr>
      <td><span class="score-badge ${scoreClass(r.lead_score)}">${escapeHtml(r.lead_score||0)}</span></td>
      <td class="cell-firma"><span class="biz-name" title="${escapeHtml(r.name)}">${escapeHtml(r.name)}</span>${contactName}<span class="badge ${prioClass(r.priority)}">${escapeHtml(r.priority||'Ilık')}</span></td>
      <td class="cell-contact"><a href="tel:${escapeHtml(r.phone)}" title="${escapeHtml(r.phone||'')}">${escapeHtml(r.phone||'—')}</a><span class="sub-ico">${r.phone?'<span class="wa-mini">WA</span>':''}${email}</span></td>
      <td><span class="cell-sub" title="${escapeHtml(sectorText)}" style="color:var(--ink-soft);font-weight:600">${escapeHtml(sectorText||'—')}</span><span class="cell-sub">${escapeHtml(regionText)}</span></td>
      <td>${digitalCell(r)}</td>
      <td>${statusSelect(r.id, r.status)}</td>
      <td><span class="cell-sub" title="Son temas">${lastContact||'—'}</span><span class="cell-sub" title="Sonraki takip" style="color:${follow?'var(--warn)':'var(--muted)'}">${follow?'→ '+follow:''}</span></td>
      <td>${actionGroup(r)}</td>
    </tr>`);
    cards.insertAdjacentHTML('beforeend', `<article class="lead-card">
      <div class="card-top"><h3>${escapeHtml(r.name)}</h3><div class="lc-badges"><span class="badge ${prioClass(r.priority)}">${escapeHtml(r.priority||'Ilık')}</span><span class="score-badge ${scoreClass(r.lead_score)}">${escapeHtml(r.lead_score||0)}</span></div></div>
      <p class="lc-contact"><a href="tel:${escapeHtml(r.phone)}">${escapeHtml(r.phone||'—')}</a>${r.email?' · '+escapeHtml(r.email):''}</p>
      <p>${escapeHtml(sectorText)} · ${escapeHtml(regionText)}</p>
      <p class="muted">${digitalStatus(r)}${lastContact?' · son temas '+lastContact:''}${follow?' · takip '+follow:''}</p>
      <div class="card-controls">${statusSelect(r.id, r.status)}${actionGroup(r)}</div>
    </article>`);
  });
}
function digitalStatus(r){ const has=(r.website||'').trim()!==''; return has?(r.website_quality||'Site var'):'Site yok'; }
function statusSelect(id, val){
  const statuses = OPT.statuses || ['Aranmadı','WhatsApp gönderildi'];
  return `<select class="status-select" onclick="event.stopPropagation()" onchange="updateLead(${Number(id)}, {status:this.value})">${statuses.map(s=>`<option ${s===(val||'Aranmadı')?'selected':''}>${escapeHtml(s)}</option>`).join('')}</select>`;
}
async function updateLead(id, fields, silent){ try{await api('api/update.php',{id,...fields}); if(!silent) toast('Güncellendi.'); await loadLeads(false); await refreshStats();} catch(e){toast(e.message,'bad');} }

/* ============ WhatsApp gönderimi (sağlam) ============ */
function waToast(type){
  const m={first:'WhatsApp açıldı ve lead “WhatsApp gönderildi” olarak işaretlendi.',detail:'Teklif mesajı açıldı ve işaretlendi.',payment:'Ödeme mesajı açıldı, durum “Ödeme linki gönderildi” yapıldı.',followup:'Takip mesajı açıldı ve kaydedildi.',tracking:'Takip linki açıldı ve kaydedildi.',contract:'Sözleşme mesajı açıldı.'};
  return m[type]||'WhatsApp mesajı gönderildi olarak işaretlendi.';
}
async function sendWhatsApp(id, type='first'){
  // Popup engeline takılmamak için pencereyi hemen (kullanıcı hareketiyle) aç
  const win = window.open('', '_blank');
  try{
    const d = await api(`api/message.php?id=${encodeURIComponent(id)}&type=${encodeURIComponent(type)}`);
    const url = d.whatsapp_url;
    if(win && !win.closed){
      win.location = url;
      toast(waToast(type), 'ok');
    } else {
      try{ await navigator.clipboard.writeText(d.message||''); }catch(e){}
      toast('WhatsApp açılamadı, mesaj panoya kopyalandı. WhatsApp’ı açıp yapıştırabilirsiniz.', 'warn');
    }
    // Sunucuda gönderildi olarak işaretle (durum + activity + message_count)
    await api('api/whatsapp.php', {id, type});
    await loadLeads(false); await refreshStats();
    if(detailLeadId===Number(id)) await loadActivities(id);
  }catch(e){ if(win && !win.closed) win.close(); toast(e.message,'bad'); }
}
async function markSentManual(id, type='manual'){ try{ await api('api/whatsapp.php',{id,type}); toast('Lead gönderildi olarak işaretlendi.','ok'); await loadLeads(false); await refreshStats(); if(detailLeadId===Number(id)) await loadActivities(id);}catch(e){toast(e.message,'bad');} }

/* ============ Arama modalı ============ */
let activeCallLeadId = null;
async function openCall(id){
  try{
    const d = await api(`api/message.php?id=${encodeURIComponent(id)}&type=call`);
    activeCallLeadId = id;
    const lead = lastLeads.find(x=>Number(x.id)===Number(id)) || {};
    $('#callTitle').textContent = `${lead.name || 'İşletme'} — Arama Scripti`;
    $('#callSubtitle').textContent = 'Konuşmayı kısa tut: problem → rakip avantajı → paket → ödeme adımı.';
    $('#callMeta').innerHTML = `<span><b>Telefon:</b> ${escapeHtml(d.phone || lead.phone || '')}</span><span><b>Sektör:</b> ${escapeHtml([lead.sector, lead.sub_sector].filter(Boolean).join(' / ') || '-')}</span><span><b>Paket:</b> ${escapeHtml(lead.package_type || 'onepage')}</span>`;
    $('#callScriptText').value = d.message || '';
    $('#callPhoneLink').href = d.call_url || `tel:${escapeHtml(lead.phone || '')}`;
    $('#callModal').classList.remove('hidden');
  }catch(e){toast(e.message,'bad');}
}
function closeCallModal(){ $('#callModal')?.classList.add('hidden'); activeCallLeadId = null; }
async function copyCallScript(){ const txt = $('#callScriptText')?.value || ''; if(!txt) return; await navigator.clipboard.writeText(txt); toast('Arama metni kopyalandı.'); }
async function markCallStatus(status){ if(!activeCallLeadId) return; await updateLead(activeCallLeadId, {status}); if(status === 'Tekrar aranmasın') closeCallModal(); }
async function startActualCall(){ if(activeCallLeadId){ try{await api('api/activities.php',{lead_id:activeCallLeadId,type:'call',title:'Telefonla arandı'}); await updateLead(activeCallLeadId,{status:'Arandı'},true);}catch(e){} } }

/* ============ Lead detay modalı (sekmeli CRM) ============ */
let detailLeadId = null;
const DETAIL_TABS = [
  {key:'genel', label:'Genel Bilgiler'},
  {key:'dijital', label:'Dijital Analiz'},
  {key:'ihtiyac', label:'İhtiyaçlar'},
  {key:'satis', label:'Satış & Teklif'},
  {key:'gecmis', label:'İletişim Geçmişi'},
  {key:'sozlesme', label:'Sözleşme & Ödeme'},
  {key:'notlar', label:'Notlar'}
];
function fld(label, name, val, type='text', opts){
  val = val==null?'':val;
  if(type==='select'){
    const options=(opts||[]).map(o=>`<option ${String(o)===String(val)?'selected':''}>${escapeHtml(o)}</option>`).join('');
    return `<div class="field"><label>${escapeHtml(label)}</label><select data-field="${name}"><option value=""></option>${options}</select></div>`;
  }
  if(type==='textarea'){ return `<div class="field full"><label>${escapeHtml(label)}</label><textarea data-field="${name}" rows="3">${escapeHtml(val)}</textarea></div>`; }
  if(type==='readonly'){ return `<div class="field"><label>${escapeHtml(label)}</label><input value="${escapeHtml(val)}" readonly></div>`; }
  return `<div class="field"><label>${escapeHtml(label)}</label><input type="${type}" data-field="${name}" value="${escapeHtml(val)}"></div>`;
}
function chk(label, name, val){ return `<label><input type="checkbox" data-field="${name}" ${Number(val)?'checked':''}> ${escapeHtml(label)}</label>`; }
function buildDetailPane(key, r){
  if(key==='genel') return `<div class="detail-grid">
    ${fld('Firma adı','name',r.name)}
    ${fld('Yetkili kişi','contact_name',r.contact_name)}
    ${fld('Yetkili pozisyonu','contact_position',r.contact_position)}
    ${fld('Öncelik','priority',r.priority,'select',OPT.priorities)}
    ${fld('Telefon','phone',r.phone,'tel')}
    ${fld('WhatsApp telefonu','whatsapp_phone',r.whatsapp_phone,'tel')}
    ${fld('E-posta','email',r.email,'email')}
    ${fld('Satış temsilcisi','assigned_to',r.assigned_to,'select',OPT.teamMembers)}
    ${fld('Sektör','sector',r.sector)}
    ${fld('Alt sektör','sub_sector',r.sub_sector)}
    ${fld('Şehir','city',r.city)}
    ${fld('İlçe','district',r.district)}
    ${fld('Mahalle','neighborhood',r.neighborhood)}
    ${fld('Kaynak','source','','readonly')}
    ${fld('Açık adres','address',r.address,'textarea')}
    ${fld('Google Maps linki','maps_url',r.maps_url,'textarea')}
  </div>`;
  if(key==='dijital') return `<div class="detail-grid">
    ${fld('Web sitesi URL','website',r.website)}
    ${fld('Web sitesi kalitesi','website_quality',r.website_quality,'select',OPT.websiteQualities)}
    ${fld('Instagram','instagram',r.instagram)}
    ${fld('Facebook','facebook',r.facebook)}
    ${fld('Google puanı','rating',r.rating,'number')}
    ${fld('Yorum sayısı','review_count',r.review_count,'number')}
    ${fld('Rakip yoğunluğu','competitor_density',r.competitor_density,'select',OPT.competitorDensities)}
    ${fld('Müşteri olma ihtimali (%)','close_probability',r.close_probability,'number')}
    ${fld('Potansiyel skor','lead_score',r.lead_score,'readonly')}
    ${fld('Skor nedeni','score_reason',r.score_reason,'readonly')}
  </div>`;
  if(key==='ihtiyac') return `<div class="detail-grid">
    ${fld('İstenen hizmet','requested_service',r.requested_service,'select',OPT.services)}
    </div><div class="check-grid" style="margin-top:12px">
    ${chk('Domain var','has_domain',r.has_domain)}
    ${chk('Hosting var','has_hosting',r.has_hosting)}
    ${chk('Logo var','has_logo',r.has_logo)}
    ${chk('Fotoğraf/görsel var','has_photos',r.has_photos)}
    ${chk('İçerik hazır','has_content',r.has_content)}
    ${chk('Çok dil gerekli','need_multilang',r.need_multilang)}
    ${chk('Randevu sistemi','need_appointment',r.need_appointment)}
    ${chk('Online ödeme','need_online_payment',r.need_online_payment)}
    ${chk('Blog / haber','need_blog',r.need_blog)}
    ${chk('Referans / galeri','need_gallery',r.need_gallery)}
  </div>`;
  if(key==='satis') return `<div class="detail-grid">
    ${fld('Paket türü','package_type',packageLabelOf(r.package_type),'readonly')}
    ${fld('Tahmini teklif tutarı','estimated_amount',r.estimated_amount,'number')}
    ${fld('Net teklif tutarı','net_amount',r.net_amount,'number')}
    ${fld('İndirim','discount_amount',r.discount_amount,'number')}
    ${fld('Kapora tutarı','deposit_amount',r.deposit_amount,'number')}
    ${fld('Sipariş tutarı (ciro)','order_amount',r.order_amount,'number')}
    ${fld('Alınan ödeme','amount_paid',r.amount_paid,'number')}
    ${fld('Ödeme durumu','payment_status',r.payment_status,'select',OPT.paymentStatuses)}
    ${fld('Teklif gönderim tarihi','offer_sent_at',(r.offer_sent_at||'').slice(0,10),'date')}
    ${fld('Sonraki takip tarihi','next_followup_at',(r.next_followup_at||'').slice(0,10),'date')}
    ${fld('Son iletişim','last_contact_at',(r.last_contact_at||'').slice(0,16).replace('T',' '),'readonly')}
  </div>`;
  if(key==='gecmis') return `<div class="activity-add"><input id="activityNote" placeholder="Not / görüşme ekle..."><button class="btn primary" onclick="addActivity()">Ekle</button></div><div id="activityTimeline" class="activity-timeline"><div class="loading-row">Yükleniyor...</div></div>`;
  if(key==='sozlesme') return `<div class="detail-grid">
    ${fld('Sözleşme durumu','contract_status',r.contract_status)}
    ${fld('Sipariş durumu','order_status',r.order_status,'select',OPT.orderStatuses)}
    ${fld('Başlangıç','start_date',(r.start_date||'').slice(0,10),'date')}
    ${fld('Tahmini teslim','estimated_delivery_date',(r.estimated_delivery_date||'').slice(0,10),'date')}
    ${fld('Gerçek teslim','actual_delivery_date',(r.actual_delivery_date||'').slice(0,10),'date')}
    ${fld('Domain adı','domain_name',r.domain_name)}
    ${fld('Domain bitiş','domain_expiry_date',(r.domain_expiry_date||'').slice(0,10),'date')}
    ${fld('Hosting bitiş','hosting_expiry_date',(r.hosting_expiry_date||'').slice(0,10),'date')}
    ${fld('Yenileme ücreti','renewal_fee',r.renewal_fee,'number')}
    <div class="field full"><label>İşlemler</label><div class="row-actions"><a class="btn secondary mini" href="contract.php?lead_id=${Number(r.id)}" target="_blank">Sözleşme aç</a><a class="btn ghost mini" href="quote.php?id=${Number(r.id)}" target="_blank">Teklif (A4)</a></div></div>
  </div>`;
  if(key==='notlar') return `<div class="detail-grid">${fld('Notlar','note',r.note,'textarea')}</div>`;
  return '';
}
function packageLabelOf(key){ return (OPT.packages||{})[key] || key || ''; }
async function openDetail(id){
  const r = lastLeads.find(x=>Number(x.id)===Number(id));
  if(!r) return toast('Lead bulunamadı.','bad');
  detailLeadId = Number(id);
  $('#detailTitle').textContent = r.name || 'Lead Detayı';
  $('#detailSub').textContent = [r.sector,r.sub_sector].filter(Boolean).join(' / ') + ' · ' + [r.district,r.city].filter(Boolean).join(' / ');
  $('#detailTabs').innerHTML = DETAIL_TABS.map((t,i)=>`<button type="button" class="tab-btn ${i===0?'active':''}" data-tab="${t.key}" onclick="switchTab('${t.key}')">${escapeHtml(t.label)}</button>`).join('');
  $('#detailBody').innerHTML = DETAIL_TABS.map((t,i)=>`<div class="tab-pane ${i===0?'active':''}" data-pane="${t.key}">${buildDetailPane(t.key, r)}</div>`).join('');
  $('#detailModal').classList.remove('hidden');
}
function switchTab(key){
  $$('#detailTabs .tab-btn').forEach(b=>b.classList.toggle('active', b.dataset.tab===key));
  $$('#detailBody .tab-pane').forEach(p=>p.classList.toggle('active', p.dataset.pane===key));
  if(key==='gecmis' && detailLeadId) loadActivities(detailLeadId);
}
function closeDetail(){ $('#detailModal')?.classList.add('hidden'); detailLeadId=null; }
async function saveDetail(){
  if(!detailLeadId) return;
  const fields = {};
  $$('#detailBody [data-field]').forEach(el=>{
    if(el.hasAttribute('readonly')) return;
    if(el.type==='checkbox') fields[el.dataset.field] = el.checked ? 1 : 0;
    else fields[el.dataset.field] = el.value;
  });
  try{ await api('api/update.php',{id:detailLeadId, ...fields}); toast('Lead güncellendi.','ok'); await loadLeads(false); await refreshStats(); closeDetail(); }
  catch(e){ toast(e.message,'bad'); }
}
async function loadActivities(id){
  const box = $('#activityTimeline'); if(!box) return;
  try{
    const d = await api(`api/activities.php?lead_id=${encodeURIComponent(id)}`);
    const acts = d.activities || [];
    if(!acts.length){ box.innerHTML='<div class="empty-state"><div class="ico">🕔</div>Henüz iletişim kaydı yok.</div>'; return; }
    const icon={whatsapp:'💬',status_change:'🔄',call:'📞',note:'📝',payment:'💳',contract:'📄',offer:'📌'};
    box.innerHTML = acts.map(a=>`<div class="activity-item"><div class="activity-dot ${escapeHtml(a.type)}">${icon[a.type]||'•'}</div><div class="activity-body"><b>${escapeHtml(a.title||a.type)}</b>${a.message?`<p>${escapeHtml(a.message)}</p>`:''}<time>${escapeHtml((a.created_at||'').replace('T',' '))} · ${escapeHtml(a.created_by||'')}</time></div></div>`).join('');
  }catch(e){ box.innerHTML='<div class="empty-state">Geçmiş yüklenemedi.</div>'; }
}
async function addActivity(){
  if(!detailLeadId) return;
  const inp = $('#activityNote'); const msg=(inp?.value||'').trim(); if(!msg) return;
  try{ await api('api/activities.php',{lead_id:detailLeadId,type:'note',title:'Not eklendi',message:msg}); inp.value=''; await loadActivities(detailLeadId); toast('Not eklendi.','ok'); }
  catch(e){ toast(e.message,'bad'); }
}

/* ============ CSV import ============ */
async function importCsv(e){
  e.preventDefault();
  const file = $('#csvFile')?.files?.[0];
  if(!file) return toast('CSV dosyası seç.', 'bad');
  const fd = new FormData(e.currentTarget);
  fd.append('csrf', window.LEAD_APP.csrf);
  const res = await fetch('api/import.php', {method:'POST', credentials:'same-origin', body:fd, headers:{'Accept':'application/json'}});
  const txt = await res.text(); let data;
  try { data = JSON.parse(txt); } catch(err) { return toast('İçe aktarma sunucudan okunamayan cevap aldı: '+txt.slice(0,180),'bad'); }
  if(!res.ok || data.ok===false) return toast(data.error || 'CSV içe aktarma başarısız.', 'bad');
  const box = $('#importResult');
  box.classList.remove('hidden');
  box.innerHTML = `<b>İçe aktarma tamamlandı.</b><span>Yeni: ${data.saved || 0}</span><span>Tekrar: ${data.duplicates || 0}</span><span>Kara liste: ${data.blacklisted || 0}</span><span>Hatalı satır: ${data.failed || 0}</span><small>${escapeHtml((data.warnings || []).slice(0,5).join(' | '))}</small>`;
  toast(`CSV içe alındı. Yeni: ${data.saved || 0}, tekrar: ${data.duplicates || 0}`, 'ok');
  await loadLeads(false); await refreshStats();
}

/* ============ Ayarlar ============ */
async function saveSettings(){
  try{
    const google_api_key = $('#googleKeyInput')?.value || '';
    const payment_settings = {iban:$('#ibanInput')?.value||'', account_holder:$('#holderInput')?.value||'', bank_name:$('#bankInput')?.value||'', payment_note:$('#paymentNoteInput')?.value||''};
    const operation_settings = {default_revision_limit:$('#revisionLimitInput')?.value||2, default_delivery_days:$('#deliveryDaysInput')?.value||7, renewal_warning_days:$('#renewalWarnInput')?.value||60};
    const d = await api('api/settings.php', {package_prices: packagePriceMap(), google_api_key, payment_settings, operation_settings});
    packagePrices = d.package_prices || packagePriceMap(); if(d.team_members) teamMembers=d.team_members; toast('Ayarlar kaydedildi.','ok');
  }catch(e){toast(e.message,'bad');}
}
async function saveTemplates(){
  try{
    const message_templates = {};
    $$('#templateGrid [data-template]').forEach(t=>{ message_templates[t.dataset.template] = t.value; });
    await api('api/settings.php', {message_templates});
    toast('Mesaj şablonları kaydedildi.','ok');
  }catch(e){toast(e.message,'bad');}
}
async function loadSettings(){
  try{const d=await api('api/settings.php'); const ps=d.payment_settings||{}, os=d.operation_settings||{};
    if($('#ibanInput')) $('#ibanInput').value=ps.iban||''; if($('#holderInput')) $('#holderInput').value=ps.account_holder||'Xtanbul Yazılım Agent'; if($('#bankInput')) $('#bankInput').value=ps.bank_name||''; if($('#paymentNoteInput')) $('#paymentNoteInput').value=ps.payment_note||'';
    if($('#revisionLimitInput')) $('#revisionLimitInput').value=os.default_revision_limit||2; if($('#deliveryDaysInput')) $('#deliveryDaysInput').value=os.default_delivery_days||7; if($('#renewalWarnInput')) $('#renewalWarnInput').value=os.renewal_warning_days||60;
  }catch(e){}
}
async function changePassword(){
  try{const d=await api('api/account.php',{action:'password',email:$('#newAdminEmail')?.value||'',current_password:$('#currentPassword')?.value||'',new_password:$('#newPassword')?.value||''}); if(d.ok) toast('Admin bilgisi güncellendi.','ok'); else toast(d.error||'Güncellenemedi','bad');}
  catch(e){toast(e.message,'bad');}
}

/* ============ Navigasyon / sidebar ============ */
function activatePanel(hash){
  const target = hash && document.querySelector(hash) ? hash : '#dashboardPanel';
  $$('.panel-section').forEach(sec=>sec.classList.toggle('active', '#'+sec.id === target));
  $$('[data-panel-nav]').forEach(a=>a.classList.toggle('active', a.getAttribute('href') === target));
  if(location.hash !== target) history.replaceState(null,'',target);
  closeSidebar();
  window.scrollTo({top:0,behavior:'smooth'});
}
function initPanelNavigation(){
  $$('[data-panel-nav]').forEach(a=>a.addEventListener('click', e=>{ e.preventDefault(); activatePanel(a.getAttribute('href')); }));
  $$('[data-jump]').forEach(b=>b.addEventListener('click', ()=>activatePanel(b.dataset.jump)));
  activatePanel(location.hash || '#dashboardPanel');
}
function openSidebar(){ document.body.classList.add('sidebar-open'); }
function closeSidebar(){ document.body.classList.remove('sidebar-open'); }

window.updateLead=updateLead; window.openDetail=openDetail; window.closeDetail=closeDetail; window.switchTab=switchTab; window.sendWhatsApp=sendWhatsApp; window.markSentManual=markSentManual; window.openCall=openCall; window.closeCallModal=closeCallModal; window.openFloatMenu=openFloatMenu; window.closeFloatMenu=closeFloatMenu; window.addActivity=addActivity;

document.addEventListener('DOMContentLoaded',()=>{
  initPanelNavigation();
  initFilters();
  $('#startSearch')?.addEventListener('click', startSearch);
  $('#stopSearch')?.addEventListener('click', ()=>{ stopFlag=true; });
  $('#refreshLeads')?.addEventListener('click', ()=>loadLeads(true));
  $('#refreshStats')?.addEventListener('click', refreshStats);
  $('#refreshStatsTop')?.addEventListener('click', refreshStats);
  $('#saveSettings')?.addEventListener('click', saveSettings);
  $('#saveTemplates')?.addEventListener('click', saveTemplates);
  $('#changePasswordBtn')?.addEventListener('click', changePassword);
  $('#detailSave')?.addEventListener('click', saveDetail);
  loadSettings();
  $('#csvImportForm')?.addEventListener('submit', importCsv);
  $('#clearImportResult')?.addEventListener('click', ()=>$('#importResult')?.classList.add('hidden'));
  $('#copyCallScript')?.addEventListener('click', copyCallScript);
  $('#callPhoneLink')?.addEventListener('click', startActualCall);
  $('#markCalledBtn')?.addEventListener('click', ()=>markCallStatus('Arandı'));
  $('#markOfferBtn')?.addEventListener('click', ()=>markCallStatus('Teklif istedi'));
  $('#markNoCallBtn')?.addEventListener('click', ()=>markCallStatus('Tekrar aranmasın'));
  $('#callModal')?.addEventListener('click', (e)=>{ if(e.target.id==='callModal') closeCallModal(); });
  $('#detailModal')?.addEventListener('click', (e)=>{ if(e.target.id==='detailModal') closeDetail(); });
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ closeCallModal(); closeDetail(); closeSidebar(); } });
  // filtreler
  const reload=()=>loadLeads(false);
  $('#quickSearch')?.addEventListener('input', ()=>{clearTimeout(window.__q); window.__q=setTimeout(reload,300);});
  $('#topSearch')?.addEventListener('input', ()=>{clearTimeout(window.__q2); window.__q2=setTimeout(reload,300);});
  ['statusFilter','scoreFilter','cityFilter','sectorFilter','priorityFilter','assignedFilter','websiteFilter','waFilter','offerFilter','followFilter'].forEach(id=>{ const el=$('#'+id); if(el) el.addEventListener(el.tagName==='INPUT'?'input':'change', ()=>{clearTimeout(window.__qf); window.__qf=setTimeout(reload,250);}); });
  const FILTER_IDS = ['statusFilter','scoreFilter','cityFilter','sectorFilter','priorityFilter','assignedFilter','websiteFilter','waFilter','offerFilter','followFilter','quickSearch'];
  $('#clearFilters')?.addEventListener('click', ()=>{ FILTER_IDS.forEach(id=>{const el=$('#'+id); if(el) el.value='';}); reload(); });
  // Gelişmiş filtre aç/kapa
  const CHIP_TO_ID = {status:'statusFilter',min_score:'scoreFilter',city:'cityFilter',sector:'sectorFilter',priority:'priorityFilter',assigned:'assignedFilter',website:'websiteFilter',wa:'waFilter',offer:'offerFilter',follow:'followFilter'};
  $('#toggleAdvanced')?.addEventListener('click', ()=>{ const a=$('#advancedFilters'); if(a){ const open=a.classList.toggle('hidden'); $('#toggleAdvanced').textContent = 'Gelişmiş Filtreler '+(open?'▾':'▴'); } });
  $('#filterChips')?.addEventListener('click', e=>{ const x=e.target.closest('[data-clear]'); if(!x) return; const id=CHIP_TO_ID[x.dataset.clear]; const el=id&&$('#'+id); if(el){ el.value=''; reload(); } });
  // sidebar drawer
  $('#sidebarToggle')?.addEventListener('click', openSidebar);
  $('#sidebarOverlay')?.addEventListener('click', closeSidebar);
  refreshStats();
  loadLeads(false).catch(e=>toast(e.message,'bad'));
});
