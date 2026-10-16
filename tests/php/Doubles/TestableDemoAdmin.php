<?php
/**
 * Demo import admin double: injectable importer / package, records exits.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Doubles;

use QeEdu\Core\DemoImport\Admin;

/**
 * Demo admin double.
 */
final class TestableDemoAdmin extends Admin {
	/** @var int */
	public $halts = 0;
	/** @var object|null */
	public $fake_importer;
	/** @var object|null */
	public $fake_package;
	/** @var bool|null */
	public $active;

	protected function importer() {
		return $this->fake_importer ? $this->fake_importer : parent::importer();
	}

	protected function package() {
		return $this->fake_package ? $this->fake_package : parent::package();
	}

	protected function elementor_active() {
		return null === $this->active ? parent::elementor_active() : $this->active;
	}

	protected function halt() {
		++$this->halts;
	}
}
