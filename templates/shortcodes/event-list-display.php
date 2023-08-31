<?php
wp_enqueue_script( 'wpems-litepicker-lb' );
wp_enqueue_script( 'wpems-ranges-lb' );
wp_enqueue_script( 'wpems-event-list-display-js' );

$get_posts = isset($args['query_posts']) ?  $args['query_posts'] : '';
$posts = isset($args['posts']) ?  $args['posts'] : '';
$types = isset($args['types']) ?  $args['types'] : '';
$categories = isset($args['categories']) ?  $args['categories']: '';
$numbers = isset($args['numbers']) ?   $args['numbers']: '';
$totalPost = isset($args['totalPost']) ?  $args['totalPost'] : '';
$pageIndex = isset($args['pageIndex']) ?  $args['pageIndex']: '';
$current_item_start = isset($args['current_item_start']) ? $args['current_item_start'] : '';
$current_item_end = isset($args['current_item_end']) ? $args['current_item_end'] : '';
$filter_by_input_search = isset($args['filter_by_input_search']) ? $args['filter_by_input_search'] : '';
$selected_status = isset($args['filter_by_status']) ? $args['filter_by_status'] : '';
$filter_by_type = isset($args['filter_by_type']) ? $args['filter_by_type'] : '';
$filter_by_category = isset($args['filter_by_category']) ? $args['filter_by_category'] : '';
$dateInput = isset($args['dateInput']) ? $args['dateInput'] : '';
$getPriceMin = isset($args['getPriceMin']) ? $args['getPriceMin'] : '';
$getPriceMax = isset($args['getPriceMax']) ? $args['getPriceMax'] : '';
$order_by = isset($args['order_by']) ? $args['order_by'] : '';


?>

<div class="eventListDisplay ">
	<!-- Search, Filter feature -->
	<div class='search-filter'>
		<form action="" method='GET' > 			
			<div class="search_status_type_category">
				
				<!-- Search input -->
				<div class="wrapper_input_search">
					<?php echo WPEMS_Event_Template::input_text_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_CHAR, 'input_search', 'Enter Keywords', $filter_by_input_search ); ?>
				</div>

				<!-- Status -->
				<div class="wrapper_status">
					<select name='<?php echo esc_attr( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_STATUS ); ?>' class="status" id="status" >
						<option value="">Status</option>
						<option value="expired"  <?php echo !empty($selected_status) && $selected_status === 'expired' ? 'selected' :  "" ;?>>Expired</option>
						<option value="upcoming" <?php echo !empty($selected_status) && $selected_status === 'upcoming' ? 'selected' :  "" ;?>>Upcoming</option>
						<option value="happening" <?php echo !empty($selected_status) && $selected_status === 'happening' ? 'selected' :  "" ;?>>Happening</option>
					</select>
				</div>

				<!-- Type -->
				<div class="wrapper_type">
					<?php echo WPEMS_Event_Template::select_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_TYPE, 'type', 'Type', $types, $filter_by_type ); ?>
				</div>

				<!-- Category -->
				<div class="wrapper_type">				
					<?php echo WPEMS_Event_Template::select_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_CATEGORY, 'category', 'Event Category', $categories , $filter_by_category); ?>			
				</div>
			</div>
	
			<div class="date_price_submit">
				<!-- Date Ranger -->		
				<div class="wrapper_date">
					<?php echo WPEMS_Event_Template::input_text_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_DATE, 'date', 'Select Date Ranger' , $dateInput ); ?>
				</div>	
				
				<!-- Price Ranger -->
				<div class="wrapper_price">
					<div class="wrapper_min">
						<?php echo WPEMS_Event_Template::input_number_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MIN, 'price_min', 'Min Price', $getPriceMin ); ?>
						<?php echo WPEMS_Event_Template::price_filter( 'priceOfMin', $numbers ); ?>
					</div>					
					<div class='lowTohigh'><span class="dashicons dashicons-minus"></span></div>				
					<div class="wrapper_max">
						<?php echo WPEMS_Event_Template::input_number_template( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MAX, 'price_max', 'Max Price', $getPriceMax ); ?>
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
				<option <?php echo !empty($order_by) && $order_by === 'a-z' ? 'selected' :  "" ;?> value="a-z">A - Z</option>
				<option <?php echo !empty($order_by) && $order_by === 'z-a' ? 'selected' :  "" ;?> value="z-a">Z - A</option>
				<option <?php echo !empty($order_by) && $order_by === 'high-low' ? 'selected' :  "" ;?> value="high-low">Price: High - Low</option>
				<option <?php echo !empty($order_by) && $order_by === 'low-high' ? 'selected' :  "" ;?> value="low-high">Price: Low - High</option>
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

