<?php

function wpct_forms_missing_dependencies()
{
    $WPCT_FORMS_DEPENDENCIES = $GLOBALS['WPCT_FORMS_DEPENDENCIES'];
    $missings = array();
    foreach ($WPCT_FORMS_DEPENDENCIES as $name => $file) {
        if (!wpct_forms_is_plugin_active($file)) {
            $missings[] = $name;
        }
    }

    return $missings;
}

function wpct_forms_is_plugin_active($plugin_main_file_path)
{
    return in_array($plugin_main_file_path, wpct_forms_get_active_plugins());
}

function wpct_forms_get_active_plugins()
{
    return apply_filters('active_plugins', get_option('active_plugins'));
}

function wpct_forms_admin_notices()
{
    $missings = wpct_forms_missing_dependencies();
    $list_items = array();
    foreach ($missings as $missing) {
        $list_items[] = '<li><b>' . $missing . '</b></li>';
    }

    $notice = '<div class="notice notice-warning" id="wpct-oc-warning">
       <p><b>WARNING:</b> WPCT Forms CE missing dependencies:</p>
       <ul style="list-style-type: decimal; padding-left: 1em;">' . implode(',', $list_items) . '</ul>
    </div>';

    echo $notice;
}

function wpct_forms_check_dependencies()
{
    $missings = wpct_forms_missing_dependencies();
    if (sizeof($missings) > 0) {
        add_action('admin_notices', 'wpct_forms_admin_notices');
    }
}
