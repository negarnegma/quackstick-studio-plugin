/* Admin-only: builds the print PDFs in the admin's browser from stored designs. Nothing here runs on the storefront. */
(function(){
const $=s=>document.querySelector(s),D=window.QSP,Q=window.QS;
if(!D||!$('#qs-print'))return;
Q.fileUrl=f=>D.fileBase+encodeURIComponent(f);
$('#qsp-sheet').value=D.sheet||'a4';
let items=[];
const log=t=>{$('#qsp-msg').textContent=t};
const stamp=()=>new Date().toISOString().slice(0,16).replace(/[-:T]/g,'');
const save=(blob,name)=>{const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();a.remove()};
const esc=t=>String(t).replace(/[<>&]/g,''),lab=()=>D.orders.map(o=>'#'+o.num).join(' ');
const PS={pending:'منتظر تأیید مشتری',changes:'مشتری تغییر خواسته'};
async function prepare(){
  await document.fonts.load('40px Lalezar','قات‌قات Aa');
  const cache=new Map(),fails=[],warns=new Set();let n=0;items=[];$('#qsp-retry').style.display='none';$('#qsp-warn').innerHTML='';
  const total=D.orders.reduce((a,o)=>a+o.items.length,0),ras=async(sp,cm)=>{const k=JSON.stringify(sp)+'|'+cm;if(!cache.has(k))cache.set(k,await Q.rasterize(sp,cm));return cache.get(k)};
  D.orders.forEach(o=>{if(o.proof&&PS[o.proof])warns.add('سفارش #'+o.num+': '+PS[o.proof]+'. چاپ قبل از تأیید ریسک داره.')});
  for(const o of D.orders)for(const it of o.items){const d=it.design,dpiw=(x,st)=>{if(x.srcDpi&&x.srcDpi<150)warns.add('سفارش #'+o.num+' ('+esc(it.name)+'): عکس برای '+st.s+' سانت کیفیت پایینی داره (حدود '+Math.round(x.srcDpi)+' dpi؛ حداقل 150 توصیه می‌شه).')};
    try{
      if(d.type==='sheet'){const rs=[];for(const st of d.stickers){const x=await ras(st.sp,st.s);dpiw(x,st);rs.push(x)}const si=Q.sheetItem(d,rs);for(let q=0;q<it.qty;q++)items.push(si)}
      else for(const st of d.stickers){const x=await ras(st.sp,st.s);dpiw(x,st);const sq=Q.squareItem(x,st.s),cnt=Math.max(1,st.n||1)*it.qty;for(let q=0;q<cnt;q++)items.push(sq)}
    }catch(e){fails.push('سفارش #'+o.num+' ('+esc(it.name)+'): '+esc(e&&e.message||e))}
    log('در حال آماده‌سازی... '+(++n)+'/'+total)}
  log('');drawSheets();
  let h='';if(fails.length)h+='<div class="notice notice-error inline"><p><strong>'+fails.length+' مورد ساخته نشد، برای همین دانلود PDF غیرفعاله تا چیزی از سفارش جا نیفته:</strong></p><ul style="list-style:disc;margin-inline-start:20px">'+[...new Set(fails)].map(t=>'<li>'+t+'</li>').join('')+'</ul><p>معمولاً یعنی فایل عکس پاک شده یا سایت با آدرس دیگه‌ای (www / http / https) باز شده. دوباره امتحان کن.</p></div>';
  if(warns.size)h+='<div class="notice notice-warning inline"><ul style="list-style:disc;margin-inline-start:20px">'+[...warns].map(t=>'<li>'+t+'</li>').join('')+'</ul></div>';
  $('#qsp-warn').innerHTML=h;
  if(fails.length){$('#qsp-retry').style.display='';return}
  ['#qsp-pdf','#qsp-pro','#qsp-mark'].forEach(s=>$(s).disabled=false);
  if(D.orders.some(o=>o.items.some(i=>i.design.type==='case')))$('#qsp-guide').disabled=false}
function drawSheets(){
  const L=Q.layout(items,$('#qsp-sheet').value),k=2,box=$('#qsp-sheets');box.innerHTML='';
  for(let p=0;p<L.pages;p++){const c=document.createElement('canvas');c.width=L.PW*k;c.height=L.PH*k;const x=c.getContext('2d');
    x.fillStyle='#fff';x.fillRect(0,0,c.width,c.height);
    L.out.filter(i=>i.pg===p).forEach(i=>{x.drawImage(i.c,i.x*k,i.y*k,i.w*k,i.h*k);
      i.cuts.forEach(cu=>{x.beginPath();cu.forEach((v,j)=>{const X=(i.x+v[0])*k,Y=(i.y+v[1])*k;j?x.lineTo(X,Y):x.moveTo(X,Y)});x.closePath();x.strokeStyle='#f0f';x.lineWidth=1;x.stroke()});
      if(i.trim){x.strokeStyle='#000';x.lineWidth=.5;x.strokeRect(i.x*k,i.y*k,i.w*k,i.h*k)}});
    c.style.cssText='border:1px solid #bbb;margin:8px;max-width:100%;height:auto;background:#fff';box.appendChild(c)}
  $('#qsp-info strong').textContent=L.pages+' ورق، '+items.length+' قطعه'}
$('#qsp-sheet').onchange=drawSheets;
$('#qsp-pdf').onclick=()=>{const {blob}=Q.buildPdf(items,{sheet:$('#qsp-sheet').value,label:lab()});save(blob,'quackstick-print-'+stamp()+'.pdf')};
$('#qsp-pro').onclick=async()=>{log('در حال ساخت PDF حرفه‌ای...');try{const {blob}=await Q.buildProPdf(items,{sheet:$('#qsp-sheet').value,label:lab()});save(blob,'quackstick-pro-'+stamp()+'.pdf');log('')}catch(e){log('خطا: '+(e&&e.message||e))}};
$('#qsp-guide').onclick=async()=>{
  log('در حال ساخت راهنما...');
  const doc=new window.jspdf.jsPDF({unit:'mm',format:'a4'});let first=true;
  for(const o of D.orders)for(const it of o.items){const d=it.design;if(d.type!=='case')continue;
    const ps=await Promise.all(d.stickers.map(s=>Q.rasterize(s.sp,s.s,150))),W=1240,H=1754,c=document.createElement('canvas');c.width=W;c.height=H;const x=c.getContext('2d');
    x.fillStyle='#fff';x.fillRect(0,0,W,H);x.fillStyle='#3A2817';x.direction='rtl';x.textAlign='right';x.font='44px Lalezar,sans-serif';
    x.fillText('سفارش #'+o.num+' | '+Q.PHONES[d.model][0]+' | '+it.qty+' عدد',W-60,90);
    x.font='28px Lalezar,sans-serif';x.fillText('اندازه‌ی واقعی (۱:۱). کادر نارنجی خط‌چین = جای دوربین',W-60,140);
    const cc=Q.drawCase(d,150/25.4,ps,true);x.drawImage(cc,(W-cc.width)/2,200);
    if(!first)doc.addPage();first=false;doc.addImage(c.toDataURL('image/jpeg',.92),'JPEG',0,0,210,297)}
  if(first){log('سفارش قاب پیدا نشد.');return}
  save(doc.output('blob'),'quackstick-guide-'+stamp()+'.pdf');log('')};
$('#qsp-mark').onclick=async()=>{const fd=new FormData();fd.append('action','qs_mark');fd.append('nonce',D.nonce);fd.append('ids',D.orders.map(o=>o.id).join(','));
  const r=await fetch(D.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(x=>x.json());log(r.success?'ثبت شد.':'ثبت ناموفق بود.')};
$('#qsp-retry').onclick=()=>prepare().catch(e=>log('خطا: '+(e&&e.message||e)));
prepare().catch(e=>log('خطا: '+(e&&e.message||e)));
})();
