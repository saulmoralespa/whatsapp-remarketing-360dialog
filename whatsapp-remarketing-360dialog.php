<?php
/*
Plugin Name: WhatsApp Remarketing 360dialog
Description: Send scheduled messages to inactive customers via WhatsApp API using templates
Version: 1.0.0
Author: Saul Morales Pacheco
Author URI: https://saulmoralespa.com
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SMP_WHATSAPP_REMARKETING_360DIALOG_WR_VERSION')){
    define('SMP_WHATSAPP_REMARKETING_360DIALOG_WR_VERSION', '1.0.0');
}

add_action('plugins_loaded','smp_whatsapp_remarketing_360dialog_wr_init');

function smp_whatsapp_remarketing_360dialog_wr_init(){
    if (!requeriments_smp_whatsapp_remarketing_360dialog_wr())
        return;

    smp_whatsapp_remarketing_360dialog_wr()->run_wr();
}

function smp_whatsapp_remarketing_360dialog_wr_notices($notice){
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function requeriments_smp_whatsapp_remarketing_360dialog_wr(){

    if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

    if ( ! is_plugin_active(
        'woocommerce/woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    smp_whatsapp_remarketing_360dialog_wr_notices( 'WhatsApp Remarketing requiere que se encuentre instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

    return true;
}

function smp_whatsapp_remarketing_360dialog_wr(){
    static $plugin;
    if(!isset($plugin)){
        require_once("includes/class-smp-whatsapp-remarketing-360dialog-wr-plugin.php");
        $plugin = new SMP_Whatsapp_Remarketing_360dialog_WR_Plugin(__FILE__, SMP_WHATSAPP_REMARKETING_360DIALOG_WR_VERSION);
    }
    return $plugin;
}

function activate_smp_whatsapp_remarketing_360dialog_wr(){
    global $wpdb;

    $table_name_schedulers = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_schedulers';
    $table_name_messages = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_messages';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name_schedulers (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		delivere_count INT(8) UNSIGNED NOT NULL default '0',
		read_count INT(8) UNSIGNED NOT NULL default '0',
		PRIMARY KEY  (id)
	) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    $sql = "CREATE TABLE $table_name_messages (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		phone VARCHAR(15) NOT NULL,
		id_scheduler BIGINT UNSIGNED,
		PRIMARY KEY  (id),
		FOREIGN KEY (id_scheduler) REFERENCES $table_name_schedulers (id)
	) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );


    wp_schedule_event( time(), 'twicedaily', 'smp_whatsapp_remarketing_360dialog_wr_schedule' );
}

function deactivation_smp_whatsapp_remarketing_360dialog_wr(){
    wp_clear_scheduled_hook( 'smp_whatsapp_remarketing_360dialog_wr_schedule' );
}

register_activation_hook( __FILE__, 'activate_smp_whatsapp_remarketing_360dialog_wr' );
register_deactivation_hook( __FILE__, 'deactivation_smp_whatsapp_remarketing_360dialog_wr' );