<?php
/**
 * Unit tests for WPEMS\Services\TaxService.
 *
 * Uses a testable subclass to stub SettingsManager calls.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use WPEMS\Services\TaxService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Testable subclass that stubs settings access.
 */
class TestableTaxService extends TaxService {

	/** @var array Option overrides. */
	public array $options = array();

	public function is_enabled(): bool {
		return 'yes' === ( $this->options['thimpress_events_tax_enable'] ?? 'no' );
	}

	public function get_rate(): string {
		$rate = $this->options['thimpress_events_tax_rate'] ?? '0';
		$rate = is_numeric( $rate ) ? (string) $rate : '0';

		if ( bccomp( $rate, '0', 4 ) < 0 ) {
			$rate = '0';
		}
		if ( bccomp( $rate, '100', 4 ) > 0 ) {
			$rate = '100';
		}

		return bcadd( $rate, '0', 4 );
	}

	public function get_label(): string {
		$label = (string) ( $this->options['thimpress_events_tax_label'] ?? 'Tax' );
		return '' !== $label ? $label : 'Tax';
	}
}

/**
 * @covers \WPEMS\Services\TaxService
 */
class TaxServiceTest extends TestCase {

	private TestableTaxService $svc;

	protected function setUp(): void {
		parent::setUp();
		$this->svc = new TestableTaxService();
	}

	/** @test */
	public function test_is_enabled_reads_option(): void {
		$this->svc->options['thimpress_events_tax_enable'] = 'yes';
		$this->assertTrue( $this->svc->is_enabled() );
	}

	/** @test */
	public function test_is_disabled_by_default(): void {
		$this->assertFalse( $this->svc->is_enabled() );
	}

	/** @test */
	public function test_get_rate_returns_zero_when_unset(): void {
		$this->svc->options['thimpress_events_tax_rate'] = '';
		$this->assertSame( '0.0000', $this->svc->get_rate() );
	}

	/** @test */
	public function test_get_rate_clamps_negative_to_zero(): void {
		$this->svc->options['thimpress_events_tax_rate'] = '-5';
		$this->assertSame( '0.0000', $this->svc->get_rate() );
	}

	/** @test */
	public function test_get_rate_clamps_over_100(): void {
		$this->svc->options['thimpress_events_tax_rate'] = '150';
		$this->assertSame( '100.0000', $this->svc->get_rate() );
	}

	/** @test */
	public function test_get_rate_normalises_to_four_dp(): void {
		$this->svc->options['thimpress_events_tax_rate'] = '10';
		$this->assertSame( '10.0000', $this->svc->get_rate() );
	}

	/** @test */
	public function test_calculate_returns_zero_when_disabled(): void {
		$this->assertSame( '0.0000', $this->svc->calculate( '100.0000', 'USD' ) );
	}

	/** @test */
	public function test_calculate_returns_zero_when_taxable_is_zero(): void {
		$this->svc->options['thimpress_events_tax_enable'] = 'yes';
		$this->assertSame( '0.0000', $this->svc->calculate( '0.0000', 'USD' ) );
	}

	/** @test */
	public function test_calculate_10_percent_on_100(): void {
		$this->svc->options['thimpress_events_tax_enable'] = 'yes';
		$this->svc->options['thimpress_events_tax_rate']   = '10';
		$this->assertSame( '10.0000', $this->svc->calculate( '100.0000', 'USD' ) );
	}

	/** @test */
	public function test_calculate_handles_fractional_subtotal(): void {
		$this->svc->options['thimpress_events_tax_enable'] = 'yes';
		$this->svc->options['thimpress_events_tax_rate']   = '10';
		$this->assertSame( '3.3333', $this->svc->calculate( '33.3333', 'USD' ) );
	}
}
