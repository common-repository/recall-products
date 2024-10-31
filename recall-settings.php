<?php 
if (isset($_POST['export'])) {
	global $wpdb;
	$table_MR_Person = $wpdb->prefix."MR_Person";
	$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
	$table_MR_Model = $wpdb->prefix."MR_Model";
	$table_MR_Ownership = $wpdb->prefix."MR_Ownership";
	
	$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
	$sitename = sanitize_key( get_bloginfo( 'name' ) );
	if ( ! empty($sitename) ) $sitename .= '';
	$file = '/wp-content/uploads/'.$sitename.'RecallProducts.csv';
	$filename = $DOCUMENT_ROOT. $file;
	
	if (file_exists($filename)) { 
	    unlink($filename);
	}
	
	if (($fp = fopen($filename, 'w')) !== false) {
		$sql = mysql_query("SELECT Firstname, LastName, Email, Manufacturer, Model FROM ".$table_MR_Ownership." LEFT JOIN ".$table_MR_Person." ON ".$table_MR_Ownership.".PersonID = ".$table_MR_Person.".PersonID LEFT JOIN ".$table_MR_Model." ON ".$table_MR_Ownership.".ModelID = ".$table_MR_Model.".ModelID LEFT JOIN ".$table_MR_Manufacturer." ON ".$table_MR_Model.".ManufacturerID = ".$table_MR_Manufacturer.".ManufacturerID");
		$num_rows = mysql_num_rows($sql);

		if ($num_rows >= 1 ) {

			$row = mysql_fetch_assoc($sql);

			$seperator = "";
			$comma = "";
			
			foreach ($row as $name => $value) {
				$seperator .= $comma.'' .str_replace('','""',$name);
				$comma = ";";
			}
			$seperator .= "\n";

			fputs($fp, $seperator);

			mysql_data_seek($sql, 0);

			while ($row = mysql_fetch_assoc($sql)) {
				$seperator = "";
				$comma = "";

				foreach ($row as $name => $value) {
					$seperator .= $comma.'' .str_replace('','""',$value);
					$comma = ";";
				}
				$seperator .= "\n";
				fputs($fp, $seperator);
			}

			fclose($fp);
			?>
			<script type="text/javascript">
      			window.location= <?php echo "'" . get_option('siteurl').$file . "'"; ?>;
      			function leave() {
      				window.location= <?php echo "'" . get_option('home') ."/wp-admin/admin.php?page=recall-settings'"; ?>;
      			}
      			setTimeout("leave()", 1000);
   			</script>
   			<?php
		}
		else {
			echo __('No data available');
		}
	}
}
?>
<div class="wrap">
	<h2>Settings</h2>
	<form method="post" action="options.php">
            <?php settings_fields('recall-options'); ?>
            <?php do_settings_sections('recall-options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e("Terms and Conditions URL")?></th>
                    <td><input type="text" name="terms and conditions url" value="<?php echo esc_attr(get_option('terms_and_conditions_url')); ?>" size="60"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
	</form>
	<form method="post" action="">
		<p class="submit">
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'recall-export' ); ?>
			<input type='submit' name='export' value="<?php _e('Export all subscribers', 'recall'); ?>"/>
		</p>
	</form>
</div>
