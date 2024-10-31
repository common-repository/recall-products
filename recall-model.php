<?php
if( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	include_once($_SERVER['DOCUMENT_ROOT'].'/wp/wp-load.php' );
}

class Recall_List_Table extends WP_List_Table {
	
	function getData() {
		$data = array();
		global $wpdb;
		
		$table_MR_Person = $wpdb->prefix."MR_Person";
		$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
		$table_MR_Model = $wpdb->prefix."MR_Model";
		$table_MR_Ownership = $wpdb->prefix."MR_Ownership";
		$table_users = $wpdb->prefix."users";

		$sql = "SELECT ".$table_MR_Model.".ModelID, Model, Manufacturer, count(".$table_MR_Ownership.".ModelID) AS Subscription FROM ".$table_MR_Model." JOIN ".$table_MR_Manufacturer." ON ".$table_MR_Model.".ManufacturerID = ".$table_MR_Manufacturer.".ManufacturerID JOIN ".$table_MR_Ownership." ON ".$table_MR_Model.".ModelID = ".$table_MR_Ownership.".ModelID GROUP BY ".$table_MR_Model.".ModelID";
		foreach ($wpdb->get_results($sql) as $row) {
			$data[] = array (
					'ID' => $row->ModelID,
					'model' => $row->Model,
					'manufacturer' => $row->Manufacturer,
					'subscription' => $row->Subscription
			);
			$data;
		}
		return $data;
	}
	
	function __construct(){
		global $status, $page;
	
		parent::__construct( array(
				'singular'  => 'model',     //singular name of the listed records
				'plural'    => 'models',   //plural name of the listed records
				'ajax'      => false        //does this table support ajax?
		) );
		
		add_action( 'admin_head', array( &$this, 'admin_header' ) );
	}
	 
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'model':
			case 'manufacturer':
			case 'subscription':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	 
	function column_title($item) {
		$action = array(
				'edit'		=> sprintf('<a href=?page%s&action=%s&model=%s">Edit</a>"',$_REQUEST['page'],'edit',$item['ID']),
				'delete'	=> sprintf('<a href=?page%s&action=%s&model=%s">Delete</a>"',$_REQUEST['page'],'delete',$item['ID'])
		);
	
	
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
				/*$1%s*/ $item['model'],
				/*$2%s*/ $item['ID'],
				/*$3%s*/ $this->row_actions($actions)
		);
	}
	 
	function column_cb($item) {
		return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
				/*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
		);
	}
	 
	function get_columns(){
		$columns = array(
				'cb'        	=> '<input type="checkbox" />',
				'model'			=> __( 'Model', 'recall' ),
				'manufacturer'	=> __( 'Manufacturer', 'recall' ),
				'subscription'		=> __('Subscription', 'recall')
		);
		return $columns;
	}
	 
	function get_sortable_columns() {
		$sortable_columns = array(
				'model'  => array('model',false), //true means it's already sorted
				'manufacturer' => array('manufacturer',false),
				'subscription'	=> array('subscription',false)
		);
		return $sortable_columns;
	}
	 
	function get_bulk_actions() {
		$actions = array(
				'delete'    => __('Delete')
		);
		return $actions;
	}
	 
	function process_bulk_action() {
		global $wpdb;
		 
		$table_MR_Person = $wpdb->prefix."MR_Person";
		$table_MR_Manufacturer = $wpdb->prefix."MR_Manufacturer";
		$table_MR_Model = $wpdb->prefix."MR_Model";
		$table_MR_Ownership = $wpdb->prefix."MR_Ownership";
		$table_users = $wpdb->prefix."users";
	
		//Detect when a bulk action is being triggered...
		if( 'delete'===$this->current_action() ) {
			foreach ($_POST['model'] as $value) {
				$sql = "SELECT count(ModelID) FROM ".$table_MR_Ownership." WHERE ModelID = ".$value;
				$count = $wpdb->get_var($sql);
				if ($count == 0) {
					$wpdb->query("DELETE FROM ".$table_MR_Model." WHERE ".$table_MR_Model.".ModelID = ".$value);
					?>
		   				<div id="message" class="updated"><p><?php _e('Model deleted', 'recall'); ?></p></div>
		   			<?php
	    		}
	    		else { ?>
	    			<div id="message" class="error"><p><?php _e('Customers have this model. Remove the customers who have this model. Only then the model can be removed.', 'recall'); ?></p></div>
	    		<?php }
	   		}
	   	}
	   	if ('export' === $this->current_action()) {
			echo "<pre>";
	   		function cleanData(&$str) {
		    	$str = preg_replace("/\t/", "\\t", $str);
		    	$str = preg_replace("/\r?\n/", "\\n", $str);
		    	if($str == 't') {
					$str = 'TRUE';
				}
		    	if($str == 'f') {
					$str = 'FALSE';
				}
		    	if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
		    		$str = "'$str";
		    	}
		    	if(strstr($str, '"')) {
					$str = '"' . str_replace('"', '""', $str) . '"';
				}
		  	}
		  	
			$flag = false;			
			foreach ($_POST['model'] as $value) {
				$sql = "SELECT Firstname, LastName, Email FROM ".$table_MR_Ownership." JOIN ".$table_MR_Person." ON ".$table_MR_Ownership.".PersonID = ".$table_MR_Person.".PersonID WHERE ModelID = ".$value;
				foreach ($wpdb->get_results($sql) as $row) {
					$data[] = array (
							'firstname' => $row->Firstname,
							'lastname' => $row->LastName,
							'email' => $row->Email
					);
					$data;
				}
			}
			
			foreach($data as $row) {
				if(!$flag) {
					// display field/column names as first row
		      		echo implode("\t", array_keys($row)) . "\r\n";
		      		$flag = true;
		    	}
		    	array_walk($row, 'cleanData');
		    	echo implode("\t", array_values($row)) . "\r\n";
		  	}
		  	echo "</pre>";
		  	exit;
	   	}	    
	}
		    
	function usort_reorder( $a, $b ) {
	  	$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'manufacturer';
	   	$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
	   	$result = strcmp( $a[$orderby], $b[$orderby] );
	   	return ( $order === 'asc' ) ? $result : -$result;
	}
		    
	function prepare_items() {
	  	global $wpdb; //This is used only if making any database queries
	   	
	   	$per_page = 20;
	   	$data = $this->getData();
	   	$columns = $this->get_columns();
	   	$hidden = array();
	   	$sortable = $this->get_sortable_columns();
		    
	   	$this->_column_headers = array($columns, $hidden, $sortable);
		    
	   	$this->process_bulk_action();
		    	
	   	usort( $data, array( &$this, 'usort_reorder' ) );
		    
	   	$current_page = $this->get_pagenum();
		    
	   	$total_items = count($data);
		    
	   	$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		    
	   	$this->items = $data;
	
	   	$this->set_pagination_args( array(
	  			'total_items' => $total_items,                  //WE have to calculate the total number of items
	   			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
	   			'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
		   	) );
		}
	}
		
	function recall_list_page(){
		$recallListTable = new Recall_List_Table();
		$recallListTable->prepare_items(); ?>
	  	<div class="wrap">
			<h2><?php _e('Model', 'recall'); ?></h2>
		  	<?php $recallListTable->prepare_items(); ?>
	  		<form method="post">
	    		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		    <?php $recallListTable->display(); ?>
			</form>
		</div>
	<?php 
	}
	recall_list_page();
?>
