/* The pesky duck. Harmless: never captures clicks, sleeps for reduced-motion users, can be dismissed. */
(function(){
if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;
const NAME=(window.QSDUCK&&QSDUCK.name)||'کوَک‌کوَکی';
const KEY='qs_duck_off',SAY=['کوَک!','اینجا چه خبره؟','این استیکر مال منه.','نگام نکن، دارم کار می‌کنم.','کوَک‌کوَک‌کوَک!','گرسنمه... دونه داری؟','بخرش بخرش!','یکی رو بردار، بقیه رو من می‌برم.','من '+NAME+'\u200cام؛ همکار شما.'];
let off=false;try{off=localStorage.getItem(KEY)==='1'}catch(_){}
const R=(a,b)=>a+Math.random()*(b-a),pick=a=>a[Math.floor(Math.random()*a.length)];
const el=document.createElement('div');el.className='qsd';el.setAttribute('aria-hidden','true');
el.innerHTML='<div class="qsd-name"></div><div class="qsd-say"></div><svg class="qsd-svg" viewBox="0 0 80 80"><g class="qsd-f1"><path d="M34 62v10h-5M34 72h7" /></g><g class="qsd-f2"><path d="M46 62v10h-5M46 72h7" /></g><ellipse cx="38" cy="50" rx="24" ry="17" fill="#FFD23F" stroke="#3A2817" stroke-width="2"/><path d="M20 46q-8-8-12-6 4 8 14 12z" fill="#FFD23F" stroke="#3A2817" stroke-width="2"/><path d="M28 50q10 12 24 0q-12-5-24 0z" fill="#000" opacity=".15"/><circle cx="56" cy="30" r="13" fill="#FFD23F" stroke="#3A2817" stroke-width="2"/><ellipse cx="71" cy="34" rx="9" ry="4.5" fill="#FF7A1A" stroke="#3A2817" stroke-width="2"/><circle cx="59" cy="27" r="2.6" fill="#14213D"/></svg>';
const btn=document.createElement('button');btn.className='qsd-toggle';btn.type='button';
document.body.append(el,btn);
el.querySelector('.qsd-name').textContent=NAME;
const say=el.querySelector('.qsd-say'),svg=el.querySelector('.qsd-svg');
let x=-90,y=innerHeight-100,tx=60,ty=y,mx=innerWidth/2,my=innerHeight/2,st='wander',until=0,dir=1,loot=null,lootOf=null,dist=0,raf=0,ac=null,wait=false;
function fit(){say.style.setProperty('--dx','0px');const w=say.offsetWidth,cx=el.getBoundingClientRect().left+40;let dx=0;if(cx-w/2<6)dx=6-(cx-w/2);else if(cx+w/2>innerWidth-6)dx=innerWidth-6-(cx+w/2);say.style.setProperty('--dx',dx+'px')}
function talk(t,ms){say.classList.remove('ask');say.textContent=t||pick(SAY);say.classList.add('on');fit();clearTimeout(talk.t);talk.t=setTimeout(()=>say.classList.remove('on'),ms||2000)}
function quack(){try{ac=ac||new (window.AudioContext||window.webkitAudioContext)();[0,.13].forEach((d,i)=>{const o=ac.createOscillator(),g=ac.createGain();o.type='sawtooth';o.frequency.setValueAtTime(520-i*80,ac.currentTime+d);o.frequency.exponentialRampToValueAtTime(260,ac.currentTime+d+.1);g.gain.setValueAtTime(.06,ac.currentTime+d);g.gain.exponentialRampToValueAtTime(.001,ac.currentTime+d+.11);o.connect(g).connect(ac.destination);o.start(ac.currentTime+d);o.stop(ac.currentTime+d+.12)})}catch(_){}}
function print(){const p=document.createElement('i');p.className='qsd-print';p.style.left=(x+34)+'px';p.style.top=(y+70)+'px';document.body.appendChild(p);setTimeout(()=>p.remove(),4000)}
function go(nx,ny){tx=Math.max(0,Math.min(innerWidth-80,nx));ty=Math.max(0,Math.min(innerHeight-80,ny))}
function targets(){return [].filter.call(document.querySelectorAll('.products .product img,.qs-duck-img svg'),e=>{const r=e.getBoundingClientRect();return r.width>20&&r.top>0&&r.bottom<innerHeight&&r.left>0&&r.right<innerWidth})}
function next(){const r=Math.random(),now=performance.now();
  if(loot&&now>until){drop();return}
  if(loot){st='wander';go(R(0,innerWidth),R(innerHeight*.4,innerHeight));until=now+R(2500,5000);return}
  if(r<.3){st='chase';until=now+R(4000,7000)}
  else if(r<.45&&targets().length){st='steal';lootOf=pick(targets());const b=lootOf.getBoundingClientRect();go(b.left+b.width/2-40,b.top+b.height/2-40)}
  else if(r<.58){st='sleep';until=now+R(5000,8000);talk('زززز...',3000)}
  else{st='wander';go(R(0,innerWidth),R(innerHeight*.35,innerHeight))}}
function grab(){const b=lootOf.getBoundingClientRect();loot=document.createElement('div');loot.className='qsd-loot';loot.style.backgroundImage=lootOf.tagName==='IMG'?'url("'+lootOf.currentSrc+'")':'none';if(lootOf.tagName!=='IMG'){loot.innerHTML=lootOf.outerHTML}document.body.appendChild(loot);lootOf.style.opacity='.12';talk('مال منه!',1800);until=performance.now()+9000;next()}
function drop(){if(loot){loot.remove();loot=null}if(lootOf){lootOf.style.opacity='';lootOf=null}talk('خوب بود، بیا پسش بگیر.',2200);st='wander';go(R(0,innerWidth),R(innerHeight*.4,innerHeight))}
function tick(){
  const now=performance.now();let sp=1.7;
  if(st==='chase'){go(mx-60,my-70);sp=2.3;if(now>until)next()}
  else if(st==='sleep'){sp=0;if(now>until){talk('کوَک؟');next()}}
  else if(st==='flee'){sp=4.5;if(now>until)next()}
  const dx=tx-x,dy=ty-y,d=Math.hypot(dx,dy);
  if(sp&&d>3){x+=dx/d*sp;y+=dy/d*sp;dist+=sp;if(Math.abs(dx)>2)dir=dx>0?1:-1;if(dist>26){dist=0;print()}el.classList.add('walk')}
  else{el.classList.remove('walk');if(sp&&d<=3){if(st==='steal'&&lootOf&&!loot)grab();else if(st==='chase'&&!el.classList.contains('nib')){el.classList.add('nib');talk('نیش!',900);setTimeout(()=>{el.classList.remove('nib');next()},1100)}else if((st==='wander'||st==='flee')&&!wait){wait=true;setTimeout(()=>{wait=false;next()},R(800,3000))}}}
  el.style.transform='translate('+x+'px,'+y+'px)';svg.style.transform='scaleX('+dir+')';
  if(loot)loot.style.transform='translate('+(x+(dir>0?58:-6))+'px,'+(y+34)+'px)';
  raf=requestAnimationFrame(tick)}
addEventListener('pointermove',e=>{mx=e.clientX;my=e.clientY},{passive:true});
document.addEventListener('click',e=>{const r=el.getBoundingClientRect();if(e.clientX>r.left-8&&e.clientX<r.right+8&&e.clientY>r.top-8&&e.clientY<r.bottom+8){quack();el.classList.add('hit');setTimeout(()=>el.classList.remove('hit'),450);const L=clickLine();talk(L,L.length>40?3600:2200);st='flee';until=performance.now()+(clicks===15?8000:2500);if(clicks===15)lastT=Date.now()+20000;go(Math.random()<.5?-80:innerWidth,R(0,innerHeight))}},true);
let clicks=0;
const CLICKS=['هی! قلقلکم می‌دی!','ای بابا! دارم کار می‌کنم.','کوَک کوَک کوَک... یعنی ولم کن.','حواست باشه، منقارم تیزه!','دلت اومد؟! من یه اردک بیچاره‌ام.','نون خشک داری؟ نداری؟ پس برو.','من اردک این فروشگاهم؛ حقوقم نون خشکه.','اگه یه استیکر بخری دیگه سیخونک نمی‌زنم.','کلیک نکن! قلقلکیم!','بازم؟! باشه، ولی این آخریشه. (دروغ گفتم.)','من '+NAME+'\u200cام، نه توپ بازی!','آخ آخ! بال‌هام!'];
function clickLine(){clicks++;
  if(clicks===3)return 'سه بار شد‌ها! من '+NAME+'\u200cام، اردک مفت نیستم.';
  if(clicks===6)return 'دارم به اتحادیه‌ی اردک‌ها شکایت می‌کنم!';
  if(clicks===10)return 'باشه تسلیم! یه استیکر بخر تا ساکت شم.';
  if(clicks===15)return 'قهرم! تا بیست ثانیه باهات حرف نمی‌زنم.';
  if(CTX==='design'&&Math.random()<.3)return 'حواست به طراحیت باشه، من اینجام!';
  return pick(CLICKS)}
function setOff(v){off=v;try{localStorage.setItem(KEY,v?'1':'0')}catch(_){}el.style.display=v?'none':'';btn.textContent=v?'اردک رو برگردون':'اردک رو بفرست بره';if(v){cancelAnimationFrame(raf);if(loot)drop()}else{cancelAnimationFrame(raf);raf=requestAnimationFrame(tick);talk('برگشتم!')}}
/* ---- context-aware comments ---- */
const has=s=>document.querySelector(s),bc=document.body.classList;
const CTX=has('.qs-app')?'design':has('.qs-land')?'land':has('.qs-req-wrap')?'req':bc.contains('single-product')?'product':(bc.contains('woocommerce-shop')||bc.contains('tax-product_cat'))?'shop':'other';
const LINES={upload:['عه! این عکس خودته؟','چه عکس قشنگی! خودتی؟','این عکس خودته؟ منم یه عکس از خودم می‌خوام.'],
  sticker:['چه استیکر باحالی!','آفرین، قاب داره پر می‌شه.','یکی دیگه هم بچسبون، جا هست.'],
  cart:['ایول! تو سبده. یکی دیگه؟','بخرش بخرش! خوب انتخابی بود.'],
  draw:['عه هنرمندی!','چه خطی! ادامه بده.'],caption:['کپشن قشنگیه!','کوَک‌کوَک! خوب نوشتی.'],
  'gift-ready':['مبارک باشه! قاب هدیه‌ات حاضره. تمش رو بنویس!'],'gift-set':['عالی! طراح‌ها کارشون رو شروع می‌کنن.'],
  request:['ثبت شد! به‌زودی توی تلگرام بهت پیام می‌دن.']};
const TIPS={studio:['اندازه رو با نوار نارنجی روی خط‌کش چک کن؛ همون جاییه که استیکر چاپ می‌شه.','کپشن کوتاه‌تر، خواناتر چاپ می‌شه.'],
  sketch:['اشتباه کردی؟ «برگردان» یا «پاک‌کن» رو بزن.','برای استیکر کوچیک، خط‌های کلفت‌تر بهتر دیده می‌شن.'],
  upload:['عکس رو بکش تا صورت‌ها وسط بیفتن.','عکس واضح و بزرگ، چاپ تمیزتر می‌ده.'],
  phone:['جای دوربین (کادر خط‌چین) رو خالی بذار!','با اسلایدر چرخششون بده، باحال‌تر می‌شه.']};
let idle=Date.now(),asked=false,lastT=0;const seen={};
try{asked=sessionStorage.getItem('qsd_help')==='1'}catch(_){}
document.addEventListener('qs:event',e=>{const d=e.detail||{},now=Date.now();if(off||!d.type)return;idle=now;
  const n=seen[d.type]=(seen[d.type]||0)+1;
  if(d.type==='draw'&&n>2||(d.type==='sticker'||d.type==='caption')&&n>3||d.type==='gift'&&n%3!==1)return;
  if(now-lastT<7000&&d.type!=='gift-ready'&&d.type!=='request')return;
  const t=d.say||(d.type==='gift'?'تا قاب هدیه فقط '+d.left+' مونده!':pick(LINES[d.type]||['']));if(!t)return;lastT=now;talk(t,3400)});
['pointerdown','keydown'].forEach(ev=>document.addEventListener(ev,e=>{if(e.target.closest&&e.target.closest('.qs-app'))idle=Date.now()},true));
function section(){let best='studio',bd=1e9;['studio','sketch','upload','phone'].forEach(i=>{const e=document.getElementById(i);if(!e)return;const r=e.getBoundingClientRect(),dd=Math.abs(r.top+r.height/2-innerHeight/2);if(dd<bd){bd=dd;best=i}});return best}
function offer(){asked=true;try{sessionStorage.setItem('qsd_help','1')}catch(_){}
  say.textContent='';const t=document.createElement('span');t.textContent='عههه داری طراحی می‌کنی؟ کمک می‌خوای؟ ';
  const mk=(l,f)=>{const b=document.createElement('button');b.type='button';b.textContent=l;b.className='qsd-opt';b.onclick=ev=>{ev.stopPropagation();f()};return b};
  say.append(t,mk('آره',()=>talk(pick(TIPS[section()]),7000)),mk('نه، برو',()=>{talk('باشه، می‌رم دنبال کار خودم.',2200);st='flee';until=performance.now()+2500;go(Math.random()<.5?-80:innerWidth,R(0,innerHeight))}));
  say.classList.add('on','ask');fit();clearTimeout(talk.t);talk.t=setTimeout(()=>say.classList.remove('on','ask'),14000)}
const once=(k,ms,txt)=>{let s=false;try{s=sessionStorage.getItem('qsd_'+k)==='1'}catch(_){}if(s)return;setTimeout(()=>{if(off||document.hidden)return;try{sessionStorage.setItem('qsd_'+k,'1')}catch(_){}talk(txt,4000)},ms)};
if(CTX==='land')once('land',14000,'بریم طراحی؟ دکمه‌ش همین بالاست.');
if(CTX==='product')once('prod',12000,'این یکی رو بخر! من که ازش خوشم اومده.');
if(CTX==='shop')once('shop',10000,'کدومش رو دوست داری؟ من همه‌شون رو می‌خوام.');
document.addEventListener('focusin',e=>{if(CTX==='req'&&e.target.name==='tg')once('tg',200,'آیدی تلگرامت رو درست بنویس؛ منم اونجام!')});
setInterval(()=>{if(off||document.hidden||CTX!=='design'||asked)return;if(Date.now()-idle>25000){const r=document.querySelector('.qs-app').getBoundingClientRect();if(r.top<innerHeight&&r.bottom>0)offer()}},4000);
btn.onclick=()=>setOff(!off);
document.addEventListener('visibilitychange',()=>{if(document.hidden)cancelAnimationFrame(raf);else if(!off){cancelAnimationFrame(raf);raf=requestAnimationFrame(tick)}});
setOff(off);if(!off)setTimeout(()=>talk('سلام! من '+NAME+'\u200cام. اینجا کار می‌کنم.',3500),1500);
})();
