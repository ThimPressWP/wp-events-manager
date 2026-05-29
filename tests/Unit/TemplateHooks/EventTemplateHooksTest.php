<?php
/**
 * Event template hook tests.
 *
 * @package WPEMS\Tests\Unit\TemplateHooks
 */

namespace WPEMS\Tests\Unit\TemplateHooks;

use Brain\Monkey\Functions;
use WPEMS\TemplateHooks\EventTemplateHooks;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test template hook registration.
 */
class EventTemplateHooksTest extends TestCase {

	/**
	 * Captured hook registrations.
	 *
	 * @var array
	 */
	private $registered_hooks = array();

	/**
	 * Reset initialized flag.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( EventTemplateHooks::class, 'initialized', false );
		$this->registered_hooks = array();
	}

	/**
	 * It registers archive wrapper hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_main_content_hooks(): void {
		$this->captureHooks();

		EventTemplateHooks::init();

		$this->assertSame( 'wpems_before_main_content', $this->registered_hooks['tp_event_before_main_content'][0] );
		$this->assertSame( 'wpems_after_main_content', $this->registered_hooks['tp_event_after_main_content'][0] );
		$this->assertSame( 'wpems_archive_event_pagination', $this->registered_hooks['tp_event_after_event_loop'][0] );
	}

	/**
	 * It registers single event display hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_single_event_hooks(): void {
		$this->captureHooks();

		EventTemplateHooks::init();

		$this->assertSame( 'wpems_before_single_event', $this->registered_hooks['tp_event_before_single_event'][0] );
		$this->assertSame( 'wpems_after_single_event', $this->registered_hooks['tp_event_after_single_event'][0] );
		$this->assertSame( 'wpems_single_event_register', $this->registered_hooks['tp_event_after_single_event'][1] );
		$this->assertSame( 'wpems_single_event_content', $this->registered_hooks['tp_event_single_event_content'][0] );
	}

	/**
	 * It does not register duplicate hooks on repeated init calls.
	 *
	 * @return void
	 */
	public function test_init_only_registers_once(): void {
		$this->captureHooks();

		EventTemplateHooks::init();
		EventTemplateHooks::init();

		$total = array_sum( array_map( 'count', $this->registered_hooks ) );

		$this->assertSame( 8, $total );
	}

	/**
	 * Capture calls to add_action.
	 *
	 * @return array
	 */
	private function captureHooks(): void {
		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) {
				$this->registered_hooks[ $hook ][] = $callback;
			}
		);
	}
}
