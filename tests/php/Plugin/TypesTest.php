<?php
/**
 * Content type definition tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use QeEdu\Core\PostTypes\Types;

/**
 * Types.
 */
final class TypesTest extends PluginTestCase {

	public function test_all_types_have_plain_language_fields() {
		$types = Types::all();
		$this->assertSame( array( 'qe_destination', 'qe_service', 'qe_partner', 'qe_event', 'qe_team', 'qe_testimonial' ), array_keys( $types ) );
		foreach ( $types as $slug => $type ) {
			$this->assertNotSame( '', $type['singular'], $slug );
			$this->assertStringStartsWith( 'dashicons-', $type['icon'] );
			foreach ( $type['fields'] as $key => $field ) {
				$this->assertNotSame( '', $field['label'], "$slug.$key" );
				$this->assertNotSame( '', $field['help'], "$slug.$key" );
			}
		}
		$this->assertArrayHasKey( 'university', Types::partner_types() );
	}

	public function test_meta_key_and_get() {
		$this->assertSame( '_qe_link', Types::meta_key( 'link' ) );
		$this->meta[3]['_qe_link'] = 'https://x.test';
		$this->assertSame( 'https://x.test', Types::get( 3, 'link' ) );
		$this->assertSame( '', Types::get( 3, 'nope' ) );
	}

	public function test_sanitize() {
		$this->assertSame( 'https://x.test', Types::sanitize( array( 'type' => 'url' ), ' https://x.test ' ) );
		$this->assertSame( '12', Types::sanitize( array( 'type' => 'media' ), '12' ) );
		$this->assertSame( '2026-05-01', Types::sanitize( array( 'type' => 'date' ), '2026-05-01' ) );
		$this->assertSame( '', Types::sanitize( array( 'type' => 'date' ), '01/05/2026' ) );
		$this->assertSame( '09:30', Types::sanitize( array( 'type' => 'time' ), '09:30' ) );
		$this->assertSame( '', Types::sanitize( array( 'type' => 'time' ), '9am' ) );
		$select = array(
			'type'    => 'select',
			'options' => array(
				'a' => 'A',
				'b' => 'B',
			),
		);
		$this->assertSame( 'b', Types::sanitize( $select, 'b' ) );
		$this->assertSame( 'a', Types::sanitize( $select, 'z' ) );
		$this->assertSame( 'hi', Types::sanitize( array( 'type' => 'text' ), '<b>hi</b>' ) );
		$this->assertSame( '', Types::sanitize( array( 'type' => 'text' ), array( 'x' ) ) );
	}
}
