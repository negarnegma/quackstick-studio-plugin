/* QuackStick storefront designer. Needs QS (qs-core.js) and window.QSD (inline, from PHP). */
(function(){
if(!document.querySelector('.qs-app'))return;
const $=s=>document.querySelector(s),{duck,ruler,INK,PHONES}=QS;
const QSE=(t,d)=>document.dispatchEvent(new CustomEvent('qs:event',{detail:Object.assign({type:t},d||{})}));
const fmt=n=>new Intl.NumberFormat('fa-IR').format(n)+' '+QSD.cur;
const PRE=[
 {n:'سر کوَک‌کوَکلو',o:{body:'#FFD23F',acc:'crown',bg:'#CDEBFF'},p:3.5},
 {n:'اردک شیک‌پوش',o:{body:'#8FD694',acc:'top',bg:'#FFE3C2',shape:'square'},p:3.5},
 {n:'اردک کول',o:{body:'#FFD23F',acc:'glasses',bg:'#FFB3C7',cap:'خیلی کولم'},p:3.5},
 {n:'جوجه‌اردک کلاه‌بافتنی',o:{body:'#FFF1A8',acc:'beanie',bg:'#BFE6FF',shape:'blob'},p:3.5},
 {n:'اردک ببیونی',o:{body:'#FFFFFF',acc:'bow',bg:'#FFD23F',cap:'حمله کوَک‌کوَک'},p:3.5},
 {n:'اردک کلاسیک',o:{body:'#FFD23F',acc:'none',bg:'#fff',shape:'blob'},p:3.5}];
/* Studio */
const S={size:8,shape:'circle',body:'#FFD23F',acc:'none',bg:'#CDEBFF',cap:'',unit:'cm',tru:false,ref:'none'};
const PRICE=QSD.sizes, STAGE=12, REF={coin:[2.4,2.4,'سکه 2.4cm'],card:[8.56,5.4,'کارت بانکی']};
const OPT={
 size:Object.keys(QSD.sizes).map(k=>[+k,k+' سانت']),
 shape:[['circle','گرد'],['square','مربع'],['blob','برش آزاد']],
 acc:[['none','هیچ'],['crown','تاج'],['top','کلاه سیلندر'],['glasses','عینک'],['bow','ببیون'],['beanie','کلاه بافتنی']],
 unit:[['cm','سانتی‌متر'],['in','اینچ']],
 ref:[['none','هیچ‌چی'],['coin','سکه'],['card','کارت بانکی']]};
const SW={body:['#FFD23F','#FFFFFF','#8FD694','#FF9EB5','#7CC5FF'],bg:['#CDEBFF','#FFE3C2','#FFB3C7','#FFD23F','#D8C7FF','#FFFFFF']};
if(!(S.size in PRICE))S.size=+Object.keys(PRICE)[0];
function chips(k){
  const el=$('#c-'+k);
  el.innerHTML=SW[k]?SW[k].map(v=>'<button class="sw" style="background:'+v+'" data-k="'+k+'" data-v="'+v+'" aria-label="'+k+' '+v+'" aria-pressed="'+(S[k]===v)+'"></button>').join('')
   :OPT[k].map(([v,l])=>'<button class="chip" data-k="'+k+'" data-v="'+v+'" aria-pressed="'+(String(S[k])===String(v))+'">'+l+'</button>').join('');
}
function render(){
  OPT.model=Object.keys(PH).filter(k=>(S.surface==='sheet')===/^a\d/.test(k)).map(k=>[k,PH[k][0]]);OPT.surface=[['case','قاب گوشی'],['sheet','ورق استیکر']];
  ['size','shape','body','acc','bg','unit','ref','model','case','surface'].forEach(chips);
  const sh=S.surface==='sheet';$('#h-model').textContent=sh?'اندازه ورق':'گوشی تو';['#h-case','#c-case'].forEach(s=>$(s).style.display=sh?'none':'');$('#autoarr').style.display=sh?'':'none';$('#paddc').textContent=sh?'افزودن ورق به سبد':'افزودن قاب به سبد';
  $('#c-true').setAttribute('aria-pressed',S.tru);
  const ppc=S.tru?96/2.54:26, W=STAGE*ppc, sp=S.size*ppc, off=(W-sp)/2;
  const unitPx=S.unit==='cm'?ppc:ppc*2.54, subs=S.unit==='cm'?10:8;
  const o={body:S.body,acc:S.acc,bg:S.bg,shape:S.shape,cap:S.cap};
  let ghost='';
  if(S.ref!=='none'){const [w,h,l]=REF[S.ref];ghost='<div class="ghost" style="left:'+W*0.03+'px;bottom:'+W*0.03+'px;width:'+w*ppc+'px;height:'+h*ppc+'px;'+(S.ref==='coin'?'border-radius:50%':'')+'">'+l+'</div>';}
  const ref=S.ref!=='none'?REF[S.ref]:null;
  $('#rg').innerHTML='<div class="rk"></div><div class="rl2">'+ruler(true,W,unitPx,subs,off,off+sp)+'</div><div>'+ruler(false,W,unitPx,subs,off,off+sp)+'</div>'
   +'<div class="stage" style="width:'+W+'px;height:'+W+'px;background-image:linear-gradient(rgba(58,40,23,.22) 1px,transparent 1px),linear-gradient(90deg,rgba(58,40,23,.22) 1px,transparent 1px);background-size:'+unitPx+'px '+unitPx+'px;background-color:var(--card)">'
   +'<div class="st" style="position:absolute;left:'+off+'px;top:'+off+'px;width:'+sp+'px;height:'+sp+'px;filter:drop-shadow(2px 3px 0 rgba(0,0,0,.25))">'+duck(o).replace('<svg ','<svg width="100%" height="100%" ')+'</div>'+ghost+'</div>';
  const inch=(S.size/2.54).toFixed(2);
  $('#read').textContent=S.size+' × '+S.size+' سانت ('+inch+' × '+inch+' اینچ)'+(ref?' · حدود '+(S.size/ref[0]).toFixed(1)+' برابر عرض '+(S.ref==='coin'?'سکه':'کارت بانکی'):'');
  $('#price').textContent=fmt(PRICE[S.size]);drawTray();srul();$('#c-unit2').innerHTML=$('#c-unit').innerHTML;$('#c-unit3').innerHTML=$('#c-unit').innerHTML;urul();uChips();
}
document.addEventListener('click',e=>{const t=e.target.closest('[data-k]');if(!t)return;const k=t.dataset.k,v=t.dataset.v;S[k]=k==='size'?+v:v;if(k==='surface')S.model=v==='sheet'?'a6':'i16';render();if(k=='model'||k=='case'||k=='unit'||k=='surface')renderCase()});
$('#c-true').onclick=()=>{S.tru=!S.tru;render()};
$('#cap').oninput=e=>{S.cap=e.target.value;if(S.cap.length===4)QSE('caption');render()};
$('#addc').onclick=()=>{const o={body:S.body,acc:S.acc,bg:S.bg,shape:S.shape,cap:S.cap};qsSubmit({type:'sticker',stickers:[{sp:{k:'duck',o},s:S.size}]},$('#addc'),'افزودن به سبد')};
Object.assign(S,{model:'i16',case:'#FFFFFF',surface:'case'});
const PH=PHONES;
OPT.model=Object.keys(PH).map(k=>[k,PH[k][0]]);SW.case=['#FFFFFF','#2B2B2B','#0E5E6F','#FFB3C7','#FFD23F','#BFE6FF'];
let P=[],selId=null,nid=1,drag=null,T=[],D=[];const PPC=24;
const cur=()=>P.find(q=>q.id===selId), elOf=p=>document.querySelector('.ps[data-id="'+p.id+'"]');
function upd(el,p){const s=p.s*PPC;el.style.cssText='position:absolute;touch-action:none;cursor:grab;left:'+(p.x-s/2)+'px;top:'+(p.y-s/2)+'px;width:'+s+'px;height:'+s+'px;transform:rotate('+p.r+'deg);filter:drop-shadow(1px 2px 0 rgba(0,0,0,.3))';el.classList.toggle('sel',p.id===selId)}
function dim(p){$('#sdim').textContent=p.s+' سانت ('+(p.s/2.54).toFixed(1)+' اینچ)'}
function select(id){selId=id;document.querySelectorAll('.ps').forEach(e=>e.classList.toggle('sel',+e.dataset.id===id));const p=cur();$('#sel').hidden=!p;if(p){$('#ssz').value=p.s;$('#srot').value=p.r;dim(p)}}
function tier(s){const t=QSD.tiers;for(const[m,p]of t)if(s<=m)return p;return t.length?t[t.length-1][1]:0}
function pp(){const t=S.surface==='sheet'?(QSD.sheets[S.model]||0):QSD.caseBase+P.reduce((a,p)=>a+tier(p.s),0);$('#pprice').textContent=fmt(t)+' ('+P.length+' استیکر)';return t}
function renderCase(){
  const [n,w,h,r,cm]=PH[S.model],W=w*2.4,H=h*2.4,u=S.unit==='cm'?PPC:PPC*2.54,sb=S.unit==='cm'?10:8;
  $('#prg').innerHTML='<div class="rk"></div><div>'+ruler(true,W,u,sb,0,0)+'</div><div>'+ruler(false,H,u,sb,0,0)+'</div><div id="case" style="position:relative;width:'+W+'px;height:'+H+'px;border-radius:'+r*2.4+'px;background:'+(S.surface==='sheet'?'#FFFFFF':S.case)+';overflow:hidden;box-shadow:inset 0 0 0 5px rgba(0,0,0,.2)">'+(cm[2]>0?'<div class="cam" style="left:'+cm[0]*2.4+'px;top:'+cm[1]*2.4+'px;width:'+cm[2]*2.4+'px;height:'+cm[3]*2.4+'px">دوربین</div>':'')+'</div>';
  const c=$('#case');
  P.forEach(p=>{p.x=Math.min(p.x,W);p.y=Math.min(p.y,H);const el=document.createElement('div');el.className='ps';el.dataset.id=p.id;el.innerHTML=p.art;c.appendChild(el);upd(el,p)});
  $('#pread').textContent=n+': '+(w/10).toFixed(1)+' × '+(h/10).toFixed(1)+' سانت ('+(w/25.4).toFixed(2)+' × '+(h/25.4).toFixed(2)+' اینچ). اندازه‌ها تقریبی‌ان.';
  pp();
}
function packSheet(report){const [nm,w,h]=PH[S.model],G=4,M=6;let x=M,y=M,rh=0,ok=true;
  P.forEach(p=>{const s=p.s*10;if(x+s>w-M){x=M;y+=rh+G;rh=0}if(y+s>h-M)ok=false;p.x=(x+s/2)*2.4;p.y=(y+s/2)*2.4;p.r=0;x+=s+G;rh=Math.max(rh,s)});
  if(report){renderCase();$('#pread').textContent=ok?'چیدمان خودکار انجام شد.':'همه‌ی استیکرها جا نشدن؛ اندازه‌ها رو کم کن یا ورق بزرگ‌تر بگیر.'}return ok}
function addSticker(a,sp){const c=$('#case'),n=P.length,W=c.offsetWidth||PH[S.model][1]*2.4,H=c.offsetHeight||PH[S.model][2]*2.4;P.push({id:nid++,art:a,sp,x:W*(n%2?.7:.3),y:H*.42+(Math.floor(n/2)%4)*66+(n%2?22:0),s:3,r:0});if(S.surface==='sheet')packSheet(false);renderCase();select(nid-1);QSE('sticker')}
function drawTray(){const so={body:S.body,acc:S.acc,bg:S.bg,shape:S.shape,cap:S.cap};T=[{l:'طرح استودیو',a:duck(so),sp:{k:'duck',o:so}}].concat(PRE.map(d=>({l:d.n,a:duck(d.o),sp:{k:'duck',o:d.o}})),D);
  $('#tray').innerHTML=T.map((t,i)=>'<button class="th" data-t="'+i+'" title="'+t.l+'" aria-label="افزودن '+t.l+'">'+t.a+'</button>').join('')}
$('#tray').onclick=e=>{const b=e.target.closest('[data-t]');if(b)addSticker(T[+b.dataset.t].a,T[+b.dataset.t].sp)};
$('#prg').addEventListener('pointerdown',e=>{const el=e.target.closest('.ps');if(!el){select(null);return}select(+el.dataset.id);const p=cur();drag={el,p,dx:e.clientX-p.x,dy:e.clientY-p.y};el.setPointerCapture(e.pointerId);e.preventDefault()});
$('#prg').addEventListener('pointermove',e=>{if(!drag)return;const c=$('#case');drag.p.x=Math.max(0,Math.min(c.offsetWidth,e.clientX-drag.dx));drag.p.y=Math.max(0,Math.min(c.offsetHeight,e.clientY-drag.dy));upd(drag.el,drag.p)});
$('#prg').addEventListener('pointerup',()=>drag=null);
$('#ssz').oninput=e=>{const p=cur();if(!p)return;p.s=+e.target.value;upd(elOf(p),p);dim(p);pp()};
$('#srot').oninput=e=>{const p=cur();if(!p)return;p.r=+e.target.value;upd(elOf(p),p)};
$('#sdel').onclick=()=>{P=P.filter(q=>q.id!==selId);renderCase();select(null)};
const flash=(b,t,o)=>{b.textContent=t;setTimeout(()=>b.textContent=o,1100)};
$('#paddc').onclick=()=>{const b=$('#paddc'),sh=S.surface==='sheet',o=sh?'افزودن ورق به سبد':'افزودن قاب به سبد';if(!P.length)return flash(b,'اول یه استیکر بچسبون',o);
  const st=P.map(p=>({sp:p.sp,s:p.s,x:Math.max(0,Math.round(p.x/2.4*10)/10),y:Math.max(0,Math.round(p.y/2.4*10)/10),r:p.r}));
  qsSubmit(sh?{type:'sheet',model:S.model,stickers:st}:{type:'case',model:S.model,caseColor:S.case,stickers:st},b,o)};
$('#autoarr').onclick=()=>packSheet(true);
/* Sketch pad */
const cv=$('#pad'),cx=cv.getContext('2d'),pen={c:'#14213D',w:4,er:false},hist=[];let down=false;
cx.lineCap=cx.lineJoin='round';
const PC=['#14213D','#E63946','#FF7A1A','#FFB703','#2E9E5B','#1D7FD6','#9B5DE5','#FF7A9A'],PW=[[2,'نازک'],[4,'متوسط'],[8,'کلفت'],[14,'خیلی کلفت']];
function penUI(){$('#pc').innerHTML=PC.map(c=>'<button class="sw" style="background:'+c+'" data-pc="'+c+'" aria-label="رنگ قلم '+c+'" aria-pressed="'+(!pen.er&&pen.c===c)+'"></button>').join('');
  $('#pw').innerHTML=PW.map(([w,l])=>'<button class="chip" data-pw="'+w+'" aria-pressed="'+(pen.w===w)+'">'+l+'</button>').join('');$('#eraser').setAttribute('aria-pressed',pen.er)}
document.addEventListener('click',e=>{const a=e.target.closest('[data-pc],[data-pw]');if(!a)return;if(a.dataset.pc){pen.c=a.dataset.pc;pen.er=false}else pen.w=+a.dataset.pw;penUI()});
$('#eraser').onclick=()=>{pen.er=!pen.er;penUI()};
const snap=()=>{hist.push(cx.getImageData(0,0,600,600));if(hist.length>20)hist.shift()};
$('#undo').onclick=()=>{if(hist.length)cx.putImageData(hist.pop(),0,0)};
$('#clr').onclick=()=>{snap();cx.clearRect(0,0,600,600)};
function seg(e){const r=cv.getBoundingClientRect(),x=(e.clientX-r.left)*600/r.width,y=(e.clientY-r.top)*600/r.height;cx.globalCompositeOperation=pen.er?'destination-out':'source-over';cx.strokeStyle=pen.c;cx.lineWidth=pen.er?pen.w*4:pen.w*2;cx.lineTo(x+.01,y);cx.stroke();cx.beginPath();cx.moveTo(x,y)}
cv.onpointerdown=e=>{QSE('draw');cv.setPointerCapture(e.pointerId);snap();down=true;cx.beginPath();seg(e)};
cv.onpointermove=e=>{if(down)seg(e)};
cv.onpointerup=cv.onpointercancel=()=>{down=false;cx.beginPath()};
function srul(){const u=S.unit==='cm'?50:127,sb=S.unit==='cm'?10:8;$('#srt').innerHTML=ruler(true,300,u,sb,0,0);$('#srl').innerHTML=ruler(false,300,u,sb,0,0)}
function sketchArt(){const d=cx.getImageData(0,0,600,600).data;let any=false;for(let i=3;i<d.length;i+=4)if(d[i]){any=true;break}
  if(!any)return null;const f='drop-shadow(2px 0 0 #fff) drop-shadow(-2px 0 0 #fff) drop-shadow(0 2px 0 #fff) drop-shadow(0 -2px 0 #fff)';
  return '<svg viewBox="0 0 100 100" style="filter:'+f+'"><image href="'+cv.toDataURL()+'" width="100" height="100"/></svg>'}
$('#sbuy').onclick=()=>{const b=$('#sbuy');if(!sketchArt())return flash(b,'اول یه چیزی بکش','افزودن به سبد');qsSubmit({type:'sticker',stickers:[{sp:{k:'sk',u:cv.toDataURL()},s:QSD.sketchCm}]},b,'افزودن به سبد')};
$('#ssave').onclick=()=>{const a=sketchArt(),b=$('#ssave'),o='ذخیره برای قاب گوشی';if(!a)return flash(b,'اول یه چیزی بکش',o);D.push({l:'نقاشی من '+(D.length+1),a,sp:{k:'sk',u:cv.toDataURL()}});drawTray();flash(b,'ذخیره شد! توی کارگاه قاب گوشی پیداش کن',o)};

const toast=(u,msg,txt)=>{const t=$('#qs-toast');t.hidden=false;t.textContent=(msg||'به سبد اضافه شد.')+' ';const a=document.createElement('a');a.href=u;a.textContent=txt||'رفتن به سبد خرید';t.appendChild(a)};
async function qsSubmit(design,btn,orig){
  const saving=!!window.__qsSave;window.__qsSave=false;
  if(saving&&!QSD.logged){toast(QSD.login,'برای ذخیره‌ی طرح اول وارد حسابت شو.','ورود / ثبت‌نام');return}
  btn.disabled=true;
  try{
    const pv=await QS.preview(design),fd=new FormData();
    fd.append('nonce',QSD.nonce);fd.append('design',JSON.stringify(design));fd.append('preview',pv);
    const r=await fetch(saving?QSD.save:QSD.add,{method:'POST',body:fd,credentials:'same-origin'}).then(x=>x.json());
    if(!r.success)throw new Error(r.data&&r.data.msg||'خطا در افزودن');
    if(saving){flash(btn,'ذخیره شد!',orig);toast(QSD.account,'طرح توی «طرح‌های من» ذخیره شد.','دیدن طرح‌ها');btn.disabled=false;return}
    flash(btn,'اضافه شد!',orig);toast(r.data.cart);QSE('cart');document.dispatchEvent(new CustomEvent('qs:cart'));
    if(window.jQuery)jQuery(document.body).trigger('wc_fragment_refresh');
  }catch(e){flash(btn,e.message||'خطا در افزودن',orig)}
  btn.disabled=false}
/* ---- save-for-later chips (logged-in customers; guests are sent to log in) ---- */
['#addc','#ubuy','#sbuy','#paddc'].forEach(sel=>{const b=$(sel);if(!b)return;const c=document.createElement('button');c.type='button';c.className='chip qs-save';c.textContent='ذخیره‌ی طرح';
  c.onclick=()=>{window.__qsSave=true;b.click();setTimeout(()=>{window.__qsSave=false},50)};b.insertAdjacentElement('afterend',c)});
/* ---- photo upload -> sticker ---- */
const CUT='drop-shadow(2px 0 0 #fff) drop-shadow(-2px 0 0 #fff) drop-shadow(0 2px 0 #fff) drop-shadow(0 -2px 0 #fff)';
const U={img:null,shape:'circle',size:PRICE[6]?6:+Object.keys(PRICE)[0],zoom:1,ox:0,oy:0},up=$('#upad'),ux=up.getContext('2d'),UW=900;
function uChips(){
  $('#u-shape').innerHTML=[['circle','گرد'],['square','مربع گرد'],['free','بدون برش']].map(([v,l])=>'<button class="chip" data-us="'+v+'" aria-pressed="'+(U.shape===v)+'">'+l+'</button>').join('');
  $('#u-size').innerHTML=Object.keys(PRICE).map(k=>'<button class="chip" data-uz="'+k+'" aria-pressed="'+(U.size==k)+'">'+k+' سانت</button>').join('');
  $('#uprice').textContent=fmt(PRICE[U.size]||0)}
function urul(){const ppc=300/U.size,u=S.unit==='cm'?ppc:ppc*2.54,sb=S.unit==='cm'?10:8;$('#urt').innerHTML=ruler(true,300,u,sb,0,0);$('#url').innerHTML=ruler(false,300,u,sb,0,0)}
function uGeom(){const iw=U.img.width,ih=U.img.height,free=U.shape==='free',s=(free?Math.min(UW/iw,UW/ih):Math.max(UW/iw,UW/ih))*U.zoom,w=iw*s,h=ih*s,mx=free?UW/2:Math.max(0,(w-UW)/2),my=free?UW/2:Math.max(0,(h-UW)/2);
  U.ox=Math.max(-mx,Math.min(mx,U.ox));U.oy=Math.max(-my,Math.min(my,U.oy));return{w,h,x:(UW-w)/2+U.ox,y:(UW-h)/2+U.oy}}
function uDraw(){ux.clearRect(0,0,UW,UW);if(!U.img)return;const g=uGeom();ux.save();ux.beginPath();
  if(U.shape==='circle')ux.arc(UW/2,UW/2,UW/2-6,0,Math.PI*2);else if(U.shape==='square')ux.roundRect(6,6,UW-12,UW-12,110);else ux.rect(0,0,UW,UW);
  ux.clip();ux.drawImage(U.img,g.x,g.y,g.w,g.h);ux.restore()}
const uHint=t=>$('#uhint').textContent=t;
$('#ufile').onchange=e=>{const f=e.target.files[0];if(!f)return;
  if(!/^image\/(png|jpe?g|webp)$/.test(f.type))return uHint('فقط PNG، JPG یا WEBP.');
  if(f.size>15e6)return uHint('حجم عکس زیاده (حداکثر ۱۵ مگابایت).');
  const url=URL.createObjectURL(f),im=new Image();
  im.onload=()=>{U.img=im;U.zoom=1;U.ox=U.oy=0;$('#uzoom').value=1;uDraw();uHint('با کشیدن روی تصویر جاش رو تنظیم کن.');URL.revokeObjectURL(url);QSE('upload')};
  im.onerror=()=>uHint('این فایل باز نشد.');im.src=url};
$('#uzoom').oninput=e=>{U.zoom=+e.target.value;uDraw()};
let upd0=null;
up.addEventListener('pointerdown',e=>{if(!U.img)return;up.setPointerCapture(e.pointerId);upd0={x:e.clientX,y:e.clientY,ox:U.ox,oy:U.oy}});
up.addEventListener('pointermove',e=>{if(!upd0)return;const k=UW/up.getBoundingClientRect().width;U.ox=upd0.ox+(e.clientX-upd0.x)*k;U.oy=upd0.oy+(e.clientY-upd0.y)*k;uDraw()});
up.addEventListener('pointerup',()=>upd0=null);
document.addEventListener('click',e=>{const a=e.target.closest('[data-us],[data-uz]');if(!a)return;if(a.dataset.us){U.shape=a.dataset.us;uDraw()}else{U.size=+a.dataset.uz;urul()}uChips()});
function upData(){let d=up.toDataURL('image/png');if(d.length<=3.2e6)return d;const t=document.createElement('canvas');for(const w of [720,560,420]){t.width=t.height=w;t.getContext('2d').drawImage(up,0,0,w,w);d=t.toDataURL('image/png');if(d.length<=3.2e6)break}return d}
const upArt=()=>'<svg viewBox="0 0 100 100" style="filter:'+CUT+'"><image href="'+upData()+'" width="100" height="100"/></svg>';
$('#ubuy').onclick=()=>{const b=$('#ubuy'),o='افزودن به سبد';if(!U.img)return flash(b,'اول عکس انتخاب کن',o);if(!$('#uok').checked)return flash(b,'تیک تأیید رو بزن',o);
  qsSubmit({type:'sticker',stickers:[{sp:{k:'sk',u:upData()},s:U.size}]},b,o)};
$('#usave').onclick=()=>{const b=$('#usave'),o='ذخیره برای قاب گوشی';if(!U.img)return flash(b,'اول عکس انتخاب کن',o);if(!$('#uok').checked)return flash(b,'تیک تأیید رو بزن',o);
  D.push({l:'عکس من '+(D.length+1),a:upArt(),sp:{k:'sk',u:upData()}});drawTray();flash(b,'ذخیره شد! توی کارگاه قاب گوشی پیداش کن',o)};
$('#sprice').textContent=QSD.sketchCm+' سانت · '+fmt(PRICE[QSD.sketchCm]||0);
document.fonts.load('20px Lalezar','کوَک Aa');
render();renderCase();penUI();
})();
