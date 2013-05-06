jQuery(document).ready(function($) {
	$('#update-nav-menu').bind('click', function(e) {
		
		if ( e.target && e.target.className && -1 != e.target.className.indexOf('item-edit')) {
			var inputclass = '';
			var inputvalue = '';
			//var inputvalue2 = ''; 
			$("input[value='"+xili_data.strings[0]+"'][type=text]").parent().parent().parent().each( function(){
				$(this).children('.description-thin,.field-url,.field-link-target,.field-description').each( function(){
					
					$(this).children('label').each( function(){
						//$(this).attr("style", "border:yellow solid 1px") ;
						if ( !$(this).parent().hasClass("field-link-target") ) { $(this).css("visibility", "hidden") } ;  // attr disabled pb avec remove...
						$(this).children('input').each( function(){
							if ( $(this).hasClass("edit-menu-item-attr-title") ) {
								inputvalue2 = $(this).attr("value") ;
							}
						});
					});
					
					//$(this).attr("style", "border:red solid 1px") ;
				});
				
				
				p = $('<p>').attr('class', 'description topo').html( '<strong>' + xili_data.strings[1] + '</strong><br />' + inputvalue2 + '<br />' + xili_data.strings[2] );
				
				if ( !$(this).children('p').hasClass('topo') )
				 { $(this).prepend(p); }
				
			});
		}
	});
});
