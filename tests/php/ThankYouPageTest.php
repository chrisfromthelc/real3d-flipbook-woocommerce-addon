<?php

class ThankYouPageTest extends WP_UnitTestCase {

	public function test_has_active_subscription_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$instance = R3D_Woo::get_instance();
		$this->assertFalse( $instance->has_active_subscription() );
	}

	public function test_check_user_bought_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$instance = R3D_Woo::get_instance();
		$this->assertFalse( $instance->check_user_bought_variation_with_flipbook() );
	}

	public function test_filter_purchased_or_subscription_returns_true_when_already_granted() {
		$instance = R3D_Woo::get_instance();
		$this->assertTrue( $instance->filter_purchased_or_subscription( true ) );
	}
}
