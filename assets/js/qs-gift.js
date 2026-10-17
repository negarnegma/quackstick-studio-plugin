/* Free-case progress pill + form. Talks to wc-ajax, so it works on any page and with block or classic cart. */
(function(){
if(!window.QSG)return;
const QSE=(t,d)=>document.dispatchEvent(new CustomEvent('qs:event',{detail:Object.assign({type:t},d||{})}));
const fa=n=>new Intl.NumberFormat('fa-IR').format(Math.round(n)),money=n=>fa(n)+' '+QSG.cur;
const root=document.createElement('div');root.className='qsg';root.hidden=true;document.body.appendChild(root);
let st=null,open=false;
async function call(url,fd){const r=await fetch(url,{method:fd?'POST':'GET',body:fd||undefined,credentials:'same-origin'});return r.json()}
async function refresh(){try{const r=await call(QSG.state);if(r&&r.success)setState(r.data)}catch(e){}}
function setState(s){const was=st;st=s;
  if(was&&!was.eligible&&s.eligible&&!s.has){open=true;QSE('gift-ready')}
  else if(was&&s.on&&s.left<was.left&&!s.eligible)QSE('gift',{left:money(s.left)});
  draw()}
const el=(t,c,h)=>{const e=document.createElement(t);if(c)e.className=c;if(h!=null)e.textContent=h;return e};
function draw(){
  root.innerHTML='';if(!st||!st.on){root.hidden=true;return}root.hidden=false;
  const pill=el('button','qsg-pill');pill.type='button';pill.setAttribute('aria-expanded',open);
  pill.append(el('span','',st.has?'قاب هدیه ثبت شد':'قاب هدیه: '+fa(st.pct)+'٪'));
  const mini=el('i','qsg-mini'),mb=el('b');mb.style.width=st.pct+'%';mini.append(mb);pill.append(mini);
  pill.onclick=()=>{open=!open;draw()};
  const card=el('div','qsg-card');card.hidden=!open;
  if(st.has){
    card.append(el('p','','قاب هدیه‌ات توی سبده. طراح‌ها با این مشخصات طراحیش می‌کنن و توی تلگرام هماهنگ می‌کنیم:'));
    card.append(el('p','qsg-sum',st.gift.model+' | '+st.gift.theme));
    const b=el('button','qsg-btn alt','حذف قاب هدیه');b.type='button';b.onclick=()=>send(null,true);card.append(b);
  }else if(st.eligible){
    card.append(el('p','','قاب گوشی هدیه‌ات آماده‌ست! مدل گوشی و تم دلخواه رو بنویس تا طراح‌ها همون رو برات طراحی کنن.'));
    const f=el('form'),sel=el('select');sel.name='model';QSG.models.forEach(m=>{const o=el('option','',m[1]);o.value=m[0];sel.append(o)});
    const ta=el('textarea');ta.name='theme';ta.rows=3;ta.maxLength=300;ta.required=true;ta.placeholder='تم و سلیقه‌ات رو بنویس. مثلاً: اردک‌های فضانورد روی زمینه‌ی آبی تیره';
    const msg=el('p','qsg-msg'),b=el('button','qsg-btn','ثبت قاب هدیه');
    f.append(sel,ta,msg,b);f.onsubmit=e=>{e.preventDefault();if(ta.value.trim().length<3){msg.textContent='تم رو بنویس.';return}send({model:sel.value,theme:ta.value},false,msg)};
    card.append(f);
  }else{
    const p=el('p');p.append('تا ');const s=el('strong','',money(st.left));p.append(s,' دیگه خرید کنی، یه قاب گوشی با طرح دلخواهت هدیه می‌گیری!');card.append(p);
    const bar=el('div','qsg-bar'),bb=el('b');bb.style.width=Math.max(st.pct,3)+'%';bar.append(bb);card.append(bar);
    card.append(el('small','',money(st.sub)+' از '+money(st.min)));
  }
  root.append(card,pill)}
async function send(v,remove,msg){const fd=new FormData();fd.append('nonce',QSG.nonce);
  if(remove)fd.append('remove','1');else{fd.append('model',v.model);fd.append('theme',v.theme)}
  try{const r=await call(QSG.gift,fd);if(!r.success)throw new Error(r.data&&r.data.msg||'خطا');setState(r.data);
    if(!remove)QSE('gift-set');if(window.jQuery)jQuery(document.body).trigger('wc_fragment_refresh')}
  catch(e){if(msg)msg.textContent=e.message}}
document.addEventListener('qs:cart',refresh);
if(window.jQuery)jQuery(document.body).on('added_to_cart removed_from_cart updated_cart_totals updated_checkout wc_fragments_refreshed',refresh);
refresh();
})();
