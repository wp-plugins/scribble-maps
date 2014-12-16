<?php
//if (!class_exists('WP_List_Table')) {
    require_once( SCRIBBLEMAPS_DIR . 'includes/class-scribble-wp-list-table.php' );
//}

/*
 * Class for Map list table
 */

class ScribbleMaps_List_Table extends Scribble_WP_List_Table {
    /*
     * Constructor
     */

    function __construct() {
        global $status, $page;

        //Set parent defaults
        parent::__construct(array(
            'singular' => 'map', //singular name of the listed records
            'plural' => 'maps', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    /*
     * Rendering Default coloumns
     */

    function column_default($item, $column_name) {
        //  echo $column_name;
        switch ($column_name) {
            case 'description':
                return $item['mapdesc'];
            case 'mapname':
            case 'mapid':
                return $item[$column_name];
            case 'shortcode':
                return '[scribblemaps mapid="' . $item["mapid"] . '" width="400" height="300"]';
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /*
     * Rendering map name coloumn
     */

    function column_mapname($item) {

        //Build row actions
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&map=%s">Edit</a>', 'scribble-new', 'edit', $item['mapid']),
            'delete' => sprintf('<a href="?page=%s&action=%s&map=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['sid']),
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
                /* $1%s */ $item['mapname'],
                /* $2%s */ $item['sid'],
                /* $3%s */ $this->row_actions($actions)
        );
    }

    /*
     * Rendering CheckBox coloumn
     */

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['plural'], $item['sid']);
    }

    /*
     *  Defining table headings
     */

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'mapname' => 'Map Name',
            'mapid' => 'Map ID',
            'description' => 'Map Description',
            'shortcode' => 'ShortCode'
        );
        return $columns;
    }

    /*
     *  Defining sortable coloumns
     */

    function get_sortable_columns() {
        $sortable_columns = array(
            'mapname' => array('mapname', true)     //true means it's already sorted
        );
        return $sortable_columns;
    }

    /*
     * Bulk actions
     */

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /*
     * Processing Bulk actions
     */

    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            if (isset($_GET['map']) && $_GET['map'] != '') {
                // Delete single map
                delete_this_map($_GET['map']);
            } else {
                //Detect when a bulk action is being triggered...
                foreach ($_GET['maps'] as $map) {
                    delete_this_map($map);
                }
            }
        }
    }

    /*
     * Fetch and Display Maps
     */

    function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . SCRIBBLEMAPS_TABLE;

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 5;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        /**
         * Bulk action handler         
         */
        $this->process_bulk_action();



        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        /*
         * Total Map count
         */
        $total_items = $wpdb->get_var(" SELECT count(*) FROM $table_name ");

        /*
         * Sorting 
         */
        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'mapname'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc

        $data = $wpdb->get_results("SELECT * FROM $table_name order by $orderby  $order LIMIT $offset, $per_page", ARRAY_A);

        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }

}

add_action('admin_menu', 'scribblemaps_menu');

function scribblemaps_menu() {
    add_menu_page('Scribble Map Dashboard', 'Scribble Map', 'edit_posts', 'scribble-map-main', 'scribblemaps_dashboard',SCRIBBLEMAPS_URL.'lib/tinymce3/images/scribble-grey.png' );
    add_submenu_page('scribble-map-main', 'New Scribble Map', 'Create New Scribble Map', 'edit_posts', 'scribble-new', 'scribblemaps_new');
}

function scribblemaps_dashboard() {
    //Create an instance of our package class...
    $listTable = new ScribbleMaps_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $listTable->prepare_items();
    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Scribble Maps</h2>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $listTable->display() ?>
        </form>
    </div>
    <?php
}

function my_enqueue($hook) {
    if ('scribble-map_page_scribble-new' != $hook) {
        return;
    }
    wp_enqueue_style('scribble-map-css', SCRIBBLEMAPS_URL . 'css/scribblemaps.css');
    wp_enqueue_script('scribble-map-js', '//scribblemaps.com/api/js/');
    wp_enqueue_script('ajax-script', SCRIBBLEMAPS_URL . 'js/script.js', array('jquery'));
    wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', 'my_enqueue');

function scribblemaps_new() {
    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"><br/></div>
        <?php if ((isset($_GET["map"]) && isset($_GET["action"]) && $_GET["action"] == 'edit')): ?>
            <input id="scribble-edit" type="hidden" value="<?php echo $_GET["map"]; ?>" />
            <h2>Edit Scribble Map</h2>
        <?php else: ?>
            <h2>New Scribble Map</h2>
        <?php endif; ?>
        <div id="popup">
            <div id="ScribbleMap"></div>
	    <div id="instructions">
				<h2>Instructions</h2>
				<ol>
					<li>Use the tools to draw or edit your Map.</li>
					<li>Click Menu.</li>
					<li>Click Save Map.</li>
					<li>Input details and click save.</li>
					<li>Retrieve Short Code from Scribble Map dashboard.</li>
				</ol>
			</div>
	     <div class="message"></div>
        </div>  

    </div>

    <?php
}

add_action('wp_ajax_save_map', 'save_map_callback');

function save_map_callback() {
    global $wpdb; // this is how you get access to the database
    $table_name = $wpdb->prefix . SCRIBBLEMAPS_TABLE;
    if (isset($_POST["id"]) && $_POST["id"] != '') {

        $mapid = esc_sql($_POST["id"]);
        $mapname = isset($_POST["title"]) ? esc_sql($_POST["title"]) : "";
        $mapdesc = isset($_POST["description"]) ? esc_sql($_POST["description"]) : "";
        $listtype = isset($_POST["listType"]) ? esc_sql($_POST["listType"]) : "";

        $result = $wpdb->get_results("SELECT sid FROM $table_name WHERE mapid = '" . $_POST["id"] . "'");
        if (count($result) > 0) {
            $wpdb->query("UPDATE $table_name SET mapname = '" . $mapname . "', mapdesc = '" . $mapdesc . "', listtype = '" . $listtype . "'  WHERE mapid = '" . $mapid . "'");
        } else {
            $wpdb->query("INSERT INTO $table_name (mapid, mapname,mapdesc,listtype) VALUES ('" . $mapid . "','" . $mapname . "','" . $mapdesc . "','" . $listtype . "' )");
        }
        echo 'success';
        die();
    }
    echo 'failed';
    die();
}

function delete_this_map($sid) {
    global $wpdb; // this is how you get access to the database
    $table_name = $wpdb->prefix . 'scribblemaps';
    $wpdb->delete($table_name, array('sid' => $sid), array('%d'));
}