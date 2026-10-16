<?php
/**
 * Leads double that records exits.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Doubles;

use QeEdu\Core\PostTypes\Leads;

/**
 * Leads double.
 */
final class TestableLeads extends Leads {
	/** @var int */
	public $halts = 0;

	protected function halt() {
		++$this->halts;
	}
}
