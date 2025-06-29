<?php

if (!defined('ABSPATH')) {
    exit();
}

class FBAPI
{
    public static function get_current_form()
    {
        return apply_filters('forms_bridge_form', null);
    }

    public static function get_form_by_id($form_id, $integration)
    {
        return apply_filters('forms_bridge_form', null, $form_id, $integration);
    }

    public static function get_forms()
    {
        return apply_filters('forms_bridge_forms', []);
    }

    public static function get_integration_forms($integration)
    {
        return apply_filters('forms_bridge_forms', [], $integration);
    }

    public static function get_bridge($name, $api = null)
    {
        return apply_filters('forms_bridge_bridge', null, $name, $api);
    }

    public static function get_current_bridge()
    {
        return Forms_Bridge::current_bridge();
    }

    public static function get_bridges()
    {
        return apply_filters('forms_bridge_bridges', []);
    }

    public static function get_form_bridges($form_id)
    {
        return apply_filters('forms_bridge_bridges', [], $form_id);
    }

    public static function get_api_bridges($api)
    {
        return apply_filters('forms_bridge_bridges', [], null, $api);
    }

    public static function get_submission()
    {
        return apply_filters('forms_bridge_submission', null);
    }

    public static function get_submission_id()
    {
        return apply_filters('forms_bridge_submission_id', null);
    }

    public static function get_uploads()
    {
        return apply_filters('forms_bridge_uploads', []);
    }

    public static function get_templates()
    {
        return apply_filters('forms_bridge_templates', []);
    }

    public static function get_api_templates($api)
    {
        return apply_filters('forms_bridge_templates', [], $api);
    }

    public static function get_template($name, $api)
    {
        return apply_filters('forms_bridge_template', null, $name, $api);
    }

    public static function get_job($name, $api)
    {
        return apply_filters('forms_bridge_workflow_job', null, $name, $api);
    }

    public static function get_jobs()
    {
        return apply_filters('forms_bridge_workflow_jobs', []);
    }

    public static function get_api_jobs($api)
    {
        return apply_filters('forms_bridge_workflow_jobs', [], $api);
    }

    public static function get_backend($name)
    {
        return apply_filters('http_bridge_backend', null, $name);
    }

    public static function get_backends()
    {
        return apply_filters('http_bridge_backends', []);
    }
}
