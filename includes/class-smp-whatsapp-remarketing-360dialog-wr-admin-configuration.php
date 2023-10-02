<?php

class SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Configuration
{
    public function __call($name, $arguments)
    {
        smp_whatsapp_remarketing_360dialog_wr()->tabsMenu->page();
    }

    public function content()
    {
        $config  = get_option('smp-whatsapp-remarketing-wr', []);

        ?>
        <div class="wrap about-wrap">
            <h2><?php _e('Configuraciones'); ?></h2>
            <form id="smp-whatsapp-remarketing-wr-config">
                <table>
                    <tbody>
                    <tr>
                        <th><?php echo __('Api key');?></th>
                        <td>
                            <label>
                                <input type="password" name="api_key" value="<?php if(isset($config['api_key'])) echo $config['api_key']; ?>" required>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __('Token Webhook');?></th>
                        <td>
                            <label>
                                <input type="password" name="token_webhook" value="<?php if(isset($config['token_webhook'])) echo $config['token_webhook']; ?>" required>
                            </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php wp_nonce_field( "smp_whatsapp_remarketing_wr", "smp_whatsapp_remarketing_wr" ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}