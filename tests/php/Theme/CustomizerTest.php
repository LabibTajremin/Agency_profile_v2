<?php
/**
 * Tests for inc/customizer.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

/**
 * Customizer.
 */
final class CustomizerTest extends ThemeTestCase {

	public function test_fields_and_ids() {
		$fields = qe_edu_customizer_fields();
		$this->assertArrayHasKey( 'footer.copyright', $fields );
		$this->assertSame( 'qe_edu_settings[footer][copyright]', qe_edu_customizer_id( 'footer.copyright' ) );
	}

	public function test_sanitize_checkbox() {
		$this->assertTrue( qe_edu_sanitize_checkbox( '1' ) );
		$this->assertFalse( qe_edu_sanitize_checkbox( '' ) );
	}

	public function test_register() {
		$fields  = qe_edu_customizer_fields();
		$manager = \Mockery::mock();
		$manager->shouldReceive( 'add_section' )->once()->with( 'qe_edu_quick', \Mockery::type( 'array' ) );
		$manager->shouldReceive( 'add_setting' )->times( count( $fields ) )->with(
			\Mockery::pattern( '/^qe_edu_settings\[/' ),
			\Mockery::on(
				static function ( $args ) {
					return 'option' === $args['type'] && is_callable( $args['sanitize_callback'] ) || is_string( $args['sanitize_callback'] );
				}
			)
		);
		$manager->shouldReceive( 'add_control' )->times( count( $fields ) );
		$name = (object) array( 'transport' => 'refresh' );
		$desc = (object) array( 'transport' => 'refresh' );
		$manager->shouldReceive( 'get_setting' )->with( 'blogname' )->andReturn( $name );
		$manager->shouldReceive( 'get_setting' )->with( 'blogdescription' )->andReturn( $desc );

		qe_edu_customize_register( $manager );

		$this->assertSame( 'postMessage', $name->transport );
		$this->assertSame( 'postMessage', $desc->transport );
	}
}
