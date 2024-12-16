<?php

namespace FORMS_BRIDGE;

use Exception;
use ReflectionClass;
use WPCT_ABSTRACT\Singleton;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

abstract class Addon extends Singleton
{
    protected static $name;
    protected static $slug;
    protected static $hook_class;

    public static function setup(...$args)
    {
        return self::get_instance(...$args);
    }

    abstract protected function register_setting($settings);
    abstract protected function sanitize_setting($value, $setting);

    protected function construct(...$args)
    {
        if (!(static::$name && static::$slug)) {
            throw new Exception('Invalid addon registration');
        }

        $this->handle_settings();
        $this->admin_scripts();
    }

    protected function setting()
    {
        return apply_filters('forms_bridge_setting', null, static::$slug);
    }

    protected function form_hooks($form_id = null)
    {
        $form_hooks = array_map(function ($hook_data) {
            return new static::$hook_class($hook_data);
        }, $this->setting()->form_hooks);

        if ($form_id) {
            $form_hooks = array_values(
                array_filter($form_hooks, function ($hook) use ($form_id) {
                    return (int) $hook->form_id === (int) $form_id;
                })
            );
        }

        return $form_hooks;
    }

    private function handle_settings()
    {
        add_filter(
            'wpct_rest_settings',
            function ($settings, $group) {
                if ($group !== Forms_Bridge::$textdomain) {
                    return $settings;
                }

                if (!is_list($settings)) {
                    $settings = [];
                }

                return array_merge($settings, [static::$slug]);
            },
            20,
            2
        );

        add_action(
            'wpct_register_settings',
            function ($group, $settings) {
                if ($group === Forms_Bridge::$textdomain) {
                    $this->register_setting($settings);
                }
            },
            10,
            2
        );

        add_filter(
            'wpct_sanitize_setting',
            function ($value, $setting) {
                return $this->_sanitize_setting($value, $setting);
            },
            10,
            2
        );
    }

    private function admin_scripts()
    {
        add_action(
            'admin_enqueue_scripts',
            function ($admin_page) {
                if (
                    'settings_page_' . Forms_Bridge::$textdomain !==
                    $admin_page
                ) {
                    return;
                }

                $reflector = new ReflectionClass(static::class);
                $__FILE__ = $reflector->getFileName();

                $script_name = Forms_Bridge::$textdomain . '-' . static::$slug;
                wp_enqueue_script(
                    $script_name,
                    plugins_url('assets/addon.bundle.js', $__FILE__),
                    [],
                    FORMS_BRIDGE_VERSION,
                    ['in_footer' => true]
                );

                add_filter('forms_bridge_admin_script_deps', static function (
                    $deps
                ) use ($script_name) {
                    return array_merge($deps, [$script_name]);
                });
            },
            9
        );
    }

    private function _sanitize_setting($value, $setting)
    {
        if ($setting->full_name() !== 'forms-bridge_' . self::$slug) {
            return $value;
        }

        return $this->sanitize_setting($value, $setting);
    }
}
