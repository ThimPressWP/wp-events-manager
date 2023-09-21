<?php
namespace WPEMS\Template;

use WPEMS\Model\WpemsAbstractEventModel;

abstract class WpemsAbstractEventTemplate {
	protected $model;

	public function __construct( WpemsAbstractEventModel $model ) {
		$this->model = $model;
	}

	abstract public function displayEventTitle( $event_id );
	abstract public function displayEventThumbnail( $event_id );
	abstract public function displayEventContent( $event_id );
	abstract public function displayEventInformation( $event_id );
	abstract public function displayEventSchedule( $event_id );

}
