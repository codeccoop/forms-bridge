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
     * Serialize the current form submission data.
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Source form data.
     *
     * @return array Submission data.
     */
    abstract public function serialize_submission($submission, $form);

    /**
     * Serialize the current form data.
     *
     * @param any $form Pair plugin form handle.
     *
     * @return array Form data.
     */
    abstract public function serialize_form($form);

    /**
     * Get uploads from pair submission handle.
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Current form data.
     *
     * @return array Collection of uploaded files.
     */
    abstract protected function submission_uploads($submission, $form_data);

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
     *
     * @param mixed $submission Pair plugin submission handle.
     */
    public function do_submission($submission)
    {
        $form_data = $this->form();
        if (!$form_data) {
            return;
        }

        if (empty($form_data['hooks'])) {
            return;
        }

        $hooks = $form_data['hooks'];

        $uploads = $this->submission_uploads($submission, $form_data);

        foreach (array_values($hooks) as $hook) {
            $payload = $this->serialize_submission($submission, $form_data);
            $payload = $hook->apply_pipes($payload);

            $prune_empties = apply_filters(
                'forms_bridge_prune_empties',
                false,
                $hook->name,
                $hook
            );
            if ($prune_empties) {
                $payload = $this->prune_empties($payload);
            }

            $attachments = apply_filters(
                'forms_bridge_attachments',
                apply_filters(
                    'forms_bridge_attachments_' . $hook->name,
                    $this->attachments($uploads),
                    $hook
                ),
                $uploads,
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
                    $attachments = $this->stringify_attachments($attachments);
                    foreach ($attachments as $name => $value) {
                        $payload[$name] = $value;
                    }
                    $attachments = [];
                }
            }

            $payload = apply_filters(
                'forms_bridge_payload',
                apply_filters(
                    'forms_bridge_payload_' . $hook->name,
                    $payload,
                    $uploads,
                    $hook
                ),
                $uploads,
                $hook
            );

            if (empty($payload)) {
                continue;
            }

            do_action(
                'forms_bridge_before_submission',
                $payload,
                $attachments,
                $form_data
            );
            $response = $hook->submit($payload, $attachments, $form_data);

            if (is_wp_error($response)) {
                do_action(
                    'forms_bridge_on_failure',
                    $response,
                    $form_data,
                    print_r($response->get_error_data(), true)
                );
            } else {
                do_action(
                    'forms_bridge_after_submission',
                    $response,
                    $payload,
                    $attachments,
                    $form_data
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
