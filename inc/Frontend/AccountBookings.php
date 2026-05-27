<?php
namespace WPEMS\Frontend;

use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;

defined( 'ABSPATH' ) || exit;

class AccountBookings {

	private BookingRepository $bookings;

	public function __construct( BookingRepository $bookings ) {
		$this->bookings = $bookings;
	}

	public function register(): void {
		add_shortcode( 'wpems_my_bookings', [ $this, 'shortcode_my_bookings' ] );
	}

	public function shortcode_my_bookings( array $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your bookings.', 'wp-events-manager' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$paged   = max( 1, (int) ( get_query_var( 'paged' ) ?: 1 ) );

		[ $items, $total ] = $this->fetch_for_user( $user_id, $paged );

		ob_start();
		?>
		<div class="wpems-account-bookings">
			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'You have no bookings.', 'wp-events-manager' ); ?></p>
			<?php else : ?>
				<table class="wpems-bookings-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'wp-events-manager' ); ?></th>
							<th><?php esc_html_e( 'Event', 'wp-events-manager' ); ?></th>
							<th><?php esc_html_e( 'Qty', 'wp-events-manager' ); ?></th>
							<th><?php esc_html_e( 'Total', 'wp-events-manager' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wp-events-manager' ); ?></th>
							<th><?php esc_html_e( 'Date', 'wp-events-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $items as $b ) : ?>
						<tr>
							<td>#<?php echo esc_html( (string) $b->get_id() ); ?></td>
							<td><?php echo esc_html( get_the_title( $b->get_event_id() ) ?: '—' ); ?></td>
							<td><?php echo esc_html( (string) $b->get_qty() ); ?></td>
							<td><?php echo esc_html( $b->get_total() . ' ' . $b->get_currency() ); ?></td>
							<td><?php echo esc_html( $b->get_status() ); ?></td>
							<td><?php echo esc_html( $b->get_created_at_gmt() ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$pages = (int) ceil( $total / 10 );
				if ( $pages > 1 ) {
					echo '<div class="wpems-pagination">';
					for ( $i = 1; $i <= $pages; $i++ ) {
						$url = add_query_arg( 'paged', $i, get_permalink() );
						echo '<a href="' . esc_url( $url ) . '" class="' . ( $i === $paged ? 'current' : '' ) . '">' . esc_html( (string) $i ) . '</a> ';
					}
					echo '</div>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function fetch_for_user( int $user_id, int $paged ): array {
		$q           = new BookingQuery();
		$q->user_id  = $user_id;
		$q->limit    = 10;
		$q->offset   = ( $paged - 1 ) * 10;
		$q->order_by = 'created_at_gmt';
		$q->order    = 'DESC';

		return [
			$this->bookings->query( $q ),
			$this->bookings->count( $q ),
		];
	}
}
