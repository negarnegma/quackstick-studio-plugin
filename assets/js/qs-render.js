/* Fills inline duck images for products/landing cards. Re-renders once Lalezar is loaded so captions are measured right. */
(function(){function go(){document.querySelectorAll('.qs-duck-img[data-qs]').forEach(function(e){try{e.innerHTML=QS.duck(JSON.parse(e.dataset.qs))}catch(_){}})}
go();if(document.fonts&&document.fonts.load)document.fonts.load('20px Lalezar','قات‌قات Aa').then(go,function(){})})();
