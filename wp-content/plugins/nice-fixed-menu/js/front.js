
	function refresh_position()
	{
		var height = window.innerHeight;
			jQuery('#nice-fixed-menu').css('line-height', height+'px');
	}

	jQuery(document).ready(function(){

		if(jQuery('#nice-fixed-menu').hasClass('right') || jQuery('#nice-fixed-menu').hasClass('left'))
		{
			refresh_position();
		}

		jQuery(window).resize(function(){

			refresh_position();

		});

	});