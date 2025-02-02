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
     * @var string
     */
    private const registry = 'forms_bridge_addons';

    /**
     * Handles addon public name.
     *
     * @var string
     */
    protected static $name;

    /**
     * Handles addon unique slug.
     *
     * @var string
     */
    protected static $slug;

    /**
     * Handles addon custom hook class name.
     *
     * @var string
     */
    protected static $hook_class;

    /**
     * Public singleton initializer.
     */
    final public static function setup(...$args)
    {
        return static::get_instance(...$args);
    }

    /**
     * Public addons registry getter.
     *
     * @return array Addons registry state.
     */
    final public static function registry()
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
    final public static function load()
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
            'wpct_validate_setting',
            static function ($data, $setting) use ($general_setting) {
                if ($setting->full_name() !== $general_setting) {
                    return $data;
                }

                self::update_registry((array) $data['addons']);
                unset($data['addons']);

                return $data;
            },
            9,
            2
        );
    }

    /**
     * Abstract setting registration method to be overwriten by its descendants.
     */
    abstract protected static function setting_config();

    /**
     * Abstract setting validation method to be overwriten by its descendants.
     * This method will be executed before each database update on the options table.
     *
     * @param array $data Setting value.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated value.
     */
    abstract protected static function validate_setting($data, $setting);

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

        add_filter(
            'forms_bridge_form_hooks',
            static function ($form_hooks, $form_id = null) {
                return self::form_hooks($form_hooks, $form_id);
            },
            9,
            2
        );
    }

    /**
     * Addon templates getter.
     *
     * @todo Define abstract template and implementations.
     *
     * @return array List with addon template instances.
     */
    final protected static function templates()
    {
        return apply_filters('forms_bridge_addon_templates', [], static::$slug);
    }

    /**
     * Addon's setting name getter.
     *
     * @return string Setting name.
     */
    final protected static function setting_name()
    {
        return Forms_Bridge::slug() . '_' . static::$slug;
    }

    /**
     * Addon setting getter.
     */
    final protected static function setting()
    {
        return Forms_Bridge::setting(static::$slug);
    }

    /**
     * Adds addons' form hooks to the available hooks.
     *
     * @param array $form_hooks List with available form hooks.
     * @param int|null $form_id Target form ID.
     *
     * @return array List with available form hooks.
     */
    private static function form_hooks($form_hooks, $form_id = null)
    {
        if (!is_list($form_hooks)) {
            $form_hooks = [];
        }

        return array_merge(
            $form_hooks,
            array_map(
                static function ($hook_data) {
                    return new static::$hook_class($hook_data);
                },
                array_filter(
                    (array) static::setting()->form_hooks,
                    static function ($hook_data) use ($form_id) {
                        return $form_id !== null
                            ? $hook_data['form_id'] === $form_id
                            : true;
                    }
                )
            )
        );
    }

    /**
     * Settings hooks interceptors to register on the plugin's settings store
     * the addon setting.
     */
    private static function handle_settings()
    {
        add_filter(
            'wpct_settings_config',
            static function ($config, $group) {
                if ($group !== Forms_Bridge::slug()) {
                    return $config;
                }

                return array_merge($config, [static::setting_config()]);
            },
            10,
            2
        );

        // Validate the addon setting before updates
        add_filter(
            'wpct_validate_setting',
            static function ($data, $setting) {
                return self::do_validation($data, $setting);
            },
            10,
            2
        );

        add_filter(
            'wpct_setting_default',
            static function ($default, $name) {
                if ($name !== self::setting_name()) {
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
            'option_' . self::setting_name(),
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
                    [
                        'react',
                        'react-jsx-runtime',
                        'wp-api-fetch',
                        'wp-components',
                        'wp-dom-ready',
                        'wp-element',
                        'wp-i18n',
                        'wp-api',
                    ],
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
     * Middelware to the addon settings validation method to filter out of domain
     * setting updates.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    private static function do_validation($data, $setting)
    {
        if ($setting->full_name() !== self::setting_name()) {
            return $data;
        }

        unset($data['templates']);
        return static::validate_setting($data, $setting);
    }

    /**
     * Loads addon templates.
     */
    private static function load_templates()
    {
        $__FILE__ = (new ReflectionClass(static::class))->getFileName();
        $dir = dirname($__FILE__) . '/templates';
        if (!is_dir($dir)) {
            $result = mkdir($dir);
            if (!$result) {
                return;
            }
        }

        if (!is_readable($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            include_once $file;
        }
    }
}
