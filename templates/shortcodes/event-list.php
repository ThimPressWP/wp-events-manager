<?php
/**
 * The Template for displaying shortcode list events.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/event-list.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


wp_enqueue_script( 'wpems-litepicker-lb' );
wp_enqueue_script( 'wpems-ranges-lb' );
wp_enqueue_script( 'wpems-event-list-js' );

use WPEMS\Templates as Template;

$filterTemplate      = new Template\WpemsFilterTemplate();
$singleEventTemplate = new Template\WpemsSingleEventTemplate();
$eventsTemplate      = new Template\WpemsEventsTemplate();
$pagination          = new Template\WpemsPaginationTemplate();

?>
<div class="eventListDisplay ">
	<!-- Search, Filter feature -->
	<div class='search-filter'>
		<form action="" method='GET' > 			
			<div class="search_status_type_category">
				
				<!-- Search input -->
				<div class="wrapper_input_search">
					<?php echo $filterTemplate->html_input_text( 'wpems_keyword', 'input_search', 'Enter Keywords', $args['filter_by_input_search'] ); ?>
				</div>

				<!-- Status -->
				<div class="wrapper_status">
					<select name='<?php echo esc_attr( 'wpems_status' ); ?>' class="status" id="status" >
						<option value="">Status</option>
						<option value="expired"  <?php echo selected( $args['filter_by_status'], 'expired' ); ?>>Expired</option>
						<option value="upcoming"  <?php echo selected( $args['filter_by_status'], 'upcoming' ); ?>>Upcoming</option>
						<option value="happening"  <?php echo selected( $args['filter_by_status'], 'happening' ); ?>>Happening</option>
					</select>
				</div>

				<!-- Type -->
				<div class="wrapper_type">
					<?php echo $filterTemplate->html_select( 'wpems_type', 'type', 'Type', $args['types'], $args['filter_by_type'] ); ?>
				</div>

				<!-- Category -->
				<div class="wrapper_type">				
					<?php echo $filterTemplate->html_select( 'wpems_category', 'category', 'Event Category', $args['categories'], $args['filter_by_category'] ); ?>			
				</div>
			</div>
	
			<div class="date_price_submit">
				<!-- Date Ranger -->		
				<div class="wrapper_date">
					<?php echo $filterTemplate->html_input_text( 'wpems_date', 'date', 'Select Date Ranger', $args['dateInput'] ); ?>
				</div>	
				
				<!-- Price Ranger -->
				<div class="wrapper_price">
					<div class="wrapper_min">
						<?php echo $filterTemplate->html_input_number( 'wpems_price_min', 'price_min', 'Min Price', $args['getPriceMin'] ); ?>
						<?php echo $filterTemplate->html_price_filter( 'priceOfMin', $args['numbers'], 'data-min-value' ); ?>
					</div>					
					<div class='lowTohigh'><span class="dashicons dashicons-minus"></span></div>				
					<div class="wrapper_max">
						<?php echo $filterTemplate->html_input_number( 'wpems_price_max', 'price_max', 'Max Price', $args['getPriceMax'] ); ?>
						<?php echo $filterTemplate->html_price_filter( 'priceOfMax', $args['numbers'], 'data-max-value' ); ?>				
					</div>					
				</div>

				<!-- Search button -->
				<button name="search_event_list" type="submit" id="event-search-btn">Search</button>				
			</div>
		</form>
	</div>

	<!-- Show result and release date -->
	<div class="showResult">
		<?php echo $filterTemplate->showResult( $args['posts'], $args['getPosts'] ); ?>
		
		<!-- Order by -->
		<div>		
			<select class="orderby" id='orderby'>
				<option value='' >Release Date (newest first)</option>
				<option value="a-z"  <?php echo selected( $args['order_by'], 'a-z' ); ?>>A - Z</option>
				<option value="z-a"  <?php echo selected( $args['order_by'], 'z-a' ); ?>>Z - A</option>
				<option value="high-low"  <?php echo selected( $args['order_by'], 'high-low' ); ?>>Price: High - Low</option>
				<option value="low-high"  <?php echo selected( $args['order_by'], 'low-high' ); ?>>Price: Low - High</option>
			</select>
		</div>
	</div>

	<div><?php echo $eventsTemplate->html_events_list( $args['posts'] ); ?></div>
	<div><?php echo $pagination->html_pagination( $args['getPosts'] ); ?></div>
</div>
