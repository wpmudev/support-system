(function ($, window, document, undefined) {
	'use strict';

	window.Support_System = {
		name: 'Support System',
		version: '2.0',

		libs: {},

		init: function(scope, libraries) {
			this.scope = scope || this.scope;

			for ( var lib in this.libs ) {
				this.init_lib( lib, libraries );
			}

			return scope;

		},
		init_lib: function ( lib, args ) {

			if ( this.libs.hasOwnProperty( lib ) ) {

				if ( args && args.hasOwnProperty( lib ) ) {
					if ( typeof this.libs[lib].settings !== 'undefined' ) {
						$.extend( true, this.libs[lib].settings, args[lib] );
					}
					else if ( typeof this.libs[lib].defaults !== 'undefined' ) {
						$.extend( true, this.libs[lib].defaults, args[lib] );
					}

					return this.libs[lib].init.apply( this.libs[lib], [this.scope, args[lib]] );
				}

				args = args instanceof Array ? args : new Array(args);
				return this.libs[lib].init.apply( this.libs[lib], args );
			}

			return function () {};
		}
	};

	$.fn.support_system = function () {

		var args = Array.prototype.slice.call(arguments, 0);

		return this.each(function () {
	    	Support_System.init.apply( Support_System, [this].concat( args ) );
	    	return this;
	    });
	};

}(jQuery, window, window.document));

;(function ($, window, document, undefined) {
	Support_System.libs.filter = {
		name: 'Support System Filter',
		version: '2.0',
		init: function() {
			var filter_button = $( 'input[name="support-system-submit-filter"]' );
			var categories_dropdown = $( '.cat-id' );
			var filter_form = $( '#support-system-filter' );

			if ( ! filter_form.length || ! categories_dropdown.length || ! filter_button.length )
				return function() {};
			
			categories_dropdown.change( function(e) {
				e.preventDefault();
				$this = $(this);
				$this.closest( 'form' ).submit();
			});

			filter_button.hide();
		}
	};
}(jQuery, window, window.document));

;(function ($, window, document, undefined) {
	Support_System.libs.attachments = {
		name: 'Support System Attachments',
		version: '2.0',
		defaults: {
			container_selector: '',
			button_text: 'Add files...',
			button_class: '',
			first_slot: 0,
			slot_name: 'support-attachment',
			current_files: [],
			files_list_id: 'support-attachments-list',
			remove_file_title: 'Remove file',
			remove_link_class: '',
			remove_link_text: '[x]',
			description: ''
		},

		init : function (scope, options) {
			var self = this;
	    	this.settings = this.settings || $.extend({}, this.defaults, options );

	    	var settings = this.settings;
	    	var container = $( this.settings.container_selector );

      		if ( ! container.length )
      			return false;

      		var current_slot = settings.first_slot;		

			return container.each( function() {
				var button = $( '<button/>', {
					text: settings.button_text,
					class: settings.button_class
				});

				button.data( 'settings', settings );
				button.data( 'current_slot', current_slot );

				button.click( function( e ) {
					e.preventDefault();
					self.addFileSlot( $(this), self );
				});

				var list = $( '<ul/>', {
					id: 'support-attachments-list'
				});

				button.data( 'list', $(list) );

				$(this)
					.append( list )
					.append( button )
					.append( settings.description );
					
			});
	      	
	    },

	    addFileSlot: function( clicked_button, self ) {
	    	var $this = $( clicked_button );

			var settings = $this.data( 'settings' );
			var current_slot = $this.data( 'current_slot' );
			var list = $this.data( 'list' );

			var file_element = $( '<input/>', {
				type: 'file',
				id: settings.slot_name + '-' + current_slot,
				name: settings.slot_name + '[]'
			});

			var remove_file_link = $( '<a>', {
				class: 'remove-file ' + settings.remove_link_class,
				'data-remove-file': settings.slot_name + '-' + current_slot,
				text: settings.remove_link_text,
				href: '',
				title: settings.remove_file_title
			});

			remove_file_link.click( function( e ) {
				e.preventDefault();
				self.removeFileSlot( $(this).data('remove-file'), $this );
			});

			list.append( 
				$('<li>' )
					.hide()
					.append( file_element )
					.append( remove_file_link )
					.fadeIn()
			);

			$this.data( 'current_slot', ++current_slot );
	    },

	    removeFileSlot: function( file_id, button ) {
			var file_slot = $( '#' + file_id );
			if ( file_slot ) {
				file_slot.parent().remove();
			}
		}
	};

}(jQuery, window, window.document));

;(function ($, window, document, undefined) {
	Support_System.libs.faqs = {
		name: 'Support System FAQS',
		version: '2.0',
		defaults: {
			spinner_class: 'support-system-spinner'		
		},

		init: function(options) {
			var self = this;
	    	this.settings = this.settings || $.extend({}, this.defaults, options );

	    	var settings = this.settings;

			$( '.vote-button' ).click( function(e) {
				e.preventDefault();
				var vote = $(this).data('vote');
				var faq_id = $(this).data('faq-id');
				var parent = $(this).parent();
				parent.find('button').attr( 'disabled', 'true' );

				var loader = parent.find('img');
				loader.show();

				var spinner = $(this).siblings('.' + settings.spinner_class);
				spinner.css('display','inline-block');

				var data = {
					vote: vote,
					faq_id: faq_id,
					action: 'vote_faq_question'
				};

				$.post( support_system_strings.ajaxurl, data, function(response) {
					loader.hide();
					spinner.hide();
				});
			});

			$('.faq-category-wrap' ).hide();

			$( '.faq-category-question' ).hide();
			

			$( '.faq-category' ).click( function( e ) {
				e.preventDefault();

                $( '.faq-category').attr( 'disabled', false );
                $(this).attr( 'disabled', true );
				var cat_id = $(this).data('cat-id');

				$( '.faq-category-wrap' ).hide();
				$( '#faq-category-' + cat_id ).fadeIn();
			});

			$('.faq-category-answer').hide();
			$('.faq-category-wrap .postbox .hndle').click( function(e) {
				$('.faq-category-wrap .postbox').addClass('closed');
				$(this).parent().animate().toggleClass( 'closed' );

				var faq_id = $(this).data('faq-id');
				$('.faq-category-answer').slideUp();

				$( '#faq-answer-' + faq_id ).slideDown();

			});


            $( '.faq-category').first().trigger( 'click' );

		}
	};
}(jQuery, window, window.document));

