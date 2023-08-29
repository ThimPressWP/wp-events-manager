<?php
namespace  Wpems_Model_Event;

class WPEMS_Model_Event_List {
	public static $_GOOGLE_CLIENTID    = 'wpems_google_clientID';
	public static $_GOOGLE_APIKEY      = 'wpems_google_apiKey';
	public static $_FILTER_SEARCH_CHAR = 'wpems_keyword';
	public static $_FILTER_STATUS      = 'wpems_status';
	public static $_FILTER_TYPE        = 'wpems_type';
	public static $_FILTER_CATEGORY    = 'wpems_category';
	public static $_FILTER_SEARCH_DATE = 'wpems_date';
	public static $_FILTER_PRICE_MIN   = 'wpems_price_min';
	public static $_FILTER_PRICE_MAX   = 'wpems_price_max';
	public static $_FILTER_ORDER_BY    = 'wpems_orderby';

	public static function get_postMeta( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$value->date_start  = get_post_meta( $value->ID, 'tp_event_date_start', true );
				$value->date_end    = get_post_meta( $value->ID, 'tp_event_date_end', true );
				$value->time_start  = get_post_meta( $value->ID, 'tp_event_time_start', true );
				$value->time_end    = get_post_meta( $value->ID, 'tp_event_time_end', true );
				$value->price       = get_post_meta( $value->ID, 'tp_event_price', true );
				$value->totalTicket = get_post_meta( $value->ID, 'tp_event_qty', true );
				$value->location    = get_post_meta( $value->ID, 'tp_event_location', true );
			}
		}
		return $array;
	}

	// To get filter data to display to the screen
	public static function get_filter( $taxonomy ) {
		$filter_data = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		return $filter_data;
	}
}
new WPEMS_Model_Event_List();
