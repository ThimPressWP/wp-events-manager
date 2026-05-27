<?php
/**
 * Checkout settings tax + coupon tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Settings
 */

namespace WPEMS\Tests\Unit\Admin\Settings;

use Brain\Monkey\Functions;
use WPEMS\Admin\Settings\Checkout;
use WPEMS\Admin\SettingsManager;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test tax and coupon fields in checkout settings.
 */
class CheckoutSettingsTaxTest extends TestCase {

	/**
	 * Captured option updates.
	 *
	 * @var array
	 */
	private $updates = array();

	/**
	 * Prepare doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->updates = array();

		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->updates[ $name ] = $value;

				return true;
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			function ( $value ) {
				return strip_tags( (string) $value, '<strong><em><a>' );
			}
		);
	}

	/**
	 * Reset SettingsManager state between tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		SettingsManager::reset_setting_pages();
		parent::tearDown();
	}

	/**
	 * It includes the four new tax + coupon entries in get_settings().
	 *
	 * @return void
	 */
	public function test_tax_enable_field_appears_in_settings(): void {
		$settings = ( new Checkout() )->get_settings();

		$ids = array_column( $settings, 'id' );

		$this->assertContains( 'thimpress_events_tax_enable', $ids );
		$this->assertContains( 'thimpress_events_tax_rate', $ids );
		$this->assertContains( 'thimpress_events_tax_label', $ids );
		$this->assertContains( 'thimpress_events_coupon_enable', $ids );
	}

	/**
	 * It uses number type with bounded atts for tax_rate.
	 *
	 * @return void
	 */
	public function test_tax_rate_uses_number_type_with_clamped_atts(): void {
		$settings = ( new Checkout() )->get_settings();

		$tax_rate = null;
		foreach ( $settings as $field ) {
			if ( isset( $field['id'] ) && 'thimpress_events_tax_rate' === $field['id'] ) {
				$tax_rate = $field;
				break;
			}
		}

		$this->assertNotNull( $tax_rate, 'Tax rate field not found.' );
		$this->assertSame( 'number', $tax_rate['type'] );
		$this->assertSame( '0', $tax_rate['default'] );
		$this->assertArrayHasKey( 'atts', $tax_rate );
		$this->assertSame( 0, $tax_rate['atts']['min'] );
		$this->assertSame( 100, $tax_rate['atts']['max'] );
		$this->assertSame( '0.0001', $tax_rate['atts']['step'] );
	}

	/**
	 * It defaults coupon_enable to 'no'.
	 *
	 * @return void
	 */
	public function test_coupon_enable_default_is_no(): void {
		$settings = ( new Checkout() )->get_settings();

		$coupon_enable = null;
		foreach ( $settings as $field ) {
			if ( isset( $field['id'] ) && 'thimpress_events_coupon_enable' === $field['id'] ) {
				$coupon_enable = $field;
				break;
			}
		}

		$this->assertNotNull( $coupon_enable, 'Coupon enable field not found.' );
		$this->assertSame( 'yes_no', $coupon_enable['type'] );
		$this->assertSame( 'no', $coupon_enable['default'] );
	}

	/**
	 * It sanitizes tax_rate: rejects non-numeric, preserves valid numeric values.
	 *
	 * The existing SettingsManager::sanitize_field_value for 'number' type
	 * uses is_numeric() — HTML atts (min/max) are only browser-level hints.
	 *
	 * @return void
	 */
	public function test_register_setting_sanitizes_tax_rate_rejects_non_numeric(): void {
		$field = array(
			'id'   => 'thimpress_events_tax_rate',
			'type' => 'number',
			'atts' => array(
				'min'  => 0,
				'max'  => 100,
				'step' => '0.0001',
			),
		);

		// Non-numeric rejected.
		$this->assertSame( '', SettingsManager::sanitize_field_value( $field, 'abc' ) );
		$this->assertSame( '', SettingsManager::sanitize_field_value( $field, '' ) );

		// Valid numeric values preserved.
		$this->assertSame( '10.5', SettingsManager::sanitize_field_value( $field, '10.5' ) );
		$this->assertSame( '0', SettingsManager::sanitize_field_value( $field, '0' ) );
		$this->assertSame( '100', SettingsManager::sanitize_field_value( $field, '100' ) );
		$this->assertSame( '0.0001', SettingsManager::sanitize_field_value( $field, '0.0001' ) );

		// Negative is numeric so passes through (HTML min=0 is browser-level).
		$this->assertSame( '-5', SettingsManager::sanitize_field_value( $field, '-5' ) );
	}

	/**
	 * It preserves tax + coupon fields after save_fields round-trip.
	 *
	 * @return void
	 */
	public function test_save_fields_round_trips_tax_and_coupon_options(): void {
		$settings = ( new Checkout() )->get_settings();

		$data = array(
			'thimpress_events_tax_enable'    => 'yes',
			'thimpress_events_tax_rate'      => '10.5',
			'thimpress_events_tax_label'     => 'VAT',
			'thimpress_events_coupon_enable' => 'yes',
		);

		$this->assertTrue( SettingsManager::save_fields( $settings, $data ) );

		$this->assertSame( 'yes', $this->updates['thimpress_events_tax_enable'] );
		$this->assertSame( '10.5', $this->updates['thimpress_events_tax_rate'] );
		$this->assertSame( 'VAT', $this->updates['thimpress_events_tax_label'] );
		$this->assertSame( 'yes', $this->updates['thimpress_events_coupon_enable'] );
	}
}
