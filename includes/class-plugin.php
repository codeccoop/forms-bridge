<?php

namespace WPCT_ERP_FORMS;


abstract class Plugin extends Singleton
{
    protected $name;
    protected $textdomain;
    private $menu;
    protected $dependencies = [];

    abstract public function init();

    abstract public static function activate();

    abstract public static function deactivate();

    public function __construct()
    {
        if (empty($this->name) || empty($this->textdomain)) {
            throw new \Exception('Bad plugin initialization');
        }

        $this->load_textdomain();
        $this->check_dependencies();

        $settings = Settings::get_instance($this->textdomain);
        $this->menu = Menu::get_instance($this->name, $settings);

        add_action('init', [$this, 'init']);
    }

    public function get_menu()
    {
        return $this->menu;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_textdomain()
    {
        return $this->textdomain;
    }

    private function check_dependencies()
    {
        add_filter('wpct_dependencies_check', function ($dependencies) {
            foreach ($this->dependencies as $label => $url) {
                $dependencies[$label] = $url;
            }

            return $dependencies;
        });
    }

    private function load_textdomain()
    {
        load_plugin_textdomain(
            $this->textdomain,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages',
        );
    }
}
