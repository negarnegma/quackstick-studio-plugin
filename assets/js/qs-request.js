/* Design-request form: optional reference image is downscaled in the browser, everything goes to admin-ajax. */
(function(){
const f=document.getElementById('qs-req');if(!f||!window.QSR)return;
let ref='';const msg=f.querySelector('.qr-msg'),done=document.querySelector('.qr-done');
f.querySelector('[name=refile]').onchange=e=>{ref='';const fl=e.target.files[0];if(!fl)return;
  if(!/^image\//.test(fl.type)){msg.textContent='فقط تصویر.';e.target.value='';return}
  const u=URL.createObjectURL(fl),im=new Image();
  im.onload=()=>{const s=Math.min(1,1000/Math.max(im.width,im.height)),c=document.createElement('canvas');c.width=Math.round(im.width*s);c.height=Math.round(im.height*s);c.getContext('2d').drawImage(im,0,0,c.width,c.height);ref=c.toDataURL('image/png');URL.revokeObjectURL(u)};
  im.onerror=()=>{msg.textContent='این فایل باز نشد.'};im.src=u};
f.addEventListener('submit',async e=>{e.preventDefault();msg.textContent='';
  const fd=new FormData(f);fd.delete('refile');fd.append('action','qs_request');fd.append('nonce',QSR.nonce);fd.append('ref',ref);
  const b=f.querySelector('button[type=submit]');b.disabled=true;
  try{const r=await fetch(QSR.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(x=>x.json());
    if(!r.success)throw new Error(r.data&&r.data.msg||'خطا');
    done.querySelector('.qr-id').textContent=r.data.id||'-';document.dispatchEvent(new CustomEvent('qs:event',{detail:{type:'request'}}));f.hidden=true;done.hidden=false;done.scrollIntoView({behavior:'smooth',block:'center'})}
  catch(err){msg.textContent=err.message||'خطا'}
  b.disabled=false})})();
