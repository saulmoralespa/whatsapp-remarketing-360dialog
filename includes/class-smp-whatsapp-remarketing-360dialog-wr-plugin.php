<?php

class SMP_Whatsapp_Remarketing_360dialog_WR_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public $lib_path;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
    }

    public function run_wr()
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( 'WhatsApp Remarketing can only be called once');
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                add_action('admin_notices', function() use($e) {
                    smp_whatsapp_remarketing_360dialog_wr_notices($e->getMessage());
                });
            }
        }
    }

    private function _run()
    {
        if (!class_exists('\Saulmoralespa\Dialog360'))
            require_once ($this->lib_path . 'vendor/autoload.php');
        require_once ($this->includes_path . 'clas-smp-whatsapp-remarketing-360dialog-wr-admin.php');
        require_once ($this->includes_path . 'class-smp-whatsapp-remarketing-360dialog-wr-admin-configuration.php');
        require_once ($this->includes_path . 'class-smp-whatsapp-remarketing-360dialog-wr-admin-scheduler.php');
        require_once ($this->includes_path . 'class-smp-whatsapp-remarketing-360dialog-wr-admin-tabs.php');

        $this->admin = new SMP_Whatsapp_Remarketing_360dialog_WR_Admin();
        $this->adminConfiguration = new SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Configuration();
        $this->scheduler = new SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Scheduler();
        $this->tabsMenu = new SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Tabs();

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_admin' ) );
        add_action( 'smp_whatsapp_remarketing_360dialog_wr_schedule', array($this, 'run_scheduled_messages') );
        add_action( 'wp', array($this, 'smp_whatsapp_remarketing_wr_webhook') );

        if (!wp_next_scheduled('smp_whatsapp_remarketing_360dialog_wr_schedule')) {
            wp_schedule_event( time(), 'twicedaily', 'smp_whatsapp_remarketing_360dialog_wr_schedule' );
        }

    }

    public function enqueue_scripts_admin($hook)
    {
        if($hook === 'whatsapp-remarketing_page_config-whatsapp-remarketing' || $hook === 'post.php') {
            wp_enqueue_script( 'smp-whatsapp-remarketing-wr-sweetalert2',  $this->plugin_url . 'assets/js/sweetalert2.js', array( 'jquery' ), $this->version, true );
            //wp_enqueue_script( 'smp-whatsapp-remarketing-wr-select2', $this->plugin_url . 'assets/js/select2.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'smp-whatsapp-remarketing-wr', $this->plugin_url . 'assets/js/smp-whatsapp-remarketing-wr.js', array( 'jquery' ), $this->version, true );
            //wp_enqueue_style('smp-whatsapp-remarketing-wr-select2', $this->plugin_url . 'assets/css/select2.css');
        }
    }

    public function log($message)
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $logger = new WC_Logger();
        $logger->add('smp-whatsapp-remarketing-360dialog-wr', $message);
    }

    public function run_scheduled_messages()
    {
        $config  = get_option('smp-whatsapp-remarketing-wr', []);
        $country_code = '57';
        global $wpdb;
        $table_name_messages = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_messages';
        $table_name_schedulers = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_schedulers';

        if (empty($config) || !isset($config['schedulers'])) return;

        foreach ($config['schedulers'] as  $key => $schedulers){
            $days = $schedulers['days'];
            $template = $schedulers['template'];
            $id_scheduler = $schedulers['id_scheduler'];

            $customers = $this->get_inactive_customers($days);
            if (empty($customers)) continue;

            $query = "SELECT id FROM $table_name_schedulers WHERE id='$id_scheduler'";
            $result = $wpdb->get_row($query);

            if (empty($result)){
                $wpdb->insert(
                    $table_name_schedulers,
                    array(
                        'id' => $id_scheduler
                    ),
                    array(
                        '%d'
                    )
                );
            }

            foreach ($customers as $customer){

                try {
                    $dialog360 = new \Saulmoralespa\Dialog360\Client($config['api_key']);
                    $phone = $customer['phone'];
                    $phone = preg_replace('/\s+/', '', $phone);
                    $phone = strlen($phone) === 10 ? $country_code . $phone : $phone;
                    $components = [
                        $customer['first_name']
                    ];

                    $query = "SELECT id FROM $table_name_messages WHERE id_scheduler='$id_scheduler' AND phone='$phone'";
                    $result = $wpdb->get_row($query);

                    if (!empty($result)) continue;

                    $res = $dialog360->sendTemplate($phone, $template, $components);

                    $id = $res->messages[0]->id ?? null;

                    if (!isset($id)) continue;

                    $wpdb->insert(
                        $table_name_messages,
                        array(
                            'id' => hexdec(hash("crc32", $id)),
                            'phone' => $phone,
                            'id_scheduler' => $id_scheduler
                        ),
                        array(
                            '%d',
                            '%s',
                            '%d'
                        )
                    );

                }catch (Exception $exception){
                    $this->log($exception->getMessage());
                }
            }

            unset($config['schedulers'][$key]);
        }

    }

    /**
     * @param int $days
     * @return array
     */
    public function get_inactive_customers(int $days)
    {
        global $wpdb;

        $startDate = wp_date('Y-m-d', strtotime("-$days days"));

        $query = $wpdb->prepare(
            <<<SQL
                SELECT
                    MAX( CASE WHEN pm.meta_key = '_billing_phone' THEN pm.meta_value END ) AS phone,
                    MAX( CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END )  AS first_name 
                FROM {$wpdb->prefix}posts AS p
                JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON p.ID = woi.order_id
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id
                LEFT JOIN {$wpdb->users} AS u ON p.post_author = u.ID
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed')
                AND DATE(p.post_date) = %s
                GROUP BY p.ID
            SQL,
            $startDate
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function smp_whatsapp_remarketing_wr_webhook()
    {
        if (isset($_REQUEST['action']) && $_REQUEST['action']==='sendwp_webhook') {

            $config  = get_option('smp-whatsapp-remarketing-wr', []);

            if(!isset($config['token_webhook'])) return;

            $token = $config['token_webhook'];

            if (isset($_GET['hub_mode']) &&
                isset($_GET['hub_verify_token']) &&
                ($token === $_GET['hub_verify_token'])
            ){
                wp_die($_GET['hub_challenge']);
            }

            $payload = file_get_contents('php://input');
            $response = json_decode($payload, true);

            $statuses  = $response['statuses'][0] ?? null;

            if (!isset($statuses)) return;

            $id = $statuses['id'];
            $status = $statuses['status'];

            if ($status === 'sent') return;

            $id = hexdec(hash("crc32", $id));

            global $wpdb;
            $table_name_messages = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_messages';
            $table_name_schedulers = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_schedulers';
            $column = $status === 'delivered' ? 'delivere_count' : 'read_count';

            $query = $wpdb->prepare(
                <<<SQL
                    UPDATE $table_name_schedulers AS s
                    INNER JOIN $table_name_messages as m
                    ON m.id_scheduler = s.id
                    SET $column = $column + %d
                    WHERE m.id = %d
                SQL,
                1, $id
            );

            $wpdb->query($query);

        }
    }
}