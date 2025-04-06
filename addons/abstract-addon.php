<?php

namespace FORMS_BRIDGE;

use Exception;
use ReflectionClass;
use WPCT_ABSTRACT\Singleton;

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
     * Handles addon's API name.
     *
     * @var string
     */
    protected static $api;

    /**
     * Handles addon's custom bridge class name.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Form_Bridge';

    /**
     * Handles addon's custom bridge template class name.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Form_Bridge_Template';

    protected static $default_config = [
        'bridges' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'name' => ['type' => 'string'],
                    'form_id' => ['type' => 'string'],
                    'mutations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'from' => ['type' => 'string'],
                                    'to' => ['type' => 'string'],
                                    'cast' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'boolean',
                                            'string',
                                            'integer',
                                            'number',
                                            'json',
                                            'csv',
                                            'concat',
                                            'join',
                                            'inherit',
                                            'copy',
                                            'null',
                                        ],
                                    ],
                                ],
                                'additionalProperties' => false,
                                'required' => ['from', 'to', 'cast'],
                            ],
                        ],
                    ],
                    'template' => ['type' => 'string'],
                    'workflow' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'is_valid' => ['type' => 'boolean'],
                ],
                'required' => [
                    'name',
                    'form_id',
                    'mutations',
                    'workflow',
                    'is_valid',
                ],
            ],
        ],
    ];

    protected static function merge_setting_config($config)
    {
        return forms_bridge_merge_object($config, self::$default_config);
    }

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
        $state = (array) get_option(self::registry, ['rest-api' => true]);
        $addons_dir = dirname(__FILE__);
        $addons = array_diff(scandir($addons_dir), ['.', '..']);

        $registry = [];
        foreach ($addons as $addon) {
            $addon_dir = "{$addons_dir}/{$addon}";
            if (!is_dir($addon_dir)) {
                continue;
            }

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
            $addon_dir = dirname(__FILE__) . "/{$addon}";

            if ($enabled) {
                require_once "{$addon_dir}/{$addon}.php";

                if (is_dir("{$addon_dir}/mu")) {
                    self::autoload_dir("{$addon}/mu", ['php']);
                }
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

        add_filter(
            "option_{$general_setting}",
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                return array_merge($value, ['addons' => self::registry()]);
            },
            10,
            1
        );

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
     * Common bridge validation method.
     *
     * @param array $bridge Bridge data.
     * @param array $uniques Carry with already validated unique bridge names.
     *
     * @return array Validated and sanitized bridge data.
     */
    protected static function validate_bridge($bridge, &$uniques = [])
    {
        if (empty($bridge['name'])) {
            return;
        }

        if (in_array($bridge['name'], $uniques)) {
            return;
        } else {
            $uniques[] = $bridge['name'];
        }

        static $forms;
        if (empty($forms)) {
            $forms = apply_filters('forms_bridge_forms', []);
        }

        $form = null;
        foreach ($forms as $_form) {
            if ($_form['_id'] === $bridge['form_id']) {
                $form = $_form;
            }
        }

        if (empty($form)) {
            $bridge['form_id'] = '';
        }

        $bridge['workflow'] = array_map(
            'sanitize_text_field',
            (array) ($bridge['workflow'] ?? [])
        );

        $mutations = [];
        foreach ((array) ($bridge['mutations'] ?? []) as $mappers) {
            $mappers = array_filter($mappers, static function ($mapper) {
                extract($mapper);

                if (empty($from) || empty($to) || empty($cast)) {
                    return;
                }

                if (
                    !JSON_Finger::validate($from) ||
                    !JSON_Finger::validate($to)
                ) {
                    return;
                }

                return true;
            });

            $mutations[] = array_values($mappers);
        }

        $bridge['mutations'] = array_slice(
            $mutations,
            0,
            count($bridge['workflow']) + 1
        );

        for ($i = 0; $i <= count($bridge['workflow']); $i++) {
            $bridge['mutations'][$i] = $bridge['mutations'][$i] ?? [];
        }

        $bridge['is_valid'] = !empty($bridge['form_id']);
        return $bridge;
    }

    /**
     * Private class constructor. Add addons scripts as dependency to the
     * plugin's scripts and setup settings hooks.
     */
    protected function construct(...$args)
    {
        if (!(static::$name && static::$api)) {
            throw new Exception('Invalid addon registration');
        }

        add_action(
            'init',
            static function () {
                static::load_templates();
                static::load_workflow_jobs();
            },
            10,
            0
        );

        static::handle_settings();
        static::admin_scripts();

        add_filter(
            'forms_bridge_bridges',
            static function ($bridges, $form_id = null, $api = null) {
                if ($api && $api !== static::$api) {
                    return $bridges;
                }

                if (!wp_is_numeric_array($bridges)) {
                    $bridges = [];
                }

                return array_merge($bridges, static::bridges($form_id));
            },
            10,
            3
        );
    }

    /**
     * Addon's setting name getter.
     *
     * @return string
     */
    final protected static function setting_name()
    {
        return Forms_Bridge::slug() . '_' . static::$api;
    }

    /**
     * Addon setting getter.
     *
     * @return Setting|null Setting instance.
     */
    final protected static function setting()
    {
        return Forms_Bridge::setting(static::$api);
    }

    /**
     * Adds addons' bridges to the available bridges list.
     *
     * @param int|string|null $form_id Target form ID. This ID should include the integration prefix if there
     * is more than one active integration.
     *
     * @return array List with available bridges.
     */
    private static function bridges($form_id)
    {
        $integrations = array_keys(Integration::integrations());

        if ($form_id) {
            if (preg_match('/^(\w+):(\d+)$/', $form_id, $matches)) {
                [, $integration, $form_id] = $matches;
                $form_id = (int) $form_id;
            } elseif (count($integrations) > 1) {
                _doing_it_wrong(
                    'forms_bridge_bridges',
                    __(
                        '$form_id param should include the integration prefix if there is more than one integration active',
                        'forms-bridge'
                    ),
                    '2.3.0'
                );

                return [];
            } else {
                $integration = array_pop($integrations);
                $form_id = (int) $form_id;
            }

            $form_id = "{$integration}:{$form_id}";
        } else {
            $form_id = null;
        }

        $bridges = static::setting()->bridges ?: [];

        return array_map(
            static function ($bridge_data) {
                return new static::$bridge_class($bridge_data, static::$api);
            },
            array_filter($bridges, static function ($bridge_data) use (
                $form_id
            ) {
                return $form_id === null ||
                    $bridge_data['form_id'] === $form_id;
            })
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
                return static::do_validation($data, $setting);
            },
            11,
            2
        );

        add_filter(
            'wpct_setting_default',
            static function ($default, $name) {
                if ($name !== static::setting_name()) {
                    return $default;
                }

                $templates = apply_filters(
                    'forms_bridge_templates',
                    [],
                    static::$api
                );
                $workflow_jobs = apply_filters(
                    'forms_bridgr_workflow_jobs',
                    [],
                    static::$api
                );

                return array_merge($default, [
                    'templates' => array_map(function ($template) {
                        return [
                            'title' => $template->title,
                            'name' => $template->name,
                        ];
                    }, $templates),
                    'workflow_jobs' => array_map(function ($job) {
                        return [
                            'title' => $job->title,
                            'name' => $job->name,
                        ];
                    }, $workflow_jobs),
                ]);
            },
            10,
            2
        );

        add_filter(
            'option_' . static::setting_name(),
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                $templates = apply_filters(
                    'forms_bridge_templates',
                    [],
                    static::$api
                );
                $workflow_jobs = apply_filters(
                    'forms_bridge_workflow_jobs',
                    [],
                    static::$api
                );

                return array_merge($value, [
                    'templates' => array_map(function ($template) {
                        return [
                            'title' => $template->title,
                            'name' => $template->name,
                        ];
                    }, $templates),
                    'workflow_jobs' => array_map(function ($job) {
                        return [
                            'title' => $job->title,
                            'name' => $job->name,
                        ];
                    }, $workflow_jobs),
                ]);
            },
            10,
            1
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

                $script_name = Forms_Bridge::slug() . '-' . static::$api;
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

                wp_set_script_translations(
                    $script_name,
                    Forms_Bridge::slug(),
                    Forms_Bridge::path() . 'languages'
                );

                add_filter('forms_bridge_admin_script_deps', static function (
                    $deps
                ) use ($script_name) {
                    return array_merge($deps, [$script_name]);
                });
            },
            9,
            1
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
        if ($setting->full_name() !== static::setting_name()) {
            return $data;
        }

        unset($data['templates']);
        unset($data['workflow_jobs']);

        return static::validate_setting($data, $setting);
    }

    /**
     * Autoload config files from a given addon's directory. Used to load
     * template and workflow job config files.
     *
     * @param string $dirname Name of the target directory.
     *
     * @return array Array with data from files.
     */
    private static function autoload_dir(
        $dirname,
        $extensions = ['php', 'json']
    ) {
        $__FILE__ = (new ReflectionClass(static::class))->getFileName();
        $dir = dirname($__FILE__) . '/' . $dirname;

        if (!is_dir($dir)) {
            $res = mkdir($dir, 755, true);
            if (!$res) {
                return [];
            }
        }

        if (!is_readable($dir)) {
            return [];
        }

        $files = [];
        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $file_path = $dir . '/' . $file;

            if (is_file($file_path) && is_readable($file_path)) {
                $files[] = $file_path;
            }
        }

        $loaded = [];
        foreach ($files as $file_path) {
            if (!is_file($file_path) || !is_readable($file_path)) {
                continue;
            }

            $file = basename($file_path);
            $name = pathinfo($file)['filename'];
            $ext = pathinfo($file)['extension'] ?? null;

            if (!in_array($ext, $extensions)) {
                continue;
            }

            $data = null;
            if ($ext === 'php') {
                $data = include_once $file_path;
            } elseif ($ext === 'json') {
                $content = file_get_contents($file_path);
                $data = json_decode($content, true);
            }

            if (is_array($data)) {
                $loaded[] = [
                    'name' => $name,
                    'data' => $data,
                    'api' => static::$api,
                ];
            }
        }

        return $loaded;
    }

    /**
     * Loads addon's bridge templates.
     */
    private static function load_templates()
    {
        $templates = self::autoload_dir('templates');
        $templates = apply_filters(
            'forms_bridge_load_templates',
            $templates,
            static::$api
        );

        foreach ($templates as $template) {
            new static::$bridge_template_class(
                $template['name'],
                $template['data'],
                static::$api
            );
        }
    }

    /**
     * Loads addon's workflow jobs.
     */
    private static function load_workflow_jobs()
    {
        $jobs = self::autoload_dir('jobs');
        $jobs = apply_filters(
            'forms_bridge_load_workflow_jobs',
            $jobs,
            static::$api
        );

        foreach ($jobs as $job) {
            new Workflow_Job($job['name'], $job['data'], static::$api);
        }
    }
}
