<?php defined('ABSPATH') || exit; ?>
<div class="qs-app" dir="rtl">
<section class="studio" id="studio"><div class="wrap">
  <h2>استودیوی طراحی اردک</h2>
  <p class="sub">اندازه رو انتخاب کن، به اردکت لباس بپوشون و با خط‌کش‌ها بسنجش. نوارهای نارنجی روی خط‌کش‌ها دقیقاً نشون می‌دن استیکر از کجا شروع می‌شه و کجا تموم.</p>
  <div class="lab">
    <div>
      <div class="rulerbox"><div class="rg" id="rg"></div></div>
      <div class="read" id="read"></div>
      <p class="note">صفحه‌نمایش‌ها با هم فرق دارن، پس «اندازه‌ی واقعی» فقط تقریبیه. یه خط‌کش واقعی رو کنار صفحه بگیر تا مطمئن بشی.</p>
    </div>
    <div class="ctl" id="ctl">
      <h3>اندازه</h3><div class="chips" id="c-size"></div>
      <h3>شکل</h3><div class="chips" id="c-shape"></div>
      <h3>رنگ اردک</h3><div class="chips" id="c-body"></div>
      <h3>اکسسوری</h3><div class="chips" id="c-acc"></div>
      <h3>پس‌زمینه</h3><div class="chips" id="c-bg"></div>
      <h3>کپشن</h3><input type="text" id="cap" dir="auto" maxlength="16" placeholder="مثلاً حمله قات‌قات" aria-label="کپشن">
      <h3>خط‌کش</h3><div class="chips" id="c-unit"></div>
      <h3>مقایسه با</h3><div class="chips" id="c-ref"></div>
      <div class="chips" style="margin-top:8px"><button class="chip" id="c-true" aria-pressed="false">اندازه‌ی واقعی (1:1)</button></div>
      <div class="tot"><span id="price"></span><button class="btn" id="addc">افزودن به سبد</button></div>
    </div>
  </div>
</div></section>

<section id="sketch"><div class="wrap">
  <h2>نقاشی آزاد</h2>
  <p class="sub">هر چی دوست داری بکش. نقاشیت تبدیل می‌شه به استیکر برش‌خورده؛ می‌تونی تکی بخریش یا روی قاب گوشی بچسبونی. صفحه ۶ سانت عرض داره، برای برنامه‌ریزی خطوط از خط‌کش‌ها کمک بگیر.</p>
  <div class="lab">
    <div class="rulerbox"><div class="rg" id="srg"><div class="rk"></div><div id="srt"></div><div id="srl"></div><canvas id="pad" width="600" height="600" style="width:300px;height:300px" aria-label="صفحه‌ی نقاشی"></canvas></div></div>
    <div class="ctl">
      <h3>رنگ قلم</h3><div class="chips" id="pc"></div>
      <h3>ضخامت</h3><div class="chips" id="pw"></div>
      <div class="chips" style="margin-top:12px"><button class="chip" id="eraser" aria-pressed="false">پاک‌کن</button><button class="chip" id="undo">برگردان</button><button class="chip" id="clr">پاک‌کردن همه</button></div>
      <h3>خط‌کش</h3><div class="chips" id="c-unit2"></div>
      <div class="tot"><span id="sprice"></span><button class="btn" id="sbuy">افزودن به سبد</button></div>
      <div class="chips" style="margin-top:10px"><button class="chip" id="ssave">ذخیره برای قاب گوشی</button></div>
    </div>
  </div>
</div></section>

<section id="upload"><div class="wrap">
  <h2>آپلود عکس</h2>
  <p class="sub">عکست رو بارگذاری کن، شکل برش رو انتخاب کن و با کشیدن و زوم جاش رو تنظیم کن. روی خط‌کش‌ها می‌بینی چاپ شده چند سانته.</p>
  <div class="lab">
    <div class="rulerbox"><div class="rg" id="urg"><div class="rk"></div><div id="urt"></div><div id="url"></div><canvas id="upad" width="900" height="900" style="width:300px;height:300px;cursor:grab;touch-action:none" aria-label="پیش‌نمایش عکس"></canvas></div><p class="note" id="uhint">هنوز عکسی انتخاب نشده.</p></div>
    <div class="ctl">
      <h3>عکس</h3><label class="btn alt" style="cursor:pointer">انتخاب عکس<input type="file" id="ufile" accept="image/png,image/jpeg,image/webp" hidden></label>
      <h3>شکل برش</h3><div class="chips" id="u-shape"></div>
      <h3>زوم</h3><label><input type="range" id="uzoom" min="1" max="3" step=".05" value="1"></label>
      <h3>اندازه</h3><div class="chips" id="u-size"></div>
      <h3>خط‌کش</h3><div class="chips" id="c-unit3"></div>
      <label style="margin-top:12px"><input type="checkbox" id="uok"> تأیید می‌کنم حق استفاده از این عکس رو دارم.</label>
      <div class="tot"><span id="uprice"></span><button class="btn" id="ubuy">افزودن به سبد</button></div>
      <div class="chips" style="margin-top:10px"><button class="chip" id="usave">ذخیره برای قاب گوشی</button></div>
    </div>
  </div>
</div></section>

<section class="studio" id="phone"><div class="wrap">
  <h2>کارگاه قاب گوشی</h2>
  <p class="sub">گوشیت رو انتخاب کن و هر تعداد استیکر که خواستی بچسبون. با کشیدن جابه‌جاشون کن و با اسلایدرها بزرگ و کج‌شون کن. خط‌کش‌ها اندازه‌ی واقعی قابن و کادر خط‌چین، جای دوربینه.</p>
  <div class="lab">
    <div><div class="rulerbox"><div class="rg" id="prg"></div></div><div class="read" id="pread"></div></div>
    <div class="ctl">
      <h3>سطح</h3><div class="chips" id="c-surface"></div>
      <h3 id="h-model">گوشی تو</h3><div class="chips" id="c-model"></div>
      <h3 id="h-case">رنگ قاب</h3><div class="chips" id="c-case"></div>
      <h3>افزودن استیکر</h3><div class="tray" id="tray"></div>
      <div id="sel" hidden><h3>انتخاب‌شده: <span id="sdim"></span></h3>
        <label>اندازه <input type="range" id="ssz" min="1.5" max="6" step=".5"></label>
        <label>چرخش <input type="range" id="srot" min="-180" max="180"></label>
        <div class="chips"><button class="chip" id="sdel">حذف استیکر</button></div></div>
      <div class="chips" style="margin-top:8px"><button class="chip" id="autoarr" style="display:none">چیدمان خودکار روی ورق</button></div>
      <div class="tot"><span id="pprice"></span><button class="btn" id="paddc">افزودن قاب به سبد</button></div>
    </div>
  </div>
</div></section>

<div id="qs-toast" class="qs-toast" hidden></div>
</div>
