<?php
/**
 * Conflict double that records exits.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Doubles;

use QeEdu\Core\Seo\Conflict;

/**
 * Conflict double.
 */
final class TestableConflict extends Conflict {
	/** @var int */
	public $halts = 0;

	protected function halt() {
		++$this->halts;
	}
}
