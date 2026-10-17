<?php
defined( 'ABSPATH' ) || exit;

/* ---------- proof approval: the customer confirms what we are about to print ---------- */
function qs_proof_states() { return array( 'pending' => 'منتظر تأیید مشتری', 'approved' => 'تأیید شد', 'changes' => 'مشتری تغییر خواسته' ); }
function qs_order_needs_proof( $order ) {
	foreach ( $order->get_items() as $it ) { if ( $it->get_meta( '_qs_custom' ) ) { return true; } }
	return false;
}
function qs_proof_url( $order ) {
	return add_query_arg( array( 'o' => $order->get_id(), 'k' => $order->get_meta( '_qs_proof_key' ) ), qs_page_url( 'proof' ) );
}
function qs_proof_init( $order ) {
	if ( ! qs_opt( 'proof' ) || $order->get_meta( '_qs_proof' ) || ! qs_order_needs_proof( $order ) ) { return; }
	$order->update_meta_data( '_qs_proof', 'pending' );
	$order->update_meta_data( '_qs_proof_key', wp_generate_password( 24, false ) );
	$order->save();
	$h = (float) qs_opt( 'proof_hours' );
	if ( $h > 0 ) { wp_schedule_single_event( time() + (int) ( $h * 3600 ), 'qs_proof_auto', array( $order->get_id() ) ); }
	qs_proof_send_link( $order );
}
function qs_proof_send_link( $order ) {
	$url = qs_proof_url( $order );
	$txt = qs_fill( qs_opt( 't_proof' ), qs_vars( $order ) );
	if ( $order->get_billing_email() ) { wp_mail( $order->get_billing_email(), 'تأیید طرح سفارش #' . $order->get_order_number(), $txt . "\n\n" . $url ); }
	if ( qs_opt( 'n_proof' ) ) { qs_sms( $order->get_billing_phone(), $txt, $order->get_id() ); }
	$order->add_order_note( 'لینک تأیید طرح برای مشتری فرستاده شد.' );
}
add_action( 'qs_proof_auto', function ( $id ) {
	$o = wc_get_order( $id );
	if ( $o && 'pending' === $o->get_meta( '_qs_proof' ) ) {
		$o->update_meta_data( '_qs_proof', 'approved' );
		$o->save();
		$o->add_order_note( 'طرح به‌صورت خودکار تأیید شد (مهلت تموم شد).' );
	}
} );

/* shown to the customer: thank-you page, order view and e-mails */
function qs_proof_box( $order ) {
	if ( 'pending' !== $order->get_meta( '_qs_proof' ) && 'changes' !== $order->get_meta( '_qs_proof' ) ) { return; }
	echo '<div class="woocommerce-info" style="margin:16px 0"><strong>تأیید طرح:</strong> قبل از چاپ، طرحت رو ببین و تأیید کن. <a class="button" href="' . esc_url( qs_proof_url( $order ) ) . '">دیدن و تأیید طرح</a></div>';
}
add_action( 'woocommerce_thankyou', function ( $id ) { $o = wc_get_order( $id ); if ( $o ) { qs_proof_box( $o ); } }, 5 );
add_action( 'woocommerce_order_details_before_order_table', function ( $o ) { qs_proof_box( $o ); } );
add_action( 'woocommerce_email_after_order_table', function ( $order, $admin, $plain ) {
	if ( $admin || 'pending' !== $order->get_meta( '_qs_proof' ) ) { return; }
	echo $plain ? "\nتأیید طرح: " . esc_url_raw( qs_proof_url( $order ) ) . "\n" : '<p><strong>تأیید طرح:</strong> <a href="' . esc_url( qs_proof_url( $order ) ) . '">برای چاپ، طرحت رو تأیید کن</a></p>';
}, 10, 3 );

/* public proof page: [quackstick_proof]  (?o=ORDER&k=KEY) */
add_shortcode( 'quackstick_proof', function () {
	qs_enqueue_request_css();
	$id = isset( $_GET['o'] ) ? absint( $_GET['o'] ) : 0;
	$k  = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
	$o  = $id ? wc_get_order( $id ) : null;
	$key = $o ? (string) $o->get_meta( '_qs_proof_key' ) : '';
	if ( ! $o || ! $key || ! $k || ! hash_equals( $key, $k ) ) { return '<div class="qs-req-wrap" dir="rtl"><div class="qs-req"><p>این لینک معتبر نیست یا منقضی شده.</p></div></div>'; }
	$st = (string) $o->get_meta( '_qs_proof' );
	ob_start();
	echo '<div class="qs-req-wrap" dir="rtl"><div class="qs-req"><h3>تأیید طرح سفارش #' . esc_html( $o->get_order_number() ) . '</h3>';
	foreach ( $o->get_items() as $it ) {
		if ( ! $it->get_meta( '_qs_custom' ) ) { continue; }
		$pv = $it->get_meta( '_qs_pv' );
		echo '<div style="display:flex;gap:14px;align-items:center;margin:14px 0">' . ( $pv ? '<img src="' . esc_url( qs_url() . $pv ) . '" alt="" style="width:140px;max-width:40%;height:auto;background:#fff;border-radius:8px;border:2px solid #3A2817">' : '' )
			. '<div><strong>' . esc_html( $it->get_meta( 'طرح' ) ) . '</strong><br>تعداد: ' . esc_html( $it->get_quantity() ) . '</div></div>';
	}
	if ( isset( $_GET['done'] ) ) { echo '<p><strong>ثبت شد. ممنون!</strong></p>'; }
	if ( 'approved' === $st ) {
		echo '<p style="color:#1a7a3a;font-weight:800">طرح تأیید شده. به‌زودی چاپ می‌شه.</p>';
	} else {
		if ( 'changes' === $st ) { echo '<p>درخواست تغییرت ثبت شده: «' . esc_html( $o->get_meta( '_qs_proof_note' ) ) . '». می‌تونی دوباره تأیید کنی یا تغییر دیگه‌ای بخوای.</p>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="qs_proof_do"><input type="hidden" name="o" value="' . esc_attr( $id ) . '"><input type="hidden" name="k" value="' . esc_attr( $k ) . '">'
			. '<label>اگه تغییری می‌خوای اینجا بنویس<textarea name="note" rows="3" maxlength="500"></textarea></label>'
			. '<button class="ql-btn" name="decision" value="approve">همین خوبه، تأیید و چاپ</button> <button class="ql-btn ql-alt" name="decision" value="change">تغییر می‌خوام</button></form>';
	}
	echo '</div></div>';
	return ob_get_clean();
} );
function qs_proof_do() {
	$id = isset( $_POST['o'] ) ? absint( $_POST['o'] ) : 0;
	$k  = isset( $_POST['k'] ) ? sanitize_text_field( wp_unslash( $_POST['k'] ) ) : '';
	$o  = $id ? wc_get_order( $id ) : null;
	$key = $o ? (string) $o->get_meta( '_qs_proof_key' ) : '';
	if ( ! $o || ! $key || ! hash_equals( $key, $k ) ) { wp_die( 'لینک نامعتبره.', 403 ); }
	$note = mb_substr( trim( sanitize_textarea_field( wp_unslash( isset( $_POST['note'] ) ? $_POST['note'] : '' ) ) ), 0, 500 );
	$dec  = isset( $_POST['decision'] ) && 'approve' === $_POST['decision'] ? 'approved' : 'changes';
	if ( 'changes' === $dec && mb_strlen( $note ) < 3 ) { wp_safe_redirect( add_query_arg( array( 'o' => $id, 'k' => $k ), qs_page_url( 'proof' ) ) ); exit; }
	$o->update_meta_data( '_qs_proof', $dec );
	if ( $note ) { $o->update_meta_data( '_qs_proof_note', $note ); }
	$o->save();
	$o->add_order_note( 'approved' === $dec ? 'مشتری طرح رو تأیید کرد.' : 'مشتری تغییر خواست: ' . $note );
	qs_notify_admin( ( 'approved' === $dec ? 'طرح سفارش #' . $o->get_order_number() . ' تأیید شد.' : 'سفارش #' . $o->get_order_number() . ': مشتری تغییر خواست: ' . $note ), $id );
	wp_safe_redirect( add_query_arg( array( 'o' => $id, 'k' => $k, 'done' => 1 ), qs_page_url( 'proof' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_qs_proof_do', 'qs_proof_do' );
add_action( 'admin_post_qs_proof_do', 'qs_proof_do' );

/* admin buttons: approve manually / resend the link */
add_action( 'admin_post_qs_proof_admin', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_proof_admin' );
	$o = wc_get_order( isset( $_GET['o'] ) ? absint( $_GET['o'] ) : 0 );
	$a = isset( $_GET['a'] ) ? sanitize_key( $_GET['a'] ) : '';
	if ( $o ) {
		if ( 'approve' === $a ) { $o->update_meta_data( '_qs_proof', 'approved' ); $o->save(); $o->add_order_note( 'طرح به‌صورت دستی تأیید شد.' ); }
		if ( 'resend' === $a ) { if ( ! $o->get_meta( '_qs_proof_key' ) ) { $o->update_meta_data( '_qs_proof_key', wp_generate_password( 24, false ) ); $o->update_meta_data( '_qs_proof', 'pending' ); $o->save(); } qs_proof_send_link( $o ); }
	}
	wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=qs-print' ) );
	exit;
} );
