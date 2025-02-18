<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

use WP_Error;

/**
 * Form bridge implementation for the Odoo JSON-RPC api.
 */
class Odoo_Form_Bridge extends Form_Bridge
{
    private const endpoint = '/jsonrpc';

    /**
     * Handles the bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Odoo_Form_Bridge_Template';

    /**
     * RPC payload decorator.
     *
     * @param int $session_id RPC session ID.
     * @param string $service RPC service name.
     * @param string $method RPC method name.
     * @param array $args RPC request arguments.
     *
     * @return array JSON-RPC conformant payload.
     */
    public static function rpc_payload($session_id, $service, $method, $args)
    {
        return [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'id' => $session_id,
            'params' => [
                'service' => $service,
                'method' => $method,
                'args' => $args,
            ],
        ];
    }

    /**
     * Handle RPC responses and catch errors on the application layer.
     *
     * @param array $response Request response.
     * @param boolean $is_single Should the result be an entity or an array.
     *
     * @return mixed|WP_Error Request result.
     */
    public static function rpc_response($res)
    {
        if (is_wp_error($res)) {
            return $res;
        }

        if (isset($res['data']['error'])) {
            return new WP_Error(
                $res['data']['error']['code'],
                $res['data']['error']['message'],
                $res['data']['error']['data']
            );
        }

        $data = $res['data'];

        if (empty($data['result'])) {
            return new WP_Error(
                'rpc_api_error',
                'An unkown error has ocurred with the RPC API',
                ['response' => $res]
            );
        }

        return $data['result'];
    }

    /**
     * JSON RPC login request.
     *
     * @param Odoo_DB $db Current db instance.
     *
     * @return array|WP_Error Tuple with RPC session id and user id.
     */
    private static function rpc_login($db)
    {
        $session_id = Forms_Bridge::slug() . '-' . time();
        $backend = $db->backend;

        $payload = self::rpc_payload($session_id, 'common', 'login', [
            $db->name,
            $db->user,
            $db->password,
        ]);

        do_action('forms_bridge_before_odoo_rpc_login', $payload, $db);

        $response = $backend->post(self::endpoint, $payload);

        do_action('forms_bridge_odoo_rpc_login', $response, $db);

        $user_id = self::rpc_response($response);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return [$session_id, $user_id];
    }

    /**
     * Parent getter interceptor ti short circtuit database access.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        if ($name === 'database') {
            return $this->database();
        }

        return parent::__get($name);
    }

    /**
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Intercepts backend access and returns it from the database.
     *
     * @return Http_Backend|null
     */
    protected function backend()
    {
        return $this->database()->backend;
    }

    /**
     * Bridge's database private getter.
     *
     * @return Odoo_DB|null
     */
    private function database()
    {
        $dbs = Forms_Bridge::setting('odoo')->databases;
        foreach ($dbs as $db) {
            if ($db['name'] === $this->data['database']) {
                return new Odoo_DB($db);
            }
        }
    }

    protected function do_submit($payload, $attachments = [])
    {
        $db = $this->database();

        $login = self::rpc_login($db);

        if (is_wp_error($login)) {
            return $login;
        }

        [$sid, $uid] = $login;

        $payload = self::rpc_payload($sid, 'object', 'execute', [
            $db->name,
            $uid,
            $db->password,
            $this->model,
            'create',
            $payload,
        ]);

        $response = $this->backend->post(self::endpoint, $payload);

        $result = self::rpc_response($response);
        if (is_wp_error($result)) {
            return $result;
        }

        return $response;
    }
}
