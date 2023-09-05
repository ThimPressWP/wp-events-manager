<?php
wp_enqueue_script( 'wpems-litepicker-lb' );
wp_enqueue_script( 'wpems-ranges-lb' );
wp_enqueue_script( 'wpems-event-list-js' );

use WPEMS\Event_Template as Template;
?>

<div class="eventListDisplay ">
	<!-- Search, Filter feature -->
	<div class='search-filter'>
		<form action="" method='GET' > 			
			<div class="search_status_type_category">
				
				<!-- Search input -->
				<div class="wrapper_input_search">
					<?php echo Template\WPEMS_Event_Template::input_text( 'wpems_keyword', 'input_search', 'Enter Keywords', $args['filter_by_input_search'] ); ?>
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
					<?php echo Template\WPEMS_Event_Template::select( 'wpems_type', 'type', 'Type', $args['types'], $args['filter_by_type'] ); ?>
				</div>

				<!-- Category -->
				<div class="wrapper_type">				
					<?php echo Template\WPEMS_Event_Template::select( 'wpems_category', 'category', 'Event Category', $args['categories'], $args['filter_by_category'] ); ?>			
				</div>
			</div>
	
			<div class="date_price_submit">
				<!-- Date Ranger -->		
				<div class="wrapper_date">
					<?php echo Template\WPEMS_Event_Template::input_text( 'wpems_date', 'date', 'Select Date Ranger', $args['dateInput'] ); ?>
				</div>	
				
				<!-- Price Ranger -->
				<div class="wrapper_price">
					<div class="wrapper_min">
						<?php echo Template\WPEMS_Event_Template::input_number( 'wpems_price_min', 'price_min', 'Min Price', $args['getPriceMin'] ); ?>
						<?php echo Template\WPEMS_Event_Template::price_filter( 'priceOfMin', $args['numbers'], 'data-min-value' ); ?>
					</div>					
					<div class='lowTohigh'><span class="dashicons dashicons-minus"></span></div>				
					<div class="wrapper_max">
						<?php echo Template\WPEMS_Event_Template::input_number( 'wpems_price_max', 'price_max', 'Max Price', $args['getPriceMax'] ); ?>
						<?php echo Template\WPEMS_Event_Template::price_filter( 'priceOfMax', $args['numbers'], 'data-max-value' ); ?>				
					</div>					
				</div>

				<!-- Search button -->
				<button name="search_event_list" type="submit" id="event-search-btn">Search</button>				
			</div>
		</form>
	</div>

	<!-- Show result and release date -->
	<div class="showResult">
		<div>
			<?php
			if ( ! isset( $args['posts'] ) || count( $args['posts'] ) === 0 ) {
				?>
					<p><?php echo esc_html__( 'Showing 0 results.' ); ?></p>
				<?php
			} else {
				?>
					<p><?php echo esc_html( 'Showing ' . $args['current_item_start'] . ' - ' . $args['current_item_end'] . ' of ' . $args['totalPost'] . ' results ' ); ?> </p> 
				<?php
			};
			?>
					
		</div>
		
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

	<!-- Event content list-->
	<?php
	if ( ! isset( $args['posts'] ) || count( $args['posts'] ) === 0 ) {
		echo 'There are no events.';
	} else {
		foreach ( $args['posts'] as $key => $value ) {
			?>
				<div class="listEvent">
					<!-- Left -->
					<div class="left" >
						<div class="date-title">
							<div class="date_month">
							<?php echo Template\WPEMS_Event_Template::date( $value->date_start, 'date_detail' ); ?>
							<?php echo Template\WPEMS_Event_Template::month( $value->date_start, 'month_detail' ); ?>
							</div>
							<div class="title">							
								<?php echo Template\WPEMS_Event_Template::content( $value->post_title, 'title_detail' ); ?>								
								<div class="time">
									<span class="dashicons dashicons-clock"></span>
									<?php echo Template\WPEMS_Event_Template::time_start_end( $value->time_start, $value->time_end, '' ); ?>
								</div>
							</div>
						</div>
						<!-- Content -->
						<?php echo Template\WPEMS_Event_Template::content( $value->post_excerpt, 'content-detail' ); ?>
					</div>

					<!-- Right -->
					<div class="right">
						<div class="rightImage">
							<?php echo Template\WPEMS_Event_Template::img( $value->ID, '', 'Feature image' ); ?>
						</div>
						<div class="totalTime">
							<?php echo Template\WPEMS_Event_Template::get_template_file( 'time-remaining', 'shortcodes/event-countdown.php', [ 'event_id' => $value->ID ] ); ?>		
						</div>
					</div>		
				</div>
			<?php
		}
	}
	?>

	<!-- Pagination -->
	<div class="event-pagination">
		<?php echo Template\WPEMS_Event_Template::pagination( 'pagination', $args['max_num_pages'], $args['pageIndex'] ); ?>
	</div>
</div>
