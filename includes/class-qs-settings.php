<?php
defined( 'ABSPATH' ) || exit;

class QS_Settings_Page extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'qs';
		$this->label = 'QuackStick';
		parent::__construct();
		add_action( 'woocommerce_admin_field_qs_tools', array( $this, 'tools' ) );
	}
	public function get_sections() {
		return array( '' => 'عمومی', 'price' => 'قیمت و تخفیف', 'proof' => 'تأیید طرح', 'notify' => 'اعلان‌ها (پیامک و تلگرام)', 'tools' => 'ابزارها' );
	}
	private function f( $id, $title, $type, $desc = '', $extra = array() ) {
		return array_merge( array( 'id' => 'qs_opts[' . $id . ']', 'title' => $title, 'type' => $type, 'default' => qs_opt( $id ), 'desc' => $desc, 'desc_tip' => false, 'autoload' => false ), $extra );
	}
	private function cb( $id, $title, $desc = '' ) { return array( 'id' => 'qs_opts[' . $id . ']', 'title' => $title, 'type' => 'checkbox', 'desc' => $desc, 'default' => qs_opt( $id ) ? 'yes' : 'no', 'autoload' => false ); }
	private function sec( $id, $title, $desc = '' ) { return array( array( 'title' => $title, 'type' => 'title', 'id' => $id, 'desc' => $desc ) ); }
	private function end( $id ) { return array( array( 'type' => 'sectionend', 'id' => $id ) ); }

	public function get_settings_for_default_section() {
		$ltr = array( 'css' => 'width:420px;direction:ltr' );
		return array_merge(
			$this->sec( 'qs_contact', 'ارتباط با مشتری' ),
			array( $this->f( 'tg', 'آیدی تلگرام فروشگاه', 'text', 'بدون @. توی لندینگ، فرم سفارش طراحی و فوتر نشون داده می‌شه.', $ltr ),
				$this->f( 'notify', 'ایمیل اعلان درخواست طراحی', 'email', 'خالی = ایمیل مدیر سایت', $ltr ) ),
			$this->end( 'qs_contact' ),
			$this->sec( 'qs_look', 'ظاهر و اردک' ),
			array( $this->cb( 'duck', 'اردک مزاحم', 'فعال' ),
				$this->f( 'duck_name', 'اسم اردک', 'text', 'روی برچسب زیر اردک و توی حرف‌هاش (حداکثر ۲۴ حرف).', array( 'css' => 'width:220px' ) ),
				$this->f( 'duck_costume', 'لباس اردک', 'select', 'خودکار: نوروز، یلدا و زمستون رو با تاریخ شمسی تشخیص می‌ده.', array( 'options' => array( 'auto' => 'خودکار', 'none' => 'بدون لباس', 'nowruz' => 'نوروز', 'yalda' => 'یلدا', 'winter' => 'زمستون' ) ) ),
				$this->cb( 'fa', 'اعداد فارسی', 'همه‌ی عددهای صفحه‌های فروشگاه فارسی نشون داده بشن' ),
				$this->cb( 'craft', 'استایل کاغذ کرافت', 'روی تم‌های دیگه (با تم QuackStick Craft نیازی نیست)' ),
				$this->f( 'sheet', 'ورق پیش‌فرض چاپ', 'select', '', array( 'options' => array( 'a4' => 'A4', 'a3' => 'A3' ) ) ) ),
			$this->end( 'qs_look' )
		);
	}
	public function get_settings_for_price_section() {
		$ltr = array( 'css' => 'width:420px;direction:ltr' );
		return array_merge(
			$this->sec( 'qs_prices', 'قیمت‌ها (به واحد پولی فروشگاه)' ),
			array( $this->f( 'sizes', 'قیمت استیکر تکی', 'text', 'فرمت «سانت:قیمت» با کاما. مثال: 4:20000,6:35000', $ltr ),
				$this->f( 'case_base', 'قیمت پایه‌ی قاب گوشی', 'number' ),
				$this->f( 'tiers', 'قیمت هر استیکر روی قاب', 'text', 'فرمت «حداکثر سانت:قیمت». مثال: 2.5:10000,4:20000', $ltr ),
				$this->f( 'sketch_cm', 'اندازه‌ی نقاشی آزاد (سانت)', 'number', 'باید یکی از اندازه‌های استیکر تکی باشه.', array( 'custom_attributes' => array( 'step' => '0.5' ) ) ),
				$this->f( 'max_stickers', 'حداکثر استیکر روی هر قاب', 'number' ),
				$this->f( 'sheet_prices', 'قیمت ورق استیکر', 'text', 'فرمت «a6:قیمت,a5:قیمت». هر ورق هر تعداد استیکر (تا سقف پایین) داره.', $ltr ),
				$this->f( 'max_sheet', 'حداکثر استیکر روی هر ورق', 'number' ) ),
			$this->end( 'qs_prices' ),
			$this->sec( 'qs_tier', 'تخفیف تعدادی', 'روی قطعه‌های طراحی‌شده توسط مشتری (استیکرهای تکی با تعدادشون، قاب و ورق هر کدوم یک قطعه). جمع قطعه‌های کل سبد حساب می‌شه.' ),
			array( $this->f( 'tiers_qty', 'پله‌ها', 'text', 'فرمت «تعداد:درصد». مثال: 10:5,25:10,50:15. خالی = بدون تخفیف.', $ltr ) ),
			$this->end( 'qs_tier' ),
			$this->sec( 'qs_gift', 'قاب هدیه', 'وقتی جمع سبد به این مبلغ برسه، مشتری مدل گوشی و تم دلخواهش رو می‌نویسه و ما براش قاب طراحی می‌کنیم.' ),
			array( $this->f( 'gift_min', 'حداقل خرید برای قاب هدیه', 'number', 'به واحد پولی فروشگاه. عدد 0 یعنی خاموش.' ) ),
			$this->end( 'qs_gift' )
		);
	}
	public function get_settings_for_proof_section() {
		return array_merge(
			$this->sec( 'qs_proof', 'تأیید طرح قبل از چاپ', 'بعد از ثبت سفارشِ طرح‌های ساخته‌شده توسط مشتری، لینک تأیید با ایمیل (و پیامک) براش فرستاده می‌شه. صف چاپ نشون می‌ده کدوم سفارش‌ها تأیید شدن.' ),
			array( $this->cb( 'proof', 'فعال', 'برای طرح‌های مشتری، تأیید بگیر' ),
				$this->f( 'proof_hours', 'تأیید خودکار بعد از (ساعت)', 'number', '0 = هیچ‌وقت خودکار تأیید نشه. برای مثال 48 یعنی اگه مشتری تا ۲ روز جواب نداد، تأیید حساب بشه.' ) ),
			$this->end( 'qs_proof' )
		);
	}
	public function get_settings_for_notify_section() {
		$ltr = array( 'css' => 'width:420px;direction:ltr' );
		return array_merge(
			$this->sec( 'qs_tgbot', 'تلگرام (برای خودت)', 'ساخت بات: از @BotFather. Chat ID رو از @userinfobot یا getUpdates بگیر. سرور باید به api.telegram.org دسترسی داشته باشه.' ),
			array( $this->f( 'bot', 'توکن بات', 'text', '', $ltr ), $this->f( 'chat', 'Chat ID', 'text', '', $ltr ) ),
			$this->end( 'qs_tgbot' ),
			$this->sec( 'qs_sms', 'پیامک', 'کاوه‌نگار آماده‌ست. برای پنل‌های دیگه «وب‌هوک» رو بزن و آدرس API پنلت رو با {to} {text} {sender} {key} بنویس.' ),
			array( $this->f( 'sms_provider', 'سرویس', 'select', '', array( 'options' => array( '' => 'خاموش', 'kavenegar' => 'کاوه‌نگار', 'webhook' => 'وب‌هوک (هر پنل دیگه)' ) ) ),
				$this->f( 'sms_key', 'کلید API', 'text', '', $ltr ), $this->f( 'sms_sender', 'شماره/نام فرستنده', 'text', '', $ltr ),
				$this->f( 'sms_url', 'آدرس وب‌هوک', 'text', 'مثال: https://panel.example.ir/send?key={key}&to={to}&text={text}', $ltr ),
				$this->f( 'sms_method', 'روش وب‌هوک', 'select', '', array( 'options' => array( 'GET' => 'GET', 'POST' => 'POST' ) ) ),
				$this->f( 'sms_admin', 'شماره‌ی موبایل تو (برای اعلان‌ها)', 'text', '', $ltr ) ),
			$this->end( 'qs_sms' ),
			$this->sec( 'qs_events', 'چی اعلان بشه؟', 'متن‌ها قابل ویرایش‌ان. جاگذاری‌ها: {order} {name} {total} {link} {tracking}' ),
			array( $this->cb( 'n_admin_order', 'سفارش جدید (به خودت)' ), $this->f( 't_admin_order', 'متن', 'text', '', array( 'css' => 'width:100%' ) ),
				$this->cb( 'n_proof', 'لینک تأیید طرح (به مشتری)' ), $this->f( 't_proof', 'متن', 'text', '', array( 'css' => 'width:100%' ) ),
				$this->cb( 'n_processing', 'پرداخت تأیید شد (به مشتری)' ), $this->f( 't_processing', 'متن', 'text', '', array( 'css' => 'width:100%' ) ),
				$this->cb( 'n_completed', 'سفارش تکمیل شد (به مشتری)' ), $this->f( 't_completed', 'متن', 'text', '', array( 'css' => 'width:100%' ) ),
				$this->cb( 'n_shipped', 'کد رهگیری ثبت شد (به مشتری)' ), $this->f( 't_shipped', 'متن', 'text', '', array( 'css' => 'width:100%' ) ) ),
			$this->end( 'qs_events' )
		);
	}
	public function get_settings_for_tools_section() {
		return array_merge(
			$this->sec( 'qs_clean', 'پاک‌سازی فایل‌ها', 'فایل‌های طرح و پیش‌نمایشی که توی هیچ سفارش، سبد، طرح ذخیره‌شده یا درخواستی نیستن، هر روز پاک می‌شن.' ),
			array( $this->f( 'cleanup_days', 'پاک‌سازی فایل‌های قدیمی‌تر از (روز)', 'number', '0 = خاموش' ) ),
			$this->end( 'qs_clean' ),
			$this->sec( 'qs_tools_t', 'میانبرها' ),
			array( array( 'type' => 'qs_tools', 'id' => 'qs_tools' ) ),
			$this->end( 'qs_tools_t' )
		);
	}
	public function tools() {
		$hpos  = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		$links = array(
			'لیست محصولات'      => admin_url( 'edit.php?post_type=product' ),
			'افزودن محصول'      => admin_url( 'post-new.php?post_type=product' ),
			'سفارش‌ها'          => $hpos ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' ),
			'صف چاپ'            => admin_url( 'admin.php?page=qs-print' ),
			'درخواست‌های طراحی' => admin_url( 'edit.php?post_type=qs_request' ),
			'کوپن‌ها'           => admin_url( 'edit.php?post_type=shop_coupon' ),
			'ارسال و مناطق'     => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
		);
		foreach ( array( 'designer' => 'صفحه‌ی طراحی', 'request' => 'صفحه‌ی سفارش طراحی', 'proof' => 'صفحه‌ی تأیید طرح', 'landing' => 'لندینگ' ) as $k => $l ) { if ( get_option( 'qs_pg_' . $k ) ) { $links[ $l ] = qs_page_url( $k ); } }
		if ( isset( $_GET['cleaned'] ) ) { echo '<tr><td colspan="2"><div class="notice notice-success inline"><p>پاک‌سازی انجام شد: ' . (int) $_GET['cleaned'] . ' فایل (' . (int) $_GET['kb'] . ' کیلوبایت) پاک شد، ' . (int) ( isset( $_GET['kept'] ) ? $_GET['kept'] : 0 ) . ' فایل چون استفاده می‌شه موند.</p></div></td></tr>'; }
		if ( isset( $_GET['coupon'] ) ) { echo '<tr><td colspan="2"><div class="notice notice-success inline"><p>کوپن duck10 (۱۰٪، یک بار برای هر کاربر، ۹۰ روزه) ساخته شد.</p></div></td></tr>'; }
		echo '<tr valign="top"><th scope="row" class="titledesc">میانبرها</th><td class="forminp"><p>';
		foreach ( $links as $l => $u ) { echo '<a class="button" style="margin:0 0 6px 6px" href="' . esc_url( $u ) . '">' . esc_html( $l ) . '</a>'; }
		echo '</p><p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_seed' ), 'qs_seed' ) ) . '">ساخت ۶ اردک آماده (محصول)</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_coupon' ), 'qs_coupon' ) ) . '">ساخت کوپن duck10</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_cleanup_run' ), 'qs_cleanup_run' ) ) . '">پاک‌سازی همین الان</a> ';
		if ( function_exists( 'qs_uses_blocks' ) && qs_uses_blocks() ) { echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_classic' ), 'qs_classic' ) ) . '">تبدیل سبد/تسویه به کلاسیک</a>'; }
		echo '</p></td></tr>';
	}
}
