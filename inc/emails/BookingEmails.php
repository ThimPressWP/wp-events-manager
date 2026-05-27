<?php
namespace WPEMS\Emails;

use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;

defined( 'ABSPATH' ) || exit;

class BookingEmails {

	private BookingRepository $bookings;

	public function __construct( BookingRepository $bookings ) {
		$this->bookings = $bookings;
	}

	public function register(): void {
		add_action( 'wpems_booking_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
	}

	public function on_status_changed( int $booking_id, string $from, string $to, string $reason ): void {
		$booking = $this->bookings->find( $booking_id );
		if ( ! $booking ) return;

		switch ( $to ) {
			case 'ea-processing':
				$this->send_customer_processing( $booking );
				$this->send_admin_new_booking( $booking );
				break;
			case 'ea-completed':
				if ( 'ea-completed' !== $from ) {
					$this->send_customer_completed( $booking );
				}
				break;
			case 'ea-cancelled':
				$this->send_customer_cancelled( $booking );
				break;
		}
	}

	public function send_customer_processing( BookingTableModel $b ): void {
		$this->send( 'customer_processing', $b );
	}

	public function send_customer_completed( BookingTableModel $b ): void {
		$this->send( 'customer_completed', $b );
	}

	public function send_customer_cancelled( BookingTableModel $b ): void {
		$this->send( 'customer_cancelled', $b );
	}

	public function send_admin_new_booking( BookingTableModel $b ): void {
		$this->send( 'admin_new_booking', $b );
	}

	public function render_pricing_block( BookingTableModel $b ): string {
		$lines = [];
		$lines[] = sprintf( 'Subtotal: %s %s', $b->get_subtotal(), $b->get_currency() );
		if ( (float) $b->get_discount_total() > 0 ) {
			$lines[] = sprintf( 'Discount: -%s', $b->get_discount_total() );
		}
		if ( (float) $b->get_tax_rate() > 0 ) {
			$lines[] = sprintf( 'Tax (%s%%): %s', $b->get_tax_rate(), $b->get_tax_total() );
		}
		$lines[] = sprintf( 'Total: %s %s', $b->get_total(), $b->get_currency() );
		return implode( "\n", $lines );
	}

	private function send( string $type, BookingTableModel $b ): void {
		$settings_key = "email_{$type}_subject";
		$subject = get_option( "thimpress_events_{$settings_key}", '' );
		$content = get_option( "thimpress_events_email_{$type}_content", '' );

		if ( empty( $subject ) || empty( $content ) ) return;

		$pricing  = $this->render_pricing_block( $b );
		$user     = get_userdata( $b->get_user_id() );
		$to_email = $user ? $user->user_email : '';

		if ( 'admin_new_booking' === $type ) {
			$to_email = get_option( 'admin_email' );
		}

		if ( ! $to_email ) return;

		$placeholders = [
			'{pricing_block}' => $pricing,
			'{total}'         => $b->get_total(),
			'{currency}'      => $b->get_currency(),
			'{subtotal}'      => $b->get_subtotal(),
			'{discount}'      => $b->get_discount_total(),
			'{tax}'           => $b->get_tax_total(),
			'{booking_id}'    => (string) $b->get_id(),
			'{event_id}'      => (string) $b->get_event_id(),
			'{event_title}'   => get_the_title( $b->get_event_id() ) ?: '',
		];

		$body = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
		$subj = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject );

		wp_mail( $to_email, $subj, $body );
	}
}
