<div class="wrap">
<?php 
	global $wpdb;
	
	if (isset($_POST['add'])) {
		if (!isset($_POST['manufacturer']) || empty($_POST['manufacturer'])) {
			?>
			<div id="message" class="error"><p><?php _e('Not all fields are filled!', 'recall'); ?></p></div>
			<?php 
		}
		else
		{
			$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
			
			$manufacturer = $_POST['manufacturer'];
			$author = $_POST['author'];
			
			$sql = "SELECT * FROM ".$table_MR_Manufacturer. " WHERE Manufacturer= '". $manufacturer. "'";
			$item = $wpdb->get_row($sql);
			if (empty($item)) {
				$wpdb->insert($wpdb->prefix.'MR_Manufacturer', array('Manufacturer' => $manufacturer, 'Author' => $author));
				?>
				<div id="message" class="updated"><p><?php _e('Manufacturer added', 'recall'); ?></p></div>
				<?php 
			}
			else {
				?>
				<div id="message" class="error"><p><?php _e('Manufacturer already exists', 'recall'); ?></p></div>
				<?php
			}			
		}
	}
?>
	<form action="" method="post">
		
		<h2><?php _e('Recall Settings', 'recall'); ?></h2>
		<?php $user_ID = get_current_user_id(); ?>
		<input type="hidden" id="author" name="author" value="<?php echo $user_ID ?>">
		<div id="poststuff">
			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<input type="text" name="manufacturer" value id="title" size="30" placeholder="<?php _e('Enter Manufacturer here', 'recall'); ?>">
					</div>
				</div>
			</div>
		</div>
		<p class="submit"><input type="submit" value="<?php _e('Add', 'recall'); ?>" name="add" class="button-primary"></p>
	</form>
</div>