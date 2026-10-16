<?php
/**
 * Test double for the admin page.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Doubles;

use QeEdu\Core\Settings\AdminPage;

/**
 * Admin page that records exits instead of stopping the process.
 */
final class TestableAdminPage extends AdminPage {
	/** @var int */
	public $halts = 0;

	protected function halt() {
		++$this->halts;
	}

	public function control( $field, $value ) {
		ob_start();
		$this->render_control( $field, $value, 'id', 'name' );
		return ob_get_clean();
	}
}

