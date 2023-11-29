<?php

/**
 * Class EventMetaConstants
 * const all meta_key in database: wp_postmeta
 *
 */

 namespace WPEMS\Models\Event\Meta;

 use WPEMS\Models\Event\Meta\EventMetaModel;

class EventMetaConstants extends EventMetaModel {
    const EDIT_LAST                         = '_edit_last';
	const EDIT_LOCK                         = '_edit_lock';
    const TP_EVENT_QTY                      = 'tp_event_qty';
    const TP_EVENT_PRICE                    = 'tp_event_price';
    const TP_EVENT_DATE_START               = 'tp_event_date_start';
    const TP_EVENT_TIME_START               = 'tp_event_time_start';
    const TP_EVENT_DATE_END                 = 'tp_event_date_end';
    const TP_EVENT_TIME_END                 = 'tp_event_time_end';
    const TP_EVENT_REGISTRATION_END_DATE    = 'tp_event_registration_end_date';
    const TP_EVENT_REGISTRATION_END_TIME    = 'tp_event_registration_end_time';
    const TP_EVENT_LOCATION                 = 'tp_event_location';
    const TP_EVENT_IFRAME                   = 'tp_event_iframe';
    const TP_EVENT_STATUS                   = 'tp_event_status';
}
