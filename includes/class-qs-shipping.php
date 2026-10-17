<?php
defined( 'ABSPATH' ) || exit;

class QS_Shipping_Method extends WC_Shipping_Method {
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'qs_ship';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = 'ارسال QuackStick (قیمت شهر/استان)';
		$this->method_description = 'هزینه‌ی ارسال بر اساس شهر یا استان مشتری، با ارسال رایگان بالای یه مبلغ.';
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
		$this->init();
	}
	public function init() {
		$this->instance_form_fields = array(
			'title'         => array( 'title' => 'عنوان', 'type' => 'text', 'default' => 'ارسال' ),
			'default_price' => array( 'title' => 'هزینه‌ی پیش‌فرض', 'type' => 'text', 'default' => '60000', 'description' => 'برای شهری که توی جدول زیر نیست.' ),
			'free_over'     => array( 'title' => 'ارسال رایگان بالای', 'type' => 'text', 'default' => '', 'description' => 'خالی = بدون ارسال رایگان.' ),
			'rules'         => array( 'title' => 'قیمت شهر/استان', 'type' => 'textarea', 'default' => "تهران=30000\nالبرز=40000", 'description' => 'هر خط: «شهر یا استان یا کد استان = قیمت». شهر بر استان اولویت داره.' ),
		);
		$this->init_settings();
		$this->title = $this->get_option( 'title', 'ارسال' );
		add_action( 'woocommerce_update_options_shipping_' . $this->id . '_' . $this->instance_id, array( $this, 'process_admin_options' ) );
	}
	private function price_for( $package ) {
		$city  = qs_norm( isset( $package['destination']['city'] ) ? $package['destination']['city'] : '' );
		$code  = strtolower( (string) ( isset( $package['destination']['state'] ) ? $package['destination']['state'] : '' ) );
		$ctry  = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
		$name  = '';
		if ( $code && $ctry && function_exists( 'WC' ) ) { $st = WC()->countries->get_states( $ctry ); $name = qs_norm( is_array( $st ) && isset( $st[ strtoupper( $code ) ] ) ? $st[ strtoupper( $code ) ] : '' ); }
		$byCity = array(); $byState = array();
		foreach ( preg_split( '/\r?\n/', (string) $this->get_option( 'rules' ) ) as $line ) {
			$p = explode( '=', $line );
			if ( 2 !== count( $p ) || ! is_numeric( trim( $p[1] ) ) ) { continue; }
			$k = qs_norm( $p[0] );
			$byCity[ $k ] = (float) trim( $p[1] );
		}
		if ( $city && isset( $byCity[ $city ] ) ) { return $byCity[ $city ]; }
		foreach ( array( $code, $name ) as $k ) { if ( $k && isset( $byCity[ qs_norm( $k ) ] ) ) { return $byCity[ qs_norm( $k ) ]; } }
		return (float) $this->get_option( 'default_price', 0 );
	}
	public function calculate_shipping( $package = array() ) {
		$cost = $this->price_for( $package );
		$free = (float) $this->get_option( 'free_over', 0 );
		if ( $free > 0 && isset( $package['contents_cost'] ) && (float) $package['contents_cost'] >= $free ) { $cost = 0; }
		$this->add_rate( array( 'id' => $this->get_rate_id(), 'label' => $this->title . ( 0 == $cost ? ' (رایگان)' : '' ), 'cost' => $cost, 'package' => $package ) );
	}
}
