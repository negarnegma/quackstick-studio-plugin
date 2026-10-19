<?php
defined( 'ABSPATH' ) || exit;

/* hidden "carrier" products the custom designs are added to the cart as */
function qs_ensure_products() {
	if ( ! class_exists( 'WC_Product_Simple' ) ) { return; }
	foreach ( array( 'sticker' => 'استیکر سفارشی', 'case' => 'قاب گوشی سفارشی', 'sheet' => 'ورق استیکر سفارشی' ) as $k => $name ) {
		$id = (int) get_option( 'qs_pid_' . $k );
		if ( $id && get_post_status( $id ) ) { continue; }
		$p = new WC_Product_Simple();
		$p->set_name( $name );
		$p->set_status( 'publish' );
		$p->set_catalog_visibility( 'hidden' );
		$p->set_regular_price( '1' );
		$p->save();
		update_option( 'qs_pid_' . $k, $p->get_id() );
	}
}
add_action( 'admin_init', function () { qs_ensure_products(); qs_protect(); } );

/* add to cart (wc-ajax) */
add_action( 'wc_ajax_qs_add', function () {
	check_ajax_referer( 'qs_add', 'nonce' );
	$d = qs_clean( wp_unslash( isset( $_POST['design'] ) ? $_POST['design'] : '' ) );
	if ( is_wp_error( $d ) ) { wp_send_json_error( array( 'msg' => $d->get_error_message() ) ); }
	$price = qs_price( $d );
	if ( $price <= 0 ) { wp_send_json_error( array( 'msg' => 'قیمت‌گذاری ناموفق بود.' ) ); }
	$pv  = qs_save_png( wp_unslash( isset( $_POST['preview'] ) ? $_POST['preview'] : '' ), 'pv' );
	$pid = (int) get_option( 'qs_pid_' . $d['type'] );
	if ( ! $pid || ! get_post_status( $pid ) ) { qs_ensure_products(); $pid = (int) get_option( 'qs_pid_' . $d['type'] ); } // carrier products are created on demand too
	$key = $pid ? WC()->cart->add_to_cart( $pid, 1, 0, array(), array( 'qs' => $d, 'qs_price' => $price, 'qs_pv' => is_wp_error( $pv ) ? '' : $pv ) ) : false;
	if ( ! $key ) { wc_clear_notices(); wp_send_json_error( array( 'msg' => 'افزودن به سبد ناموفق بود.' ) ); }
	wc_clear_notices();
	wp_send_json_success( array( 'cart' => wc_get_cart_url(), 'count' => WC()->cart->get_cart_contents_count() ) );
} );

/* cart display + price */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	$pct = qs_discount_pct( $cart );
	foreach ( $cart->get_cart() as $it ) {
		if ( isset( $it['qs_price'] ) ) { $it['data']->set_price( ! empty( $it['qs'] ) && $pct > 0 ? round( $it['qs_price'] * ( 1 - $pct / 100 ) ) : $it['qs_price'] ); }
	}
	$min = (float) qs_opt( 'gift_min' ); // free case only while the real items reach the threshold
	foreach ( $cart->get_cart() as $k => $it ) {
		if ( ! empty( $it['qs_gift'] ) && ( $min <= 0 || qs_cart_subtotal( $cart ) < $min ) ) {
			$cart->remove_cart_item( $k );
			wc_add_notice( 'قاب هدیه از سبد برداشته شد چون جمع سفارش از حداقل کمتر شد.', 'notice' );
		}
	}
} );
function qs_cart_subtotal( $cart ) {
	$t   = 0;
	$pct = function_exists( 'qs_discount_pct' ) ? qs_discount_pct( $cart ) : 0;
	foreach ( $cart->get_cart() as $it ) {
		if ( ! empty( $it['qs_gift'] ) ) { continue; }
		$p = isset( $it['qs_price'] ) ? (float) $it['qs_price'] : (float) $it['data']->get_price();
		if ( ! empty( $it['qs'] ) && $pct > 0 ) { $p = round( $p * ( 1 - $pct / 100 ) ); }
		$t += $p * $it['quantity'];
	}
	return $t;
}
add_filter( 'woocommerce_get_item_data', function ( $data, $it ) {
	if ( ! empty( $it['qs'] ) ) { $data[] = array( 'key' => 'طرح', 'value' => qs_summary( $it['qs'] ) ); }
	if ( ! empty( $it['qs_gift'] ) ) {
		$m = qs_models();
		$data[] = array( 'key' => 'مدل گوشی', 'value' => $m[ $it['qs_gift']['model'] ][0] );
		$data[] = array( 'key' => 'تم درخواستی', 'value' => $it['qs_gift']['theme'] );
	}
	return $data;
}, 10, 2 );
add_filter( 'woocommerce_cart_item_thumbnail', function ( $img, $it ) {
	if ( ! empty( $it['qs_pv'] ) ) { return '<img src="' . esc_url( qs_url() . $it['qs_pv'] ) . '" alt="" style="max-width:90px;height:auto">'; }
	return $img;
}, 10, 2 );
add_filter( 'woocommerce_cart_item_permalink', function ( $l, $it ) { return empty( $it['qs'] ) ? $l : ''; }, 10, 2 );

/* cart item -> order item. Underscore meta = hidden from customer and emails. */
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( ! empty( $values['qs'] ) ) {
		$item->add_meta_data( '_qs_design', wp_json_encode( $values['qs'] ), true );
		$item->add_meta_data( '_qs_custom', 1, true ); // made by the customer (needs proof approval)
		if ( ! empty( $values['qs_pv'] ) ) { $item->add_meta_data( '_qs_pv', $values['qs_pv'], true ); }
		$item->add_meta_data( 'طرح', qs_summary( $values['qs'] ), true );
		return;
	}
	if ( ! empty( $values['qs_gift'] ) ) { // free case: designed by hand from the theme text, so no print spec
		$g = $values['qs_gift'];
		$m = qs_models();
		$item->set_name( 'قاب هدیه (رایگان)' );
		$item->add_meta_data( '_qs_gift', wp_json_encode( array( 'model' => $m[ $g['model'] ][0], 'theme' => $g['theme'] ) ), true );
		$item->add_meta_data( 'مدل گوشی', $m[ $g['model'] ][0], true );
		$item->add_meta_data( 'تم درخواستی', $g['theme'], true );
		return;
	}
	$pre = json_decode( (string) get_post_meta( $item->get_product_id(), '_qs_preset', true ), true ); // ready-made duck products
	if ( is_array( $pre ) && ! empty( $pre['o'] ) ) {
		$d = qs_clean( wp_json_encode( array( 'type' => 'sticker', 'stickers' => array( array( 'sp' => array( 'k' => 'duck', 'o' => $pre['o'] ), 's' => isset( $pre['cm'] ) ? $pre['cm'] : 6 ) ) ) ), false );
		if ( ! is_wp_error( $d ) ) { $item->add_meta_data( '_qs_design', wp_json_encode( $d ), true ); }
		return;
	}
	$pd = qs_product_design( $item->get_product_id(), $item->get_variation_id() );
	if ( $pd ) { $item->add_meta_data( '_qs_design', wp_json_encode( $pd ), true ); }
}, 10, 3 );
add_filter( 'woocommerce_admin_order_item_thumbnail', function ( $img, $item_id, $item ) {
	$pv = is_object( $item ) ? $item->get_meta( '_qs_pv' ) : '';
	return $pv ? '<img src="' . esc_url( qs_url() . $pv ) . '" style="width:40px;height:auto" alt="">' : $img;
}, 10, 3 );

/* ---------- print artwork for ANY product (uses the images you upload to the media library) ---------- */
add_action( 'woocommerce_product_options_general_product_data', function () {
	global $post;
	$img = (int) get_post_meta( $post->ID, '_qs_print_img', true );
	echo '<div class="options_group show_if_simple show_if_variable">';
	echo '<p class="form-field"><strong>QuackStick: چاپ</strong></p>';
	woocommerce_wp_select( array( 'id' => '_qs_print_mode', 'label' => 'این محصول استیکر چاپیه؟', 'options' => array( '' => 'نه (غیرچاپی)', 'featured' => 'بله، از تصویر شاخص محصول', 'custom' => 'بله، از یه فایل چاپ جدا' ),
		'description' => 'سفارش‌های این محصول توی «صف چاپ» می‌آن و عکسش توی PDF چاپ قرار می‌گیره.' ) );
	echo '<p class="form-field"><label>فایل چاپ</label><input type="hidden" id="_qs_print_img" name="_qs_print_img" value="' . esc_attr( $img ) . '">';
	echo '<img id="qs_prev" src="' . esc_url( $img ? wp_get_attachment_image_url( $img, 'thumbnail' ) : '' ) . '" style="max-width:90px;height:auto;vertical-align:middle;' . ( $img ? '' : 'display:none' ) . '"> ';
	echo '<button class="button" id="qs_pick">انتخاب از رسانه</button> <button class="button" id="qs_clear">حذف</button> <span class="description">فقط وقتی «فایل چاپ جدا» رو انتخاب کردی.</span></p>';
	woocommerce_wp_text_input( array( 'id' => '_qs_print_cm', 'label' => 'اندازه چاپ (سانت)', 'type' => 'number', 'custom_attributes' => array( 'step' => '0.5', 'min' => '1', 'max' => '10' ), 'value' => get_post_meta( $post->ID, '_qs_print_cm', true ) ?: 6 ) );
	woocommerce_wp_select( array( 'id' => '_qs_print_shape', 'label' => 'شکل برش', 'options' => array( 'circle' => 'گرد', 'square' => 'مربع گرد', 'free' => 'دور تصویر (برای PNG شفاف)' ) ) );
	woocommerce_wp_text_input( array( 'id' => '_qs_print_n', 'label' => 'تعداد استیکر در هر عدد', 'type' => 'number', 'custom_attributes' => array( 'step' => '1', 'min' => '1', 'max' => '50' ), 'value' => get_post_meta( $post->ID, '_qs_print_n', true ) ?: 1, 'description' => 'برای پک: مثلاً 5 یعنی هر عدد محصول، 5 تا استیکر چاپ بشه.' ) );
	woocommerce_wp_textarea_input( array( 'id' => '_qs_preset', 'label' => 'پیشرفته: مشخصات اردک (JSON)', 'description' => 'برای اردک‌های ساخته‌شده توسط خود افزونه. معمولاً دست نزن.' ) );
	echo '</div>';
} );
add_action( 'admin_footer', function () {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || 'product' !== $s->id ) { return; }
	wp_enqueue_media();
	echo '<script>jQuery(function($){var f;$("#qs_pick").on("click",function(e){e.preventDefault();f=f||wp.media({title:"فایل چاپ استیکر",multiple:false,library:{type:"image"}});f.off("select").on("select",function(){var a=f.state().get("selection").first().toJSON();$("#_qs_print_img").val(a.id);$("#qs_prev").attr("src",(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)).show();$("#_qs_print_mode").val("custom")});f.open()});$("#qs_clear").on("click",function(e){e.preventDefault();$("#_qs_print_img").val("");$("#qs_prev").hide()})});</script>';
} );
add_action( 'woocommerce_process_product_meta', function ( $id ) {
	$g    = function ( $k ) { return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : ''; };
	$mode = in_array( $g( '_qs_print_mode' ), array( 'featured', 'custom' ), true ) ? $g( '_qs_print_mode' ) : '';
	$img  = absint( $g( '_qs_print_img' ) );
	update_post_meta( $id, '_qs_print_mode', $mode );
	update_post_meta( $id, '_qs_print_img', ( $img && wp_attachment_is_image( $img ) ) ? $img : '' );
	update_post_meta( $id, '_qs_print_cm', max( 1, min( 10, round( (float) $g( '_qs_print_cm' ), 1 ) ) ) ?: 6 );
	update_post_meta( $id, '_qs_print_shape', in_array( $g( '_qs_print_shape' ), array( 'circle', 'square', 'free' ), true ) ? $g( '_qs_print_shape' ) : 'circle' );
	update_post_meta( $id, '_qs_print_n', max( 1, min( 50, absint( $g( '_qs_print_n' ) ) ) ) );
	$j = json_decode( $g( '_qs_preset' ), true );
	update_post_meta( $id, '_qs_preset', is_array( $j ) ? wp_json_encode( $j ) : '' );
} );
/* per-variation print size (e.g. a "Size" attribute with 4/6/8 cm) */
add_action( 'woocommerce_variation_options_pricing', function ( $loop, $data, $variation ) {
	woocommerce_wp_text_input( array( 'id' => '_qs_cm_' . $loop, 'name' => '_qs_cm[' . $loop . ']', 'label' => 'اندازه چاپ QuackStick (سانت)', 'type' => 'number', 'wrapper_class' => 'form-row form-row-full',
		'custom_attributes' => array( 'step' => '0.5', 'min' => '1', 'max' => '10' ), 'value' => get_post_meta( $variation->ID, '_qs_cm', true ), 'description' => 'خالی = اندازه‌ی خود محصول' ) );
}, 10, 3 );
add_action( 'woocommerce_save_product_variation', function ( $vid, $i ) {
	$v = isset( $_POST['_qs_cm'][ $i ] ) ? (float) wp_unslash( $_POST['_qs_cm'][ $i ] ) : 0;
	update_post_meta( $vid, '_qs_cm', $v >= 1 && $v <= 10 ? round( $v, 1 ) : '' );
}, 10, 2 );
function qs_product_design( $product_id, $variation_id = 0 ) {
	$mode = get_post_meta( $product_id, '_qs_print_mode', true );
	if ( ! $mode ) { return null; }
	$att = 'custom' === $mode ? (int) get_post_meta( $product_id, '_qs_print_img', true ) : (int) get_post_thumbnail_id( $product_id );
	if ( ! $att || ! wp_attachment_is_image( $att ) ) { return null; }
	$cm = $variation_id ? (float) get_post_meta( $variation_id, '_qs_cm', true ) : 0;
	if ( $cm < 1 ) { $cm = (float) get_post_meta( $product_id, '_qs_print_cm', true ); }
	$sh = get_post_meta( $product_id, '_qs_print_shape', true );
	return array( 'type' => 'sticker', 'stickers' => array( array(
		'sp' => array( 'k' => 'img', 'id' => $att, 'shape' => in_array( $sh, array( 'circle', 'square', 'free' ), true ) ? $sh : 'circle' ),
		's'  => ( $cm >= 1 && $cm <= 10 ) ? $cm : 6,
		'n'  => max( 1, min( 50, (int) get_post_meta( $product_id, '_qs_print_n', true ) ) ),
	) ) );
}
/* products list: show which products print */
add_filter( 'manage_edit-product_columns', function ( $c ) { $c['qs_pr'] = 'چاپ'; return $c; } );
add_action( 'manage_product_posts_custom_column', function ( $col, $id ) {
	if ( 'qs_pr' !== $col ) { return; }
	$m = get_post_meta( $id, '_qs_print_mode', true );
	if ( $m ) { echo esc_html( ( get_post_meta( $id, '_qs_print_cm', true ) ?: 6 ) . ' cm' ); }
	elseif ( get_post_meta( $id, '_qs_preset', true ) ) { echo 'اردک آماده'; }
	else { echo '<span style="opacity:.4">-</span>'; }
}, 10, 2 );
add_filter( 'woocommerce_product_get_image', function ( $html, $product ) {
	$pre = $product->get_image_id() ? '' : get_post_meta( $product->get_id(), '_qs_preset', true );
	$j   = $pre ? json_decode( $pre, true ) : null;
	return ( is_array( $j ) && ! empty( $j['o'] ) ) ? '<span class="qs-duck-img" data-qs="' . esc_attr( wp_json_encode( $j['o'] ) ) . '"></span>' : $html;
}, 10, 2 );
function qs_seed_presets() {
	$list = array(
		array( 'سر کوَک‌کوَکلو', array( 'body' => '#FFD23F', 'acc' => 'crown', 'bg' => '#CDEBFF', 'shape' => 'circle', 'cap' => '' ) ),
		array( 'اردک شیک‌پوش', array( 'body' => '#8FD694', 'acc' => 'top', 'bg' => '#FFE3C2', 'shape' => 'square', 'cap' => '' ) ),
		array( 'اردک کول', array( 'body' => '#FFD23F', 'acc' => 'glasses', 'bg' => '#FFB3C7', 'shape' => 'circle', 'cap' => 'خیلی کولم' ) ),
		array( 'جوجه‌اردک کلاه‌بافتنی', array( 'body' => '#FFF1A8', 'acc' => 'beanie', 'bg' => '#BFE6FF', 'shape' => 'blob', 'cap' => '' ) ),
		array( 'اردک ببیونی', array( 'body' => '#FFFFFF', 'acc' => 'bow', 'bg' => '#FFD23F', 'shape' => 'circle', 'cap' => 'حمله کوَک‌کوَک' ) ),
		array( 'اردک کلاسیک', array( 'body' => '#FFD23F', 'acc' => 'none', 'bg' => '#FFFFFF', 'shape' => 'blob', 'cap' => '' ) ),
	);
	$z = qs_sizes();
	foreach ( $list as $l ) {
		$p = new WC_Product_Simple();
		$p->set_name( $l[0] );
		$p->set_status( 'publish' );
		$p->set_regular_price( (string) ( isset( $z['6'] ) ? $z['6'] : 35000 ) );
		$p->set_short_description( 'استیکر ۶×۶ سانتی، وینیل ضدآب.' );
		$p->save();
		update_post_meta( $p->get_id(), '_qs_preset', wp_json_encode( array( 'o' => $l[1], 'cm' => 6 ) ) );
	}
}

/* [quackstick_designer] */
add_shortcode( 'quackstick_designer', function () {
	qs_enqueue_designer();
	ob_start();
	include QS_DIR . 'templates/designer.php';
	return ob_get_clean();
} );

/* ---------- free case (gift) ---------- */
add_filter( 'woocommerce_cart_item_name', function ( $n, $it ) { return empty( $it['qs_gift'] ) ? $n : 'قاب هدیه (رایگان)'; }, 10, 2 );
add_filter( 'woocommerce_cart_item_quantity', function ( $q, $k, $it ) { return empty( $it['qs_gift'] ) ? $q : '1'; }, 10, 3 );

function qs_gift_state() {
	$min = (float) qs_opt( 'gift_min' );
	if ( $min <= 0 || ! WC()->cart ) { return array( 'on' => false ); }
	$sub  = qs_cart_subtotal( WC()->cart );
	$gift = null;
	$n    = 0;
	$m    = qs_models();
	foreach ( WC()->cart->get_cart() as $it ) {
		if ( ! empty( $it['qs_gift'] ) ) { $gift = array( 'model' => $m[ $it['qs_gift']['model'] ][0], 'theme' => $it['qs_gift']['theme'] ); }
		else { $n += (int) $it['quantity']; }
	}
	return array( 'on' => true, 'min' => $min, 'sub' => $sub, 'left' => max( 0, $min - $sub ), 'pct' => (int) min( 100, floor( $sub / $min * 100 ) ),
		'eligible' => $sub >= $min, 'has' => (bool) $gift, 'gift' => $gift, 'count' => $n );
}
add_action( 'wc_ajax_qs_gift_state', function () { wp_send_json_success( qs_gift_state() ); } );
add_action( 'wc_ajax_qs_gift', function () {
	check_ajax_referer( 'qs_gift', 'nonce' );
	$cart = WC()->cart;
	if ( ! $cart ) { wp_send_json_error( array( 'msg' => 'سبد در دسترس نیست.' ) ); }
	foreach ( $cart->get_cart() as $k => $it ) { if ( ! empty( $it['qs_gift'] ) ) { $cart->remove_cart_item( $k ); } }
	if ( empty( $_POST['remove'] ) ) {
		$model = isset( $_POST['model'] ) ? wp_unslash( $_POST['model'] ) : '';
		$theme = mb_substr( trim( sanitize_textarea_field( wp_unslash( isset( $_POST['theme'] ) ? $_POST['theme'] : '' ) ) ), 0, 300 );
		$min   = (float) qs_opt( 'gift_min' );
		if ( $min <= 0 || qs_cart_subtotal( $cart ) < $min ) { wp_send_json_error( array( 'msg' => 'هنوز به حداقل خرید نرسیدی.' ) ); }
		if ( ! isset( qs_models()[ $model ] ) ) { wp_send_json_error( array( 'msg' => 'مدل گوشی رو انتخاب کن.' ) ); }
		if ( mb_strlen( $theme ) < 3 ) { wp_send_json_error( array( 'msg' => 'تم رو بنویس.' ) ); }
		$cart->add_to_cart( (int) get_option( 'qs_pid_case' ), 1, 0, array(), array( 'qs_gift' => array( 'model' => $model, 'theme' => $theme ), 'qs_price' => 0 ) );
	}
	wc_clear_notices();
	wp_send_json_success( qs_gift_state() );
} );
function qs_order_gift( $order ) {
	foreach ( $order->get_items() as $it ) {
		$g = json_decode( (string) $it->get_meta( '_qs_gift' ), true );
		if ( is_array( $g ) ) { return $g; }
	}
	return null;
}
