<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
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
                $plugin = 'wpforms-lite/wpforms.php';
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

        return Forms_Bridge::is_plugin_active($plugin);
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
            10,
            2
        );

        add_filter("option_{$general_setting}", static function ($value) {
            if (!is_array($value)) {
                return $value;
            }

            return array_merge($value, ['integrations' => self::registry()]);
        });

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

    /**
     * Proceed with the submission sub-routine.
     */
    protected function do_submission()
    {
        $form_data = $this->form();
        if (!$form_data) {
            return;
        }

        if (empty($form_data['hooks'])) {
            return;
        }

        $hooks = $form_data['hooks'];

        $submission = $this->submission();
        $uploads = $this->uploads();

        foreach (array_values($hooks) as $hook) {
            try {
                // TODO: Exclude attachments from payload finger mangling
                $payload = $hook->apply_pipes($submission);

                $prune_empties = apply_filters(
                    'forms_bridge_prune_empties',
                    false,
                    $hook
                );

                if ($prune_empties) {
                    $payload = $this->prune_empties($payload);
                }

                $attachments = apply_filters(
                    'forms_bridge_attachments',
                    $this->attachments($uploads),
                    $hook
                );

                if (!empty($attachments)) {
                    $content_type = $hook->content_type;
                    if (
                        in_array($content_type, [
                            'application/json',
                            'application/x-www-form-urlencoded',
                        ])
                    ) {
                        $attachments = $this->stringify_attachments(
                            $attachments
                        );
                        foreach ($attachments as $name => $value) {
                            $payload[$name] = $value;
                        }
                        $attachments = [];
                    }
                }

                $payload = apply_filters(
                    'forms_bridge_payload',
                    $payload,
                    $hook
                );

                if (empty($payload)) {
                    continue;
                }

                $skip = apply_filters(
                    'forms_bridge_skip_submission',
                    false,
                    $hook,
                    $payload,
                    $attachments
                );
                if ($skip) {
                    continue;
                }

                do_action(
                    'forms_bridge_before_submission',
                    $hook,
                    $payload,
                    $attachments
                );

                $response = $hook->submit($payload, $attachments);

                if ($error = is_wp_error($response) ? $response : null) {
                    do_action(
                        'forms_bridge_on_failure',
                        $hook,
                        $error,
                        $payload,
                        $attachments
                    );
                } else {
                    do_action(
                        'forms_bridge_after_submission',
                        $hook,
                        $payload,
                        $attachments
                    );
                }
            } catch (Error | Exception $e) {
                $message = $e->getMessage();
                if (strstr($message, 'Error while submitting form ')) {
                    throw $e;
                }

                $error = new WP_Error(
                    'internal_server_error',
                    $e->getMessage(),
                    $e->getTrace()
                );

                do_action(
                    'forms_bridge_on_failure',
                    $hook,
                    $error,
                    $payload ?? $submission,
                    $attachments ?? []
                );
            }
        }
    }

    /**
     * Clean up submission empty fields.
     *
     * @param array $submission_data Submission data.
     *
     * @return array Submission data without empty fields.
     */
    private function prune_empties($submission_data)
    {
        foreach ($submission_data as $key => $val) {
            if ($val === '' || $val === null) {
                unset($submission_data[$key]);
            }
        }

        return $submission_data;
    }

    /**
     * Transform collection of uploads to an attachments map.
     *
     * @param array $uploads Collection of uploaded files.
     *
     * @return array Map of uploaded files.
     */
    private function attachments($uploads)
    {
        return array_reduce(
            array_keys($uploads),
            function ($carry, $name) use ($uploads) {
                if ($uploads[$name]['is_multi']) {
                    for ($i = 1; $i <= count($uploads[$name]['path']); $i++) {
                        $carry[$name . '_' . $i] =
                            $uploads[$name]['path'][$i - 1];
                    }
                } else {
                    $carry[$name] = $uploads[$name]['path'];
                }

                return $carry;
            },
            []
        );
    }

    /**
     * Returns the attachments array with each attachment path replaced with its
     * content as a base64 encoded string. For each file on the list, adds an
     * additonal field with the file name on the response.
     *
     * @param array $attachments Submission attachments data.
     *
     * @return array Array with base64 encoded file contents and file names.
     */
    private function stringify_attachments($attachments)
    {
        foreach ($attachments as $name => $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $filename = basename($path);
            $content = file_get_contents($path);
            $attachments[$name] = base64_encode($content);
            $attachments[$name . '_filename'] = $filename;
        }

        return $attachments;
    }
}
