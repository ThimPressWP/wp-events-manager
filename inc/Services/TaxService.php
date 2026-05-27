<?php
/**
 * Tax calculation service.
 *
 * Thin wrapper around the thimpress_events_tax_* options.
 * Pure-functional: settings in → tax out.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use WPEMS\Admin\SettingsManager;

/**
 * Tax service.
 */
class TaxService {

	/**
	 * Whether tax is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === SettingsManager::get_option( 'thimpress_events_tax_enable', 'no' );
	}

	/**
	 * Get the tax rate, clamped to [0, 100] and normalized to 4 decimal places.
	 *
	 * @return string DECIMAL(7,4) as string.
	 */
	public function get_rate(): string {
		$rate = SettingsManager::get_option( 'thimpress_events_tax_rate', '0' );
		$rate = is_numeric( $rate ) ? (string) $rate : '0';

		if ( bccomp( $rate, '0', 4 ) < 0 ) {
			$rate = '0';
		}

		if ( bccomp( $rate, '100', 4 ) > 0 ) {
			$rate = '100';
		}

		return bcadd( $rate, '0', 4 );
	}

	/**
	 * Get the tax label for display.
	 *
	 * @return string
	 */
	public function get_label(): string {
		$label = (string) SettingsManager::get_option( 'thimpress_events_tax_label', 'Tax' );

		return '' !== $label ? $label : 'Tax';
	}

	/**
	 * Calculate tax for a given taxable amount.
	 *
	 * @param string $taxable_amount DECIMAL(15,4) as string.
	 * @param string $currency       ISO 4217 code (reserved for future per-currency overrides).
	 *
	 * @return string DECIMAL(15,4) tax amount.
	 */
	public function calculate( string $taxable_amount, string $currency ): string {
		if ( ! $this->is_enabled() ) {
			return '0.0000';
		}

		if ( bccomp( $taxable_amount, '0', 4 ) <= 0 ) {
			return '0.0000';
		}

		$rate = $this->get_rate();

		return bcdiv( bcmul( $taxable_amount, $rate, 8 ), '100', 4 );
	}
}
