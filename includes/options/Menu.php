<?php

namespace WPCT_ERP_FORMS\Options;

class Menu
{

    private $name;
    private $settings;

    public function __construct($name, $settings)
    {
        $this->name = $name;
        $this->settings = $settings;
    }

    public function register()
    {
        add_options_page(
            $this->name,
            $this->name,
            'manage_options',
            $this->settings->getName(),
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
            <h1><?= $this->name ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->settings->getName());
                do_settings_sections($this->settings->getName());
                submit_button();
                ?>
            </form>
        </div>
<?php
        echo ob_get_clean();
    }

    public function getSettings()
    {
        return $this->settings;
    }
}
