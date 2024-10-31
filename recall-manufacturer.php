<?php
	if( !class_exists( 'WP_List_Table' ) ) {
	    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
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
			
	
			$sql = "SELECT ManufacturerID, Manufacturer, display_name FROM ".$table_MR_Manufacturer. " JOIN ".$table_users." ON ".$table_MR_Manufacturer.".Author = ".$table_users.".ID";
			foreach ($wpdb->get_results($sql) as $row) {
				$data[] = array (
						'ID' => $row->ManufacturerID,
						'manufacturer' => $row->Manufacturer,
						'author' =>$row->display_name
				);
				$data;
			}
			return $data;
		}
	    
	    function __construct(){
		    global $status, $page;
		
		        parent::__construct( array(
		            'singular'  => 'manufacturer',     //singular name of the listed records
		            'plural'    => 'manufacturers',   //plural name of the listed records
		            'ajax'      => false        //does this table support ajax?
		    ) );
		
		    add_action( 'admin_head', array( &$this, 'admin_header' ) );            
	
	    }
	    
	    function column_default( $item, $column_name ) {
	    	switch( $column_name ) {
	    		case 'manufacturer':
	    		case 'author':
	    			return $item[ $column_name ];
	    		default:
	    			return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	    	}
	    }
	    
	    function column_title($item) {
	    	$action = array(
				'edit'		=> sprintf('<a href=?page%s&action=%s&manufacturer=%s">Edit</a>"',$_REQUEST['page'],'edit',$item['ID']),
	    		'delete'	=> sprintf('<a href=?page%s&action=%s&manufacturer=%s">Delete</a>"',$_REQUEST['page'],'delete',$item['ID'])
	    	);
	    	
	    	
	        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
	            /*$1%s*/ $item['manufacturer'],
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
	    			'cb'        => '<input type="checkbox" />',
	    			'manufacturer' => __( 'Manufacturer', 'recall' ),
	    			'author'    => __( 'Author', 'recall' )
	    	);
	    	return $columns;
	    }
	    
	    function get_sortable_columns() {
	    	$sortable_columns = array(
	    			'manufacturer'  => array('manufacturer',false), //true means it's already sorted
	    			'author' => array('author',false)
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
	    		foreach ($_POST['manufacturer'] as $value) {
		    		$sql = "SELECT count(ModelID) FROM ".$table_MR_Model." WHERE ManufacturerID = ".$value;
		    		$count = $wpdb->get_var($sql);
		    		if ($count == 0) {
		    			$wpdb->query("DELETE FROM ".$table_MR_Manufacturer." WHERE ".$table_MR_Manufacturer.".ManufacturerID = ".$value);
		    			?>
		    				<div id="message" class="updated"><p><?php _e('Manufacturer deleted', 'recall'); ?></p></div>
		    			<?php
		    		}
		    		else { ?>
		    			<div id="message" class="error"><p><?php _e('Manufacturer has models. First remove the models for which the manufacturer can be removed.', 'recall'); ?></p></div>
		    		<?php }
	    		}
	    	}
	    	if ('export'===$this->current_action()) {
	    		wp_die('Items export');
	    	}
	    
	    }
	    
	    function usort_reorder( $a, $b ) {
	    	$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'author';
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
			<h2><?php _e('Manufacturer', 'recall'); ?><a href="admin.php?page=recall-add" class="add-new-h2"><?php _e('Add New', 'recall'); ?></a></h2>
	  	<?php $recallListTable->prepare_items(); ?>
	  	<form method="post">
	    	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
	    <?php
	    /* $recallListTable->search_box( 'search', 'search_id' ); */
	
	  	$recallListTable->display(); 
	  	echo '</form></div>'; 
	}
	recall_list_page();
?>
