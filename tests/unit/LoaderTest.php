<?php

/**
 * Loader unit tests.
 *
 * @package Agent_Ready_Content
 */

namespace Agent_Ready_Content\Tests\Unit;

use Agent_Ready_Content\Loader;
use PHPUnit\Framework\TestCase;

/**
 * Verifies queued hooks are registered with WordPress.
 */
class LoaderTest extends TestCase {

	/**
	 * Clear recorded hook calls between tests.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['agent_ready_content_test_actions'] = array();
		$GLOBALS['agent_ready_content_test_filters'] = array();
	}

	/**
	 * The loader preserves hook registration arguments.
	 */
	public function test_run_registers_queued_actions_and_filters(): void {
		$component = $this->getMockBuilder( \stdClass::class )
			->addMethods( array( 'handle_action', 'handle_filter' ) )
			->getMock();
		$loader    = new Loader();

		$loader->add_action( 'agent_ready_content_test_action', $component, 'handle_action', 7, 2 );
		$loader->add_filter( 'agent_ready_content_test_filter', $component, 'handle_filter', 12, 3 );
		$loader->run();

		$this->assertSame( 'agent_ready_content_test_action', $GLOBALS['agent_ready_content_test_actions'][0]['hook'] );
		$this->assertSame( array( $component, 'handle_action' ), $GLOBALS['agent_ready_content_test_actions'][0]['callback'] );
		$this->assertSame( 7, $GLOBALS['agent_ready_content_test_actions'][0]['priority'] );
		$this->assertSame( 2, $GLOBALS['agent_ready_content_test_actions'][0]['accepted_args'] );

		$this->assertSame( 'agent_ready_content_test_filter', $GLOBALS['agent_ready_content_test_filters'][0]['hook'] );
		$this->assertSame( array( $component, 'handle_filter' ), $GLOBALS['agent_ready_content_test_filters'][0]['callback'] );
		$this->assertSame( 12, $GLOBALS['agent_ready_content_test_filters'][0]['priority'] );
		$this->assertSame( 3, $GLOBALS['agent_ready_content_test_filters'][0]['accepted_args'] );
	}
}
