<?php
namespace WPEMS\Template;

use WPEMS\Model\WpemsEventModel;

abstract class WpemsAbstractEventTemplate {
	protected $model;

	public function __construct( WpemsEventModel $model ) {
		$this->model = $model;
	}

	abstract public function displayEventTitle( $event_id );
	abstract public function displayEventThumbnail( $event_id );
	abstract public function displayEventContent( $event_id );
	abstract public function displayEventInformation( $event_id );
	abstract public function displayEventCountdown( $event_id );
	abstract public function displayEventIframe( $event_id );
	abstract public function displayEventSchedules( $event_id );
}
