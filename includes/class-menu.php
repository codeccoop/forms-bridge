<?php

namespace WPCT_ERP_FORMS;

class Menu extends Abstract\Singleton
{
    private $name;
    private $settings;

    protected function __construct($name, $settings)
    {
        $this->name = $name;
        $this->settings = $settings;

        add_action('admin_menu', function () {
            $this->add_menu();
        });

        add_action('admin_init', function () {
            $this->settings->register();
        });
    }

    private function add_menu()
    {
        add_options_page(
            $this->name,
            $this->name,
            'manage_options',
            $this->settings->get_name(),
            function () {
                $this->render_page();
            },
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
            settings_fields($this->settings->get_name());
			do_settings_sections($this->settings->get_name());
			submit_button();
			?>
            </form>
        </div>
<?php
        $output = ob_get_clean();
        echo apply_filters('wpct_epr_forms_menu_page_content', $output);
    }

    public function get_settings()
    {
        return $this->settings;
    }
}
