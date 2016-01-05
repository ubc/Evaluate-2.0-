<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Evaluate_Metric_Table extends WP_List_Table {

	private static $nonce = 'eval_delete_metric';

	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Metric', Evaluate::$namespace ), // Singular label
			'plural'   => __( 'Metrics', Evaluate::$namespace ), // Plural label
			'ajax'     => false,
		) );

		$this->process_actions();
	}

	public function column_name( $item ) {
		$nonce_value = wp_create_nonce( self::$nonce );

		ob_start();
		?>
		<strong><?php echo $item['name']; ?></strong>
		<?php
		echo $this->row_actions( array(
			'edit' => sprintf( '<a href="?page=%s&metric_id=%d&_wpnonce=%s">Edit</a>', esc_attr( $_REQUEST['page'].'_edit' ), absint( $item['metric_id'] ), $nonce_value ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&metric_id=%d&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), absint( $item['metric_id'] ), $nonce_value ),
		) );

		return ob_get_clean();
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	public function column_cb( $item ) {
		ob_start();
		?>
		<input type="checkbox" name="bulk-delete[]" value="<?php echo $item['metric_id']; ?>" />
		<?php
		return ob_get_clean();
	}

	public function get_columns() {
		return array(
		    'cb'   => '<input type="checkbox" />',
			'name' => __( 'Name', Evaluate::$namespace ),
			'date' => __( 'Created On', Evaluate::$namespace ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'name'  => array( 'name', false ),
			'date'  => array( 'created', true ),
		);
	}

	public function get_bulk_actions() {
		return array(
			'bulk-delete' => "Delete",
		);
	}

	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$per_page     = $this->get_items_per_page( 'metrics_per_page', 10 );
		$current_page = $this->get_pagenum();
		$metrics      = Evaluate_Metrics::get_metrics( null, $per_page, $current_page );
		$total_items  = count( $metrics );

		$this->set_pagination_args( [
			'total_items' => $total_items, // We have to calculate the total number of items
			'per_page'    => $per_page // We have to determine how many items to show on a page
		] );

		$this->items = $metrics;
	}

	public function process_actions() {
		if ( 'delete' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, self::$nonce ) ) {
				die( 'Nonce check failed.' );
			}

			Evaluate_Manager::delete_metric( absint( $_GET['metric_id'] ) );
			wp_redirect( add_query_arg() );
			exit;
		}

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' ) ) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $metric_id ) {
				Evaluate_Manager::delete_metric( $metric_id );
			}

			wp_redirect( add_query_arg() );
			exit;
		}
	}

}