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
     * Handles addon's registry option name.
     *
     * @var string registry
     */
    private const registry = 'forms_bridge_addons';

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
        return static::get_instance(...$args);
    }

    /**
     * Public addons registry getter.
     *
     * @return array Addons registry state.
     */
    public static function registry()
    {
        $state = (array) get_option(self::registry, []);
        $addons_dir = dirname(__FILE__);
        $addons = array_diff(scandir($addons_dir), ['.', '..']);
        $registry = [];
        foreach ($addons as $addon) {
            $addon_dir = "{$addons_dir}/{$addon}";
            $index = "{$addon_dir}/{$addon}.php";
            if (is_file($index)) {
                $registry[$addon] = isset($state[$addon])
                    ? (bool) $state[$addon]
                    : false;
            }
        }

        return $registry;
    }

    /**
     * Updates the addons' registry state.
     *
     * @param array $value Plugin's general setting data.
     */
    private static function update_registry($addons = [])
    {
        $registry = self::registry();
        foreach ($addons as $addon => $enabled) {
            if (!isset($registry[$addon])) {
                continue;
            }

            $registry[$addon] = (bool) $enabled;
        }

        update_option(self::registry, $registry);
    }

    /**
     * Public addons loader.
     */
    public static function load()
    {
        $registry = self::registry();
        foreach ($registry as $addon => $enabled) {
            if ($enabled) {
                require_once dirname(__FILE__) . "/{$addon}/{$addon}.php";
            }
        }

        $general_setting = Forms_Bridge::slug() . '_general';
        add_filter(
            'wpct_setting_default',
            static function ($default, $name) use ($general_setting) {
                if ($name !== $general_setting) {
                    return $default;
                }

                return array_merge($default, ['addons' => self::registry()]);
            },
            10,
            2
        );

        add_filter("option_{$general_setting}", static function ($value) {
            return array_merge($value, ['addons' => self::registry()]);
        });

        add_filter(
            'pre_update_option',
            function ($value, $option) use ($general_setting) {
                if ($option !== $general_setting) {
                    return $value;
                }

                self::update_registry((array) $value['addons']);
                unset($value['addons']);

                return $value;
            },
            9,
            2
        );
    }

    /**
     * Abstract setting registration method to be overwriten by its descendants.
     *
     * @param Settings $settings Plugin's settings store instance
     */
    abstract protected static function register_setting($settings);

    /**
     * Abstract setting sanitization method to be overwriten by its descendants.
     * This method will be executed before each database update on the options table.
     *
     * @param array $value Setting value.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated value.
     */
    abstract protected static function sanitize_setting($value, $setting);

    /**
     * Private class constructor. Add addons scripts as dependency to the
     * plugin's scripts and setup settings hooks.
     */
    protected function construct(...$args)
    {
        if (!(static::$name && static::$slug)) {
            throw new Exception('Invalid addon registration');
        }

        self::load_templates();
        self::handle_settings();
        self::admin_scripts();

        // Add addon templates to the plugin's form hooks registry.
        add_filter(
            'forms_bridge_form_hooks',
            function ($form_hooks, $integration, $form_id = null) {
                return self::form_hooks($form_hooks, $integration, $form_id);
            },
            9,
            3
        );
    }

    /**
     * Addon templates getter.
     *
     * @todo Define abstract template and implementations.
     *
     * @return array List with addon template instances.
     */
    protected static function templates()
    {
        return apply_filters('forms_bridge_addon_templates', [], static::$slug);
    }

    protected static function setting_name()
    {
        return Forms_Bridge::slug() . '_' . static::$slug;
    }

    /**
     * Addon setting getter.
     */
    protected static function setting()
    {
        return Forms_Bridge::setting(static::$slug);
    }

    /**
     * Adds addons' form hooks to the available hooks.
     *
     * @param array $form_hooks List with available form hooks.
     * @param string $integration Integration slug.
     * @param int|null $form_id Target form ID.
     *
     * @return array List with available form hooks.
     */
    private static function form_hooks($form_hooks, $integration, $form_id)
    {
        if (empty($form_id)) {
            $form = apply_filters(
                'forms_bridge_form',
                null,
                $integration,
                $form_id
            );
            if (!$form) {
                return [];
            }

            $form_id = $form['id'];
        }

        $_id = "{$integration}:{$form_id}";

        if (!is_list($form_hooks)) {
            $form_hooks = [];
        }

        return array_merge(
            $form_hooks,
            array_map(
                static function ($hook_data) {
                    return new static::$hook_class($hook_data);
                },
                array_filter(static::setting()->form_hooks, static function (
                    $hook_data
                ) use ($_id) {
                    return $hook_data['form_id'] === $_id;
                })
            )
        );
    }

    /**
     * Settings hooks interceptors to register on the plugin's settings store
     * the addon setting.
     */
    private static function handle_settings()
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
            static function ($group, $settings) {
                if ($group === Forms_Bridge::slug()) {
                    static::register_setting($settings);
                }
            },
            10,
            2
        );

        // Sanitize the addon setting before updates
        add_filter(
            'wpct_sanitize_setting',
            function ($value, $setting) {
                return self::do_sanitize($value, $setting);
            },
            10,
            2
        );

        $plugin_slug = Forms_Bridge::slug();
        $addon_slug = static::$slug;
        add_filter(
            'wpct_setting_default',
            static function ($default, $name) use ($plugin_slug, $addon_slug) {
                if ($name !== $plugin_slug . '_' . $addon_slug) {
                    return $default;
                }

                return array_merge($default, [
                    'templates' => static::templates(),
                ]);
            },
            10,
            2
        );

        add_filter(
            "option_{$plugin_slug}_{$addon_slug}",
            static function ($value) {
                return array_merge($value, [
                    'templates' => static::templates(),
                ]);
            },
            10
        );
    }

    /**
     * Enqueue addon scripts as wordpress scripts and shifts it
     * as dependency to the forms bridge admin script.
     */
    private static function admin_scripts()
    {
        add_action(
            'admin_enqueue_scripts',
            static function ($admin_page) {
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
                    Forms_Bridge::version(),
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
    private static function do_sanitize($value, $setting)
    {
        if (
            $setting->full_name() !==
            Forms_Bridge::slug() . '_' . static::$slug
        ) {
            return $value;
        }

        unset($value['templates']);
        return static::sanitize_setting($value, $setting);
    }

    /**
     * Loads addon templates.
     */
    private static function load_templates()
    {
        $__FILE__ = (new ReflectionClass(static::class))->getFileName();
        $dir = dirname($__FILE__) . '/templates';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            include_once $file;
        }
    }
}
