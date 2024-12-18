<?php

namespace FORMS_BRIDGE;

use Exception;
use ReflectionClass;
use WPCT_ABSTRACT\Singleton;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Abstract addon class to be used by addons.
 */
abstract class Addon extends Singleton
{
    /**
     * Handles addon public name.
     *
     * @var string $name
     */
    protected static $name;

    /**
     * Handles addon unique slug.
     *
     * @var string $slug
     */
    protected static $slug;

    /**
     * Handles addon custom hook class name.
     *
     * @var string $hook_class Class name.
     */
    protected static $hook_class;

    /**
     * Public singleton initializer.
     */
    public static function setup(...$args)
    {
        return self::get_instance(...$args);
    }

    /**
     * Abstract setting registration method to be overwriten by its descendants.
     *
     * @param Settings $settings Plugin's settings store instance
     */
    abstract protected function register_setting($settings);

    /**
     * Abstract setting sanitization method to be overwriten by its descendants.
     * This method will be executed before each database update on the options table.
     *
     * @param array $value Setting value.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated value.
     */
    abstract protected function sanitize_setting($value, $setting);

    /**
     * Private class constructor. Add addons scripts as dependency to the
     * plugin's scripts and setup settings hooks.
     */
    protected function construct(...$args)
    {
        if (!(static::$name && static::$slug)) {
            throw new Exception('Invalid addon registration');
        }

        $this->handle_settings();
        $this->admin_scripts();
    }

    /**
     * Addon setting getter.
     */
    protected function setting()
    {
        return apply_filters('forms_bridge_setting', null, static::$slug);
    }

    /**
     * Addon's custom form hooks getter.
     *
     * @param int|null $form_id Target form ID.
     *
     * @return array Addon custom form hooks filtereds by form id.
     */
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

    /**
     * Settings hooks interceptors to register on the plugin's settings store
     * the addon setting.
     */
    private function handle_settings()
    {
        // Add addon setting name on the settings store.
        add_filter(
            'wpct_rest_settings',
            function ($settings, $group) {
                if ($group !== Forms_Bridge::slug()) {
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

        // Register the addon setting
        add_action(
            'wpct_register_settings',
            function ($group, $settings) {
                if ($group === Forms_Bridge::slug()) {
                    $this->register_setting($settings);
                }
            },
            10,
            2
        );

        // Sanitize the addon setting before updates
        add_filter(
            'wpct_sanitize_setting',
            function ($value, $setting) {
                return $this->_sanitize_setting($value, $setting);
            },
            10,
            2
        );
    }

    /**
     * Enqueue addon scripts as wordpress scripts and shifts it
     * as dependency to the forms bridge admin script.
     */
    private function admin_scripts()
    {
        add_action(
            'admin_enqueue_scripts',
            function ($admin_page) {
                if ('settings_page_' . Forms_Bridge::slug() !== $admin_page) {
                    return;
                }

                $reflector = new ReflectionClass(static::class);
                $__FILE__ = $reflector->getFileName();

                $script_name = Forms_Bridge::slug() . '-' . static::$slug;
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

    /**
     * Middelware to the addon sanitization method to filter out of domain
     * setting updates.
     */
    private function _sanitize_setting($value, $setting)
    {
        if (
            $setting->full_name() !==
            Forms_Bridge::slug() . '_' . static::$slug
        ) {
            return $value;
        }

        return $this->sanitize_setting($value, $setting);
    }
}
