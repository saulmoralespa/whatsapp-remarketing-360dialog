<?php

class SMP_Whatsapp_Remarketing_360dialog_WR_Admin_Tabs
{
    public function page()
    {
        if ($_GET['page'] == "config-whatsapp-remarketing" &&
            isset($_GET['tab']) && $_GET['tab'] == "scheduler"
        ) {
            $this->tab = 'scheduler';
        }elseif ($_GET['page'] == "config-whatsapp-remarketing") {
            $this->tab = 'general';
        }

        $this->page_tabs($this->tab);

        if($this->tab == 'general' ) {
            $config = smp_whatsapp_remarketing_360dialog_wr()->adminConfiguration;
            $config->content();
        }

        if($this->tab == 'scheduler') {
            $scheduler = smp_whatsapp_remarketing_360dialog_wr()->scheduler;
            $scheduler->content();
        }
    }

    public function page_tabs($current = 'general')
    {
        $tabs = array(
            'general'   => array('config-whatsapp-remarketing', __("General")),
            'scheduler'   => array('config-whatsapp-remarketing', __("Programador"))
        );
        $html =  '<h2 class="nav-tab-wrapper">';
        foreach( $tabs as $tab => $name ){
            $class = ($tab == $current) ? 'nav-tab-active' : '';
            $html .=  '<a class="nav-tab ' . $class . '" href="?page='.$name[0].'&tab=' . $tab . '">' . $name[1] . '</a>';
        }
        $html .= '</h2>';
        echo $html;
    }
}