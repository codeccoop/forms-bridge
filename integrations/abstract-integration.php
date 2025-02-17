<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Singleton;

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

    /**
     * Handles available integrations state.
     *
     * @var array $integrations.
     */
    private static $integrations = [
        'gf' => null,
        'wpforms' => null,
        'wpcf7' => null,
        'ninja' => null,
        // 'formidable' => null,
        // 'forminator' => null,
    ];

    private static function check_dependencies($integration)
    {
        switch ($integration) {
            case 'wpcf7':
                $plugin = 'contact-form-7/wp-contact-form-7.php';
                break;
            case 'gf':
                $plugin = 'gravityforms/gravityforms.php';
                break;
            case 'wpforms':
                $plugin = ['wpforms-lite/wpforms.php', 'wpforms/wpforms.php'];
                break;
            case 'ninja':
                $plugin = 'ninja-forms/ninja-forms.php';
                break;
            // case 'formidable':
            //     $plugin = 'formidable/formidable.php';
            //     break;
            // case 'forminator':
            //     $plugin = 'forminator/forminator.php';
            //     break;
            default:
                $plugin = null;
        }

        if (!is_array($plugin)) {
            $plugin = [$plugin];
        }

        $is_active = false;
        foreach ($plugin as $p) {
            $is_active = Forms_Bridge::is_plugin_active($p);
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
    public static function registry()
    {
        $state = (array) get_option(self::registry, []);
        $integrations_dir = dirname(__FILE__);
        $integrations = array_diff(scandir($integrations_dir), ['.', '..']);
        $registry = [];
        foreach ($integrations as $integration) {
            $integration_dir = "{$integrations_dir}/{$integration}";
            if (!is_dir($integration_dir)) {
                continue;
            }

            $index = "{$integration_dir}/class-integration.php";
            $has_dependencies = self::check_dependencies($integration);
            if (is_file($index) && $has_dependencies) {
                $registry[$integration] = isset($state[$integration])
                    ? (bool) $state[$integration]
                    : false;
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
    public static function update_registry($integrations = [])
    {
        $registry = self::registry();
        foreach ($integrations as $integration => $enabled) {
            if (
                !(
                    isset($registry[$integration]) &&
                    self::check_dependencies($integration)
                )
            ) {
                continue;
            }

            $registry[$integration] = (bool) $enabled;
        }

        update_option(self::registry, $registry);
    }

    /**
     * Public active integration instances getter.
     *
     * @return array List with integration instances.
     */
    public static function integrations()
    {
        $actives = [];
        foreach (self::$integrations as $integration => $instance) {
            if ($instance) {
                $actives[$integration] = $instance;
            }
        }

        return $actives;
    }

    /**
     * Public integrations loader.
     */
    public static function load()
    {
        $integrations_dir = dirname(__FILE__);
        $registry = self::registry();
        foreach ($registry as $integration => $enabled) {
            $has_dependencies = self::check_dependencies($integration);
            if ($enabled && $has_dependencies) {
                $NS = strtoupper($integration);
                require_once "{$integrations_dir}/{$integration}/class-integration.php";
                self::$integrations[$integration] = (
                    '\FORMS_BRIDGE\\' .
                    $NS .
                    '\Integration'
                )::get_instance();
            }
        }

        self::custom_hooks();
        self::handle_setting();
    }

    private static function custom_hooks()
    {
        // Gets available forms' data.
        add_filter(
            'forms_bridge_forms',
            static function ($forms, $integration = null) {
                if (!wp_is_numeric_array($forms)) {
                    $forms = [];
                }

                $integrations = self::integrations();

                if ($integration) {
                    return isset($integrations[$integration])
                        ? $integrations[$integration]->forms()
                        : [];
                }

                $forms = [];
                foreach ($integrations as $integration) {
                    $forms = array_merge($forms, $integration->forms());
                }

                return $forms;
            },
            5,
            2
        );

        // Gets form data by context or by ID
        add_filter(
            'forms_bridge_form',
            static function ($value, $form_id = null, $integration = null) {
                $integrations = self::integrations();

                if ($integration) {
                    $integrations = isset($integrations[$integration])
                        ? [$integration => $integrations[$integration]]
                        : [];
                }

                if ($form_id) {
                    if (preg_match('/^(\w+):(\d+)$/', $form_id, $matches)) {
                        [, $integration, $form_id] = $matches;
                        $form_id = (int) $form_id;
                    } elseif (empty($integration) && count($integration) > 1) {
                        _doing_it_wrong(
                            'forms_bridge_form',
                            __(
                                '$form_id param should incloude the integration prefix if there is more than one integration active',
                                'forms-bridge'
                            ),
                            '2.3.0'
                        );

                        return;
                    } else {
                        $form_id = (int) $form_id;
                    }
                }

                if ($integration) {
                    $integrations = isset($integrations[$integration])
                        ? [$integration => $integrations[$integration]]
                        : [];
                }

                foreach ($integrations as $integration) {
                    if ($form_id) {
                        $form = $integration->get_form_by_id($form_id);
                    } else {
                        $form = $integration->form();
                    }

                    if ($form) {
                        return $form;
                    }
                }

                return $value;
            },
            5,
            3
        );

        // Gets current submission data
        add_filter(
            'forms_bridge_submission',
            static function ($value) {
                $integrations = self::integrations();
                foreach ($integrations as $integration) {
                    if ($submission = $integration->submission()) {
                        return $submission;
                    }
                }

                return $value;
            },
            5,
            1
        );

        // Gets curent submission uploads
        add_filter(
            'forms_bridge_uploads',
            static function ($value) {
                $integrations = self::integrations();
                foreach ($integrations as $integration) {
                    if ($uploads = $integration->uploads()) {
                        return $uploads;
                    }
                }

                return $value;
            },
            5,
            1
        );
    }

    private static function handle_setting()
    {
        $general_setting = Forms_Bridge::slug() . '_general';
        add_filter(
            'wpct_setting_default',
            static function ($default, $name) use ($general_setting) {
                if ($name !== $general_setting) {
                    return $default;
                }

                return array_merge($default, [
                    'integrations' => self::registry(),
                ]);
            },
            9,
            2
        );

        add_filter(
            "option_{$general_setting}",
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                return array_merge($value, [
                    'integrations' => self::registry(),
                ]);
            },
            9,
            1
        );

        add_filter(
            'wpct_validate_setting',
            function ($data, $setting) use ($general_setting) {
                if ($setting->full_name() !== $general_setting) {
                    return $data;
                }

                self::update_registry((array) $data['integrations']);
                unset($data['integrations']);

                return $data;
            },
            9,
            2
        );
    }

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

    /**
     * Retrives the current form submission.
     *
     * @return array Submission data.
     */
    abstract public function submission();

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Collection of uploaded files.
     */
    abstract public function uploads();

    /**
     * Integration initializer to be fired on wp init.
     */
    abstract protected function init();

    /**
     * Binds integration initializer to wp init hook.
     */
    protected function construct(...$args)
    {
        add_action('init', function () {
            $this->init();
        });
    }
}
