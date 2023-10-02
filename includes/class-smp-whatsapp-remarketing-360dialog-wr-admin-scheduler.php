<?php

use Saulmoralespa\Dialog360\Client;

class SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Scheduler
{
    public function __call($name, $arguments)
    {
        smp_whatsapp_remarketing_360dialog_wr()->tabsMenu->page();
    }

    public function content()
    {
        $config  = get_option('smp-whatsapp-remarketing-wr', []);

        if(empty($config)) wp_redirect( admin_url( 'admin.php?page=config-whatsapp-remarketing' ) );

        $templates = [];

        try {
            $dialog360 = new Client($config['api_key']);
            $data = $dialog360->getTemplateList();
            $templates = $data->waba_templates;

            $templates = array_map(function ($template){
                $count_params = preg_match_all('/\{\{\d+\}\}/', $template->components[1]->text);
                if ($count_params === 1 && $template->language === 'es') return $template->name;
            }, $templates);
            $templates = array_filter($templates);
        }catch (Exception $exception){
        }

        $options = '';

        if (!empty($templates)){
            foreach ($templates as $template){
                $options .= <<<HTML
                    <option value="$template">$template</option>
                HTML;
            }
        }

        $rows = <<<HTML
            <tr>
                <td>
                    <input type="checkbox" class="chosen_box">
                </td>
                <td>
                    <select name="smp_whatsapp_remarketing_wr[0][template]" class="smp_whatsapp_remarketing_wr_template" required>
                        <?= $options ?>
                    </select>
                </td>
                <td>
                    <input type="number" min="1" name="smp_whatsapp_remarketing_wr[0][days]" value="" required>
                </td>
                <td>
                    0
                </td>
                <td>
                    0
                </td>
                <td>
                    0
                </td>
            </tr>
HTML;


        if (isset($config['schedulers'])){
            $rows = '';
            foreach ($config['schedulers'] as  $key => $schedulers){
                $id_scheduler = $schedulers['id_scheduler'];
                $sent_count = $this->stats($id_scheduler);
                $delivere_count = $this->stats($id_scheduler, 'delivere');
                $read_count = $this->stats($id_scheduler, 'read');
                $rows .= <<<HTML
            <tr>
                <td>
                    <input type="checkbox" class="chosen_box">
                </td>
                <td>
                    <select name="smp_whatsapp_remarketing_wr[$key][template]" class="smp_whatsapp_remarketing_wr_template" data-selected="{$schedulers['template']}" required>
                        $options
                    </select>
                </td>
                <td>
                    <input type="number" min="1" name="smp_whatsapp_remarketing_wr[$key][days]" value="{$schedulers['days']}" required>
                </td>
                <td>
                    $sent_count
                </td>
                <td>
                    $delivere_count
                </td>
                <td>
                    $read_count
                </td>
            </tr>
HTML;
            }
        }
        ?>
        <div class="wrap about-wrap">
            <h1><?php _e( 'Programar mensajes'); ?> </h1>
            <form id="smp-whatsapp-remarketing-wr-scheduler">
                <table class="widefat">
                    <thead>
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox"/>
                            </label>
                        </th>
                        <th>Plantilla</th>
                        <th># de días</th>
                        <th># Enviados</th>
                        <th># Entregados</th>
                        <th># Leídos</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?= $rows; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><button type="button" class="button-secondary add">Añadir</button></td>
                            <td><button type="button" class="button-secondary remove">Borrar selecionados</button></td>
                        </tr>
                    </tfoot>
                </table>
                <?php wp_nonce_field( "smp_whatsapp_remarketing_wr_scheduler", "smp_whatsapp_remarketing_wr_scheduler" ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function stats($id_scheduler, $status = 'sent'){
        global $wpdb;
        $table_name_messages = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_messages';
        $table_name_schedulers = $wpdb->prefix . 'smp_whatsapp_remarketing_wr_schedulers';

        if ($status === 'read'){
            $query = $wpdb->prepare(
                <<<SQL
                SELECT read_count as count from $table_name_schedulers WHERE id=%d
                SQL,
                $id_scheduler
            );
        }elseif ($status === 'delivere'){
            $query = $wpdb->prepare(
                <<<SQL
                SELECT delivere_count as count from $table_name_schedulers WHERE id=%d
                SQL,
                $id_scheduler
            );
        }else{
            $query = $wpdb->prepare(
                <<<SQL
                SELECT COUNT(*) as count from $table_name_messages WHERE id_scheduler=%d
                SQL,
                $id_scheduler
            );
        }


        $result = $wpdb->get_row($query, ARRAY_A);

        if(empty($result)) return 0;

        return $result['count'];

    }
}