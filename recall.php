<?php
	/**
	* Plugin Name: Recall Products
	* Plugin URI: http://www.mikerooijackers.nl
	* Description: If you sell products you can provide visitiors/customers with recall information about a product.
	*
	* You can provide every brand/manufactor for a specific product where the customer can provide the model of the product.
	* The module will provide double entries of models.
	*
	* Later on you will able to export the customer-mailling list to get in contact for a brand recall
	*
	* Language changed depending wordpress installation. 
	* Available in: Dutch, English
	* Version: 0.8
	* Author: Mike Rooijackers
	* Author URI: http://www.mikerooijackers.nl
	* License: GPL2
	* Text Domain: recall
	*/
	if (is_admin()) {
		add_action('admin_menu', 'recall_menu');
                add_action('admin_init', 'register_recall_settings' );
	}
	
	load_plugin_textdomain( 'recall', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	register_activation_hook(__FILE__,'recall_install');
	register_uninstall_hook(__FILE__,'recall_uninstall');
	
	add_shortcode('recall', 'recall');
	
	function recall_uninstall() {
		if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    		exit();
		}
		
		global $wpdb;
		$table_MR_Person = $wpdb->prefix . "MR_Person";
		$table_MR_Manufacturer = $wpdb->prefix . "MR_Manufacturer";
		$table_MR_Model = $wpdb->prefix . "MR_Model";
		$table_MR_Ownership = $wpdb->prefix . "MR_Ownership";
		$trigger_MR_lcase_model = $wpdb->prefix."MR_LCase_Model";
		
		$wpdb->query("DROP TABLE IF EXISTS ".$table_MR_Ownership);
		$wpdb->query("DROP TABLE IF EXISTS ".$table_MR_Model);
		$wpdb->query("DROP TABLE IF EXISTS ".$table_MR_Person);
		$wpdb->query("DROP TABLE IF EXISTS ".$table_MR_Manufacturer);
		$wpdb->query("DROP TRIGGER IF EXISTS ".$trigger_MR_lcase_model);
		
		$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty($sitename) ) $sitename .= '';
		$file = '/wp-content/uploads/'.$sitename.'RecallProducts.csv';
		$filename = $DOCUMENT_ROOT. $file;
		
		if (file_exists($filename)) {
			unlink($filename);
		}
	}
	
	function recall_install() {
		global $wpdb;
		
		$table_MR_Person = $wpdb->prefix."MR_Person";
		$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
		$table_MR_Model = $wpdb->prefix."MR_Model";
		$table_MR_Ownership = $wpdb->prefix."MR_Ownership";
		
		if ($wpdb->get_var('show tables like'.$table_MR_Person)!=$table_MR_Person &&
			$wpdb->get_var('show tables like'.$table_MR_Manufacturer)!=$table_MR_Manufacturer &&
			$wpdb->get_var('show tables like'.$table_MR_Model)!=$table_MR_Model &&
			$wpdb->get_var('show tables like'.$table_MR_Ownership)!=$table_MR_Ownership) {
			
			$sql_table_Person = 'CREATE TABLE ' . $table_MR_Person . '(
					PersonID INT NOT NULL AUTO_INCREMENT,
					Firstname VARCHAR(255) NOT NULL,
					LastName VARCHAR(255) NOT NULL,
					Email VARCHAR(255) NOT NULL,
					PRIMARY KEY (PersonID))';
			
			$sql_table_Manufacturer = 'CREATE TABLE ' . $table_MR_Manufacturer . '(
					ManufacturerID INT NOT NULL AUTO_INCREMENT,
					Manufacturer VARCHAR(255) NOT NULL UNIQUE,
					Logo VARCHAR(255) NULL,
					Author INT NOT NULL,
					PRIMARY KEY (ManufacturerID))';
			
			$sql_table_Model = 'CREATE TABLE ' . $table_MR_Model .'(
					ModelID INT NOT NULL AUTO_INCREMENT,
					Model VARCHAR(255) NOT NULL UNIQUE,
					ManufacturerID INT NOT NULL,
					PRIMARY KEY (ModelID),
					FOREIGN KEY (ManufacturerID) REFERENCES ' . $table_MR_Manufacturer . ' (ManufacturerID))';
			
			$sql_table_Ownership = 'CREATE TABLE ' . $table_MR_Ownership .'(
					PersonID INT NOT NULL,
					ModelID INT NOT NULL,
					PRIMARY KEY (PersonID, ModelID),
					FOREIGN KEY (PersonID) REFERENCES ' . $table_MR_Person . ' (PersonID),
					FOREIGN KEY (ModelID) REFERENCES ' . $table_MR_Model . ' (ModelID))';
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_table_Person);
			dbDelta($sql_table_Manufacturer);
			dbDelta($sql_table_Model);
			dbDelta($sql_table_Ownership);
			
			add_option('recall_products','0.5');
		}
	}
	
	function on_load() {
		add_action('plugins_loaded', array(__CLASS__,'plugins_loaded'));
	}
	
	function recall_menu() {
		add_menu_page(__('Recall Settings', 'recall'), __('Recall', 'recall'), 'manage_options', 'recall-settings', 'recall_settings');
		add_submenu_page('recall-settings', __('Manufacturer', 'recall'), __('Manufacturer', 'recall'), 'manage_options', 'recall-manufacturer', 'recall_manufacturer');
		add_submenu_page('recall-settings', __('Add Manufacturer', 'recall'), __('Add New', 'recall'), 'manage_options', 'recall-add', 'recall_add');
		add_submenu_page('recall-settings', __('Model', 'recall'), __('Model', 'recall'), 'manage_options', 'recall-model', 'recall_model');
		/* add_submenu_page('recall-settings', __('Export', 'recall'), __('Export', 'recall'), 'manage_options', 'recall-export', 'recall_export'); */
	}
	
	function recall_settings() {
		require( dirname( __FILE__ ) . '/recall-settings.php' );
	}
	
	function recall_manufacturer() {
		require( dirname( __FILE__ ) . '/recall-manufacturer.php' );
	}
	
	function recall_add() {
		require( dirname( __FILE__ ) . '/recall-add.php' );
	}
	
	function recall_model() {
		require( dirname( __FILE__ ) . '/recall-model.php' );
	}
	
	/* function recall_export() {
		require( dirname( __FILE__ ) . '/recall-export.php' );
	} */
	function recall() {
		$recall = new Recall();
	}
        
        function register_recall_settings() {
            //register our settings
            register_setting( 'recall-options', 'terms_and_conditions_url' );
        }
	
class Recall {
	function recall() {
		global $wpdb;
                $termsandconditions = get_option('terms_and_conditions_url');
		$manufacturerID = $model = $firstname = $lastname = $email = "";
		$table_MR_Person = $wpdb->prefix."MR_Person";
		$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
		$table_MR_Model = $wpdb->prefix."MR_Model";
		$table_MR_Ownership = $wpdb->prefix."MR_Ownership";
		
		$sql = "SELECT ManufacturerID, Manufacturer FROM ".$table_MR_Manufacturer. " ORDER BY Manufacturer ASC";
		$manufacurers = $wpdb->get_results($sql);
		
		if (isset($_POST['register']) && isset($_POST['conditions'])) {
			$manufacturerID = $_POST['manufacturerid'];
			$model = $_POST['model'];
			$firstname = $_POST['firstname'];
			$lastname = $_POST['lastname'];
			$email = $_POST['email'];
			$model = strtolower($model);
			$firstname = strtolower($firstname);
			$lastname = strtolower($lastname);
			$email = strtolower($email);
                        
			
			if (!isset($manufacturerID)  || $manufacturerID == -1) {
				?><p style="color:red;"><?php _e('Manufacturer is not filled!', 'recall'); ?></p><?php
			}
			elseif (!isset($model) || empty($model)) {
				?><p style="color:red;"><?php _e('Model is not filled!', 'recall'); ?></p><?php
			}
			elseif (!isset($firstname) || empty($firstname)) {
				?><p style="color:red;"><?php _e('Firstname is not filled!', 'recall'); ?></p><?php
			}
			elseif (!isset($lastname) || empty($lastname)) {
				?><p style="color:red;"><?php _e('Lastname is not filled!', 'recall'); ?></p><?php
			}
			elseif (!isset($email) || empty($email)) {
				?><p style="color:red;"><?php _e('E-mail is not filled!', 'recall'); ?></p><?php
			}
			else
			{
				$modelID = $wpdb->get_row("SELECT ModelID FROM ".$table_MR_Model. " WHERE Model= '". $model. "'");
				if ($modelID == 0) {
					$wpdb->insert($wpdb->prefix.'MR_Model', array('Model' => $model, 'ManufacturerID' => $manufacturerID));
				}
				$modelID = $wpdb->get_row("SELECT ModelID FROM ".$table_MR_Model. " WHERE Model= '". $model. "'");
				
				$personID = $wpdb->get_row("SELECT PersonID FROM ".$table_MR_Person. " WHERE FirstName= '". $firstname. "' AND LastName= '". $lastname."' AND Email= '". $email. "'");
				if ($personID == 0) {
					$wpdb->insert($wpdb->prefix.'MR_Person', array('FirstName' => $firstname, 'LastName' => $lastname, 'Email' => $email));
				}
				$personID = $wpdb->get_row("SELECT PersonID FROM ".$table_MR_Person. " WHERE FirstName= '". $firstname. "' AND LastName= '". $lastname."' AND Email= '". $email. "'");
								
				$wpdb->insert($wpdb->prefix.'MR_Ownership', array('PersonID' => $personID->PersonID, 'ModelID' => $modelID->ModelID));
				?><p style="color:green;"><?php _e('You are registered', 'recall'); ?></p><?php
			}
		}
		elseif (isset($_POST['register']) && !isset($_POST['conditions'])) {
                    ?><p style="color:red;"><?php _e('Terms and Conditions not accepted', 'recall'); ?></p><?php
		}
		else {
			
		}
		?>
			<form action="" method="post">
				<table>
					<tr>
						<td><?php _e('Manufacturer', 'recall'); ?>*</td> 
						<td>
							<select name="manufacturerid">
								<option value="-1">--<?php _e('Select a manufacturer', 'recall'); ?>--</option>
							<?php 
								foreach ( $manufacurers as $manufacturer ) { ?>
									<option value="<?php echo $manufacturer->ManufacturerID; ?>"><?php echo $manufacturer->Manufacturer; ?></option>
								<?php }
							?>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php _e('Model', 'recall'); ?>*</td>
						<td><input type="text" name="model" value="<?php echo $model; ?>"></td>
					</tr>			
					<tr>
						<td><?php _e('Firstname', 'recall'); ?>*</td>
						<td><input type="text" name="firstname" value="<?php echo $firstname; ?>"></td>
					</tr>
					<tr>
						<td><?php _e('Lastname', 'recall'); ?>*</td>
						<td><input type="text" name="lastname" value="<?php echo $lastname; ?>"></td>
					</tr>
					<tr>
						<td><?php _e('E-mail', 'recall'); ?>*</td><td>
						<input type="email" name="email" value="<?php echo $email; ?>"></td>
					</tr>
				</table>
				<sup><?php _e('required', 'recall'); ?> *</sup>
                                <p><input type="checkbox" name="conditions"><a href="<?php echo $termsandconditions ?>" target="__blank" rel="nofollow"><?php _e('Terms and Conditions', 'recall'); ?></a></p>
				<input type="submit" value="<?php _e('Register', 'recall'); ?>" name="register">
			</form>
		<?php 
	}
}
	
	class Recall_Widget extends WP_Widget {
		function Recall_Widget() {
			parent::__construct(false, $name = 'Recall Products Widget');
		}
	
		function widget($args, $instance) {
			
			
			extract( $args );
			$title 		= apply_filters('widget_title', $instance['title']);
			?>
		              <?php echo $before_widget; ?>
		                  <?php if ( $title ) {
		                        echo $before_title . $title . $after_title; 
		                  } ?>
		                  <?php $recall = new Recall();	?>
		              <?php echo $after_widget; ?>
		        <?php
		        
		    }
		    
		    function update($new_instance, $old_instance) {
		    	$instance = $old_instance;
		    	$instance['title'] = strip_tags($new_instance['title']);
		    	return $instance;
		    }
		    
		    function form($instance) {
		    
		    	$title 		= esc_attr($instance['title']);
		    	?>
		             <p>
		              <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:'); ?></label> 
		              <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		            </p>
		            <?php 
		        }
	}
	add_action('widgets_init', create_function('', 'return register_widget("Recall_Widget");'));
?>