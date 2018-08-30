<h2>Nice fixed menu settings</h2>
<form action="" method="post" class="nfm-form">

	<?php wp_nonce_field( 'nfm_settings' ); ?>
	<label for="menu_position">Menu position: </label>
	<select id="menu_position" name="settings[menu_position]">
	<?php

		global $NFM_MENU_POSITIONS;

		foreach($NFM_MENU_POSITIONS as $key => $value)
		{
			if($settings['menu_position'] == $key)
				echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
			else
				echo '<option value="'.$key.'">'.$value.'</option>';
		}

	?>
	</select><br />
	<label for="font_size">Font size: </label>
	<input type="text" name="settings[font_size]" id="font_size" value="<?php echo $settings['font_size'] ?>" />px<br />
	<label for="font_size">Icon size: </label>
	<input type="text" name="settings[icon_size]" id="icon_size" value="<?php echo $settings['icon_size'] ?>" />px<br />
	<input type="image" src="<?php echo plugins_url('images/save.png', dirname(__FILE__)) ?>" />

</form>

<div id="nfm_pro">
	<h2>Need more options ? Look at <a href="http://www.info-d-74.com/produit/nice-fixed-menu-pro-plugin-wordpress/" target="_blank">Nice Fixed Menu Pro !</a></h2>
	<a href="" target="_blank">
		<img src="<?php echo plugins_url('images/pro.png', dirname(__FILE__)) ?>" />
	</a>
</div>