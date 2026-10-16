<?php
/**
 * Base for theme tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use QeEdu\Tests\TestCase;
use QeEdu\Tests\ThemeLoader;

/**
 * Theme test case.
 */
abstract class ThemeTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		ThemeLoader::load();
	}

	/**
	 * Set a theme setting in the fake option store.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	protected function settings( array $settings ) {
		$this->options['qe_edu_settings'] = $settings;
	}
}
