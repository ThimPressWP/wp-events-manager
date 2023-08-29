<?php
wp_enqueue_script( 'wpems-litepicker-lb' );
wp_enqueue_script( 'wpems-ranges-lb' );
wp_enqueue_script( 'wpems-event-list-display-js' );


if ( $args['query_posts'] ) {
	$get_posts = $args['query_posts'];
}
if ( $args['posts'] ) {
	$posts = $args['posts'];
}

if ( $args['types'] ) {
	$types = $args['types'];
}
if ( $args['categories'] ) {
	$categories = $args['categories'];
};
if ( $args['numbers'] ) {
	$numbers = $args['numbers'];
};
if ( $args['totalPost'] ) {
	$totalPost = $args['totalPost'];
};
if ( $args['pageIndex'] ) {
	$pageIndex = $args['pageIndex'];
};

if ( $args['current_item_start'] ) {
	$current_item_start = $args['current_item_start'];
};
if ( $args['current_item_end'] ) {
	$current_item_end = $args['current_item_end'];
};

?>

<div class="eventListDisplay ">
	<!-- Search, Filter feature -->
	<div class='search-filter'>
		<form action="" method='GET' > 			
			<div class="search_status_type_category">
				
				<!-- Search input -->
				<div class="wrapper_input_search">
					<?php echo WPEMS_Event_Template::input_text_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_CHAR, 'input_search', 'Enter Keywords' ); ?>
				</div>

				<!-- Status -->
				<div class="wrapper_status">
					<select name='<?php echo esc_attr( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_STATUS ); ?>' class="status" id="status">
						<option value="">Status</option>
						<option value="expired">Expired</option>
						<option value="upcoming">Upcoming</option>
						<option value="happening">Happening</option>
					</select>
				</div>

				<!-- Type -->
				<div class="wrapper_type">
					<?php echo WPEMS_Event_Template::select_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_TYPE, 'type', 'Type', $types ); ?>
				</div>

				<!-- Category -->
				<div class="wrapper_type">				
					<?php echo WPEMS_Event_Template::select_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_CATEGORY, 'category', 'Event Category', $categories ); ?>			
				</div>
			</div>
	
			<div class="date_price_submit">
				<!-- Date Ranger -->		
				<div class="wrapper_date">
					<?php echo WPEMS_Event_Template::input_text_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_DATE, 'date', 'Select Date Ranger' ); ?>
				</div>	
				
				<!-- Price Ranger -->
				<div class="wrapper_price">
					<div class="wrapper_min">
						<?php echo WPEMS_Event_Template::input_number_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MIN, 'price_min', 'Min Price' ); ?>
						<?php echo WPEMS_Event_Template::price_filter( 'priceOfMin', $numbers ); ?>
					</div>					
					<div class='lowTohigh'><span class="dashicons dashicons-minus"></span></div>				
					<div class="wrapper_max">
						<?php echo WPEMS_Event_Template::input_number_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MAX, 'price_max', 'Max Price' ); ?>
						<?php echo WPEMS_Event_Template::price_filter( 'priceOfMax', $numbers ); ?>				
					</div>					
				</div>

				<!-- Search button -->
				<button name="search_event_list" type="submit" id="event-search-btn">Search</button>				
			</div>
		</form>
	</div>

	<!-- Show result and relase date -->
	<div class="showResult">
		<div>
			<?php
			if ( ! isset( $posts ) || count( $posts ) === 0 ) {
				?>
						<p><?php echo esc_html__( 'Showing 0 results.' ); ?></p>
				<?php
			} else {
				?>
					<p><?php echo esc_html( 'Showing ' . $current_item_start . ' - ' . $current_item_end . ' of ' . $totalPost . ' results ' ); ?> </p> 
				<?php
			};
			?>
					
		</div>
		<!-- Order by -->
		<div>		
			<select name="<?php echo esc_attr( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_ORDER_BY ); ?>" class="orderby" id='orderby'>
				<option  >Release Date (newest first)</option>
				<option value="a-z">A - Z</option>
				<option value="z-a">Z - A</option>
				<option value="hight-low">Price: High - Low</option>
				<option value="low-high">Price: Low - High</option>
			</select>
		</div>
	</div>

	<!-- List events -->

	<?php
	if ( ! isset( $posts ) || count( $posts ) === 0 ) {
		echo 'There are no events.';
	} else {
		foreach ( $posts as $key => $value ) {
			?>
				<div class="listEvent">
					<!-- Left -->
					<div class="left" >
						<div class="date-title">
							<div class="date_month">
								<h3><?php echo esc_html( date( 'd', strtotime( $value->date_start ) ) ); ?></h3>
								<div><?php echo esc_html( date_i18n( 'M', strtotime( $value->date_start ) ) ); ?></div>
							</div>
							<div class="title">
								<h4><?php echo esc_html__( $value->post_title ); ?></h4>
								<div class="time">
									<span class="dashicons dashicons-clock"></span>
									<span><?php echo esc_html( date( 'g:i A', strtotime( $value->time_start ) ) ) . ' - ' . esc_html( date( 'g:i A', strtotime( $value->time_end ) ) ); ?> 
								</div>
							</div>
						</div>
						<!-- Content -->
						<div><?php echo esc_html__( $value->post_excerpt ); ?></div>
					</div>

					<!-- Right -->
					<div class="right">
						<div class="rightImage">
							<img src=" <?php echo esc_html( get_the_post_thumbnail_url( $value->ID, 'full' ) ); ?> " alt="Feature image">
						</div>
						<div class="totalTime">
							<div class="time-remaining">
								<?php wpems_get_template( 'shortcodes/event-countdown.php', [ 'event_id' => $value->ID ] ); ?>
							</div>
						</div>
					</div>
					
				</div>
			<?php
		}
	}

	?>

	<!-- Pagination -->
	<div class="event-pagination">
					   <div  class="pagination" >
					<?php
					echo paginate_links(
						array(
							'total'   => $get_posts->max_num_pages,
							'current' => $pageIndex,
						)
					);
					?>
			</div> 
			<?php
;
			?>
	</div>
</div>

