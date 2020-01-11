var $ice_dragon_paywall_settings = jQuery.noConflict();

$ice_dragon_paywall_settings(document).ready(function($) {
	
	$( '#lpaywall_default_restriction_options' ).on( 'click', 'input#add-restriction-row', function( event ) {
		event.preventDefault();
        var data = {
            'action': 'issuem-leaky-paywall-add-new-restriction-row',
            'row-key': ++lpaywall_restriction_row_key,
        }
        $.post( ajaxurl, data, function( response ) {
            $( 'td#issuem-leaky-paywall-restriction-rows table' ).append( response );
        });
	});
		
	$( '#lpaywall_default_restriction_options' ).on( 'click', '.delete-restriction-row', function ( event ) {
		event.preventDefault();
		var parent = $( this ).parents( '.issuem-leaky-paywall-restriction-row' );
		parent.slideUp( 'normal', function() { $( this ).remove(); } );
	});

	$( '#issuem-leaky-paywall-restriction-rows').on( 'change', '.leaky-paywall-restriction-post-type', function() {
		// replace taxonomy select with loader
		console.log('here');
		var post_type = $(this).children("option:selected").val();
		var taxCell = $(this).parent().next();
		taxCell.append('<div class="spinner" style="visibility: visible; float: left;"></div>');
		taxCell.find('select').remove();

		// ajax call to find taxonomies and terms
        var data = {
            'action': 'leaky-paywall-get-restriction-row-post-type-taxonomies',
            'post_type': post_type
        }

		$.post( ajaxurl, data, function( response ) {
          	// build out the new select box
			taxCell.append( response );
			taxCell.find('.spinner').remove();
        });

	});

	$('#enable_combined_restrictions').click(function() {
		if ( $(this).is(':checked' ) ) {
			$('.restriction-allowed-number-setting').css('display', 'none');
			$('.allowed-number-helper-text').css('display', 'block');
			$('.combined-restrictions-total-allowed').removeClass('hide-setting');
		} else {
			$('.restriction-allowed-number-setting').css('display', 'block');
			$('.allowed-number-helper-text').css('display', 'none');
			$('.combined-restrictions-total-allowed').addClass('hide-setting');
		}
		
	});

});