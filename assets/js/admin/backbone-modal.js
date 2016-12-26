/* global Backbone */

( function ( $, Backbone, _ ) {

    $.fn.Event_Modal = function () {

    };

    var Event_Backbone_Modal = window.Event_Backbone_Modal = {};

    Event_Backbone_Modal.Collection = Backbone.Collection.extend( {
        model: Event_Backbone_Modal.Model,
        initialize: function () {

        }
    } );

    Event_Backbone_Modal.Model = Backbone.Model.extend( {
        defaults: {
            id: null,
            title: null,
            start: null,
            end: null
        }
    } );
    
    Event_Backbone_Modal.Model = Backbone.Model.extend( {
        defaults: {
            id: null,
            title: null,
            start: null,
            end: null
        }
    } );

    $( document ).ready( function () {
        var collection = new Event_Backbone_Modal.Collection(),
                _timings = $( '.event-timing-period' ),
                _timing_length = _timings.length;

        if ( _timing_length > 0 ) {
            var _models = [ ];
            for ( var i = 0; i < _timing_length; i++ ) {
                var timing = $( _timings.length[i] ),
                        id = i,
                        title = timing.find( '.timing-title' ).text(),
                        start = timing.attr( 'data-start' ),
                        end = timing.attr( 'data-end' );

                var model = new Event_Backbone_Modal.Model( {
                    id: id,
                    title: title,
                    start: start,
                    end: end
                } );
                _models.push( model );
            }
//            console.debug( _models );
            collection.add( _models );
        }
    } );

} )( jQuery, Backbone, _ );
