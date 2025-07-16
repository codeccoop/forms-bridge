<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-nextcloud-form-bridge.php';
require_once 'hooks.php';

/**
 * Nextcloud Addon class.
 */
class Nextcloud_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Nextcloud';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'nextcloud';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Nextcloud_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params null $credential Target credential name.
     *
     * @return boolean
     */
    public function ping($backend, $credential = null)
    {
        $backend = FBAPI::get_backend($backend);
        $user = $backend->authentication['client_id'] ?? '';

        if (!$user) {
            return false;
        }

        $bridge = new Nextcloud_Form_Bridge(
            [
                'name' => '__nextcloud-' . time(),
                'method' => 'GET',
                'endpoint' => "/remote.php/dev/files/{$user}",
                'backend' => $backend->name,
            ],
            self::name
        );

        $response = $bridge->submit();
        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend model and retrive the response data.
     *
     * @param string $endpoint Target model name.
     * @param string $backend Target backend name.
     * @param null $credential Target credential name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        $bridge = new Nextcloud_Form_Bridge(
            [
                'name' => '__nextcloud-' . time(),
                'method' => 'GET',
                'endpoint' => $endpoint,
                'backend' => $backend,
            ],
            self::name
        );

        return $bridge->submit();
    }

    /**
     * Performs an introspection of the backend model and returns API fields
     * and accepted content type.
     *
     * @param string $filepath Filepath.
     * @param string $backend Backend name.
     * @params null $credential Credential name.
     *
     * @return array List of fields and content type of the model.
     */
    public function get_endpoint_schema($filepath, $backend, $credential = null)
    {
        $bridge = new Nextcloud_Form_Bridge(
            [
                'name' => '__nextcloud-' . time(),
                'method' => 'GET',
                'endpoint' => $filepath,
                'backend' => $backend,
            ],
            self::name
        );

        $response = $bridge->submit();

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['result'] as $name => $spec) {
            if ($spec['readonly']) {
                continue;
            }

            if ($spec['type'] === 'char' || $spec['type'] === 'html') {
                $schema = ['type' => 'string'];
            } elseif ($spec['type'] === 'float') {
                $schema = ['type' => 'number'];
            } elseif (
                in_array(
                    $spec['type'],
                    ['one2many', 'many2one', 'many2many'],
                    true
                )
            ) {
                $schema = [
                    'type' => 'array',
                    'items' => [['type' => 'integer'], ['type' => 'string']],
                    'additionalItems' => false,
                ];
            } else {
                $schema = ['type' => $spec['type']];
            }

            $schema['required'] = $spec['required'];

            $fields[] = [
                'name' => $name,
                'schema' => $schema,
            ];
        }

        return $fields;
    }
}

Nextcloud_Addon::setup();
