<?php
/**
 * Redirects double that records exits.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Doubles;

use QeEdu\Core\Seo\Redirects;

/**
 * Redirects double.
 */
final class TestableRedirects extends Redirects {
	/** @var int */
	public $halts = 0;

	protected function halt() {
		++$this->halts;
	}
}
