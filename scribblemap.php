<?php
/*
  Plugin Name: Scribble Maps
  Description: Plugin create and embed Scribble Maps
  Version: 1.0
  Author: Scribble Maps
  Author URI: http://www.scribblemaps.com/
  Plugin URI: Author URI: http://www.scribblemaps.com/
 */

global $scribble_db_version;
$scribble_db_version = '1.0';

define('SCRIBBLEMAPS_DIR', plugin_dir_path(__FILE__));
define('SCRIBBLEMAPS_URL', plugin_dir_url(__FILE__));
define('SCRIBBLEMAPS_TABLE', 'scribblemaps');

function scribblemaps_load() {
    if (is_admin()) {
        add_action('admin_head', 'setAdminJSVars');
        add_action('admin_enqueue_scripts', 'enqueueAdminJS');
        require_once(SCRIBBLEMAPS_DIR . 'includes/admin.php');
    }
}
function enqueueAdminJS() {
    wp_enqueue_script('scribble-maps-editor', plugins_url('js/scribble-map-editor.js',__FILE__), array(), '2.0');
}

function setAdminJSVars() {
    ?>
    <script type="text/javascript" charset="utf-8">
    // <![CDATA[
        if (typeof Scribble !== 'undefined' && typeof Scribble.Map !== 'undefined') {
            Scribble.Map.configUrl = "<?php echo admin_url('admin-ajax.php') . '?action=editor_embed'; ?>";
        };
    // ]]>
    </script>
    <?php
}

scribblemaps_load();

register_activation_hook(__FILE__, "scribblemaps_activation");

function scribblemaps_activation() {
    //actions to perform once on plugin activation go here
    global $wpdb;
    $table_name = $wpdb->prefix . SCRIBBLEMAPS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        `sid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique map serial number.',
        `mapid` varchar(255) NOT NULL COMMENT 'Map ID',
        `mapname` varchar(255) DEFAULT NULL COMMENT 'Title of the map',
        `mapdesc` varchar(255) DEFAULT NULL COMMENT 'Description of the map',
        `listtype` varchar(255) DEFAULT NULL COMMENT 'List Type of the map',
        PRIMARY KEY (`sid`)
      ) $charset_collate AUTO_INCREMENT=1 ;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
    add_option('scribble_db_version', $scribble_db_version);
}

function scribblemaps_shortcode($atts) {
    $atts = shortcode_atts(array(
        'mapid' => '',
        'width' => '400',
        'height' => '300',
        'currentview' => "",
        'marker' => 0,
        'drag' => 0,
        'maptype' => 0,
        'zoom' => 0,
        'search' => 0,
        'overlay' => 0,
        'legend' => 0), $atts, 'scribblemaps');


    $params = array();

    if (isset($atts['mapid']) && $atts['mapid'] != "")
        $params[] = "id=" . $atts['mapid'];
    if (isset($atts['width']) && $atts['width'] != "")
        $params[] = "width=" . $atts['width'];
    if (isset($atts['height']) && $atts['height'] != "")
        $params[] = "height=" . $atts['height'];

    //if(isset($atts['currentview']) && $atts['currentview'] != "") $params[] = "type=".$atts['currentview'];
    if (isset($atts['marker']) && $atts['marker'] == 1)
        $params[] = "mc=true";
    if (isset($atts['maptype']) && $atts['maptype'] == 1)
        $params[] = "mt=true";
    if (isset($atts['drag']) && $atts['drag'] == 1)
        $params[] = "d=true";
    if (isset($atts['zoom']) && $atts['zoom'] == 1)
        $params[] = "z=true";
    if (isset($atts['search']) && $atts['search'] == 1)
        $params[] = "sc=true";
    if (isset($atts['overlay']) && $atts['overlay'] == 1)
        $params[] = "ol=true";
    if (isset($atts['legend']) && $atts['legend'] == 1)
        $params[] = "l=true";


    $queryString = implode("&", $params);

    return '<div class="scribblemaps">
            <iframe width="' . $atts['width'] . '" height="' . $atts['height'] . '" frameborder="0" src="//widgets.scribblemaps.com/sm/?' . $queryString . '">
                </iframe>
                </div>';
}

add_shortcode('scribblemaps', 'scribblemaps_shortcode');

add_action('init', 'scribble_mapembed_addbuttons');

function scribble_mapembed_addbuttons() {
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return;
    }
    if (get_user_option('rich_editing') == 'true') {
        add_filter('tiny_mce_version', 'tiny_mce_version', 0);
        add_filter('mce_external_plugins', 'scribble_mapembed_plugin', 0);
        add_filter('mce_buttons', 'scribble_mapembed_button', 0);
    }
}

// Load the custom TinyMCE plugin
function scribble_mapembed_plugin($plugins) {
    $plugins['scribblemapembed'] = SCRIBBLEMAPS_URL . 'lib/tinymce3/editor_plugin.js';
    return $plugins;
}

function scribble_mapembed_button($buttons) {
    array_push($buttons, 'separator', 'scribbleMapEmbed');
    return $buttons;
}

/*
 * Code for tinymce button popup
 */
add_action('wp_ajax_editor_embed', 'editorembed_callback');

function editorembed_callback() {
    $title = "Scribble Map Embed";
    ?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
        <head>
            <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
            <title><?php bloginfo('name') ?> &rsaquo; <?php echo esc_html($title); ?> &#8212; WordPress</title>
            <?php
            wp_admin_css();
            wp_admin_css('css/colors');
            wp_admin_css('css/ie');

            $hook_suffix = '';
            if (isset($page_hook))
                $hook_suffix = "$page_hook";
            else if (isset($plugin_page))
                $hook_suffix = "$plugin_page";
            else if (isset($pagenow))
                $hook_suffix = "$pagenow";

            // do_action( 'admin_enqueue_scripts', $hook_suffix );
            do_action("admin_print_styles-$hook_suffix");
            do_action('admin_print_styles');
            do_action("admin_print_scripts-$hook_suffix");
            do_action('admin_print_scripts');
            do_action("admin_head-$hook_suffix");
            do_action('admin_head');
            ?>
            <link rel="stylesheet" href="<?php echo SCRIBBLEMAPS_URL . 'css/editor.css'; ?>?ver=2.0" type="text/css" media="screen" title="no title" charset="utf-8" />
            <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
            <script src="<?php echo SCRIBBLEMAPS_URL . 'js/scribble-map-editor.js'; ?>?ver=2.0" type="text/javascript" charset="utf-8"></script>
        </head>
        <body class="<?php echo apply_filters('admin_body_class', ''); ?>">

            <div class="wrap" id="scribble-insert">

                <h2><?php echo esc_html($title . " " . __("Tag Generator", 'scribble-maps')); ?></h2>

                <div class="note"><?php _e('Asterisk (<span class="req">*</span>) sd indicates required field', 'scribble-maps'); ?></div>
                <fieldset>
                    <legend><?php _e("Scribble Map", 'scribble-maps'); ?></legend>
                    <div id="toggleable2">
                        <div class="col1">
                            <label for="swf" title="<?php _e("Map Id of the map to be inserted", 'scribble-maps'); ?>" class="info"><?php _e("Map ID", 'scribble-maps'); ?>:</label> <span class="req">*</span>
                        </div>
                        <div class="col2">
                            <input type="text" id="mapid" name="mapid" value="" size="20" />
                        </div>
                        <div class="clear">&nbsp;</div>
                        <div class="col1">
                            <label title="<?php _e("Width &times; height (unit)", 'scribble-maps'); ?>" class="info"><?php _e("Dimensions", 'scribble-maps'); ?>:</label> <!--span class="req">*</span-->
                        </div>
                        <div class="col2">
                            <input type="text" id="width" name="width" value="400" size="5" maxlength="5" />
                            &times;
                            <input type="text" id="height" name="height" value="300" size="5" maxlength="5" />
                            <input type="hidden" id="unit" name="unit" value="px" />
                        </div>
                        <div class="clear">&nbsp;</div>
                        <div class="col1">
                            <label title="<?php _e("Marker Clustering", 'scribble-maps'); ?>" class="info"><?php _e("Marker Clustering", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="marker" name="marker"  checked="checked"/>
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Enable Drag", 'scribble-maps'); ?>" class="info"><?php _e("Enable Drag", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="drag" name="drag" checked="checked" />
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Map Type Control", 'scribble-maps'); ?>" class="info"><?php _e("Map Type Control", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="maptype" name="maptype" />
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Zoom Control", 'scribble-maps'); ?>" class="info"><?php _e("Zoom Control", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="zoom" name="zoom" checked="checked"/>
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Search Control", 'scribble-maps'); ?>" class="info"><?php _e("Search Control", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="search" name="search" />
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Overlay List", 'scribble-maps'); ?>" class="info"><?php _e("Overlay List", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="overlay" name="overlay" />
                        </div>
                        <div class="clear">&nbsp;</div>

                        <div class="col1">
                            <label title="<?php _e("Legend Control", 'scribble-maps'); ?>" class="info"><?php _e("Legend Control", 'scribble-maps'); ?>:</label>
                        </div>
                        <div class="col2">
                            <input type="checkbox" id="legend" name="legend" checked="checked" />
                        </div>
                        <div class="clear">&nbsp;</div>

                    </div>
                </fieldset>
                <div class="col1">
                    <input type="button" class="button" id="generate" name="generate" value="<?php _e("Insert", 'scribble-maps'); ?>" />
                </div>

            </div>

            <script type="text/javascript" charset="utf-8">
                // <![CDATA[
                jQuery(document).ready(function() {
                    try {
                        Scribble.Map.Generator.initialize();
                    } catch (e) {
                        throw "<?php _e("Scribble is not defined. This generator isn't going to put a Scribble Map in your code.", 'scribble-maps'); ?>";
                    }
                });
                // ]]>
            </script>
        </body>
    </html>
    <?php
    die();
}