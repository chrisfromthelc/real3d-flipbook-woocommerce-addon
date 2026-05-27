<?php

class ProductFlipbookTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		if ( ! post_type_exists( 'product' ) ) {
			register_post_type( 'product' );
		}
	}

	public function test_save_meta_box_requires_nonce() {
		$post_id  = $this->factory->post->create( array( 'post_type' => 'product' ) );
		$instance = R3D_Woo::get_instance();

		$_POST = array( 'post_type' => 'product' );
		$instance->save_meta_box( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, 'r3d_flipbook_id', true ) );
	}

	public function test_meta_box_saves_flipbook_id() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'product' ) );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_POST = array(
			'post_type'               => 'product',
			'r3d_nonce'               => wp_create_nonce( 'r3d_save' ),
			'r3d_flipbook_id'         => '42',
			'r3d_preview_flipbook_id' => '43',
		);

		R3D_Woo::get_instance()->save_meta_box( $post_id );

		$this->assertSame( '42', get_post_meta( $post_id, 'r3d_flipbook_id', true ) );
		$this->assertSame( '43', get_post_meta( $post_id, 'r3d_preview_flipbook_id', true ) );
	}

	public function test_meta_box_saves_thankyou_override() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'product' ) );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_POST = array(
			'post_type'                  => 'product',
			'r3d_nonce'                  => wp_create_nonce( 'r3d_save' ),
			'r3d_show_thankyou_flipbook' => 'yes',
		);

		R3D_Woo::get_instance()->save_meta_box( $post_id );

		$this->assertSame( 'yes', get_post_meta( $post_id, 'r3d_show_thankyou_flipbook', true ) );
	}

	public function test_meta_box_deletes_empty_flipbook_id() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'product' ) );
		update_post_meta( $post_id, 'r3d_flipbook_id', '42' );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_POST = array(
			'post_type'       => 'product',
			'r3d_nonce'       => wp_create_nonce( 'r3d_save' ),
			'r3d_flipbook_id' => '',
		);

		R3D_Woo::get_instance()->save_meta_box( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, 'r3d_flipbook_id', true ) );
	}
}
