<?php
defined( 'ABSPATH' ) || exit;

/* ---------- design requests: stored privately, handled by us on Telegram ---------- */
function qs_req_status() { return array( 'new' => 'جدید', 'talking' => 'در گفتگو', 'done' => 'تمام شد' ); }

add_action( 'init', function () {
	register_post_type( 'qs_request', array(
		'labels'          => array( 'name' => 'درخواست‌های طراحی', 'singular_name' => 'درخواست طراحی', 'menu_name' => 'درخواست‌های طراحی', 'edit_item' => 'درخواست طراحی', 'search_items' => 'جستجو', 'not_found' => 'درخواستی نیست' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'woocommerce',
		'supports'        => array( 'title' ),
		// Same permission set WooCommerce uses for orders (admins + shop managers).
		// IMPORTANT: never map meta caps (edit_post/read_post/delete_post) to a plain capability such as
		// manage_woocommerce: WordPress would then treat manage_woocommerce itself as a meta cap and deny it to everyone.
		'capability_type' => 'shop_order',
		'map_meta_cap'    => true,
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
	) );
} );

add_filter( 'manage_qs_request_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'title' => 'نام', 'qs_tg' => 'تلگرام', 'qs_kind' => 'نوع', 'qs_st' => 'وضعیت', 'date' => 'تاریخ' );
} );
add_action( 'manage_qs_request_posts_custom_column', function ( $col, $id ) {
	if ( 'qs_tg' === $col ) {
		$t = get_post_meta( $id, '_qs_tg', true );
		echo $t ? '<a href="' . esc_url( 'https://t.me/' . $t ) . '" target="_blank" rel="noopener" style="direction:ltr;display:inline-block">@' . esc_html( $t ) . '</a>' : '';
	} elseif ( 'qs_kind' === $col ) {
		echo esc_html( get_post_meta( $id, '_qs_kind', true ) . ' ' . get_post_meta( $id, '_qs_detail', true ) );
	} elseif ( 'qs_st' === $col ) {
		$s = qs_req_status();
		$k = get_post_meta( $id, '_qs_st', true );
		echo esc_html( isset( $s[ $k ] ) ? $s[ $k ] : $s['new'] );
	}
}, 10, 2 );

add_action( 'add_meta_boxes', function () { add_meta_box( 'qs_req', 'جزئیات درخواست', 'qs_req_box', 'qs_request', 'normal', 'high' ); } );
function qs_req_box( $p ) {
	$m   = function ( $k ) use ( $p ) { return get_post_meta( $p->ID, $k, true ); };
	$tg  = $m( '_qs_tg' );
	$ref = $m( '_qs_ref' );
	wp_nonce_field( 'qs_req_save', 'qs_req_nonce' );
	echo '<p><strong>تلگرام:</strong> <a href="' . esc_url( 'https://t.me/' . $tg ) . '" target="_blank" rel="noopener">@' . esc_html( $tg ) . '</a> &nbsp; <strong>نوع:</strong> ' . esc_html( $m( '_qs_kind' ) . ' ' . $m( '_qs_detail' ) ) . '</p>';
	echo '<p style="white-space:pre-wrap;background:#f6f6f6;padding:10px;border-radius:6px">' . esc_html( $m( '_qs_brief' ) ) . '</p>';
	if ( $ref ) {
		$u = add_query_arg( array( 'action' => 'qs_file', '_wpnonce' => wp_create_nonce( 'qs_file' ), 'f' => $ref ), admin_url( 'admin-post.php' ) );
		echo '<p><a href="' . esc_url( $u ) . '" target="_blank"><img src="' . esc_url( $u ) . '" style="max-width:260px;height:auto;border:1px solid #ccc" alt=""></a></p>';
	}
	echo '<p><label>وضعیت: <select name="qs_st">';
	foreach ( qs_req_status() as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '" ' . selected( $m( '_qs_st' ) ?: 'new', $k, false ) . '>' . esc_html( $l ) . '</option>'; }
	echo '</select></label></p><p><label>یادداشت داخلی:<br><textarea name="qs_note" rows="3" style="width:100%">' . esc_textarea( $m( '_qs_note' ) ) . '</textarea></label></p>';
}
add_action( 'save_post_qs_request', function ( $id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! isset( $_POST['qs_req_nonce'] ) || ! wp_verify_nonce( $_POST['qs_req_nonce'], 'qs_req_save' ) || ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$st = isset( $_POST['qs_st'] ) ? sanitize_key( $_POST['qs_st'] ) : 'new';
	update_post_meta( $id, '_qs_st', isset( qs_req_status()[ $st ] ) ? $st : 'new' );
	update_post_meta( $id, '_qs_note', sanitize_textarea_field( wp_unslash( isset( $_POST['qs_note'] ) ? $_POST['qs_note'] : '' ) ) );
} );

/* ---------- submit ---------- */
function qs_ajax_request() {
	check_ajax_referer( 'qs_req', 'nonce' );
	if ( ! empty( $_POST['website'] ) ) { wp_send_json_success( array( 'id' => 0 ) ); } // honeypot
	$rl = 'qs_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' );
	$n  = (int) get_transient( $rl );
	if ( $n >= 5 ) { wp_send_json_error( array( 'msg' => 'درخواست‌های زیادی فرستادی. کمی بعد دوباره امتحان کن.' ) ); }
	$g = function ( $k ) { return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : ''; };
	$name  = mb_substr( sanitize_text_field( $g( 'name' ) ), 0, 60 );
	$tg    = qs_tg_clean( $g( 'tg' ) );
	$brief = trim( sanitize_textarea_field( $g( 'brief' ) ) );
	$kinds = array( 'sticker' => 'استیکر', 'pack' => 'پک استیکر', 'case' => 'قاب گوشی' );
	$kind  = isset( $kinds[ $g( 'kind' ) ] ) ? $kinds[ $g( 'kind' ) ] : $kinds['sticker'];
	$det   = mb_substr( sanitize_text_field( $g( 'detail' ) ), 0, 80 );
	if ( mb_strlen( $name ) < 2 ) { wp_send_json_error( array( 'msg' => 'اسمت رو بنویس.' ) ); }
	if ( ! $tg ) { wp_send_json_error( array( 'msg' => 'آیدی تلگرام معتبر نیست. مثلاً @username' ) ); }
	if ( mb_strlen( $brief ) < 10 || mb_strlen( $brief ) > 1500 ) { wp_send_json_error( array( 'msg' => 'توضیح طراحی باید بین ۱۰ تا ۱۵۰۰ حرف باشه.' ) ); }
	if ( empty( $_POST['consent'] ) ) { wp_send_json_error( array( 'msg' => 'تیک رضایت برای پیام توی تلگرام رو بزن.' ) ); }
	$ref = '';
	if ( $g( 'ref' ) ) { $r = qs_save_png( $g( 'ref' ), 'ref' ); if ( ! is_wp_error( $r ) ) { $ref = $r; } }
	$id = wp_insert_post( array(
		'post_type' => 'qs_request', 'post_status' => 'publish', 'post_title' => $name . ' - ' . $kind,
		'meta_input' => array( '_qs_tg' => $tg, '_qs_kind' => $kind, '_qs_detail' => $det, '_qs_brief' => $brief, '_qs_ref' => $ref, '_qs_st' => 'new' ),
	), true );
	if ( is_wp_error( $id ) ) { wp_send_json_error( array( 'msg' => 'ثبت ناموفق بود. دوباره امتحان کن.' ) ); }
	set_transient( $rl, $n + 1, HOUR_IN_SECONDS );
	$txt = "درخواست طراحی جدید #$id\nنام: $name\nتلگرام: @$tg\nنوع: $kind $det\n\n$brief\n\n" . admin_url( 'post.php?post=' . $id . '&action=edit' );
	wp_mail( qs_opt( 'notify' ) ? qs_opt( 'notify' ) : get_option( 'admin_email' ), 'درخواست طراحی جدید #' . $id, $txt );
	qs_notify_admin( $txt );
	wp_send_json_success( array( 'id' => $id ) );
}
add_action( 'wp_ajax_qs_request', 'qs_ajax_request' );
add_action( 'wp_ajax_nopriv_qs_request', 'qs_ajax_request' );

add_shortcode( 'quackstick_request', function () {
	qs_enqueue_request();
	$tg = qs_opt( 'tg' );
	ob_start();
	?>
	<div class="qs-req-wrap" id="request" dir="rtl">
	<form id="qs-req" class="qs-req" novalidate>
		<h3>سفارش طراحی</h3>
		<p>می‌خوای ما برات طراحی کنیم؟ توضیح بده چی تو ذهنته و آیدی تلگرامت رو بذار. ما اونجا بهت پیام می‌دیم و باهم حرف می‌زنیم.</p>
		<label>اسم<input name="name" maxlength="60" required></label>
		<label>آیدی تلگرام<input name="tg" dir="ltr" placeholder="@username" required></label>
		<label>چی می‌خوای؟<select name="kind"><option value="sticker">استیکر تکی</option><option value="pack">پک استیکر</option><option value="case">قاب گوشی</option></select></label>
		<label>اندازه یا مدل گوشی<input name="detail" maxlength="80" placeholder="مثلاً 8 سانت، یا iPhone 16"></label>
		<label>توضیح طراحی<textarea name="brief" rows="5" maxlength="1500" required placeholder="تم، رنگ‌ها، متن روی استیکر، هر چی تو ذهنته..."></textarea></label>
		<label>نمونه‌ی تصویری (اختیاری)<input type="file" name="refile" accept="image/*"></label>
		<input type="text" name="website" class="qr-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
		<label class="qr-chk"><input type="checkbox" name="consent" value="1"> قبول دارم که برای هماهنگی طراحی توی تلگرام بهم پیام بدین.</label>
		<p class="qr-msg" role="alert"></p>
		<button type="submit" class="ql-btn">ارسال درخواست</button>
	</form>
	<div class="qs-req qr-done" hidden>
		<h3>درخواستت ثبت شد</h3>
		<p>کد پیگیری: <strong class="qr-id"></strong>. به‌زودی توی تلگرام بهت پیام می‌دیم.<?php if ( $tg ) { ?> اگه خواستی خودت هم می‌تونی اول پیام بدی: <a href="<?php echo esc_url( 'https://t.me/' . $tg ); ?>" target="_blank" rel="noopener" dir="ltr">@<?php echo esc_html( $tg ); ?></a><?php } ?></p>
	</div>
	</div>
	<?php
	return ob_get_clean();
} );
