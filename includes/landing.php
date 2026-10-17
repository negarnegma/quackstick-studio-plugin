<?php
defined( 'ABSPATH' ) || exit;

/* create the three pages once (designer, request form, landing) */
function qs_ensure_pages( $force = false ) {
	if ( ! $force && get_option( 'qs_pages_done' ) === QS_VER ) { return; }
	$pages = array(
		'designer' => array( 'طراحی استیکر', '[quackstick_designer]' ),
		'request'  => array( 'سفارش طراحی', '[quackstick_request]' ),
		'proof'    => array( 'تأیید طرح', '[quackstick_proof]' ),
		'landing'  => array( 'خانه', '[quackstick_landing]' ),
	);
	foreach ( $pages as $k => $p ) {
		$id = (int) get_option( 'qs_pg_' . $k );
		if ( $id && get_post_status( $id ) ) { continue; }
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_content' => $p[1], 'meta_input' => array( '_wp_page_template' => 'page-full.php' ) ) );
		if ( $id && ! is_wp_error( $id ) ) { update_option( 'qs_pg_' . $k, $id ); }
	}
	update_option( 'qs_pages_done', QS_VER );
}
add_action( 'admin_init', 'qs_ensure_pages' );

add_shortcode( 'quackstick_landing', function () {
	qs_enqueue_landing();
	$d   = qs_page_url( 'designer' );
	$r   = qs_page_url( 'request' );
	$tgu = qs_tg_url();
	$min = (float) qs_opt( 'gift_min' );
	$minp = $min > 0 ? qs_fa( wp_strip_all_tags( wc_price( $min ) ) ) : '';
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$duck = function ( $o ) { return '<span class="qs-duck-img" data-qs="' . esc_attr( wp_json_encode( $o ) ) . '"></span>'; };
	ob_start();
	?>
<div class="qs-land" dir="rtl">
	<div class="ql-ruler" aria-hidden="true"></div>

	<header class="ql-sec ql-hero"><div class="ql-in ql-grid">
		<div>
			<span class="ql-kicker">فروشگاه استیکر اردکی</span>
			<h1>استیکر با کمی قات‌قات.</h1>
			<p>یه اردک آماده بردار، عکس یا نقاشی خودت رو استیکر کن، یا یه قاب گوشی بساز و پر از استیکرش کن. همه‌چیز رو قبل از چاپ با خط‌کش می‌سنجی.</p>
			<p><a class="ql-btn" href="<?php echo esc_url( $d ); ?>">شروع طراحی</a> <a class="ql-btn ql-alt" href="#request">طراحی رو بسپار به ما</a></p>
		</div>
		<div class="ql-stage" aria-hidden="true">
			<div class="ql-stk"><?php echo $duck( array( 'body' => '#FFD23F', 'acc' => 'crown', 'bg' => '#CDEBFF', 'shape' => 'circle', 'cap' => '' ) ); // phpcs:ignore ?></div>
			<div class="ql-dim"><span>8 سانت</span></div>
			<div class="ql-mini m1"><?php echo $duck( array( 'body' => '#8FD694', 'acc' => 'top', 'bg' => '#FFE3C2', 'shape' => 'square', 'cap' => '' ) ); // phpcs:ignore ?></div>
			<div class="ql-mini m2"><?php echo $duck( array( 'body' => '#FFD23F', 'acc' => 'glasses', 'bg' => '#FFB3C7', 'shape' => 'blob', 'cap' => '' ) ); // phpcs:ignore ?></div>
		</div>
	</div></header>

	<section class="ql-sec ql-paper"><div class="ql-in">
		<h2>چطور کار می‌کنه؟</h2>
		<ol class="ql-steps">
			<li><b>۱</b><h3>طراحی کن</h3><p>اردک‌ساز، نقاشی آزاد یا عکس خودت.</p></li>
			<li><b>۲</b><h3>با خط‌کش بسنج</h3><p>اندازه‌ی واقعی رو می‌بینی، نه حدسی.</p></li>
			<li><b>۳</b><h3>سفارش بده</h3><p>فایل چاپ رو خودمون می‌سازیم، تو نگران چیزی نیستی.</p></li>
			<li><b>۴</b><h3>ما چاپ و برش می‌کنیم</h3><p>دور هر استیکر یه حاشیه‌ی سفید و برش دقیق.</p></li>
		</ol>
	</div></section>

	<section class="ql-sec"><div class="ql-in">
		<h2>چهار راه برای ساختن</h2>
		<div class="ql-ways">
			<a href="<?php echo esc_url( $d . '#studio' ); ?>"><?php echo $duck( array( 'body' => '#FFD23F', 'acc' => 'bow', 'bg' => '#FFD23F', 'shape' => 'circle', 'cap' => '' ) ); // phpcs:ignore ?><h3>اردک‌ساز</h3><p>لباس، کلاه و عینک بپوشون.</p></a>
			<a href="<?php echo esc_url( $d . '#sketch' ); ?>"><?php echo $duck( array( 'body' => '#FFFFFF', 'acc' => 'none', 'bg' => '#FFE3C2', 'shape' => 'blob', 'cap' => 'نقاشی' ) ); // phpcs:ignore ?><h3>نقاشی آزاد</h3><p>با دست خودت بکش.</p></a>
			<a href="<?php echo esc_url( $d . '#upload' ); ?>"><?php echo $duck( array( 'body' => '#7CC5FF', 'acc' => 'beanie', 'bg' => '#D8C7FF', 'shape' => 'square', 'cap' => 'عکس' ) ); // phpcs:ignore ?><h3>آپلود عکس</h3><p>عکست رو استیکر کن.</p></a>
			<a href="<?php echo esc_url( $d . '#phone' ); ?>"><?php echo $duck( array( 'body' => '#FF9EB5', 'acc' => 'crown', 'bg' => '#BFE6FF', 'shape' => 'circle', 'cap' => '' ) ); // phpcs:ignore ?><h3>قاب گوشی</h3><p>هر چند تا استیکر که خواستی.</p></a>
		</div>
	</div></section>

	<section class="ql-sec ql-paper"><div class="ql-in">
		<h2>اندازه‌ها</h2>
		<p>روی صفحه تقریباً اندازه‌ی واقعی نشون داده می‌شن. یه خط‌کش واقعی کنارش بگیر.</p>
		<div class="ql-sizes">
			<?php foreach ( array( 10, 8, 6, 4 ) as $cm ) { ?>
				<div style="width:<?php echo (int) $cm * 10; ?>%;aspect-ratio:1"><span><?php echo (int) $cm; ?> سانت</span></div>
			<?php } ?>
		</div>
	</div></section>

	<?php if ( $min > 0 ) { ?>
	<section class="ql-sec"><div class="ql-in"><div class="ql-gift">
		<h2>خرید بالای <?php echo esc_html( $minp ); ?>؟ یه قاب گوشی هدیه!</h2>
		<p>سبدت که به این مبلغ برسه، توی صفحه‌ی سبد مدل گوشیت و تم دلخواهت رو می‌نویسی و طراح‌ها همون رو برات طراحی می‌کنن. توی تلگرام باهات هماهنگ می‌کنیم.</p>
		<a class="ql-btn" href="<?php echo esc_url( $d ); ?>">برم سبد رو پر کنم</a>
	</div></div></section>
	<?php } ?>

	<?php if ( function_exists( 'wc_get_products' ) && wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'visibility' => 'catalog' ) ) ) { ?>
	<section class="ql-sec ql-paper"><div class="ql-in">
		<h2>اردک‌های آماده</h2>
		<p><a class="ql-btn ql-alt" href="<?php echo esc_url( $shop ); ?>">دیدن همه‌ی محصولات</a></p>
		<div class="ql-prod"><?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?></div>
	</div></section>
	<?php } ?>

	<section class="ql-sec"><div class="ql-in">
		<h2>طراحی رو بسپار به ما</h2>
		<?php echo do_shortcode( '[quackstick_request]' ); ?>
	</div></section>

	<section class="ql-sec ql-paper"><div class="ql-in ql-faq">
		<h2>سؤال‌های پرتکرار</h2>
		<details><summary>عکسم چه کیفیتی باید داشته باشه؟</summary><p>هر چی بزرگ‌تر و واضح‌تر بهتر. اگه عکس خیلی کوچیک باشه، چاپش لب‌پر می‌شه؛ توی صفحه‌ی آپلود اندازه‌ی چاپ رو با خط‌کش می‌بینی.</p></details>
		<details><summary>برش چطوریه؟</summary><p>دور هر استیکر یه حاشیه‌ی سفید می‌ذاریم و روی لبه‌ی بیرونی اون برش می‌خوره.</p></details>
		<?php if ( $min > 0 ) { ?><details><summary>قاب هدیه چطور کار می‌کنه؟</summary><p>وقتی جمع سبد از <?php echo esc_html( $minp ); ?> بیشتر بشه، توی صفحه‌ی سبد مدل گوشی و تم رو می‌نویسی. اگه بعدش سبد از این مبلغ کمتر بشه، قاب هدیه برداشته می‌شه.</p></details><?php } ?>
		<details><summary>می‌تونم طراحی رو بسپارم به شما؟</summary><p>آره. فرم «طراحی رو بسپار به ما» رو پر کن و آیدی تلگرامت رو بذار؛ ما اونجا بهت پیام می‌دیم.</p></details>
		<details><summary>از هر عکسی می‌تونم استفاده کنم؟</summary><p>فقط عکسی رو آپلود کن که حق استفاده ازش رو داری.</p></details>
	</div></section>

	<section class="ql-sec ql-cta"><div class="ql-in">
		<h2>بریم سراغ طراحی؟</h2>
		<p><a class="ql-btn" href="<?php echo esc_url( $d ); ?>">شروع طراحی</a> <a class="ql-btn ql-alt" href="<?php echo esc_url( $shop ); ?>">فروشگاه</a> <a class="ql-btn ql-alt" href="<?php echo esc_url( $r ); ?>">سفارش طراحی</a><?php if ( $tgu ) { ?> <a class="ql-btn ql-alt" href="<?php echo esc_url( $tgu ); ?>" target="_blank" rel="noopener">تلگرام</a><?php } ?></p>
	</div></section>
	<div class="ql-ruler" aria-hidden="true"></div>
</div>
	<?php
	return ob_get_clean();
} );
