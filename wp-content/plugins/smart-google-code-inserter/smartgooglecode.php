<?php 
#     /* 
#     Plugin Name: Smart Google Analytics Code
#     Plugin URI: http://oturia.com/
#     Description: Smart Google Analytics, Webmaster Tools and AdWords Code
#     Author: Oturia
#     Version: 3.5 
#     Author URI: http://oturia.com/
#     */  


if( !class_exists('SmartGoogleCode') )
{
	class SmartGoogleCode{
	
		function SmartGoogleCode() { //constructor
		
			//ACTIONS
			//Add Menu in Left Side bar
				add_action( 'admin_menu', array($this, 'my_plugin_menu') );
				
				add_action('wp_head', array($this,'smart_webmaster_head'));
				
				add_action('wp_footer', array($this,'smart_google_pcc_code'));
				
				add_action( 'admin_head', array($this, 'smart_google_add_js'),5 );
				
			# Update General Settings
				if(isset($_POST['action']) && $_POST['action'] == 'savegooglecode' )
					add_action( 'init', array($this,'saveGoogleCode') );
									

				if(isset($_POST['action']) &&  $_POST['action'] == 'saveadwords' )
					add_action( 'init', array($this,'saveGoogleAdWords') );
									
									
									
				register_activation_hook( __FILE__, array($this, 'InstallSmartDefaultValues') );									
				//register_deactivation_hook(__FILE__, array($this, 'UninstallSmartGoogle'));
		}
		
		function my_plugin_menu() {

			global $objSmartGoogleCode;
		
			add_options_page('Smart Google Code', 'Smart Google Code', 'manage_options', 'smartcode', array($objSmartGoogleCode, 'googleCodefrm'));
	
		}


function smart_google_add_js()
{
	wp_enqueue_script( 'jquery' );

}


	// Google Code Function
function googleCodefrm()
{


$Varsgcwebtools  = get_option( 'sgcwebtools' );

$Varsgcgoogleanalytic  = get_option( 'sgcgoogleanalytic' );
 $pages = get_pages(); 

$ppccode = get_option( 'ppccode' );
$ppccap = get_option( 'ppccap' );
$ppcpageid = get_option( 'ppcpageid' );

if( $ppccap == "") {
	$ppccap="ex: Lead";
}

?>


<script>
jQuery(document).ready(function(e) {
	
			jQuery("#selectallbtn").toggle(
					function () {
						jQuery("#adWordsTable input[type=checkbox][id^='chkdel']").each(function(index){
							jQuery(this).attr('checked','checked');
						});	
					},
					function () {
						jQuery("#adWordsTable input[type=checkbox][id^='chkdel']").each(function(index){
							jQuery(this).removeAttr('checked');
						});	
					}
		);
	
	
	//setEditor(0);
    jQuery("#addWordBtn").click(function(){
		
		 var LastID = parseInt( jQuery('#adWordsTable tr td textarea:last').attr('id').replace(/ppccode/gi, '') );
   	 		LastID += 1;  
			var opt='';

                <?php
					  foreach ( $pages as $page ) {
				?> 

               opt += '<option value="<?php echo $page->ID ?>"><?php echo str_replace("'", "", $page->post_title); ?></option>';
                
				<?php } ?> 


		var insert='<tr valign="top"><td style="width:30%; border-bottom: 1px solid #DFDFDF;" scope="row"><input type="checkbox" id="chkdel'+LastID+'" name="nchkdel'+LastID+'" /> </td><th style="width:30%;border-bottom: 1px solid #DFDFDF;" scope="row"><label style="padding: 0 0 15px 0;">Name of Conversion</label><br /><input name="ppccap[]" type="text" id="ppccap'+LastID+'" value="" /><br /><br /><br /><label style="padding: 0 0 16px 0;">Please Select Page</label><br /><select name="ppcpageid[]" id="ppcpageid'+LastID+'" style="width: 150px;"><option value="-1">Select Page</option>'+opt+'</select></th><td style="width:90%;border-bottom: 1px solid #DFDFDF;"><textarea rows="7" cols="55" id="ppccode'+LastID+'" name="ppccode[]"></textarea></td></tr>';
		  
		jQuery("#adWordsTable").append(insert);
	
	
	})

return false;
	
});
 
 	function validate()
	{
		flag=0;
		jQuery("#adWordsTable textarea[id^='ppccode']").each(function(index){
		//	alert($("#ppccode"+(index+1)).val());
			if(jQuery("#ppccode"+(index+1)).val() != ""  )
			  {   
					if( jQuery("#ppcpageid"+(index+1)).val() == "-1" ){
					
						flag=1;
					//	return false;
					}
			  }
		});
		
		if(flag != "0" ) {
			alert('Please select Page');
			return false;
		}
		else 
			return true;
	}
	
	
	function secondFrmSubmit()
	{
		if(confirm('Delete this row?') ) {
			jQuery("#delconf").val("1");
			jQuery("#adwordsform").submit();
		} else {
			jQuery("#delconf").val("0");
			return;
		}
		
	}
	
</script>


<div class="wrap">
  <h2>Smart Google Code Settings</h2>
  <?php if ( !empty($_POST ) ) { ?>
  <div id="message" class="updated fade">
    <p><strong>
      <?php _e('Settings saved.') ?>
      </strong></p>
  </div>
  <br />
  <?php } ?>
  <form name="generalform" id="generalform" method="post" action="" >
    <div class="metabox-holder" style="width: 800px;">
       <div class="postbox">
		<h3 class="hndle">Google Analytics</h3>
		<p style="margin-left:12px;">In this area, paste your Google Analytics tracking code.</p>
        <div>
          <table class="form-table">
            <tbody>
              <tr valign="top">
                <td style="width:70%;"><textarea rows="15" cols="90" name="sgcgoogleanalytic"><?php echo stripslashes($Varsgcgoogleanalytic); ?></textarea></td>
              </tr>

            </tbody>
          </table>
        </div>
		</div>
    </div>
    <!-- end .metabox-holder -->
    
    <div class="metabox-holder" style="width: 800px;">
	<div class="postbox">
        <h3 class="hndle">Webmaster Tools</h3>
		<p style="margin-left:12px;">In this area, paste your Google Webmaster Tools HTML tag for verifying your site. If you need help getting this code, visit <a target="_blank" href="https://support.google.com/webmasters/bin/answer.py?hl=en&answer=35659">this page</a></p>
        <div>
		
          <table class="form-table">
            <tbody>
              <tr valign="top">
                <td style="width:90%;"><textarea rows="7" cols="90" name="sgcwebtools"><?php echo stripslashes($Varsgcwebtools); ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>
    <!-- end .metabox-holder -->
      <input type="submit" name="button" id="button" class="button-primary" value="<?php echo _e("Save Changes"); ?>" />
    <input name="action" value="savegooglecode" type="hidden" />
  </form><br />
</div> 
</div>    
    
     <form name="adwordsform" id="adwordsform" method="post" action="" onsubmit="return validate();">
     
     <input name="action" value="saveadwords" type="hidden" />
     <input type="hidden" name="delconf" id="delconf" value="0" />
    <div class="metabox-holder" style="width: 800px;">
      <div class="postbox">
        <h3>AdWords Conversion Code Settings</h3>
		<p style="margin-left:12px;">This area is only for the Google AdWords conversion tracking code. It is not required. Select the page you want your conversion code to trigger on (e.g. Thank You or Order Confirmation Page).</p>
        <div>
		
        
        
          <table class="form-table">
            <tbody>

            <tr><td>
            <input type="button" value="Select All" id="selectallbtn"/>&nbsp;&nbsp;<input type="button" value="Delete" onclick="secondFrmSubmit();" />
            </td>
            </tr>
            
              <tr><td>
              
     <table width="100%" border="0" cellspacing="0" cellpadding="0" id="adWordsTable">
			
            <?php global $wpdb;
			$table_name= $wpdb->prefix.'smartgoogleadwords';
			$adRows = $wpdb->get_results("SELECT * FROM $table_name");	
			 if(!empty($adRows)) {
			 	$i=0;
				foreach($adRows as $adRow) {
				
			 ?>
          <tr valign="top">
                <td style="width:10%; border-bottom: 1px solid #DFDFDF;" scope="row"><input type="checkbox" id="chkdel<?php echo $i+1 ;?>" name="nchkdel<?php echo $i+1 ;?>" /> </td>
                <td style="width:10%; border-bottom: 1px solid #DFDFDF;" scope="row">
                <label style="padding: 0 0 15px 0;">Name of Conversion</label><br />
				<input type="hidden" name="oId[]" value="<?php echo $adRow->id; ?>"  />
                <input name="ppccap[]" type="text" id="ppccap<?php echo $i+1 ;?>" value="<?php echo stripslashes($adRow->ppccap); ?>" />
                
                <br />
				<br />
				<br />
                <label style="padding: 0 0 16px 0;">Please Select Page</label><br />
                                
                <select name="ppcpageid[]" id="ppcpageid<?php echo $i+1 ;?>" style="width: 150px;">
                <option value="-1">Select Page</option>
                <?php
					  foreach ( $pages as $page ) {
				?> 
                <option value="<?php echo $page->ID ?>" <?php if($adRow->ppcpageid == $page->ID ) {?> selected="selected" <?php } ?> ><?php echo $page->post_title; ?></option>
                
				<?php } ?>                 
                
                </select>
                
                </td>
                <td style="width:40%; border-bottom: 1px solid #DFDFDF;"><textarea rows="7" cols="55" id="ppccode<?php echo $i+1 ;?>" name="ppccode[]"><?php echo stripslashes($adRow->ppccode); ?></textarea></td>
          </tr>
              
          <?php $i++; } } else { ?>

          <tr valign="top">
           <td style="width:30%; border-bottom: 1px solid #DFDFDF;" scope="row"><input type="checkbox" id="chkdel1" name="nchkdel1" /> </td>
                <td style="width:30%; border-bottom: 1px solid #DFDFDF;" scope="row">
                <label style="padding: 0 0 15px 0;">Name of Conversion</label><br />

                <input name="ppccap[]" type="text" id="ppccap1" value="ex: mywplead" />
                
                <br />
				<br />
				<br />
                <label style="padding: 0 0 16px 0;">Please Select Page</label><br />

          
                                
                <select name="ppcpageid[]" id="ppcpageid1" style="width: 150px;">
                <option value="-1">Select Page</option>
                <?php
					  foreach ( $pages as $page ) {
				?> 
                <option value="<?php echo $page->ID ?>" ><?php echo $page->post_title; ?></option>
                
				<?php } ?>                 
                
                </select>
                
                </td>
                <td style="width:90%; border-bottom: 1px solid #DFDFDF;"><textarea rows="7" cols="55" id="ppccode1" name="ppccode[]"></textarea></td>
          </tr>


          
          <?php } ?>
          
</table>
</td></tr>
<tr>
<td colspan="2" style="padding-right:30px;"><input type="button" name="addWordBtn" id="addWordBtn" style="float: right;" class="button-primary" value="<?php echo _e("Add More"); ?>" /></td>

</tr>
            </tbody>
          </table>

        </div>
      </div>

  <input type="submit" name="button" id="button" class="button-primary" value="<?php echo _e("Save AdWords"); ?>" />

    </div>
 
	 </form>

<?php
}


	function saveGoogleAdWords()
	{
		// save Google Ad words
	global $wpdb;
	if( current_user_can('administrator')) {
	$table_name= $wpdb->prefix.'smartgoogleadwords';
					
					if($_POST["delconf"] == 1 ) {
					
						for($i=0; $i < count($_POST["ppccode"]); $i++ )
						{
							
							if($_POST["nchkdel".($i+1)] == "on" ) {
						
								if(isset($_POST["oId"][$i])  && $_POST["oId"][$i] != "")
								{
									$del = "DELETE FROM $table_name WHERE id = ".$_POST["oId"][$i]." LIMIT 1";
									$wpdb->query($del);
								}
						
							}
						}
					} else {
					
					
							for($i=0; $i < count($_POST["ppccode"]); $i++ )
							{	 
								$data = array();
								$ppccode = stripslashes($_POST["ppccode"][$i]); 
								$ppccap = $_POST["ppccap"][$i];
								$ppcpageid = $_POST["ppcpageid"][$i];
		
								if($ppccode != "")
								{
										if($_POST["oId"][$i] != '' ) {
											$data["ppccap"]=$wpdb->escape($ppccap);
											$data["ppccode"]=$wpdb->escape($ppccode);
											$data["ppcpageid"]=$wpdb->escape($ppcpageid);
											$wpdb->update( $table_name, $data , array( 'id' => $_POST["oId"][$i] ) );
										} else {
											$data["ppccap"]=$wpdb->escape($ppccap);
											$data["ppccode"]=$wpdb->escape($ppccode);
											$data["ppcpageid"]=$wpdb->escape($ppcpageid);
											$rows_affected = $wpdb->insert( $table_name, $data );									
										}
									
								}else{
									if(isset($_POST["oId"][$i])  && $_POST["oId"][$i] != "")
									{
										$del = "DELETE FROM $table_name WHERE id = ".$_POST["oId"][$i]." LIMIT 1";
										$wpdb->query($del);
										
									}
								}
							
		
							
				
							}// end of for
					
					} // end of else part

				
				if($rows_affected === 1 ){
					$_POST['notice']= "AdWords successfully added.";
				} else {
					$_POST['notice']= "AdWords not added.";
				}
		
	}
	}



	//Save Google Code Info
		function saveGoogleCode()
		{
		if( current_user_can('administrator')) {
			update_option( 'sgcwebtools', $_POST["sgcwebtools"] );
	
			update_option( 'sgcgoogleanalytic', $_POST["sgcgoogleanalytic"] );

			$_POST['notice'] = __('Settings Saved');			
		} else {
			echo "Sorry, but you don't have permission to do that.";
		}
		}


	function UninstallSmartGoogle() {
			global $wpdb;
			
			//delete_option("sgcwebtools");
			
			//delete_option("sgcgoogleanalytic");
	
			$table_name = $wpdb->prefix . "smartgoogleadwords";
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) :
				$sql = "DROP TABLE $table_name";
				$wpdb->query($sql);
			endif;	
			
	}


	function smart_webmaster_head()
	{
		
		$Varsgcwebtools  = get_option( 'sgcwebtools' );
		
		$Varsgcgoogleanalytic  = get_option( 'sgcgoogleanalytic' );		
		
		echo stripslashes($Varsgcwebtools);
		echo "\n";
		echo stripslashes($Varsgcgoogleanalytic);
		
	}

	function smart_google_pcc_code()
	{
		global $post;
		global $wpdb;
		$pstId = $post->ID;

		$table_name= $wpdb->prefix.'smartgoogleadwords';
		$adRows = $wpdb->get_results("SELECT * FROM $table_name");	
		
		 if(!empty($adRows)) {

				foreach($adRows as $adRow) {
		
					if($adRow->ppcpageid == $pstId ) {
						echo stripslashes($adRow->ppccode);
					}
				}
		}
		
	}


	function  InstallSmartDefaultValues() {

		global $wpdb;
		add_option("sgcwebtools", "");
		add_option("sgcgoogleanalytic", "");
	
			$table_name = $wpdb->prefix . "smartgoogleadwords";
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE $table_name  (
						  id bigint(20) NOT NULL AUTO_INCREMENT,
						  ppccap varchar(200) DEFAULT NULL,
						  ppccode longtext,
						  ppcpageid bigint(20) DEFAULT NULL,
						  PRIMARY KEY (id)
					);";
				
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				
				}

	}


	}
} 

if( class_exists('SmartGoogleCode') ) {
	$objSmartGoogleCode = new SmartGoogleCode();
}
?>