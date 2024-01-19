<?php

namespace WPCT_ERP_FORMS\Settings;

class Menu
{

    private $setting = null;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function register()
    {
        add_options_page(
            'WPCT ERP Forms',
            'WPCT ERP Forms',
            'manage_options',
            $this->setting->getName(),
            function () {
                $this->render_page();
            }
        );
    }

    private function render_page()
    {
        ob_start();
?>
        <div class="wrap">
            <h1>WPCT ERP Forms</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->setting->getName());
                do_settings_sections($this->setting->getName());
                submit_button();
                ?>
            </form>
        </div>
<?php
        echo ob_get_clean();
    }
}
