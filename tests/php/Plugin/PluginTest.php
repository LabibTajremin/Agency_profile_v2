<?php
/**
 * Plugin bootstrap tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use QeEdu\Core\Plugin;

/**
 * A module used to observe boot().
 */
final class FakeModule {
	/** @var int */
	public static $registered = 0;

	public function register() {
		++self::$registered;
	}
}

/**
 * Plugin.
 */
final class PluginTest extends PluginTestCase {

	protected function setUp(): void {
		parent::setUp();
		Plugin::reset();
		FakeModule::$registered = 0;
		Functions\when( 'plugin_basename' )->justReturn( 'qe-edu-core/qe-edu-core.php' );
	}

	protected function tearDown(): void {
		Plugin::reset();
		parent::tearDown();
	}

	public function test_singleton() {
		$this->assertSame( Plugin::instance(), Plugin::instance() );
	}

	public function test_default_modules() {
		$modules = Plugin::instance()->module_classes();
		$this->assertContains( 'QeEdu\Core\Settings\AdminPage', $modules );
		foreach ( $modules as $class ) {
			$this->assertTrue( class_exists( $class ), $class );
		}
	}

	public function test_boot_registers_modules_once() {
		Filters\expectApplied( 'qe_edu_core_modules' )->once()->andReturn( array( FakeModule::class ) );
		Functions\expect( 'load_plugin_textdomain' )->once()->with( 'qe-edu', false, 'qe-edu-core/languages' );
		Actions\expectDone( 'qe_edu_core_loaded' )->once();

		$plugin = Plugin::instance();
		$plugin->boot();
		$plugin->boot();

		$this->assertSame( 1, FakeModule::$registered );
		$this->assertInstanceOf( FakeModule::class, $plugin->module( FakeModule::class ) );
		$this->assertNull( $plugin->module( 'Nope' ) );
		$this->assertNotFalse( has_action( 'load_textdomain', array( \QeEdu\Core\Settings\Schema::class, 'flush' ) ) );
		$this->assertNotFalse( has_action( 'change_locale', array( \QeEdu\Core\Settings\Schema::class, 'flush' ) ) );
	}

	public function test_activate_and_deactivate() {
		Filters\expectApplied( 'qe_edu_core_modules' )->andReturn( array() );
		Functions\when( 'load_plugin_textdomain' )->justReturn( true );
		Actions\expectDone( 'qe_edu_core_activated' )->once();
		Functions\expect( 'flush_rewrite_rules' )->twice();

		Plugin::activate();
		Plugin::deactivate();
		$this->addToAssertionCount( 1 );
	}
}
