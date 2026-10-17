/* Latin digits -> Persian digits on storefront pages (text nodes only; skips code, inputs, dir=ltr, and digits glued to Latin names like S25). */
(function(){
const M='۰۱۲۳۴۵۶۷۸۹',SKIP=/^(SCRIPT|STYLE|TEXTAREA|INPUT|CODE|PRE|NOSCRIPT)$/;
const fix=s=>s.replace(/[0-9]+/g,(m,off)=>/[A-Za-z]/.test(s.slice(0,off).replace(/\s+$/,'').slice(-1))?m:m.replace(/[0-9]/g,d=>M[d]));
function walk(n){
  if(n.nodeType===3){if(/[0-9]/.test(n.nodeValue)){const v=fix(n.nodeValue);if(v!==n.nodeValue)n.nodeValue=v}return}
  if(n.nodeType!==1||SKIP.test(n.tagName)||n.isContentEditable||n.getAttribute('dir')==='ltr'||n.classList.contains('qs-nofa'))return;
  for(let c=n.firstChild;c;c=c.nextSibling)walk(c)}
walk(document.body);
let q=[],t=0;
new MutationObserver(ms=>{ms.forEach(m=>{m.addedNodes.forEach(a=>q.push(a));if(m.type==='characterData')q.push(m.target)});
  if(!t)t=requestAnimationFrame(()=>{t=0;const l=q;q=[];l.forEach(walk)})}).observe(document.body,{childList:true,subtree:true,characterData:true});
})();
