<?php

namespace WPCT_ERP_FORMS\Abstract;

use WPCT_ERP_FORMS\Menu;
use WPCT_ERP_FORMS\Settings;

abstract class Plugin extends Singleton
{
    protected $name;
    protected $index;
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

        $this->index = dirname(__FILE__, 2) . '/' . $this->index;

        $this->check_dependencies();

        $settings = Settings::get_instance($this->textdomain);
        $this->menu = Menu::get_instance($this->name, $settings);

        add_action('init', [$this, 'init'], 10);
        add_action('init', function () {
            $this->load_textdomain();
        }, 5);

        add_filter('load_textdomain_mofile', function ($mofile, $domain) {
            return $this->load_mofile($mofile, $domain);
        }, 10, 2);
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

    public function get_data()
    {
        return apply_filters('wpct_dc_plugin_data', null, $this->index);
    }

    private function check_dependencies()
    {
        add_filter('wpct_dc_dependencies', function ($dependencies) {
            foreach ($this->dependencies as $label => $url) {
                $dependencies[$label] = $url;
            }

            return $dependencies;
        });
    }

    private function load_textdomain()
    {
        $data = $this->get_data();
        $domain_path = isset($data['DomainPath']) && !empty($data['DomainPath']) ? $data['DomainPath'] : '/languages';

        load_plugin_textdomain(
            $this->textdomain,
            false,
            dirname($this->index) . $domain_path,
        );
    }

    private function load_mofile($mofile, $domain)
    {
        $data = $this->get_data();
        $domain_path = isset($data['DomainPath']) && !empty($data['DomainPath']) ? $data['DomainPath'] : '/languages';

        if ($domain === $this->textdomain && strpos($mofile, WP_LANG_DIR . '/plugins/') === false) {
            $locale = apply_filters('plugin_locale', determine_locale(), $domain);
            $mofile = dirname($this->index) . $domain_path . '/' . $domain . '-' . $locale . '.mo';
        }

        return $mofile;
    }
}
