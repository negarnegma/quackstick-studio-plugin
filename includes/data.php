<?php
defined( 'ABSPATH' ) || exit;

/* ---------- options & price tables ---------- */
function qs_opt( $k = null ) {
	static $d = array(
		'sizes'        => '4:20000,6:35000,8:50000,10:70000', // cm:price (store currency)
		'case_base'    => 180000,
		'tiers'        => '2.5:10000,4:20000,5.5:30000,6:40000', // max cm:price per sticker on a case
		'sketch_cm'    => 6,
		'max_stickers' => 12,
		'duck'         => 1,
		'craft'        => 1,
		'sheet'        => 'a4',
		'tg'           => '',        // our Telegram username (no @)
		'gift_min'     => 500000,    // free-case threshold (store currency); 0 = off
		'notify'       => '',
		'bot'          => '',
		'chat'         => '',
		'sheet_prices' => 'a6:60000,a5:100000',
		'tiers_qty'    => '10:5,25:10,50:15', // pieces:percent discount on custom items
		'proof'        => 1,
		'proof_hours'  => 0,   // auto-approve after N hours (0 = never)
		'cleanup_days' => 14,
		'duck_costume' => 'auto',
		'n_admin_order' => 1, 'n_proof' => 1, 'n_processing' => 1, 'n_completed' => 1, 'n_shipped' => 1,
		'sms_provider' => '', 'sms_key' => '', 'sms_sender' => '', 'sms_admin' => '', 'sms_url' => '', 'sms_method' => 'GET',
		't_admin_order' => 'سفارش جدید #{order} از {name}. مبلغ: {total}',
		't_proof'       => '{name} عزیز، سفارش #{order} ثبت شد. لطفاً طرحت رو تأیید کن تا چاپ کنیم: {link}',
		't_processing'  => '{name} عزیز، پرداخت سفارش #{order} تأیید شد و در حال آماده‌سازیه.',
		't_completed'   => '{name} عزیز، سفارش #{order} آماده/ارسال شد. {tracking}',
		't_shipped'     => '{name} عزیز، سفارش #{order} ارسال شد. کد رهگیری: {tracking}',
		'max_sheet'    => 30,
		'fa'           => 1,
		'duck_name'    => 'قات‌قاتی',         // Persian digits on the storefront
	);
	$o = array_merge( $d, (array) get_option( 'qs_opts', array() ) );
	foreach ( array( 'duck', 'craft', 'fa', 'proof', 'n_admin_order', 'n_proof', 'n_processing', 'n_completed', 'n_shipped' ) as $kk ) { $o[ $kk ] = ( empty( $o[ $kk ] ) || 'no' === $o[ $kk ] || '0' === $o[ $kk ] ) ? 0 : 1; } // WooCommerce checkboxes save yes/no
	foreach ( array( 'case_base', 'sketch_cm', 'max_stickers', 'gift_min', 'max_sheet', 'proof_hours', 'cleanup_days' ) as $kk ) { $o[ $kk ] = (float) $o[ $kk ]; }
	foreach ( array( 'sizes', 'tiers', 'sheet_prices', 'tiers_qty' ) as $kk ) { $o[ $kk ] = preg_replace( '/[^0-9.,:]/', '', (string) $o[ $kk ] ); }
	$o['duck_name'] = trim( mb_substr( trim( strip_tags( (string) $o['duck_name'] ) ), 0, 24 ) ) ?: 'قات‌قاتی';
	$o['tg']    = qs_tg_clean( $o['tg'] );
	$o['duck_costume'] = in_array( $o['duck_costume'], array( 'auto', 'none', 'yalda', 'nowruz', 'winter' ), true ) ? $o['duck_costume'] : 'auto';
	$o['sms_provider'] = in_array( $o['sms_provider'], array( 'kavenegar', 'webhook' ), true ) ? $o['sms_provider'] : '';
	$o['sms_method']   = 'POST' === strtoupper( (string) $o['sms_method'] ) ? 'POST' : 'GET';
	$o['sms_key']      = preg_replace( '/[^0-9A-Za-z._:=+\/-]/', '', (string) $o['sms_key'] );
	$o['sms_sender']   = preg_replace( '/[^0-9A-Za-z._-]/', '', (string) $o['sms_sender'] );
	$o['sms_url']      = preg_replace( '/[^A-Za-z0-9:\/?&=_.{}%+-]/', '', (string) $o['sms_url'] );
	$o['sms_admin']    = qs_phone( $o['sms_admin'] );
	foreach ( array( 't_admin_order', 't_proof', 't_processing', 't_completed', 't_shipped' ) as $kk ) { $o[ $kk ] = mb_substr( trim( strip_tags( (string) $o[ $kk ] ) ), 0, 300 ); }
	$o['sheet'] = in_array( $o['sheet'], array( 'a4', 'a3' ), true ) ? $o['sheet'] : 'a4';
	$o['notify'] = filter_var( $o['notify'], FILTER_VALIDATE_EMAIL ) ? $o['notify'] : '';
	$o['bot']    = preg_replace( '/[^0-9A-Za-z:_-]/', '', (string) $o['bot'] );
	$o['chat']   = preg_replace( '/[^0-9-]/', '', (string) $o['chat'] );
	return null === $k ? $o : ( isset( $o[ $k ] ) ? $o[ $k ] : null );
}
function qs_pairs( $s ) {
	$r = array();
	foreach ( explode( ',', (string) $s ) as $p ) {
		$a = explode( ':', trim( $p ) );
		if ( 2 === count( $a ) && is_numeric( $a[0] ) && is_numeric( $a[1] ) ) {
			$r[ (string) (float) $a[0] ] = (float) $a[1];
		}
	}
	ksort( $r, SORT_NUMERIC );
	return $r;
}
function qs_sizes() { return qs_pairs( qs_opt( 'sizes' ) ); }
function qs_sheet_prices() { $r = array(); foreach ( qs_pairs( str_replace( array( 'a6', 'a5' ), array( '6', '5' ), qs_opt( 'sheet_prices' ) ) ) as $k => $p ) { $r[ 'a' . $k ] = $p; } return $r; }
/** sticker sheets: id => [name, width mm, height mm] */
function qs_sheets() { return array( 'a6' => array( 'ورق A6', 105, 148 ), 'a5' => array( 'ورق A5', 148, 210 ) ); }
function qs_tiers() {
	$t = array();
	foreach ( qs_pairs( qs_opt( 'tiers' ) ) as $m => $p ) { $t[] = array( (float) $m, $p ); }
	return $t;
}
/** id => [name, width mm, height mm] (approximate public specs) */
function qs_models() {
	return array(
		'i16'  => array( 'iPhone 16', 71.6, 147.6 ),
		'i16p' => array( 'iPhone 16 Pro Max', 77.6, 163 ),
		's25'  => array( 'Galaxy S25', 70.5, 146.9 ),
		's25u' => array( 'Galaxy S25 Ultra', 77.6, 162.8 ),
		'px9'  => array( 'Pixel 9', 72, 152.8 ),
	);
}

/** Server-side price: never trust the browser. */
function qs_price( $d ) {
	if ( 'sticker' === $d['type'] ) {
		$z = qs_sizes();
		$k = (string) (float) $d['stickers'][0]['s'];
		return isset( $z[ $k ] ) ? $z[ $k ] : 0;
	}
	if ( 'sheet' === $d['type'] ) { $sp = qs_sheet_prices(); return isset( $sp[ $d['model'] ] ) ? $sp[ $d['model'] ] : 0; }
	$t = qs_tiers();
	if ( ! $t ) { return 0; }
	$sum = (float) qs_opt( 'case_base' );
	foreach ( $d['stickers'] as $s ) {
		$p = $t[ count( $t ) - 1 ][1];
		foreach ( $t as $tt ) {
			if ( $s['s'] <= $tt[0] ) { $p = $tt[1]; break; }
		}
		$sum += $p;
	}
	return $sum;
}

/* ---------- validation / sanitising of the design JSON ---------- */
function qs_color( $c, $def = '#FFFFFF' ) {
	return preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $c ) ? strtoupper( $c ) : $def;
}
function qs_err( $m ) { return new WP_Error( 'qs', $m ); }

function qs_clean( $raw, $check_size = true ) {
	$d = json_decode( (string) $raw, true );
	if ( ! is_array( $d ) ) { return qs_err( 'طرح نامعتبر است.' ); }
	$type = ( isset( $d['type'] ) && in_array( $d['type'], array( 'case', 'sheet' ), true ) ) ? $d['type'] : 'sticker';
	$max  = 'case' === $type ? max( 1, (int) qs_opt( 'max_stickers' ) ) : ( 'sheet' === $type ? max( 1, (int) qs_opt( 'max_sheet' ) ) : 1 );
	$st   = array_slice( array_values( (array) ( isset( $d['stickers'] ) ? $d['stickers'] : array() ) ), 0, $max );
	if ( ! $st ) { return qs_err( 'استیکری انتخاب نشده.' ); }

	$out = array( 'type' => $type, 'stickers' => array() );
	if ( 'case' === $type ) {
		$models = qs_models();
		$m      = isset( $d['model'] ) ? $d['model'] : '';
		if ( ! isset( $models[ $m ] ) ) { return qs_err( 'مدل گوشی نامعتبر است.' ); }
		$out['model']     = $m;
		$out['caseColor'] = qs_color( isset( $d['caseColor'] ) ? $d['caseColor'] : '' );
	}
	if ( 'sheet' === $type ) {
		$sh = qs_sheets();
		$m  = isset( $d['model'] ) ? $d['model'] : '';
		if ( ! isset( $sh[ $m ] ) || ! isset( qs_sheet_prices()[ $m ] ) ) { return qs_err( 'اندازه‌ی ورق نامعتبره.' ); }
		$out['model'] = $m;
	}
	$accs   = array( 'none', 'crown', 'top', 'glasses', 'bow', 'beanie' );
	$shapes = array( 'circle', 'square', 'blob' );

	foreach ( $st as $s ) {
		$cm = round( (float) ( isset( $s['s'] ) ? $s['s'] : 0 ), 1 );
		if ( 'sticker' === $type && $check_size ) {
			if ( ! isset( qs_sizes()[ (string) $cm ] ) ) { return qs_err( 'اندازه‌ی استیکر مجاز نیست.' ); }
		} elseif ( $cm < 1.5 || $cm > 10 || ( 'case' === $type && $cm > 6 ) || ( 'sheet' === $type && $cm > 8 ) ) {
			return qs_err( 'اندازه‌ی استیکر مجاز نیست.' );
		}
		$sp = isset( $s['sp'] ) && is_array( $s['sp'] ) ? $s['sp'] : array();
		$k  = isset( $sp['k'] ) ? $sp['k'] : '';
		if ( 'duck' === $k ) {
			$o     = isset( $sp['o'] ) && is_array( $sp['o'] ) ? $sp['o'] : array();
			$clean = array( 'k' => 'duck', 'o' => array(
				'body'  => qs_color( isset( $o['body'] ) ? $o['body'] : '', '#FFD23F' ),
				'bg'    => qs_color( isset( $o['bg'] ) ? $o['bg'] : '' ),
				'acc'   => in_array( isset( $o['acc'] ) ? $o['acc'] : '', $accs, true ) ? $o['acc'] : 'none',
				'shape' => in_array( isset( $o['shape'] ) ? $o['shape'] : '', $shapes, true ) ? $o['shape'] : 'circle',
				'cap'   => mb_substr( trim( wp_strip_all_tags( (string) ( isset( $o['cap'] ) ? $o['cap'] : '' ) ) ), 0, 16 ),
			) );
		} elseif ( 'sk' === $k ) {
			if ( isset( $sp['f'] ) && preg_match( '#^sk/[a-f0-9]{32}\.png$#', (string) $sp['f'] ) ) {
				$f = $sp['f']; // already stored (e.g. preset / re-validated)
			} else {
				$f = qs_save_png( isset( $sp['u'] ) ? $sp['u'] : '', 'sk' );
				if ( is_wp_error( $f ) ) { return $f; }
			}
			$clean = array( 'k' => 'sk', 'f' => $f );
		} else {
			return qs_err( 'نوع استیکر نامعتبر است.' );
		}
		$item = array( 'sp' => $clean, 's' => $cm );
		if ( 'case' === $type || 'sheet' === $type ) {
			$dim       = 'case' === $type ? qs_models()[ $out['model'] ] : qs_sheets()[ $out['model'] ];
			$item['x'] = max( 0, min( $dim[1], round( (float) ( isset( $s['x'] ) ? $s['x'] : 0 ), 1 ) ) );
			$item['y'] = max( 0, min( $dim[2], round( (float) ( isset( $s['y'] ) ? $s['y'] : 0 ), 1 ) ) );
			$item['r'] = max( -180, min( 180, (int) ( isset( $s['r'] ) ? $s['r'] : 0 ) ) );
		}
		$out['stickers'][] = $item;
	}
	return $out;
}

function qs_summary( $d ) {
	if ( 'sheet' === $d['type'] ) { return sprintf( 'ورق استیکر %s با %d استیکر', strtoupper( $d['model'] ), count( $d['stickers'] ) ); }
	if ( 'case' === $d['type'] ) {
		$m = qs_models();
		return sprintf( 'قاب %s با %d استیکر', isset( $m[ $d['model'] ] ) ? $m[ $d['model'] ][0] : '', count( $d['stickers'] ) );
	}
	$s = $d['stickers'][0];
	return sprintf( 'استیکر %s سانتی (%s)', $s['s'], 'sk' === $s['sp']['k'] ? 'نقاشی آزاد' : 'اردک' );
}

/* ---------- file storage (sketch PNGs private, previews public) ---------- */
function qs_base() { $u = wp_upload_dir(); return trailingslashit( $u['basedir'] ) . 'quackstick/'; }
function qs_url()  { $u = wp_upload_dir(); return trailingslashit( $u['baseurl'] ) . 'quackstick/'; }
function qs_protect() {
	$b = qs_base();
	foreach ( array( '', 'sk', 'pv', 'ref' ) as $d ) {
		wp_mkdir_p( $b . $d );
		if ( ! file_exists( $b . $d . '/index.php' ) ) { @file_put_contents( $b . $d . '/index.php', "<?php\n// Silence is golden.\n" ); }
	}
	foreach ( array( 'sk', 'ref' ) as $pd ) if ( ! file_exists( $b . $pd . '/.htaccess' ) ) {
		@file_put_contents( $b . $pd . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n" );
	}
}
/** @return string|WP_Error relative path like sk/<32hex>.png */
function qs_save_png( $data, $dir ) {
	if ( ! is_string( $data ) || 0 !== strpos( $data, 'data:image/png;base64,' ) || strlen( $data ) > 4500000 ) { return qs_err( 'تصویر نامعتبر است.' ); }
	$bin = base64_decode( substr( $data, 22 ), true );
	if ( false === $bin ) { return qs_err( 'تصویر نامعتبر است.' ); }
	$i = @getimagesizefromstring( $bin );
	if ( ! $i || IMAGETYPE_PNG !== $i[2] || $i[0] > 1400 || $i[1] > 1400 ) { return qs_err( 'تصویر نامعتبر است.' ); }
	if ( function_exists( 'imagecreatefromstring' ) ) { // re-encode: strips anything that is not pixels
		$im = @imagecreatefromstring( $bin );
		if ( $im ) {
			imagesavealpha( $im, true );
			ob_start();
			imagepng( $im );
			$o = ob_get_clean();
			imagedestroy( $im );
			if ( $o ) { $bin = $o; }
		}
	}
	qs_protect();
	$n = bin2hex( random_bytes( 16 ) ) . '.png';
	if ( ! @file_put_contents( qs_base() . $dir . '/' . $n, $bin ) ) { return qs_err( 'ذخیره‌ی فایل ناموفق بود.' ); }
	return $dir . '/' . $n;
}

/* ---------- misc helpers ---------- */
function qs_tg_clean( $s ) {
	$s = trim( (string) $s );
	$s = preg_replace( '#^(https?://)?(t|telegram)\.me/#i', '', $s );
	$s = ltrim( $s, '@' );
	return preg_match( '/^[A-Za-z][A-Za-z0-9_]{4,31}$/', $s ) ? $s : '';
}
function qs_page_url( $k ) {
	static $tried = false;
	$id = (int) get_option( 'qs_pg_' . $k );
	if ( ( ! $id || ! get_post_status( $id ) ) && ! $tried && function_exists( 'qs_ensure_pages' ) ) { $tried = true; qs_ensure_pages( true ); $id = (int) get_option( 'qs_pg_' . $k ); } // pages are created on demand, not only when an admin visits wp-admin
	return ( $id && get_post_status( $id ) ) ? get_permalink( $id ) : home_url( '/' );
}
function qs_tg_url() { $t = qs_opt( 'tg' ); return $t ? 'https://t.me/' . $t : ''; }

function qs_fa( $s ) { return strtr( (string) $s, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) ); }

/** Iranian mobile -> 09xxxxxxxxx (accepts Persian digits, +98, 0098). Empty string if invalid. */
function qs_phone( $p ) {
	$p = strtr( (string) $p, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
	$p = preg_replace( '/\D+/', '', $p );
	if ( 0 === strpos( $p, '0098' ) ) { $p = '0' . substr( $p, 4 ); }
	elseif ( 0 === strpos( $p, '98' ) && 12 === strlen( $p ) ) { $p = '0' . substr( $p, 2 ); }
	elseif ( 10 === strlen( $p ) && '9' === $p[0] ) { $p = '0' . $p; }
	return preg_match( '/^09\d{9}$/', $p ) ? $p : '';
}
