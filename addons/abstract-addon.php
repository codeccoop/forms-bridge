<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Backend;
use TypeError;
use WPCT_PLUGIN\Singleton;
use FBAPI;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Abstract addon class to be used by addons.
 */
abstract class Addon extends Singleton
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
    public const title = 'Abstract Addon';

    /**
     * Handles addon's API name.
     *
     * @var string
     */
    public const name = 'forms-bridge';

    /**
     * Handles addon's custom bridge class name.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Form_Bridge';

    /**
     * Addon's default config getter.
     *
     * @return array
     */
    public static function schema()
    {
        $bridge_class = static::bridge_class;

        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'logo' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => $bridge_class::schema(),
                    'default' => [],
                ],
                'credentials' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'required' => ['name'],
                    ],
                    'default' => [],
                ],
            ],
            'required' => ['title', 'bridges'],
        ];
    }

    protected static function defaults()
    {
        $defaults = [
            'title' => static::title,
            'bridges' => [],
        ];

        return $defaults;
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
        $state = get_option(self::registry, ['rest-api' => true]) ?: [];
        $addons_dir = FORMS_BRIDGE_ADDONS_DIR;
        $addons = array_diff(scandir($addons_dir), ['.', '..']);

        $registry = [];
        foreach ($addons as $addon) {
            $addon_dir = "{$addons_dir}/{$addon}";
            if (!is_dir($addon_dir)) {
                continue;
            }

            $index = "{$addon_dir}/{$addon}.php";
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
            require_once "{$addons_dir}/{$addon}/{$addon}.php";

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
    protected static function sanitize_setting($data)
    {
        return $data;
    }

    /**
     * Common bridge validation method.
     *
     * @param array $bridge Bridge data.
     * @param array $uniques Carry with already validated unique bridge names.
     *
     * @return array Validated and sanitized bridge data.
     */
    protected static function sanitize_bridge($bridge, &$uniques = [])
    {
        if (empty($bridge['name'])) {
            return;
        }

        $bridge['name'] = trim($bridge['name']);
        if (in_array($bridge['name'], $uniques)) {
            return;
        } else {
            $uniques[] = $bridge['name'];
        }

        $backends =
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: [];

        $backend = null;
        foreach ($backends as $candidate) {
            if ($candidate['name'] === $bridge['backend']) {
                $backend = $candidate;
                break;
            }
        }

        if (empty($backend)) {
            $bridge['backend'] = '';
        }

        static $forms;
        if (empty($forms)) {
            $forms = FBAPI::get_forms();
        }

        $form = null;
        foreach ($forms as $candidate) {
            if ($candidate['_id'] === $bridge['form_id']) {
                $form = $candidate;
                break;
            }
        }

        if (empty($form)) {
            $bridge['form_id'] = '';
        }

        $custom_fields = [];
        foreach ($bridge['custom_fields'] ?? [] as $field) {
            if (!JSON_Finger::validate($field['name'])) {
                continue;
            }

            if (
                empty($field['value']) &&
                $field['value'] !== '0' &&
                $field['value'] !== 0
            ) {
                continue;
            }

            $custom_fields[] = [
                'name' => strval($field['name']),
                'value' => strval($field['value']),
            ];
        }

        $bridge['custom_fields'] = $custom_fields;

        $bridge['workflow'] = array_map(
            'sanitize_text_field',
            (array) ($bridge['workflow'] ?? [])
        );

        $mutations = [];
        foreach ($bridge['mutations'] ?? [] as $mappers) {
            $valids = [];
            foreach ($mappers as $mapper) {
                if (!JSON_Finger::validate($mapper['from'])) {
                    continue;
                }

                if (!JSON_Finger::validate($mapper['to'])) {
                    continue;
                }

                if (empty($mapper['cast'])) {
                    continue;
                }

                $valids[] = $mapper;
            }

            $mutations[] = array_values($valids);
        }

        $bridge['mutations'] = array_slice(
            $mutations,
            0,
            count($bridge['workflow']) + 1
        );

        for ($i = 0; $i <= count($bridge['workflow']); $i++) {
            $bridge['mutations'][$i] = $bridge['mutations'][$i] ?? [];
        }

        $bridge['is_valid'] =
            !empty($bridge['form_id']) && !empty($bridge['backend']);

        $bridge['enabled'] = boolval($bridge['enabled'] ?? true);
        return $bridge;
    }

    /**
     * Common credential validation method.
     *
     * @param array $credential Credential data.
     * @param array $fields Credential required fields.
     * @param array $uniques Carry with already validated unique credential names.
     *
     * @return array Validated and sanitized credential data.
     */
    protected static function sanitize_credential(
        $credential,
        $fields,
        &$uniques = []
    ) {
        if (empty($credential['name'])) {
            return;
        }

        $credential['name'] = trim($credential['name']);
        if (in_array($credential['name'], $uniques)) {
            return;
        } else {
            $uniques[] = $credential['name'];
        }

        foreach ($fields as $field) {
            if (empty($credential[$field])) {
                $credential[$field] = '';
            } else {
                $credential[$field] = strval($credential[$field]);
            }
        }

        return $credential;
    }

    public $enabled = false;

    /**
     * Private class constructor. Add addons scripts as dependency to the
     * plugin's scripts and setup settings hooks.
     */
    protected function construct(...$args)
    {
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
                    $bridges[] = new $bridge_class($bridge_data);
                }

                return $bridges;
            },
            10,
            2
        );

        add_filter(
            'forms_bridge_credentials',
            static function ($credentials, $addon = null) {
                if (!wp_is_numeric_array($credentials)) {
                    $credentials = [];
                }

                if ($addon && $addon !== static::name) {
                    return $credentials;
                }

                $setting = static::setting();
                if (!$setting) {
                    return $credentials;
                }

                foreach ($setting->credentials ?: [] as $credential_data) {
                    $credentials[] = new Credential(
                        $credential_data,
                        static::name
                    );
                }

                return $credentials;
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

            $store::use_setter(static::name, static function ($data) {
                if (!is_array($data)) {
                    return $data;
                }

                unset($data['templates']);
                unset($data['jobs']);

                return static::sanitize_setting($data);
            });
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
     * Addon setting getter.
     *
     * @return Setting|null Setting instance.
     */
    final protected static function setting()
    {
        return Forms_Bridge::setting(static::name);
    }

    /**
     * Proxy to the addons' do_ping private method.
     *
     * @params string $addon Target addon name.
     * @params array $backend Backend data to be used on the request.
     * @params array|null $credential Credential data.
     *
     * @return array Ping result.
     */
    final public static function ping($addon, $backend, $credential)
    {
        $addon = self::addon($addon);
        if (!$addon) {
            return new WP_Error('bad_request', 'Unknown API', [
                'status' => 400,
            ]);
        }

        self::temp_backend_registration($backend);

        $result = $addon->do_ping($backend['name'], $credential);

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result !== true) {
            return ['success' => false];
        }

        return ['success' => true];
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return boolean Ping result.
     */
    abstract protected function do_ping($backend, $request);

    /**
     * Proxy to the addons' do_fetch private method.
     *
     * @param string $addon Addon name.
     * @param array $backend Backend data to be used on the request.
     * @param string $endpoint Target endpoint name.
     * @params array|null $credential Credential data.
     *
     * @return array
     */
    final public static function fetch($addon, $backend, $endpoint, $credential)
    {
        $addon = self::addon($addon);
        if (!$addon) {
            return new WP_Error('bad_request', 'Unknown API', [
                'status' => 400,
            ]);
        }

        self::temp_backend_registration($backend);

        return $addon->do_fetch($backend['name'], $endpoint, $credential);
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array
     */
    abstract protected function do_fetch($backend, $endpoint, $request);

    /**
     * Proxy to the addons' get_schema private method.
     *
     * @param string $api Target API name.
     * @param array $backend Backend data to be used on the request.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array List of fields and content type of the endpoint.
     */
    final public static function endpoint_schema(
        $api,
        $backend,
        $endpoint,
        $request
    ) {
        $addon = self::addon($api);
        if (!$addon) {
            return new WP_Error('bad_request', 'Unknown API', [
                'status' => 400,
            ]);
        }

        self::temp_backend_registration($backend);

        return $addon->get_endpoint_schema(
            $backend['name'],
            $endpoint,
            $request
        );
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array List of fields and content type of the endpoint.
     */
    abstract protected function get_endpoint_schema(
        $backend,
        $endpoint,
        $request
    );

    /**
     * Ephemeral backend registration as an interceptor to allow
     * api fetch, ping and introspection of non registered backends.
     *
     * @param array $data Backend data.
     */
    private static function temp_backend_registration($data)
    {
        add_filter(
            'http_bridge_backends',
            static function ($backends) use ($data) {
                foreach ($backends as $candidate) {
                    if ($candidate->name === $data['name']) {
                        $backend = $candidate;
                        break;
                    }
                }

                if (!isset($backend)) {
                    $backend = new Http_Backend($data);

                    if ($backend->is_valid) {
                        $backends[] = $backend;
                    }
                }

                return $backends;
            },
            99,
            1
        );
    }

    private static function autoload_posts($post_type, $api)
    {
        if (!in_array($post_type, ['fb-bridge-template', 'fb-job'])) {
            return [];
        }

        return get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'meta_key' => '_fb-api',
            'meta_value' => $api,
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
                    $data = json_decode($content, true);
                } catch (TypeError) {
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
     * Loads addon's jobs.
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
