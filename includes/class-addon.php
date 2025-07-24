<?php

namespace FORMS_BRIDGE;

use Error;
use HTTP_BRIDGE\Settings_Store as Http_Store;
use TypeError;
use WPCT_PLUGIN\Singleton;
use FBAPI;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Base addon class to be used by addons.
 */
class Addon extends Singleton
{
    /**
     * Handles acitve addons instance references.
     *
     * @var array<string, Addon>
     */
    private static $addons = [];

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
    public const title = '';

    /**
     * Handles addon's API name.
     *
     * @var string
     */
    public const name = '';

    /**
     * Handles addon's custom bridge class name.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Form_Bridge';

    /**
     * Handles the addon's credential class name.
     *
     * @var string
     */
    public const credential_class = '\FORMS_BRIDGE\Credential';

    /**
     * Addon's default config getter.
     *
     * @return array
     */
    public static function schema()
    {
        $bridge_schema = FBAPI::get_bridge_schema(static::name);
        $credential_schema = FBAPI::get_credential_schema(static::name);

        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => $bridge_schema,
                    'default' => [],
                ],
            ],
            'required' => ['title', 'bridges'],
        ];
    }

    /**
     * Addon's default data getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return [
            'title' => static::title,
            'bridges' => [],
        ];
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
    private static function registry()
    {
        $state = get_option(self::registry, ['rest' => true]) ?: [];
        $addons_dir = FORMS_BRIDGE_ADDONS_DIR;
        $addons = array_diff(scandir($addons_dir), ['.', '..']);

        $registry = [];
        foreach ($addons as $addon) {
            $addon_dir = "{$addons_dir}/{$addon}";
            if (!is_dir($addon_dir)) {
                continue;
            }

            $index = "{$addon_dir}/class-{$addon}-addon.php";
            if (is_file($index) && is_readable($index)) {
                $registry[$addon] = boolval($state[$addon] ?? false);
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

    final public static function addons()
    {
        $addons = [];
        foreach (self::$addons as $addon) {
            if ($addon->enabled) {
                $addons[] = $addon;
            }
        }

        return $addons;
    }

    /**
     * Addon instances getter.
     *
     * @var string $name Addon name.
     *
     * @return Addon|null
     */
    final public static function addon($name)
    {
        return self::$addons[$name] ?? null;
    }

    /**
     * Public addons loader.
     */
    final public static function load_addons()
    {
        $addons_dir = FORMS_BRIDGE_ADDONS_DIR;
        $registry = self::registry();
        foreach ($registry as $addon => $enabled) {
            require_once "{$addons_dir}/{$addon}/class-{$addon}-addon.php";

            if ($enabled) {
                self::$addons[$addon]->load();
            }
        }

        Settings_Store::ready(function ($store) {
            $store::use_getter('general', function ($data) {
                $registry = self::registry();
                $addons = [];
                foreach (self::$addons as $name => $addon) {
                    $logo_path =
                        FORMS_BRIDGE_ADDONS_DIR .
                        '/' .
                        $addon::name .
                        '/assets/logo.png';

                    if (is_file($logo_path) && is_readable($logo_path)) {
                        $logo = plugin_dir_url($logo_path) . 'logo.png';
                    } else {
                        $logo = '';
                    }

                    $addons[$name] = [
                        'name' => $name,
                        'title' => $addon::title,
                        'enabled' => $registry[$name] ?? false,
                        'logo' => $logo,
                    ];
                }

                ksort($addons);
                $addons = array_values($addons);

                $addons = apply_filters('forms_bridge_addons', $addons);
                return array_merge($data, ['addons' => $addons]);
            });

            $store::use_setter(
                'general',
                function ($data) {
                    if (!isset($data['addons']) || !is_array($data['addons'])) {
                        return $data;
                    }

                    $registry = [];
                    foreach ($data['addons'] as $addon) {
                        $registry[$addon['name']] = (bool) $addon['enabled'];
                    }

                    self::update_registry($registry);

                    unset($data['addons']);
                    return $data;
                },
                9
            );
        });
    }

    /**
     * Middelware to the addon settings validation method to filter out of domain
     * setting updates.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    private static function sanitize_setting($data)
    {
        if (!isset($data['bridges'])) {
            return $data;
        }

        $data['bridges'] = static::sanitize_bridges($data['bridges'], $data);

        return $data;
    }

    /**
     * Apply bridges setting data sanitization and validation.
     *
     * @param array $bridges Collection of bridges data.
     * @param array $setting_data Parent setting data.
     *
     * @return array
     */
    private static function sanitize_bridges($bridges, $setting_data)
    {
        $uniques = [];
        $sanitized = [];

        $schema = FBAPI::get_bridge_schema(static::name);
        foreach ($bridges as $bridge) {
            $bridge['name'] = trim($bridge['name']);
            if (in_array($bridge['name'], $uniques, true)) {
                continue;
            }

            $bridge = static::sanitize_bridge($bridge, $schema);
            if ($bridge) {
                $sanitized[] = $bridge;
                $uniques[] = $bridge['name'];
            }
        }

        return $sanitized;
    }

    /**
     * Common bridge sanitization method.
     *
     * @param array $bridge Bridge data.
     * @param array $schema Bridge schema.
     *
     * @return array
     */
    protected static function sanitize_bridge($bridge, $schema)
    {
        $backends = Http_Store::setting('general')->backends ?: [];

        foreach ($backends as $candidate) {
            if ($candidate['name'] === $bridge['backend']) {
                $backend = $candidate;
                break;
            }
        }

        if (!isset($backend)) {
            $bridge['backend'] = '';
        }

        static $forms;
        if ($forms === null) {
            $forms = FBAPI::get_forms();
        }

        foreach ($forms as $candidate) {
            if ($candidate['_id'] === $bridge['form_id']) {
                $form = $candidate;
                break;
            }
        }

        if (!isset($form)) {
            $bridge['form_id'] = '';
        }

        $bridge['mutations'] = array_slice(
            $bridge['mutations'],
            0,
            count($bridge['workflow']) + 1
        );

        for ($i = 0; $i <= count($bridge['workflow']); $i++) {
            $bridge['mutations'][$i] = $bridge['mutations'][$i] ?? [];
        }

        $bridge['is_valid'] =
            $bridge['form_id'] &&
            $bridge['backend'] &&
            $bridge['method'] &&
            $bridge['endpoint'];

        $bridge['enabled'] = boolval($bridge['enabled'] ?? true);
        return $bridge;
    }

    public $enabled = false;

    /**
     * Private class constructor. Add addons scripts as dependency to the
     * plugin's scripts and setup settings hooks.
     */
    protected function construct(...$args)
    {
        if (empty(static::name) || empty(static::title)) {
            Logger::log('Skip invalid addon registration', Logger::DEBUG);
            Logger::log(
                'Addon name and title const are required',
                Logger::ERROR
            );
            return;
        }

        self::$addons[static::name] = $this;
    }

    public function load()
    {
        add_action(
            'init',
            static function () {
                self::load_data();
            },
            5,
            0
        );

        add_filter(
            'forms_bridge_templates',
            static function ($templates, $addon = null) {
                if (!wp_is_numeric_array($templates)) {
                    $templates = [];
                }

                if ($addon && $addon !== static::name) {
                    return $templates;
                }

                foreach (static::load_templates() as $template) {
                    $templates[] = $template;
                }

                return $templates;
            },
            10,
            2
        );

        add_filter(
            'forms_bridge_jobs',
            static function ($jobs, $addon = null) {
                if (!wp_is_numeric_array($jobs)) {
                    $jobs = [];
                }

                if ($addon && $addon !== static::name) {
                    return $jobs;
                }

                foreach (static::load_jobs() as $job) {
                    $jobs[] = $job;
                }

                return $jobs;
            },
            10,
            2
        );

        add_filter(
            'forms_bridge_bridges',
            static function ($bridges, $addon = null) {
                if (!wp_is_numeric_array($bridges)) {
                    $bridges = [];
                }

                if ($addon && $addon !== static::name) {
                    return $bridges;
                }

                $setting = static::setting();
                if (!$setting) {
                    return $bridges;
                }

                foreach ($setting->bridges ?: [] as $bridge_data) {
                    $bridge_class = static::bridge_class;
                    $bridges[] = new $bridge_class($bridge_data, static::name);
                }

                return $bridges;
            },
            10,
            2
        );

        Settings_Store::register_setting(static function ($settings) {
            $schema = static::schema();
            $schema['name'] = static::name;
            $schema['default'] = static::defaults();

            $settings[] = $schema;
            return $settings;
        });

        Settings_Store::ready(static function ($store) {
            $store::use_getter(static::name, static function ($data) {
                $templates = FBAPI::get_addon_templates(static::name);
                $jobs = FBAPI::get_addon_jobs(static::name);

                return array_merge($data, [
                    'templates' => array_map(static function ($template) {
                        return [
                            'title' => $template->title,
                            'name' => $template->name,
                            'integrations' => $template->integrations,
                        ];
                    }, $templates),
                    'jobs' => array_map(static function ($job) {
                        return [
                            'title' => $job->title,
                            'name' => $job->name,
                        ];
                    }, $jobs),
                ]);
            });

            $store::use_setter(
                static::name,
                static function ($data) {
                    if (!is_array($data)) {
                        return $data;
                    }

                    unset($data['templates']);
                    unset($data['jobs']);

                    return static::sanitize_setting($data);
                },
                9
            );
        });

        $this->enabled = true;
    }

    /**
     * Addon's setting name getter.
     *
     * @return string
     */
    final protected static function setting_name()
    {
        return 'forms-bridge_' . static::name;
    }

    /**
     * Addon's setting instance getter.
     *
     * @return Setting|null Setting instance.
     */
    final protected static function setting()
    {
        return Forms_Bridge::setting(static::name);
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     *
     * @return boolean|WP_Error
     */
    public function ping($backend)
    {
        return true;
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint Target endpoint name.
     * @param string $backend Target backend name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend)
    {
        return [
            'headers' => [],
            'cookies' => [],
            'filename' => null,
            'body' => '',
            'response' => [
                'status' => 202,
                'message' => 'Accepted',
            ],
            'http_response' => [
                'data' => null,
                'headers' => null,
                'status' => null,
            ],
            'data' => [],
        ];
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $endpoint Target endpoint name.
     * @param string $backend Target backend name.
     *
     * @return array|WP_Error
     */
    public function get_endpoint_schema($endpoint, $backend)
    {
        return [];
    }

    private static function autoload_posts($post_type, $addon)
    {
        if (!in_array($post_type, ['fb-bridge-template', 'fb-job'])) {
            return [];
        }

        return get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'meta_key' => '_fb-addon',
            'meta_value' => $addon,
        ]);
    }

    /**
     * Autoload config files from a given addon's directory. Used to load
     * template and job config files.
     *
     * @param string $dir Path of the target directory.
     *
     * @return array Array with data from files.
     */
    private static function autoload_dir($dir, $extensions = ['php', 'json'])
    {
        if (!is_readable($dir) || !is_dir($dir)) {
            return [];
        }

        static $load_cache;

        $files = [];
        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $file_path = $dir . '/' . $file;

            if (is_file($file_path) && is_readable($file_path)) {
                $files[] = $file_path;
            }
        }

        $loaded = [];
        foreach ($files as $file_path) {
            $file = basename($file_path);
            $name = pathinfo($file)['filename'];
            $ext = pathinfo($file)['extension'] ?? null;

            if (!in_array($ext, $extensions)) {
                continue;
            }

            if (isset($load_cache[$file_path])) {
                $loaded[] = $load_cache[$file_path];
                continue;
            }

            $data = null;
            if ($ext === 'php') {
                $data = include_once $file_path;
            } elseif ($ext === 'json') {
                try {
                    $content = file_get_contents($file_path);
                    $data = json_decode($content, true, JSON_THROW_ON_ERROR);
                } catch (TypeError) {
                    // pass
                } catch (Error) {
                    // pass
                }
            }

            if (is_array($data)) {
                $data['name'] = $name;
                $loaded[] = $data;
                $load_cache[$file_path] = $data;
            }
        }

        return $loaded;
    }

    /**
     * Loads addon's bridge data.
     */
    private static function load_data()
    {
        $dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::name . '/data';
        self::autoload_dir($dir);
    }

    /**
     * Loads addon's bridge templates.
     *
     * @return Form_Bridge_Template[].
     */
    private static function load_templates()
    {
        $dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::name . '/templates';

        $directories = apply_filters(
            'forms_bridge_template_directories',
            [
                $dir,
                Forms_Bridge::path() . 'includes/templates',
                get_stylesheet_directory() .
                '/forms-bridge/templates/' .
                static::name,
            ],
            static::name
        );

        $templates = [];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (self::autoload_dir($dir) as $template) {
                $template['name'] = sanitize_title($template['name']);
                $templates[$template['name']] = $template;
            }
        }

        foreach (
            self::autoload_posts('fb-bridge-template', static::name)
            as $template_post
        ) {
            $template[$template->post_name] = $template_post;
        }

        $templates = array_values($templates);

        $templates = apply_filters(
            'forms_bridge_load_templates',
            $templates,
            static::name
        );

        $loaded = [];
        foreach ($templates as $template) {
            if (
                is_array($template) &&
                isset($template['data'], $template['name'])
            ) {
                $template = array_merge($template['data'], [
                    'name' => $template['name'],
                ]);
            }

            $template = new Form_Bridge_Template($template, static::name);

            if ($template->is_valid) {
                $loaded[] = $template;
            }
        }

        return $loaded;
    }

    /**
     * Addon's jobs loader.
     *
     * @return Job[]
     */
    private static function load_jobs()
    {
        $dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::name . '/jobs';

        $directories = apply_filters(
            'forms_bridge_job_directories',
            [
                $dir,
                Forms_Bridge::path() . 'includes/jobs',
                get_stylesheet_directory() .
                '/forms-bridge/jobs/' .
                static::name,
            ],
            static::name
        );

        $jobs = [];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (self::autoload_dir($dir) as $job) {
                $job['name'] = sanitize_title($job['name']);
                $jobs[$job['name']] = $job;
            }
        }

        foreach (self::autoload_posts('fb-job', static::name) as $job_post) {
            $jobs[$job_post->post_name] = $job_post;
        }

        $jobs = array_values($jobs);

        $jobs = apply_filters('forms_bridge_load_jobs', $jobs, static::name);

        $loaded = [];
        foreach ($jobs as $job) {
            if (is_array($job) && isset($job['data'], $job['name'])) {
                $job = array_merge($job['data'], ['name' => $job['name']]);
            }

            $job = new Job($job, static::name);

            if ($job->is_valid) {
                $loaded[] = $job;
            }
        }

        return $loaded;
    }

    // public static function get_api()
    // {
    //     $__FILE__ = (new ReflectionClass(static::class))->getFileName();
    //     $file = dirname($__FILE__) . '/api.php';

    //     if (!is_file($file) || !is_readable($file)) {
    //         return [];
    //     }

    //     $source = file_get_contents($file);
    //     $tokens = token_get_all($source);

    //     $functions = [];
    //     $nextStringIsFunc = false;
    //     $inClass = false;
    //     $bracesCount = 0;

    //     foreach ($tokens as $token) {
    //         switch ($token[0]) {
    //             case T_CLASS:
    //                 $inClass = true;
    //                 break;
    //             case T_FUNCTION:
    //                 if (!$inClass) {
    //                     $nextStringIsFunc = true;
    //                 }
    //                 break;

    //             case T_STRING:
    //                 if ($nextStringIsFunc) {
    //                     $nextStringIsFunc = false;
    //                     $functions[] = $token[1];
    //                 }
    //                 break;
    //             case '(':
    //             case ';':
    //                 $nextStringIsFunc = false;
    //                 break;
    //             case '{':
    //                 if ($inClass) {
    //                     $bracesCount++;
    //                 }
    //                 break;

    //             case '}':
    //                 if ($inClass) {
    //                     $bracesCount--;
    //                     if ($bracesCount === 0) {
    //                         $inClass = false;
    //                     }
    //                 }
    //                 break;
    //         }
    //     }

    //     return $functions;
    // }
}
