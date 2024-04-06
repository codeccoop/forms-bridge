<?php

namespace WPCT_ERP_FORMS;

class Settings extends Abstract\Settings
{

    public function register()
    {
        $setting_name = $this->group_name . '_general';
        $this->register_setting($setting_name);

        $setting_name = $this->group_name . '_api';
        $this->register_setting($setting_name);

        $this->register_field('endpoints', $setting_name);
    }
}
