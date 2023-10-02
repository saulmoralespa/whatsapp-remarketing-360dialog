<?php

use Saulmoralespa\Dialog360\Client;
class SMP_Whatsapp_Remarketing_360dialog_WR_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action( 'wp_ajax_smp_whatsapp_remarketing_wr_config', array($this,'smp_whatsapp_remarketing_wr_config'));
        add_action( 'wp_ajax_smp_whatsapp_remarketing_wr_scheduler', array($this,'smp_whatsapp_remarketing_wr_scheduler'));
    }

    public function menu()
    {
        $config = smp_whatsapp_remarketing_360dialog_wr()->adminConfiguration;
        add_menu_page('WhatsApp Remarketing', 'WhatsApp Remarketing', 'manage_options', 'whatsapp-remarketing', array($this,'whatsapp-remarketing'), 'dashicons-admin-tools');
        add_submenu_page('whatsapp-remarketing', __('Configuración'), __('Configuración'), 'manage_options', 'config-whatsapp-remarketing', array($config,'configInit'));
        remove_submenu_page('whatsapp-remarketing', 'whatsapp-remarketing');
    }

    public function smp_whatsapp_remarketing_wr_config()
    {
        if ( ! wp_verify_nonce(  $_POST['smp_whatsapp_remarketing_wr'], 'smp_whatsapp_remarketing_wr' ) ||
            !isset($_POST['api_key'])
        )
            return;

        $config = [];
        $config['api_key'] = sanitize_text_field($_POST['api_key']);

        $status = true;

        try {
            $dialog360 = new Client($config['api_key']);
            $dialog360->getWebhook();
        }catch (Exception $exception){
            $status = false;
            $config = [];
        }

        update_option('smp-whatsapp-remarketing-wr', $config);

        wp_send_json(['status' => $status]);
    }

    public function smp_whatsapp_remarketing_wr_scheduler()
    {
        if ( ! wp_verify_nonce(  $_POST['smp_whatsapp_remarketing_wr_scheduler'], 'smp_whatsapp_remarketing_wr_scheduler' )
        )
            return;

        $config = get_option('smp-whatsapp-remarketing-wr', []);
        global $wpdb;
        $table_name_messages = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_messages';
        $table_name_schedulers = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_schedulers';

        if (isset($_POST['smp_whatsapp_remarketing_wr'])){

            $id_schedulers = [];
            $schedulers = [];

            foreach ($_POST['smp_whatsapp_remarketing_wr'] as $key => $scheduler){
                $template = sanitize_text_field( $scheduler['template'] );
                $days = sanitize_text_field( $scheduler['days'] );
                $schedulers['schedulers'][$key]['template'] = $template;
                $schedulers['schedulers'][$key]['days'] = $days;
                $template_days = "{$template}_{$days}";
                $id_scheduler = hexdec(hash("crc32", $template_days));
                $schedulers['schedulers'][$key]['id_scheduler'] = $id_scheduler;
                $id_schedulers[] = $id_scheduler;
            }

            $config['schedulers'] = $schedulers['schedulers'];

            $query = $wpdb->prepare(
                <<<SQL
                    DELETE m, s FROM $table_name_messages as m 
                    JOIN $table_name_schedulers as s 
                    ON m.id_scheduler = s.id WHERE m.id_scheduler NOT IN (%s);
                SQL,
                implode(", ", $id_schedulers)
            );

            $wpdb->query($query);

        }else{
            unset($config['schedulers']);
        }

        update_option('smp-whatsapp-remarketing-wr', $config);

    }
}