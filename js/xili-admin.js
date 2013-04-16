jQuery(document).ready( function() {
 
	/** Check that pointer support exists AND that text is not empty - inspired www.generalthreat.com */
	
	if(typeof(jQuery().pointer) != 'undefined' && strings[a].pointerText != '') {
		jQuery( strings.a.pointerDiv ).pointer({
			content    : strings.a.pointerText,
			position : {
                        at: 'left top',
                        my: 'left top',
                        offset: strings.a.pointerOffset
                    },
			close  : function() {
				jQuery.post( ajaxurl, {
					pointer: strings.a.pointerDismiss,
					action: 'dismiss-wp-pointer'
				});
			}
		}).pointer('open');
	}
});