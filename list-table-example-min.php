<?php
/*
Plugin Name: Custom List Table Example
Plugin URI: http://www.mattvanandel.com/
Description: A highly documented plugin that demonstrates how to create custom List Tables using official WordPress APIs.
Version: 1.3
Author: Matt Van Andel
Author URI: http://www.mattvanandel.com
License: GPL2
*/
/*  Copyright 2014  Matthew Van Andel  (email : matt@mattvanandel.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Avoid 'hook_suffix' notice
error_reporting( ~E_NOTICE );

class TT_Example_List_Table extends WP_List_Table {

    var $example_data = array(
            array(
                'ID'        => 1,
                'title'     => '300',
                'rating'    => 'R',
                'director'  => 'Zach Snyder'
            ),
            array(
                'ID'        => 2,
                'title'     => 'Eyes Wide Shut',
                'rating'    => 'R',
                'director'  => 'Stanley Kubrick'
            ),
            array(
                'ID'        => 3,
                'title'     => 'Moulin Rouge!',
                'rating'    => 'PG-13',
                'director'  => 'Baz Luhrman'
            ),
            array(
                'ID'        => 4,
                'title'     => 'Snow White',
                'rating'    => 'G',
                'director'  => 'Walt Disney'
            ),
            array(
                'ID'        => 5,
                'title'     => 'Super 8',
                'rating'    => 'PG-13',
                'director'  => 'JJ Abrams'
            ),
            array(
                'ID'        => 6,
                'title'     => 'The Fountain',
                'rating'    => 'PG-13',
                'director'  => 'Darren Aronofsky'
            ),
            array(
                'ID'        => 7,
                'title'     => 'Watchmen',
                'rating'    => 'R',
                'director'  => 'Zach Snyder'
            )
        );

    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'movie',     //singular name of the listed records
            'plural'    => 'movies',    //plural name of the listed records
            'ajax'      => true         //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'rating':
            case 'director':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item){
        
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'     => 'Title',
            'rating'    => 'Rating',
            'director'  => 'Director'
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array('title',false),     //true means it's already sorted
            'rating'    => array('rating',false),
            'director'  => array('director',false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
    }

    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        $per_page = 2;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $data = $this->example_data;

        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');

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

    /**
     * Display the pagination.
     * This is a plain copy of parent::pagination() with a few tricks to edit
     * the links that will trigger jQuery. I tend to prefer this way rather than
     * have a heavier jQuery part playing with classes to get the right links.
     */
    function pagination($which){
        if ( empty( $this->_pagination_args ) )
                return;

        extract( $this->_pagination_args, EXTR_SKIP );

        $output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current = $this->get_pagenum();

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

        $page_links = array();

        $disable_first = $disable_last = '';
        if ( $current == 1 )
            $disable_first = ' disabled';
        if ( $current == $total_pages )
            $disable_last = ' disabled';

        $page_links[] = sprintf( "<a class='%s' title='%s' href='%s' data-nav='%s' data-nav-direction='first' data-nav-paged='%s'>%s</a>",
            'first-page' . $disable_first,
            esc_attr__( 'Go to the first page' ),
            esc_url( remove_query_arg( 'paged', $current_url ) ),
            ( '' == $disable_first ? 'true' : 'false' ),
            1,
            '&laquo;'
        );

        $page_links[] = sprintf( "<a class='%s' title='%s' href='%s' data-nav='%s' data-nav-direction='prev' data-nav-paged='%s'>%s</a>",
            'prev-page' . $disable_first,
            esc_attr__( 'Go to the previous page' ),
            esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
            ( '' == $disable_first ? 'true' : 'false' ),
            max( 1, $current-1 ),
            '&lsaquo;'
        );

        if ( 'bottom' == $which )
            $html_current_page = $current;
        else
            $html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
                esc_attr__( 'Current page' ),
                $current,
                strlen( $total_pages )
            );

        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        $page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

        $page_links[] = sprintf( "<a class='%s' title='%s' href='%s' data-nav='%s' data-nav-direction='next' data-nav-paged='%s'>%s</a>",
            'next-page' . $disable_last,
            esc_attr__( 'Go to the next page' ),
            esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
            ( '' == $disable_last ? 'true' : 'false' ),
            min( $total_pages, $current+1 ),
            '&rsaquo;'
        );

        $page_links[] = sprintf( "<a class='%s' title='%s' href='%s' data-nav='%s' data-nav-direction='last' data-nav-paged='%s'>%s</a>",
            'last-page' . $disable_last,
            esc_attr__( 'Go to the last page' ),
            esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
            ( '' == $disable_last ? 'true' : 'false' ),
            $total_pages,
            '&raquo;'
        );

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) )
            $pagination_links_class = ' hide-if-js';
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages )
            $page_class = $total_pages < 2 ? ' one-page' : '';
        else
            $page_class = ' no-pages';

        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    /**
     * Display the table
     * Adds a Nonce field and calls parent's display method
     *
     * @since 3.1.0
     * @access public
     */
    function display() {

        wp_nonce_field( 'ajax-fetch-custom-list-nonce', '_ajax_fetch_custom_list_nonce' );

        parent::display();
    }

    /**
     * Handle an incoming ajax request (called from admin-ajax.php)
     */
    function ajax_response() {

        check_ajax_referer( 'ajax-fetch-custom-list-nonce', '_ajax_fetch_custom_list_nonce' );

        $this->prepare_items();

        extract( $this->_args );
        extract( $this->_pagination_args, EXTR_SKIP );

        ob_start();
        if ( ! empty( $_REQUEST['no_placeholder'] ) )
            $this->display_rows();
        else
            $this->display_rows_or_placeholder();

        $rows = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array( 'rows' => $rows );
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;

        if ( isset( $total_items ) )
            $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

        if ( isset( $total_pages ) ) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n( $total_pages );
        }

        die( json_encode( $response ) );
    }


}

function tt_add_menu_items(){
    add_menu_page('Example Plugin List Table', 'List Table Example', 'activate_plugins', 'tt_list_test', 'tt_render_list_page');
} add_action('admin_menu', 'tt_add_menu_items');


function tt_render_list_page(){
    
    //Create an instance of our package class...
    $testListTable = new TT_Example_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();
    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>List Table Test</h2>
        
        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p>This page demonstrates the use of the <tt><a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WP_List_Table</a></tt> class in plugins.</p> 
            <p>For a detailed explanation of using the <tt><a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WP_List_Table</a></tt>
            class in your own plugins, you can view this file <a href="<?php echo admin_url( 'plugin-editor.php?plugin='.plugin_basename(__FILE__) ); ?>" style="text-decoration:none;">in the Plugin Editor</a> or simply open <tt style="color:gray;"><?php echo __FILE__ ?></tt> in the PHP editor of your choice.</p>
            <p>Additional class details are available on the <a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WordPress Codex</a>.</p>
        </div>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>
        
    </div>
    <?php
}


/** *************************** AJAX CALLBACK ********************************
 *******************************************************************************
 * This function loads the Custom List Table Class and calls ajax_response method
 */
function _ajax_fetch_custom_list_callback() {
    $wp_list_table = new TT_Example_List_Table();
    $wp_list_table->ajax_response();
} add_action('wp_ajax__ajax_fetch_custom_list', '_ajax_fetch_custom_list_callback');

/** ************************* LOAD JQUERY PART ********************************
 *******************************************************************************
 * This function adds the jQuery script to the plugin's page footer
 */
function ajax_script(){
    $screen = get_current_screen();
    if( 'toplevel_page_tt_list_test' != $screen->id )
        return false;
?>
<script>
(function($) {

	var update_wp_list_table = function( $link ) {
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: '_ajax_fetch_custom_list',
				_ajax_fetch_custom_list_nonce: $('#_ajax_fetch_custom_list_nonce').val(),
				paged: $link.attr('data-nav-paged')
			},
			success: function(response) {
				response = $.parseJSON( response );

				if ( response.rows.length )
					$('#the-list').html( response.rows );
				if ( response.pagination.bottom.length )
					$('.tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
				if ( response.pagination.top.length )
					$('.tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );

				update_wp_list_table_init();
			}
		});
	};

	var update_wp_list_table_init = function() {
		$('a[data-nav=true]').on('click', function(e) {
			e.preventDefault();
			update_wp_list_table( $(this) );
		});
		$('a[data-nav=false]').on('click', function(e) {
			e.preventDefault();
		});
	};

	update_wp_list_table_init();

})(jQuery);
</script>
<?php
} add_action('admin_footer', 'ajax_script');