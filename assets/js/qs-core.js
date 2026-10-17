/* QuackStick core: duck art, cut paths, rasterizer, sheet layout, PDF builder. Shared by storefront + admin. */
(function(){
const QS=window.QS={},INK='#14213D';
function capSvg(c){c=String(c||'').replace(/[<>&]/g,'').trim();if(!c)return '';
  const fs=c.length>11?8:10,F='direction="ltr" font-family="Lalezar,Chewy,cursive" font-size="'+fs+'" fill="'+INK+'"',ws=c.split(/\s+/);
  if(ws.length<2)return '<text x="50" y="91" text-anchor="middle" '+F+'>'+c+'</text>';
  const cx=capSvg.cx||(capSvg.cx=document.createElement('canvas').getContext('2d'));cx.font='100px Lalezar,Chewy,cursive';
  const rtl=/[\u0600-\u06FF]/.test(c),w=ws.map(t=>cx.measureText(t).width*fs/100),g=fs*.3,tot=w.reduce((a,b)=>a+b,0)+g*(ws.length-1);
  let x=rtl?50+tot/2:50-tot/2,out='';
  ws.forEach((t,i)=>{if(rtl)x-=w[i];out+='<text x="'+x.toFixed(2)+'" y="91" text-anchor="start" '+F+'>'+t+'</text>';x+=rtl?-g:w[i]+g});
  return out}
function duck(o,id){
  const b=o.body||'#FFD23F',a=o.acc||'none',bg=o.bg||'#fff',sh=o.shape||'circle',c=o.cap||'';
  const shape=sh==='circle'?'<circle cx="50" cy="50" r="47"':sh==='square'?'<rect x="4" y="4" width="92" height="92" rx="16"'
    :'<path d="M50 3C70 1 80 12 91 22 99 38 98 62 91 76 80 92 66 98 50 97 32 98 18 92 9 78 1 62 2 38 9 24 20 10 34 4 50 3Z"';
  const acc={none:'',crown:'<polygon points="48,24 50,10 55,18 60,8 65,18 70,10 72,24" fill="#FFB703" stroke="'+INK+'" stroke-width="1.5"/>',
    top:'<rect x="50" y="8" width="20" height="16" fill="'+INK+'"/><rect x="45" y="22" width="30" height="4" rx="2" fill="'+INK+'"/><rect x="50" y="19" width="20" height="3" fill="#E63946"/>',
    glasses:'<circle cx="64" cy="32" r="6" fill="#fff6" stroke="'+INK+'" stroke-width="2"/><line x1="58" y1="32" x2="46" y2="31" stroke="'+INK+'" stroke-width="2"/>',
    bow:'<polygon points="47,47 56,52 47,57" fill="#E63946"/><polygon points="65,47 56,52 65,57" fill="#E63946"/><circle cx="56" cy="52" r="3" fill="#B1232F"/>',
    beanie:'<path d="M45 30A15 15 0 0 1 75 30Z" fill="#E63946"/><rect x="44" y="28" width="32" height="5" rx="2" fill="#fff"/><circle cx="60" cy="16" r="4" fill="#fff"/>'}[a];
  return '<svg viewBox="0 0 100 100" role="img" aria-label="استیکر اردک">'+shape+' fill="'+bg+'" stroke="#fff" stroke-width="5"/>'+shape+' fill="none" stroke="'+INK+'" stroke-opacity=".25" stroke-width=".8"/>'
   +'<g transform="translate(5 '+(c?-5:0)+') scale(.9)"><path d="M26 62L10 50 32 54Z" fill="'+b+'"/><ellipse cx="48" cy="64" rx="26" ry="20" fill="'+b+'"/>'
   +'<path d="M36 62Q48 80 62 62Q50 55 36 62Z" fill="#000" fill-opacity=".14"/><circle cx="60" cy="36" r="15" fill="'+b+'"/>'
   +'<ellipse cx="78" cy="40" rx="10" ry="5" fill="#FF7A1A"/><circle cx="64" cy="32" r="2.4" fill="'+INK+'"/><circle cx="68" cy="41" r="3" fill="#FF7A9A" fill-opacity=".5"/>'+acc+'</g>'
   +capSvg(c)+'</svg>';
}
function ruler(horiz,px,ppu,subs,from,to){
  const n=Math.floor(px/ppu*subs),L=24;let t='';
  for(let i=0;i<=n;i++){const x=i*ppu/subs+.5,mj=i%subs==0,h=mj?14:(i%(subs/2)==0?9:5);
    t+=horiz?'<line x1="'+x+'" y1="'+L+'" x2="'+x+'" y2="'+(L-h)+'" stroke="'+INK+'"/>':'<line x1="'+L+'" y1="'+x+'" x2="'+(L-h)+'" y2="'+x+'" stroke="'+INK+'"/>';
    if(mj&&i)t+=horiz?'<text x="'+(x+2)+'" y="9">'+i/subs+'</text>':'<text x="1" y="'+(x+10)+'">'+i/subs+'</text>';}
  const band=horiz?'<rect x="'+from+'" y="0" width="'+(to-from)+'" height="'+L+'" fill="#FF7A1A" fill-opacity=".45"/>':'<rect x="0" y="'+from+'" width="'+L+'" height="'+(to-from)+'" fill="#FF7A1A" fill-opacity=".45"/>';
  return '<svg width="'+(horiz?px+1:L)+'" height="'+(horiz?L:px+1)+'">'+band+t+'</svg>';
}
const loadImg=async u=>{
  const dec=src=>new Promise((ok,no)=>{const i=new Image();i.onload=()=>ok(i);i.onerror=()=>no(new Error('decode failed'));i.src=src});
  if(/^data:/.test(u))return dec(u);
  const r=await fetch(u,{credentials:'same-origin'});if(!r.ok)throw new Error('HTTP '+r.status);   /* blob => never a tainted canvas */
  const url=URL.createObjectURL(await r.blob());try{return await dec(url)}finally{URL.revokeObjectURL(url)}};
function cutPoly(sh){const Q=[],arc=(a,b,r,t0,t1,n)=>{for(let i=0;i<=n;i++){const t=t0+(t1-t0)*i/n;Q.push([a+r*Math.cos(t),b+r*Math.sin(t)])}};
  if(sh==='circle')arc(50,50,49.5,0,2*Math.PI,96);
  else if(sh==='square'){const r=18.5,a=1.5,b=98.5,H=Math.PI/2;arc(b-r,a+r,r,-H,0,10);arc(b-r,b-r,r,0,H,10);arc(a+r,b-r,r,H,2*H,10);arc(a+r,a+r,r,2*H,3*H,10)}
  else{let p=[50,3];[[70,1,80,12,91,22],[99,38,98,62,91,76],[80,92,66,98,50,97],[32,98,18,92,9,78],[1,62,2,38,9,24],[20,10,34,4,50,3]].forEach(q=>{for(let i=0;i<=16;i++){const t=i/16,u=1-t,f=k=>u*u*u*p[k]+3*u*u*t*q[k]+3*u*t*t*q[k+2]+t*t*t*q[k+4];Q.push([50+(f(0)-50)*1.05,50+(f(1)-50)*1.05])}p=[q[4],q[5]]})}
  return Q}
function hull(pts){pts.sort((a,b)=>a[0]-b[0]||a[1]-b[1]);const cr=(o,a,b)=>(a[0]-o[0])*(b[1]-o[1])-(a[1]-o[1])*(b[0]-o[0]),lo=[],up=[];
  for(const p of pts){while(lo.length>1&&cr(lo[lo.length-2],lo[lo.length-1],p)<=0)lo.pop();lo.push(p)}
  for(const p of pts.slice().reverse()){while(up.length>1&&cr(up[up.length-2],up[up.length-1],p)<=0)up.pop();up.push(p)}
  return lo.slice(0,-1).concat(up.slice(0,-1))}
function mk(w,h){const c=document.createElement('canvas');c.width=w;c.height=h||w;return c}
/* true outline (not a convex hull): dilate the alpha, fill holes, merge close parts, trace the boundary, simplify, smooth. Returns a px polygon or null. */
function outline(src,px,bpx){
  const S=mk(px),sx=S.getContext('2d');sx.drawImage(src,0,0);sx.globalCompositeOperation='source-in';sx.fillStyle='#000';sx.fillRect(0,0,px,px);
  const Dc=mk(px),dx=Dc.getContext('2d');for(let k=0;k<24;k++){const a=k/24*Math.PI*2;dx.drawImage(S,Math.cos(a)*bpx,Math.sin(a)*bpx)}dx.drawImage(S,0,0);
  const g=3,n=Math.ceil(px/g),dd=dx.getImageData(0,0,px,px).data,G=new Uint8Array(n*n),N4=[[1,0],[-1,0],[0,1],[0,-1]];
  for(let y=0;y<n;y++)for(let x=0;x<n;x++)G[y*n+x]=dd[(Math.min(px-1,y*g+1)*px+Math.min(px-1,x*g+1))*4+3]>40?1:0;
  const fill=()=>{const out=new Uint8Array(n*n),st=[],seed=(x,y)=>{const i=y*n+x;if(!G[i]&&!out[i]){out[i]=1;st.push(i)}};
    for(let i=0;i<n;i++){seed(i,0);seed(i,n-1);seed(0,i);seed(n-1,i)}
    while(st.length){const p=st.pop(),x=p%n,y=(p/n)|0;for(const[a,b]of N4){const X=x+a,Y=y+b;if(X>=0&&Y>=0&&X<n&&Y<n)seed(X,Y)}}
    for(let i=0;i<n*n;i++)G[i]=out[i]?0:1};
  const comps=()=>{const seen=new Uint8Array(n*n);let c=0;for(let i=0;i<n*n;i++)if(G[i]&&!seen[i]){c++;const st=[i];seen[i]=1;
    while(st.length){const p=st.pop(),x=p%n,y=(p/n)|0;for(const[a,b]of N4){const X=x+a,Y=y+b;if(X>=0&&Y>=0&&X<n&&Y<n){const q=Y*n+X;if(G[q]&&!seen[q]){seen[q]=1;st.push(q)}}}}}return c};
  const grow=()=>{const H=G.slice();for(let y=0;y<n;y++)for(let x=0;x<n;x++)if(G[y*n+x])for(const[a,b]of N4){const X=x+a,Y=y+b;if(X>=0&&Y>=0&&X<n&&Y<n)H[Y*n+X]=1}G.set(H)};
  fill();let c=comps();for(let k=0;c>1&&k<16;k++){grow();fill();c=comps()}
  if(c!==1)return null;
  const E=new Map(),add=(x1,y1,x2,y2)=>{const k=x1+','+y1;if(!E.has(k))E.set(k,[]);E.get(k).push([x2,y2])},sol=(x,y)=>x>=0&&y>=0&&x<n&&y<n&&G[y*n+x];
  let first=null;
  for(let y=0;y<n;y++)for(let x=0;x<n;x++)if(G[y*n+x]){
    if(!sol(x,y-1)){add(x,y,x+1,y);if(!first)first=[x,y,x+1,y]}
    if(!sol(x+1,y))add(x+1,y,x+1,y+1);
    if(!sol(x,y+1))add(x+1,y+1,x,y+1);
    if(!sol(x-1,y))add(x,y+1,x,y)}
  if(!first)return null;
  const P=[[first[0],first[1]]];let cur=[first[2],first[3]],dir=[1,0],guard=0;
  while((cur[0]!==first[0]||cur[1]!==first[1])&&guard++<n*n*4){
    P.push(cur.slice());const outs=E.get(cur[0]+','+cur[1])||[];if(!outs.length)return null;
    let best=null,bs=-9;for(const o of outs){const d=[o[0]-cur[0],o[1]-cur[1]],cr=dir[0]*d[1]-dir[1]*d[0],dot=dir[0]*d[0]+dir[1]*d[1],sc=cr>0?3:(dot>0?2:1);if(sc>bs){bs=sc;best=o}}
    dir=[best[0]-cur[0],best[1]-cur[1]];cur=best}
  if(guard>=n*n*4)return null;
  const rdp=(pts,eps)=>{const keep=new Uint8Array(pts.length);keep[0]=keep[pts.length-1]=1;const st=[[0,pts.length-1]];
    while(st.length){const[a,b]=st.pop(),A=pts[a],B=pts[b],dx_=B[0]-A[0],dy_=B[1]-A[1],L=Math.hypot(dx_,dy_)||1;let m=-1,id=-1;
      for(let i=a+1;i<b;i++){const d=Math.abs(dy_*(pts[i][0]-A[0])-dx_*(pts[i][1]-A[1]))/L;if(d>m){m=d;id=i}}
      if(m>eps){keep[id]=1;st.push([a,id],[id,b])}}
    return pts.filter((_,i)=>keep[i])};
  let far=0,fd=-1;for(let i=1;i<P.length;i++){const q=(P[i][0]-P[0][0])**2+(P[i][1]-P[0][1])**2;if(q>fd){fd=q;far=i}}
  const RA=rdp(P.slice(0,far+1),1.2),RB=rdp(P.slice(far).concat([P[0]]),1.2);let R=RA.slice(0,-1).concat(RB.slice(0,-1));
  for(let it=0;it<2;it++){const o=[];for(let i=0;i<R.length;i++){const a=R[i],b=R[(i+1)%R.length];o.push([.75*a[0]+.25*b[0],.75*a[1]+.25*b[1]],[.25*a[0]+.75*b[0],.25*a[1]+.75*b[1]])}R=o}
  return R.map(p=>[p[0]*g,p[1]*g])}
async function rasterize(sp,cm,dpi){
  const px=Math.round(cm*10*(dpi||300)/25.4),c=document.createElement('canvas');c.width=c.height=px;const x=c.getContext('2d');
  if(sp.k==='duck'){
    const svg=duck(sp.o).replace(/<text[\s\S]*?<\/text>/g,'').replace('<svg ','<svg xmlns="http://www.w3.org/2000/svg" width="'+px+'" height="'+px+'" ');
    x.drawImage(await loadImg('data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg)),0,0,px,px);
    if(sp.o.cap){x.fillStyle=INK;x.textAlign='center';x.font=Math.round(px*(sp.o.cap.length>11?.08:.1))+'px Lalezar,Chewy,cursive';x.fillText(sp.o.cap,px*.5,px*.91)}
    return{c,poly:cutPoly(sp.o.shape||'circle')}}
  const img=await loadImg(sp.k==='img'?QS.fileUrl('a:'+sp.id):(sp.u||QS.fileUrl(sp.f))),iw=img.naturalWidth||img.width,ih=img.naturalHeight||img.height;
  if(sp.k==='img'&&(sp.shape==='circle'||sp.shape==='square')){
    const sh=sp.shape,P=cutPoly(sh);x.fillStyle='#fff';x.beginPath();P.forEach((v,i)=>i?x.lineTo(v[0]*px/100,v[1]*px/100):x.moveTo(v[0]*px/100,v[1]*px/100));x.closePath();x.fill();
    x.save();x.beginPath();if(sh==='circle')x.arc(px/2,px/2,px*.47,0,Math.PI*2);else x.roundRect(px*.04,px*.04,px*.92,px*.92,px*.16);x.clip();
    const bs=px*.94,s=Math.max(bs/iw,bs/ih);x.drawImage(img,(px-iw*s)/2,(px-ih*s)/2,iw*s,ih*s);x.restore();
    return{c,poly:P,srcDpi:(dpi||300)/s}}
  const t=document.createElement('canvas');t.width=t.height=px;const tc=t.getContext('2d');
  const m=Math.round(px*.05),fs=Math.min((px-2*m)/iw,(px-2*m)/ih);tc.drawImage(img,(px-iw*fs)/2,(px-ih*fs)/2,iw*fs,ih*fs); /* contain-fit; leaves room for the white border so the cut line is never clipped by the sheet box */
  let h=outline(t,px,Math.round(Math.max(px*.025,1.2*(dpi||300)/25.4)));
  if(!h){
  const d=tc.getImageData(0,0,px,px).data,pts=[],b=px*.03;
  for(let yy=0;yy<px;yy+=3)for(let xx=0;xx<px;xx+=3)if(d[(yy*px+xx)*4+3]>20)pts.push([xx,yy]);
  h=pts.length>2?hull(pts):[];
  if(h.length<3)h=[[0,0],[px,0],[px,px],[0,px]];
  else{const mx=h.reduce((a,p)=>a+p[0],0)/h.length,my=h.reduce((a,p)=>a+p[1],0)/h.length;
    h=h.map(p=>{const dx=p[0]-mx,dy=p[1]-my,l=Math.hypot(dx,dy)||1;return[Math.max(0,Math.min(px,p[0]+dx/l*b*1.5)),Math.max(0,Math.min(px,p[1]+dy/l*b*1.5))]})}
  }
  x.fillStyle='#fff';x.beginPath();h.forEach((p,i)=>i?x.lineTo(p[0],p[1]):x.moveTo(p[0],p[1]));x.closePath();x.fill();x.drawImage(t,0,0);
  return{c,poly:h.map(p=>[p[0]/px*100,p[1]/px*100]),srcDpi:(dpi||300)/fs}}
const PHONES={i16:['iPhone 16',71.6,147.6,11,[4,4,26,26]],i16p:['iPhone 16 Pro Max',77.6,163,12,[4,4,36,36]],s25:['Galaxy S25',70.5,146.9,9,[6,6,14,36]],s25u:['Galaxy S25 Ultra',77.6,162.8,8,[6,6,14,44]],px9:['Pixel 9',72,152.8,10,[0,22,72,14]],a6:['ورق A6',105,148,0,[0,0,0,0]],a5:['ورق A5',148,210,0,[0,0,0,0]]};
const SHEETS={a4:[210,297],a3:[297,420]};
/* print items: {w,h (mm), c (canvas), cuts:[[[x,y] mm...]], trim?:bool} */
function squareItem(r,cm){const s=cm*10;return{w:s,h:s,c:r.c,cuts:[r.poly.map(v=>[v[0]*s/100,v[1]*s/100])]}}
function sheetItem(d,rs){const [nm,w,h]=PHONES[d.model]||PHONES.a6,k=300/25.4,c=mk(Math.round(w*k),Math.round(h*k)),x=c.getContext('2d'),cuts=[];
  d.stickers.forEach((s,i)=>{const sm=s.s*10,r=(s.r||0)*Math.PI/180,co=Math.cos(r),si=Math.sin(r);
    x.save();x.translate(s.x*k,s.y*k);x.rotate(r);x.drawImage(rs[i].c,-sm*k/2,-sm*k/2,sm*k,sm*k);x.restore();
    cuts.push(rs[i].poly.map(v=>{const lx=(v[0]-50)*sm/100,ly=(v[1]-50)*sm/100;return[s.x+lx*co-ly*si,s.y+lx*si+ly*co]}))});
  return{w,h,c,cuts,trim:true}}
function layout(items,sheet){const [PW,PHH]=SHEETS[sheet]||SHEETS.a4,M=10,G=4;let pg=0,x=M,y=M,rh=0;const out=[];
  items.slice().sort((a,b)=>b.h-a.h).forEach(it=>{if(x+it.w>PW-M){x=M;y+=rh+G;rh=0}if(y+it.h>PHH-M){pg++;x=M;y=M;rh=0}out.push({...it,x,y,pg});x+=it.w+G;rh=Math.max(rh,it.h)});
  return{out,pages:pg+1,PW,PH:PHH}}
function buildPdf(items,o){o=o||{};const L=layout(items,o.sheet),doc=new window.jspdf.jsPDF({unit:'mm',format:[L.PW,L.PH],orientation:'portrait',compress:true});
  doc.setProperties({title:'QuackStick print sheet',subject:o.label||'',creator:'QuackStick Studio'});
  for(let p=1;p<L.pages;p++)doc.addPage([L.PW,L.PH],'portrait');
  L.out.forEach(i=>{doc.setPage(i.pg+1);doc.addImage(i.c.toDataURL('image/png'),'PNG',i.x,i.y,i.w,i.h)});
  L.out.forEach(i=>{doc.setPage(i.pg+1);doc.setDrawColor(255,0,255);doc.setLineWidth(.25);
    i.cuts.forEach(cu=>{const q=cu.map(v=>[i.x+v[0],i.y+v[1]]);doc.lines(q.slice(1).map((v,k)=>[v[0]-q[k][0],v[1]-q[k][1]]),q[0][0],q[0][1],[1,1],'S',true)});
    if(i.trim){doc.setDrawColor(0,0,0);doc.setLineWidth(.1);doc.rect(i.x,i.y,i.w,i.h)}});
  doc.setDrawColor(0,0,0);doc.setLineWidth(.25);doc.setFontSize(6);doc.setTextColor(90);
  for(let p=1;p<=L.pages;p++){doc.setPage(p);doc.setDrawColor(0,0,0);doc.setLineWidth(.25);const W=L.PW,H=L.PH,a=3,b=8;
    [[a,a,1,1],[W-a,a,-1,1],[a,H-a,1,-1],[W-a,H-a,-1,-1]].forEach(([x,y,dx,dy])=>{doc.line(x,y,x+dx*(b-a),y);doc.line(x,y,x,y+dy*(b-a))});
    doc.text('QuackStick | '+(o.label||'')+' | cut line: magenta 0.25mm | art 300dpi | '+p+'/'+L.pages,W/2,H-3,{align:'center'})}
  return{blob:doc.output('blob'),L}}
/* "pro" PDF: written by hand so it can be CMYK, have a real spot colour (CutContour) and Trim/Bleed boxes. Colour conversion is naive (no ICC): ask your printer's RIP to colour-manage. */
async function deflate(u8){const cs=new CompressionStream('deflate'),w=cs.writable.getWriter();w.write(u8);w.close();return new Uint8Array(await new Response(cs.readable).arrayBuffer())}
function cmykOf(c){const w=c.width,h=c.height,d=c.getContext('2d').getImageData(0,0,w,h).data,cm=new Uint8Array(w*h*4),a=new Uint8Array(w*h);
  for(let i=0,j=0,k=0;i<d.length;i+=4,j+=4,k++){a[k]=d[i+3];const r=d[i]/255,g=d[i+1]/255,b=d[i+2]/255,K=1-Math.max(r,g,b);
    if(K>=1){cm[j+3]=255}else{cm[j]=Math.round((1-r-K)/(1-K)*255);cm[j+1]=Math.round((1-g-K)/(1-K)*255);cm[j+2]=Math.round((1-b-K)/(1-K)*255);cm[j+3]=Math.round(K*255)}}
  return{cm,a}}
async function buildProPdf(items,o){o=o||{};const L=layout(items,o.sheet),K=72/25.4,f=v=>(+v).toFixed(3),enc=s=>{const u=new Uint8Array(s.length);for(let i=0;i<s.length;i++)u[i]=s.charCodeAt(i)&255;return u};
  const body=[];let next=1;const nid=()=>next++,put=(id,u8)=>{body[id]=u8};
  const cat=ps=>{let n=0;ps.forEach(p=>n+=p.length);const u=new Uint8Array(n);let o2=0;ps.forEach(p=>{u.set(p,o2);o2+=p.length});return u};
  const obj=(id,dict,stream)=>put(id,stream?cat([enc(id+' 0 obj\n<< '+dict+' /Length '+stream.length+' >>\nstream\n'),stream,enc('\nendstream\nendobj\n')]):enc(id+' 0 obj\n'+dict+'\nendobj\n'));
  const cat1=nid(),pages=nid(),font=nid(),sep=nid(),info=nid();
  obj(font,'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
  obj(sep,'[/Separation /CutContour /DeviceCMYK << /FunctionType 2 /Domain [0 1] /C0 [0 0 0 0] /C1 [0 1 0 0] /N 1 >>]');
  obj(info,'<< /Title (QuackStick print sheet) /Producer (QuackStick Studio) /Subject ('+String(o.label||'').replace(/[()\\]/g,'')+') >>');
  const imgId=new Map();
  for(const it of L.out){if(imgId.has(it.c))continue;const {cm,a}=cmykOf(it.c),sm=nid(),im=nid();
    obj(sm,'/Type /XObject /Subtype /Image /Width '+it.c.width+' /Height '+it.c.height+' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode',await deflate(a));
    obj(im,'/Type /XObject /Subtype /Image /Width '+it.c.width+' /Height '+it.c.height+' /ColorSpace /DeviceCMYK /BitsPerComponent 8 /Filter /FlateDecode /SMask '+sm+' 0 R',await deflate(cm));imgId.set(it.c,im)}
  const kids=[];
  for(let p=0;p<L.pages;p++){const W=L.PW*K,H=L.PH*K,names=new Map();let cs='';
    L.out.filter(i=>i.pg===p).forEach(i=>{if(!names.has(i.c))names.set(i.c,'Im'+names.size);cs+='q '+f(i.w*K)+' 0 0 '+f(i.h*K)+' '+f(i.x*K)+' '+f((L.PH-i.y-i.h)*K)+' cm /'+names.get(i.c)+' Do Q\n'});
    cs+='/CS0 CS 1 SCN 0.709 w 1 j 1 J\n';
    L.out.filter(i=>i.pg===p).forEach(i=>i.cuts.forEach(cu=>{cu.forEach((v,j)=>{cs+=f((i.x+v[0])*K)+' '+f((L.PH-i.y-v[1])*K)+(j?' l\n':' m\n')});cs+='h S\n'}));
    cs+='0 0 0 1 K 0.5 w\n';
    L.out.filter(i=>i.pg===p&&i.trim).forEach(i=>{cs+=f(i.x*K)+' '+f((L.PH-i.y-i.h)*K)+' '+f(i.w*K)+' '+f(i.h*K)+' re S\n'});
    const a=3,b=8,Wm=L.PW,Hm=L.PH,mk_=(x1,y1,x2,y2)=>f(x1*K)+' '+f((Hm-y1)*K)+' m '+f(x2*K)+' '+f((Hm-y2)*K)+' l S\n';
    [[a,a,1,1],[Wm-a,a,-1,1],[a,Hm-a,1,-1],[Wm-a,Hm-a,-1,-1]].forEach(([x,y,dx,dy])=>{cs+=mk_(x,y,x+dx*(b-a),y)+mk_(x,y,x,y+dy*(b-a))});
    const label=('QuackStick | '+(o.label||'')+' | CMYK | spot CutContour | '+(p+1)+'/'+L.pages).replace(/[()\\]/g,'');
    cs+='BT /F1 5 Tf 0 0 0 0.6 k '+f(W/2-label.length*1.3)+' '+f(3*K)+' Td ('+label+') Tj ET\n';
    const cid=nid(),pid=nid();obj(cid,'',enc(cs));
    const xo=[...names].map(([c,n])=>'/'+n+' '+imgId.get(c)+' 0 R').join(' ');
    obj(pid,'<< /Type /Page /Parent '+pages+' 0 R /MediaBox [0 0 '+f(W)+' '+f(H)+'] /TrimBox [0 0 '+f(W)+' '+f(H)+'] /BleedBox [0 0 '+f(W)+' '+f(H)+'] /Contents '+cid+' 0 R /Resources << /XObject << '+xo+' >> /ColorSpace << /CS0 '+sep+' 0 R >> /Font << /F1 '+font+' 0 R >> >> >>');kids.push(pid)}
  obj(cat1,'<< /Type /Catalog /Pages '+pages+' 0 R >>');
  obj(pages,'<< /Type /Pages /Kids ['+kids.map(k=>k+' 0 R').join(' ')+'] /Count '+kids.length+' >>');
  const parts=[enc('%PDF-1.4\n%\xe2\xe3\xcf\xd3\n')],offs=[];let len=parts[0].length;
  for(let i=1;i<next;i++){offs[i]=len;parts.push(body[i]);len+=body[i].length}
  let x='xref\n0 '+next+'\n0000000000 65535 f \n';for(let i=1;i<next;i++)x+=String(offs[i]).padStart(10,'0')+' 00000 n \n';
  x+='trailer\n<< /Size '+next+' /Root '+cat1+' 0 R /Info '+info+' 0 R >>\nstartxref\n'+len+'\n%%EOF\n';parts.push(enc(x));
  return{blob:new Blob(parts,{type:'application/pdf'}),L}}
function drawCase(d,ppm,ps,guide){const [n,w,h,r,cm]=PHONES[d.model]||PHONES.i16,c=mk(Math.round(w*ppm),Math.round(h*ppm)),x=c.getContext('2d');
  x.beginPath();x.roundRect(0,0,c.width,c.height,r*ppm);x.fillStyle=d.caseColor||'#fff';x.fill();x.save();x.clip();
  d.stickers.forEach((s,i)=>{const z=s.s*10*ppm;x.save();x.translate(s.x*ppm,s.y*ppm);x.rotate((s.r||0)*Math.PI/180);x.drawImage(ps[i].c,-z/2,-z/2,z,z);x.restore()});
  x.restore();
  if(guide&&cm[2]>0){x.setLineDash([8,6]);x.strokeStyle='#FF7A1A';x.lineWidth=2;x.strokeRect(cm[0]*ppm,cm[1]*ppm,cm[2]*ppm,cm[3]*ppm);x.setLineDash([])}
  x.beginPath();x.roundRect(1,1,c.width-2,c.height-2,r*ppm);x.strokeStyle='#3A2817';x.lineWidth=2;x.stroke();return c}
async function preview(d){const ps=await Promise.all(d.stickers.map(s=>rasterize(s.sp,s.s,60)));
  if(d.type==='sticker'){const c=mk(240);c.getContext('2d').drawImage(ps[0].c,0,0,240,240);return c.toDataURL('image/png')}
  return drawCase(d,3,ps).toDataURL('image/png')}
Object.assign(QS,{INK,duck,ruler,cutPoly,hull,outline,mk,rasterize,loadImg,layout,squareItem,sheetItem,buildPdf,buildProPdf,drawCase,preview,PHONES,SHEETS,fileUrl:f=>f});
})();
