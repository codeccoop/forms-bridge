<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Zoho_Credential extends Credential
{
    /**
     * Compare two scope strings and return true if the first one is compatible with the second one.
     *
     * @param string $scope Zoho OAuth scope string.
     * @param string $required Zoho OAuth scope string.
     *
     * @return boolean
     */
    private static function check_oauth_scope($scope, $required)
    {
        $scopes = array_filter(array_map('trim', explode(',', $scope)));
        $requireds = array_filter(array_map('trim', explode(',', $required)));

        $is_valid = true;
        foreach ($requireds as $required) {
            $chunks = explode('.', $required);

            if (count($chunks) < 3) {
                return false;
            } elseif (count($chunks) > 3) {
                [$rapp, $rmodule, $rsubmodule, $rpermission] = $chunks;
            } else {
                [$rapp, $rmodule, $rpermission] = $chunks;
                $rsubmodule = null;
            }

            $match = false;
            foreach ($scopes as $scope) {
                $chunks = explode('.', $scope);

                if (count($chunks) < 3) {
                    continue;
                } elseif (count($chunks) > 3) {
                    [$sapp, $smodule, $ssubmodule, $spermission] = $chunks;
                } else {
                    [$sapp, $smodule, $spermission] = $chunks;
                    $ssubmodule = null;
                }

                if ($rapp !== $sapp) {
                    continue;
                }

                if ($rmodule !== $smodule) {
                    continue;
                }

                if ($rsubmodule) {
                    if ($ssubmodule === null && $spermission === 'ALL') {
                        $match = true;
                        break;
                    } elseif ($rsubmodule !== $ssubmodule) {
                        continue;
                    }
                }

                $match =
                    $spermission === 'ALL' || $spermission === $rpermission;

                if ($match) {
                    break;
                }
            }

            $is_valid = $is_valid && $match;
        }

        return $is_valid;
    }

    protected function refresh_access_token()
    {
        if (!$this->is_valid) {
            return;
        }

        $refresh_token = $this->refresh_token;
        if (!$refresh_token) {
            return;
        }
    }

    public function get_access_token()
    {
        $token = parent::get_access_token();
    }

    public function oauth_grant()
    {
        $url = 'https://accounts.zoho.eu/oauth/v2/token';
        $query = http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => '',
            'scope' => $this->scope,
        ]);

        $response = http_bridge_post($url . '?' . $query);

        if (is_wp_error($response)) {
        }

        $data = $response['data'];
        $data['expires_at'] = $data['expires_in'] + time() - 10;

        $credential = new static(
            [
                'name' => $this->name,
                'type' => $this->type,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope' => $this->scope,
                'organitzation_id' => $this->organitzation_id ?: '',
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? '',
                'expires_at' => $data['expires_in'] + time() - 10,
            ],
            'zoho'
        );

        if ($credential->is_valid) {
            $credential->save();
        }
    }
}
