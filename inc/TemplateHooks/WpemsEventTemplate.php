<?php
namespace WPEMS\Template;

use WPEMS\Model\WpemsAbstractEventModel;

class WpemsEventTemplate extends WpemsAbstractEventModel {
	public function __construct(WpemsEventModel $model) {
        parent::__construct($model);
    }

    public function displayEventTitle($event_id) {
        $title = $this->model->getEventTitle($event_id);

        $html = '<div class="entry-title">';
        if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
            $html .= '<h4><a href="' . get_permalink( $event_id ) . '">';
        } else {
            $html .= '<h3>';
        }

        $html .= $title;

        if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
            $html .= '</a></h4>';
        } else {
            $html .= '</h1>';
        }

        $html .= '</div>';

        return $html;
    }

    public function displayEventThumbnail($event_id) {
        $thumbnail = $this->model->getEventThumbnail($event_id);

        $html = '';
        if ( has_post_thumbnail( $event_id ) ) {
            $html .= '<div class="entry-thumbnail">';

            if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
                $html .= '<a href="' . get_permalink( $event_id ) . '">';
            }

            $html .= $thumbnail;

            if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
                $html .= '</a>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    public function displayEventContent($event_id) {
        $content = $this->model->getEventContent($event_id);

        $html = '<div class="entry-content">';
        $html .= $content;
        $html .= '</div>';

        return $html;
    }

    public function displayEventInformation( $event_id ) {
        $start_time        = $this->model->getEventStartTime( $event_id );
        $start_date        = $this->model->getEventStartDate( $event_id );
        $end_time          = $this->model->getEventEndTime( $event_id );
        $end_date          = $this->model->getEventEndDate( $event_id );
        $register_end_time = $this->model->getEventRegisterEndTime( $event_id );
        $register_end_date = $this->model->getEventRegisterEndDate( $event_id );
        $location_f        = $this->model->getEventDatabaseLoacationF( $event_id );

        $html = <<<HTML
        <div class="entry-information">
            <table>
                <tr>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-clock"></span>
                            <h6>Start Time</h6>
                        </div>
                        <p class="content">$start_time - $start_date</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-flag"></span>
                            <h6>End Time</h6>
                        </div>
                        <p class="content">$end_time - $end_date</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-location"></span>
                            <h6>Location</h6>
                        </div>
                        <p class="content">$location_f</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-hourglass"></span>
                            <h6>Registration End Date</h6>
                        </div>
                        <p class="content">$register_end_time - $register_end_date</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-category"></span>
                            <h6>Category</h6>
                        </div>
                        <p class="content">content</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-editor-ul"></span>
                            <h6>Type</h6>
                        </div>
                        <p class="content">content</p>
                    </td>
                </tr>
            </table>
        </div>
        HTML;
        return $html;
    }

    public function displayEventSchedules($event_id) {
        $schedules = $this->model->getEventSchedules($event_id);

        $html = '<div class="entry-schedule">';
        $html .= '<h6 class="schedule_header">Schedule</h6>';

        foreach ( $schedules as $key => $value ) {
            $html .= '<div class="schedule_body" id="' . $key . '">';
            $html .= '<div class="schedule_body-header">';
            $html .= '<p class="schedule_title">';
            $html .= $value['title'];
            $html .= '</p>';
            $html .= '<div class="schedule_button">';
            $html .= '<span class="dashicons-before dashicons-minus"></span>';
            $html .= '<span class="dashicons-before dashicons-plus"></span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="schedule_body-content">';
            $html .= '<p>' . $value['description'] . '</p>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

}
