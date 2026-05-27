<?php

class WooAddonTest extends WP_UnitTestCase {

	public function test_r3d_woo_version_constant_is_defined() {
		$this->assertTrue( defined( 'R3D_WOO_VERSION' ) );
		$this->assertSame( '1.6.2', R3D_WOO_VERSION );
	}

	public function test_r3d_woo_file_constant_is_defined() {
		$this->assertTrue( defined( 'R3D_WOO_FILE' ) );
	}

	public function test_r3d_woo_singleton_returns_instance() {
		$instance = R3D_Woo::get_instance();
		$this->assertInstanceOf( R3D_Woo::class, $instance );
	}

	public function test_r3d_woo_singleton_returns_same_instance() {
		$a = R3D_Woo::get_instance();
		$b = R3D_Woo::get_instance();
		$this->assertSame( $a, $b );
	}

	public function test_product_flipbook_shortcode_method_exists() {
		$instance = R3D_Woo::get_instance();
		$this->assertTrue( method_exists( $instance, 'product_flipbook_shortcode' ) );
	}

	public function test_minimum_version_constant() {
		$this->assertSame( '3.17.1', R3D_Woo::MINIMUM_REAL3D_FLIPBOOK_VERSION );
	}
}
