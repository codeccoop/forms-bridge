<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Brevo_Form_Bridge extends Form_Bridge
{
    /**
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error
     */
    public function submit($payload = [], $attachments = [])
    {
        $response = parent::submit($payload, $attachments);

        if (is_wp_error($response)) {
            $error_response = $response->get_error_data()['response'];
            if (
                $error_response['response']['code'] !== 425 &&
                $error_response['response']['code'] !== 400
            ) {
                return $response;
            }

            $data = json_decode($error_response['body'], true);
            if ($data['code'] !== 'duplicate_parameter') {
                return $response;
            }

            if (
                !isset($payload['email']) ||
                strstr($this->endpoint, '/v3/contacts') === false
            ) {
                return $response;
            }

            $update_response = $this->patch([
                'name' => 'brevo-update-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}?identifierType=email_id",
                'method' => 'PUT',
            ])->submit($payload);

            if (is_wp_error($update_response)) {
                return $update_response;
            }

            return $this->patch([
                'name' => 'brevo-search-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}",
                'method' => 'GET',
            ])->submit(['identifierType' => 'email_id']);
        }

        return $response;
    }
}
