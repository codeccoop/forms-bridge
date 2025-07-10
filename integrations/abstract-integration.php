<?php

namespace FORMS_BRIDGE;

use WPCT_PLUGIN\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Integration abstract class.
 */
abstract class Integration extends Singleton
{
    /**
     * Handles integration's registry option name.
     *
     * @var string registry
     */
    private const registry = 'forms_bridge_integrations';

    public const title = 'Abstract Integration';

    public const name = 'abstract-integration';

    /**
     * Handles available integrations state.
     *
     * @var array<string, Integration|null> $integrations.
     */
    private static $integrations = [];
    // 'gf' => null,
    // 'wpforms' => null,
    // 'wpcf7' => null,
    // 'ninja' => null,
    // 'woo' => null,
    // 'formidable' => null,
    // 'forminator' => null,
    // ];

    private static function check_dependencies($integration)
    {
        switch ($integration) {
            case 'wpcf7':
                $deps = ['contact-form-7/wp-contact-form-7.php'];
                break;
            case 'gf':
                $deps = ['gravityforms/gravityforms.php'];
                break;
            case 'wpforms':
                $deps = ['wpforms-lite/wpforms.php', 'wpforms/wpforms.php'];
                break;
            case 'ninja':
                $deps = ['ninja-forms/ninja-forms.php'];
                break;
            case 'woo':
                $deps = ['woocommerce/woocommerce.php'];
                break;
            // case 'formidable':
            //     $plugin = 'formidable/formidable.php';
            //     break;
            // case 'forminator':
            //     $plugin = 'forminator/forminator.php';
            //     break;
            default:
                return false;
        }

        $is_active = false;
        foreach ($deps as $dep) {
            $is_active = Forms_Bridge::is_plugin_active($dep);

            if ($is_active) {
                break;
            }
        }

        return $is_active;
    }

    /**
     * Public integrations registry getter.
     *
     * @return array Integration registry state.
     */
    private static function registry()
    {
        $state = get_option(self::registry, []) ?: [];
        $integrations_dir = FORMS_BRIDGE_INTEGRATIONS_DIR;
        $integrations = array_diff(scandir($integrations_dir), ['.', '..']);

        $registry = [];
        foreach ($integrations as $integration) {
            $integration_dir = "{$integrations_dir}/{$integration}";
            if (!is_dir($integration_dir)) {
                continue;
            }

            $index = "{$integration_dir}/class-integration.php";
            $has_deps = self::check_dependencies($integration);

            if (is_file($index) && is_readable($index) && $has_deps) {
                $registry[$integration] = boolval(
                    $state[$integration] ?? false
                );
            }
        }

        $is_single = count(array_keys($registry)) === 1;
        if ($is_single) {
            foreach (array_keys($registry) as $integration) {
                $registry[$integration] = true;
            }
        }

        return $registry;
    }

    /**
     * Updates the integration's registry state.
     *
     * @param array $integrations New integrations' registry state.
     */
    private static function update_registry($integrations = [])
    {
        $registry = self::registry();
        foreach ($integrations as $name => $enabled) {
            $has_deps = self::check_dependencies($name);
            if (!($has_deps && isset($registry[$name]))) {
                continue;
            }

            $registry[$name] = (bool) $enabled;
        }

        update_option(self::registry, $registry);
    }

    /**
     * Public active integration instances getter.
     *
     * @return array List with integration instances.
     */
    final public static function integrations()
    {
        $integrations = [];
        foreach (self::$integrations as $instance) {
            if ($instance->enabled) {
                $integrations[] = $instance;
            }
        }

        return $integrations;
    }

    final public static function integration($name)
    {
        return self::$integrations[$name] ?? null;
    }

    /**
     * Public integrations loader.
     */
    public static function load_integrations()
    {
        $integrations_dir = dirname(__FILE__);
        $registry = self::registry();

        foreach ($registry as $integration => $enabled) {
            $has_dependencies = self::check_dependencies($integration);

            if ($has_dependencies) {
                require_once "{$integrations_dir}/{$integration}/class-integration.php";

                if ($enabled) {
                    self::$integrations[$integration]->load();
                }
            }
        }

        Settings_Store::ready(function ($store) {
            $store::use_getter('general', function ($data) {
                $registry = self::registry();
                $integrations = [];
                foreach (self::$integrations as $name => $integration) {
                    $integrations[$name] = [
                        'name' => $name,
                        'title' => $integration::title,
                        'enabled' => $registry[$name] ?? false,
                    ];
                }

                ksort($integrations);
                $integrations = array_values($integrations);

                $integrations = apply_filters(
                    'forms_bridge_integrations',
                    $integrations
                );
                return array_merge($data, ['integrations' => $integrations]);
            });

            $store::use_setter(
                'general',
                function ($data) {
                    if (
                        !isset($data['integrations']) ||
                        !is_array($data['integrations'])
                    ) {
                        return $data;
                    }

                    $registry = [];
                    foreach ($data['integrations'] as $integration) {
                        $registry[$integration['name']] =
                            (bool) $integration['enabled'];
                    }

                    self::update_registry($registry);

                    unset($data['integrations']);
                    return $data;
                },
                9
            );
        });
    }

    public static function setup(...$args)
    {
        return static::get_instance(...$args);
    }

    public $enabled = false;

    protected function construct(...$args)
    {
        self::$integrations[static::name] = $this;
    }

    public function load()
    {
        add_action('init', function () {
            $this->init();
        });

        // Gets available forms' data.
        add_filter(
            'forms_bridge_forms',
            function ($forms, $integration = null) {
                if (!wp_is_numeric_array($forms)) {
                    $forms = [];
                }

                if ($integration && $integration !== static::name) {
                    return $forms;
                }

                $forms = array_merge($forms, $this->forms());
                return $forms;
            },
            9,
            2
        );

        // Gets form data by context or by ID
        add_filter(
            'forms_bridge_form',
            function ($form, $form_id = null, $integration = null) {
                if (is_array($form) && isset($form['id']) && $form['id']) {
                    return $form;
                }

                if ($form_id) {
                    if (preg_match('/^(\w+):(\d+)$/', $form_id, $matches)) {
                        [, $integration, $form_id] = $matches;
                        $form_id = (int) $form_id;
                    } elseif (empty($integration)) {
                        return $form;
                    }
                }

                if ($integration && $integration !== static::name) {
                    return $form;
                }

                if ($form_id) {
                    return $this->get_form_by_id($form_id);
                }

                return $this->form();
            },
            9,
            3
        );

        // Gets current submission data
        add_filter(
            'forms_bridge_submission',
            function ($submission, $raw = false) {
                return $this->submission($raw) ?: $submission;
            },
            9,
            2
        );

        add_filter(
            'forms_bridge_submission_id',
            function ($submission_id) {
                return $this->submission_id() ?: $submission_id;
            },
            9,
            1
        );

        // Gets curent submission uploads
        add_filter(
            'forms_bridge_uploads',
            function ($uploads) {
                return $this->uploads() ?: $uploads;
            },
            9,
            1
        );

        $this->enabled = true;
    }

    /**
     * Integration initializer to be fired on wp init.
     */
    abstract protected function init();

    /**
     * Retrives the current form.
     *
     * @return array Form data.
     */
    abstract public function form();

    /**
     * Retrives form by ID.
     *
     * @return array Form data.
     */
    abstract public function get_form_by_id($form_id);

    /**
     * Retrives available forms.
     *
     * @return array Collection of form data.
     */
    abstract public function forms();

    /**
     * Creates a form from a given template fields.
     *
     * @param array $data Form template data.
     *
     * @return int|null ID of the new form.
     */
    abstract public function create_form($data);

    /**
     * Removes a form by ID.
     *
     * @param integer $form_id Form ID.
     *
     * @return boolean Removal result.
     */
    abstract public function remove_form($form_id);

    abstract public function submission_id();

    /**
     * Retrives the current form submission.
     *
     * @param boolean $raw Control if the submission is serialized before exit.
     *
     * @return array Submission data.
     */
    abstract public function submission($raw);

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Collection of uploaded files.
     */
    abstract public function uploads();
}
