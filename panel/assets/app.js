const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => [...r.querySelectorAll(s)];
let stopFlag = false;
let lastLeads = [];
let teamMembers = [];
let packagePrices = window.LEAD_APP?.packagePrices || {};

function toast(msg, type='ok') {
  const t = $('#toast');
  if(!t) return alert(msg);
  t.textContent = msg;
  t.className = `toast ${type}`;
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => t.classList.add('hidden'), 5200);
}
function moneyFmt(v){return new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(Number(v||0));}
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
    $('#statTotal').textContent=s.total; $('#statToday').textContent=s.today; $('#statNew').textContent=s.new; $('#statOffer').textContent=s.offer; $('#statPayment').textContent=s.payment||0; $('#statCustomer').textContent=s.customer||0; $('#statConversion').textContent=(s.conversion||0)+'%'; $('#statDb').textContent=s.db; if($('#statRevenue')) $('#statRevenue').textContent=moneyFmt(s.revenue_total||0); if($('#statPaid')) $('#statPaid').textContent=moneyFmt(s.revenue_paid||0); if($('#statPendingRevenue')) $('#statPendingRevenue').textContent=moneyFmt(s.revenue_pending||0); if($('#statContracts')) $('#statContracts').textContent=s.contracts||0; if($('#statActiveOrders')) $('#statActiveOrders').textContent=s.active_orders||0; if($('#statOverdueDelivery')) $('#statOverdueDelivery').textContent=s.overdue_delivery||0; if($('#statRenewalDue')) $('#statRenewalDue').textContent=s.renewal_due||0;
    $('#statusBreakdown').innerHTML = (s.by_status||[]).map(x=>`<span><b>${escapeHtml(x.status)}</b>${escapeHtml(x.c)}</span>`).join('') || '<p class="muted">Henüz veri yok.</p>';
    $('#topSectors').innerHTML = (s.top_sectors||[]).map((x,i)=>`<div><b>${i+1}. ${escapeHtml(x.sector||'-')}</b><span>${escapeHtml(x.c)} lead ${x.avg_score?`· skor ${Math.round(x.avg_score)}`:''}</span></div>`).join('') || '<p class="muted">Henüz veri yok.</p>';
  }catch(e){}
}
async function loadLeads(showToast=false){
  const q = $('#quickSearch')?.value || ''; const status = $('#statusFilter')?.value || ''; const minScore = $('#scoreFilter')?.value || '';
  const d = await api(`api/leads.php?q=${encodeURIComponent(q)}&status=${encodeURIComponent(status)}&min_score=${encodeURIComponent(minScore)}`);
  lastLeads = d.leads || []; renderLeads(lastLeads); if(showToast) toast(`${lastLeads.length} kayıt listelendi.`);
}
function scoreClass(score){score=Number(score||0); return score>=80?'hot':score>=60?'warm':'cold';}
function renderLeads(rows){
  const tbody = $('#leadRows'); const cards = $('#mobileCards'); tbody.innerHTML=''; cards.innerHTML='';
  if(!rows.length){ tbody.innerHTML='<tr><td colspan="7" class="muted">Kayıt yok.</td></tr>'; cards.innerHTML='<div class="lead-card"><p>Kayıt yok.</p></div>'; return; }
  rows.forEach(r => {
    const msg = r.whatsapp_message || buildMessage(r); const wa = waUrl(r.phone, msg); const maps = r.maps_url ? `<a class="btn ghost mini" target="_blank" href="${escapeHtml(r.maps_url)}">Harita</a>` : '';
    const sectorText = [r.sector, r.sub_sector].filter(Boolean).join(' / '); const regionText = [r.district, r.city].filter(Boolean).join(' / ');
    tbody.insertAdjacentHTML('beforeend', `<tr>
      <td><span class="score ${scoreClass(r.lead_score)}">${escapeHtml(r.lead_score||0)}</span><small>${escapeHtml(r.score_reason||'')}</small></td>
      <td><span class="biz-name">${escapeHtml(r.name)}</span><span class="muted">${escapeHtml(r.address||'')}</span></td>
      <td>${escapeHtml(sectorText)}</td><td>${escapeHtml(regionText)}</td><td><a href="tel:${escapeHtml(r.phone)}">${escapeHtml(r.phone)}</a></td>
      <td>${statusSelect(r.id, r.status)}</td>
      <td><div class="row-actions"><button class="btn call mini" onclick="openCall(${Number(r.id)})">Ara</button><a class="btn primary mini" target="_blank" onclick="markSent(${Number(r.id)})" href="${wa}">İlk WA</a><button class="btn secondary mini" onclick="openMessage(${Number(r.id)},'detail')">Detay</button><button class="btn secondary mini" onclick="openMessage(${Number(r.id)},'payment')">Ödeme</button><button class="btn ghost mini" onclick="openMessage(${Number(r.id)},'followup')">Takip</button><button class="btn ghost mini" onclick="openMessage(${Number(r.id)},'tracking')">Takip Linki</button>${maps}<a class="btn ghost mini" target="_blank" href="contract.php?lead_id=${Number(r.id)}">Sözleşme</a><a class="btn ghost mini" target="_blank" href="quote.php?id=${Number(r.id)}">Teklif</a></div><textarea class="note-input" placeholder="Not" onblur="updateNote(${Number(r.id)}, this.value)">${escapeHtml(r.note||'')}</textarea></td>
    </tr>`);
    cards.insertAdjacentHTML('beforeend', `<article class="lead-card"><div class="card-top"><h3>${escapeHtml(r.name)}</h3><span class="score ${scoreClass(r.lead_score)}">${escapeHtml(r.lead_score||0)}</span></div><p>${escapeHtml(sectorText)} · ${escapeHtml(regionText)}</p><p><a href="tel:${escapeHtml(r.phone)}">${escapeHtml(r.phone)}</a></p><p>${escapeHtml(r.address||'')}</p><small>${escapeHtml(r.score_reason||'')}</small><div class="row-actions"><button class="btn call mini" onclick="openCall(${Number(r.id)})">Ara</button><a class="btn primary mini" target="_blank" onclick="markSent(${Number(r.id)})" href="${wa}">İlk WA</a><button class="btn secondary mini" onclick="openMessage(${Number(r.id)},'detail')">Detay</button><button class="btn secondary mini" onclick="openMessage(${Number(r.id)},'payment')">Ödeme</button><button class="btn ghost mini" onclick="openMessage(${Number(r.id)},'followup')">Takip</button><button class="btn ghost mini" onclick="openMessage(${Number(r.id)},'tracking')">Takip Linki</button><a class="btn ghost mini" target="_blank" href="contract.php?lead_id=${Number(r.id)}">Sözleşme</a><a class="btn ghost mini" target="_blank" href="quote.php?id=${Number(r.id)}">Teklif</a>${maps}</div><div class="card-controls">${statusSelect(r.id, r.status)}</div><textarea class="note-input" placeholder="Not" onblur="updateNote(${Number(r.id)}, this.value)">${escapeHtml(r.note||'')}</textarea></article>`);
  });
}
function buildMessage(r){return `Merhaba, ${r.name} için yazıyorum. Google’da işletmenizi gördüm; telefon numaranız var ama web siteniz görünmüyor.\n\nSektörünüzde güçlü rakipler hizmet açıklamaları, güven veren görseller, Google Harita/konum, tek tık WhatsApp ve hızlı teklif/randevu alanları kullanıyor.\n\nBaşlangıç teklifimiz tek sayfalık HTML web sitesi için geçerlidir. En düşük paketimiz 4.999 TL’den başlıyor.\n\nUygun görürseniz bugün size sektörünüze uygun 2 örnek tasarım ve paket karşılaştırması göndereyim.\n\nİstemiyorsanız “istemiyorum” yazmanız yeterli, tekrar rahatsız etmeyiz.`;}
function statusSelect(id, val){
  const statuses=['Aranmadı','WhatsApp gönderildi','Arandı','Cevap bekleniyor','Teklif istedi','Ödeme linki gönderildi','Ödeme bekleniyor','Kapora alındı','Müşteri oldu','İlgilenmedi','Tekrar aranmasın','Sipariş oluşturuldu','Tasarım hazırlanıyor','Yayında','Tamamlandı'];
  return `<select class="status-select" onchange="updateLead(${Number(id)}, {status:this.value})">${statuses.map(s=>`<option ${s===(val||'Aranmadı')?'selected':''}>${escapeHtml(s)}</option>`).join('')}</select>`;
}
async function updateLead(id, fields){ try{await api('api/update.php',{id,...fields}); toast('Güncellendi.'); await refreshStats();} catch(e){toast(e.message,'bad');} }
async function updateNote(id, note){ await updateLead(id, {note}); }
async function markSent(id){ try{await api('api/update.php',{id,status:'WhatsApp gönderildi'}); await refreshStats();}catch(e){} }
async function markCalled(id){ try{await api('api/update.php',{id,status:'Arandı'}); await refreshStats(); await loadLeads(false);}catch(e){toast(e.message,'bad');} }
async function openMessage(id, type){
  try{const d=await api(`api/message.php?id=${encodeURIComponent(id)}&type=${encodeURIComponent(type)}`); window.open(d.whatsapp_url, '_blank'); if(type==='payment') await updateLead(id,{status:'Ödeme linki gönderildi'});}
  catch(e){toast(e.message,'bad');}
}

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
async function copyCallScript(){
  const txt = $('#callScriptText')?.value || '';
  if(!txt) return;
  await navigator.clipboard.writeText(txt);
  toast('Arama metni kopyalandı.');
}
async function markCallStatus(status){
  if(!activeCallLeadId) return;
  await updateLead(activeCallLeadId, {status});
  if(status === 'Tekrar aranmasın') closeCallModal();
}
async function startActualCall(){
  if(activeCallLeadId) await markCalled(activeCallLeadId);
}
async function importCsv(e){
  e.preventDefault();
  const file = $('#csvFile')?.files?.[0];
  if(!file) return toast('CSV dosyası seç.', 'bad');
  const fd = new FormData(e.currentTarget);
  fd.append('csrf', window.LEAD_APP.csrf);
  const res = await fetch('api/import.php', {method:'POST', credentials:'same-origin', body:fd, headers:{'Accept':'application/json'}});
  const txt = await res.text(); let data;
  try { data = JSON.parse(txt); } catch(err) { throw new Error('İçe aktarma sunucudan okunamayan cevap aldı: '+txt.slice(0,250)); }
  if(!res.ok || data.ok===false) return toast(data.error || 'CSV içe aktarma başarısız.', 'bad');
  const box = $('#importResult');
  box.classList.remove('hidden');
  box.innerHTML = `<b>İçe aktarma tamamlandı.</b><span>Yeni: ${data.saved || 0}</span><span>Tekrar: ${data.duplicates || 0}</span><span>Kara liste: ${data.blacklisted || 0}</span><span>Hatalı satır: ${data.failed || 0}</span><small>${escapeHtml((data.warnings || []).slice(0,5).join(' | '))}</small>`;
  toast(`CSV içe alındı. Yeni: ${data.saved || 0}, tekrar: ${data.duplicates || 0}`, 'ok');
  await loadLeads(false); await refreshStats();
}

async function copyMsg(id){ const r = lastLeads.find(x=>Number(x.id)===Number(id)); if(!r) return; const msg = r.whatsapp_message || buildMessage(r); await navigator.clipboard.writeText(msg); toast('Mesaj kopyalandı.'); }
async function saveSettings(){
  try{
    const google_api_key = $('#googleKeyInput')?.value || '';
    const payment_settings = {iban:$('#ibanInput')?.value||'', account_holder:$('#holderInput')?.value||'', bank_name:$('#bankInput')?.value||'', payment_note:$('#paymentNoteInput')?.value||''};
    const operation_settings = {default_revision_limit:$('#revisionLimitInput')?.value||2, default_delivery_days:$('#deliveryDaysInput')?.value||7, renewal_warning_days:$('#renewalWarnInput')?.value||60};
    const d = await api('api/settings.php', {package_prices: packagePriceMap(), google_api_key, payment_settings, operation_settings});
    packagePrices = d.package_prices || packagePriceMap(); if(d.team_members) teamMembers=d.team_members; toast('Ayarlar kaydedildi.');
  }catch(e){toast(e.message,'bad');}
}
async function loadSettings(){
  try{const d=await api('api/settings.php'); const ps=d.payment_settings||{}, os=d.operation_settings||{}; if($('#ibanInput')) $('#ibanInput').value=ps.iban||''; if($('#holderInput')) $('#holderInput').value=ps.account_holder||'Xtanbul Yazılım Agent'; if($('#bankInput')) $('#bankInput').value=ps.bank_name||''; if($('#paymentNoteInput')) $('#paymentNoteInput').value=ps.payment_note||''; if($('#revisionLimitInput')) $('#revisionLimitInput').value=os.default_revision_limit||2; if($('#deliveryDaysInput')) $('#deliveryDaysInput').value=os.default_delivery_days||7; if($('#renewalWarnInput')) $('#renewalWarnInput').value=os.renewal_warning_days||60;}catch(e){}
}
async function changePassword(){
  try{const d=await api('api/account.php',{action:'password',email:$('#newAdminEmail')?.value||'',current_password:$('#currentPassword')?.value||'',new_password:$('#newPassword')?.value||''}); if(d.ok) toast('Admin bilgisi güncellendi.'); else toast(d.error||'Güncellenemedi','bad');}
  catch(e){toast(e.message,'bad');}
}
window.updateLead=updateLead; window.updateNote=updateNote; window.openMessage=openMessage; window.openCall=openCall; window.closeCallModal=closeCallModal; window.copyMsg=copyMsg; window.markSent=markSent; window.markCalled=markCalled;


function activatePanel(hash){
  const target = hash && document.querySelector(hash) ? hash : '#dashboardPanel';
  $$('.panel-section').forEach(sec=>sec.classList.toggle('active', '#'+sec.id === target));
  $$('[data-panel-nav]').forEach(a=>a.classList.toggle('active', a.getAttribute('href') === target));
  if(location.hash !== target) history.replaceState(null,'',target);
  window.scrollTo({top:0,behavior:'smooth'});
}
function initPanelNavigation(){
  $$('[data-panel-nav]').forEach(a=>a.addEventListener('click', e=>{ e.preventDefault(); activatePanel(a.getAttribute('href')); }));
  $$('[data-jump]').forEach(b=>b.addEventListener('click', ()=>activatePanel(b.dataset.jump)));
  activatePanel(location.hash || '#dashboardPanel');
}

document.addEventListener('DOMContentLoaded',()=>{
  initPanelNavigation();
  initFilters();
  $('#startSearch')?.addEventListener('click', startSearch);
  $('#stopSearch')?.addEventListener('click', ()=>{ stopFlag=true; });
  $('#refreshLeads')?.addEventListener('click', ()=>loadLeads(true));
  $('#refreshStats')?.addEventListener('click', refreshStats);
  $('#refreshStatsTop')?.addEventListener('click', refreshStats);
  $('#saveSettings')?.addEventListener('click', saveSettings);
  $('#changePasswordBtn')?.addEventListener('click', changePassword);
  loadSettings();
  $('#csvImportForm')?.addEventListener('submit', importCsv);
  $('#clearImportResult')?.addEventListener('click', ()=>$('#importResult')?.classList.add('hidden'));
  $('#copyCallScript')?.addEventListener('click', copyCallScript);
  $('#callPhoneLink')?.addEventListener('click', startActualCall);
  $('#markCalledBtn')?.addEventListener('click', ()=>markCallStatus('Arandı'));
  $('#markOfferBtn')?.addEventListener('click', ()=>markCallStatus('Teklif istedi'));
  $('#markNoCallBtn')?.addEventListener('click', ()=>markCallStatus('Tekrar aranmasın'));
  $('#callModal')?.addEventListener('click', (e)=>{ if(e.target.id==='callModal') closeCallModal(); });
  $('#quickSearch')?.addEventListener('input', ()=>{clearTimeout(window.__q); window.__q=setTimeout(()=>loadLeads(false),300);});
  $('#statusFilter')?.addEventListener('change', ()=>loadLeads(false));
  $('#scoreFilter')?.addEventListener('change', ()=>loadLeads(false));
  refreshStats();
  loadLeads(false).catch(e=>toast(e.message,'bad'));
});
