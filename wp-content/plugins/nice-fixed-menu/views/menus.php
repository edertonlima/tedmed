<script>



	jQuery(document).ready(function(){



		//color picker

		jQuery('.nfm-menu input[name=text_color], .nfm-menu input[name=bg_color]').wpColorPicker();



		//choix d'une image

	    jQuery('.nfm_browse').click(function(e) {

	    	var _this = this;

	        e.preventDefault();

	        var image = wp.media({ 

	            title: 'Upload Image',

	            // mutiple: true if you want to upload multiple files at once

	            multiple: false

	        }).open()

	        .on('select', function(e){

	            // This will return the selected image from the Media Uploader, the result is an object

	            var uploaded_image = image.state().get('selection').first();

	            // We convert uploaded_image to a JSON object to make accessing it easier

	            // Output to the console uploaded_image

	            var image_url = uploaded_image.toJSON().url;

	            // Let's assign the url value to the input field

	            jQuery(_this).parent().find('input[name="icon"]').val(image_url);

	        });

	    });



	    //changement d'ordre des menus

		jQuery('#nfm_menus').sortable({

			update: function( event, ui ) {

				//effectuer le changement de position en BDD par Ajax

				jQuery.post(ajaxurl, {action: 'nfm_order_menu', id: jQuery(ui.item).attr('rel'), order: (ui.item.index()+1), _ajax_nonce: '<?php echo wp_create_nonce( "nfm_order_menu" ); ?>' });

			}

		});



		//click suppression

	    jQuery('.nfm-menu img.remove').click(function(){

	    	var _this = this;

	    	jQuery.post(ajaxurl, {action: 'nfm_remove_menu', id: jQuery(_this).attr('rel'), _ajax_nonce: '<?php echo wp_create_nonce( "nfm_remove_menu" ); ?>'}, function(){

	    		jQuery(_this).parent('form').remove();

	    	});

	    });



	    jQuery('.nfm-menu input[name="icon"]').keyup(function(){


	    	if(jQuery(this).val() != '')
	    	{

		    	var _this = this;

			    //autocomplète ajax pour la choix de l'icone

				jQuery.post(ajaxurl, {action: 'nfm_fa_icons_list', q: jQuery(this).val(), _ajax_nonce: '<?php echo wp_create_nonce( "nfm_fa_icons_list" ); ?>' }, function(icons){



					jQuery(_this).parent().append('<div class="icons_list_search"></div>');

					jQuery(_this).parent().find('div.icons_list_search').html(icons);

					jQuery(_this).parent().find('div.icons_list_search li').click(function(){

						var icon = jQuery(this).attr('rel');

						jQuery(_this).val(icon);

						jQuery(_this).parent().find('div.icons_list_search').remove();

					});

				});

			}
			else
			{
				jQuery(this).parent().find('div.icons_list_search').remove();
			}


		});



	});



</script>



<h2>Nice fixed menu</h2>



<form class="nfm-menu" method="post">

	<?php wp_nonce_field( 'new_nfm' ) ?>

	<label>Name: </label><input type="text" name="name" /><br />

	<div class="icon_line">

		<label>Icon: </label><input type="text" name="icon" autocomplete="off" /> 

		<button class="nfm_browse">Browse...</button><br />

	</div>

	<label>Link: </label><input type="text" name="link" /> 

	<input type="checkbox" name="blank" value="1" /> Blank ?<br />

	<label>Text color: </label><input type="text" name="text_color" /><br />

	<label>Background color: </label><input type="text" name="bg_color" /><br />

	<input type="submit" value="Add menu" />

</form>



<div id="nfm_menus">



	<?php



		foreach($menus as $menu)

		{

			echo '<form class="nfm-menu" method="post" rel="'.$menu->id.'">';

			wp_nonce_field( 'update_nfm_'.$menu->id );

			echo '<input type="hidden" name="id" value="'.$menu->id.'" />';

			echo '<input type="hidden" name="order" value="'.$menu->order.'" />';

			echo '<label>Name: </label><input type="text" name="name" value="'.$menu->name.'" /><br />';

			echo '<div class="icon_line">';

			echo '<label>Icon: </label><input type="text" name="icon" value="'.$menu->icon.'" autocomplete="off" /> <button class="nfm_browse">Browse...</button><br />';

			echo '</div>';

			echo '<label>Link: </label><input type="text" name="link" value="'.$menu->link.'" /> ';

			echo '<input type="checkbox" '.($menu->blank == 1 ? 'checked="checked"' : '').' /> Blank ?<br />';

			echo '<label>Text color: </label><input type="text" name="text_color" value="'.$menu->text_color.'" /><br />';

			echo '<label>Background color: </label><input type="text" name="bg_color" value="'.$menu->bg_color.'" /><br />';

			echo '<input type="image" src="'.plugins_url( 'images/save.png', dirname( __FILE__ )).'"  />';

			echo '<img src="'.plugins_url( 'images/remove.png', dirname( __FILE__ )).'" class="remove" rel="'.$menu->id.'" />';

			echo '</form>';

		}



	?>



</div>