<?php

namespace WPCT_ERP_FORMS;

class Settings extends Abstract\Settings
{

    public function register()
    {
        $setting_name = $this->group_name . '_general';
        $this->register_setting($setting_name);

        $setting_name = $this->group_name . '_api';
        $this->register_setting($setting_name);

        $this->register_field('endpoints', $setting_name);
    }

	public function input_render($setting, $field, $value)
	{
		if (preg_match('/^endpoints.*form_id$/', $field)) {
			return $this->render_forms_dropdown($setting, $field, $value);
		}

		return parent::input_render($setting, $field, $value);
	}

	private function render_forms_dropdown($setting, $field, $value)
	{
		$forms = $this->get_forms();
		$options = array_map(function ($form) use ($value) {
			$selected = $form->id == $value ? 'selected' : '';
			return "<option value='{$form->id}' {$selected}>{$form->title}</option>";
		}, $forms);
		return "<select name='{$setting}[{$field}]'>" . implode('', $options) . '</select>';
	}

	private function get_forms()
	{
		global $wpdb;
		if (apply_filters('wpct_dc_is_active', false, 'contact-form-7/wp-contact-form-7.php')) {
			return $wpdb->get_results("SELECT id, post_title title FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND post_status = 'publish'");
        } elseif (apply_filters('wpct_dc_is_active', false, 'gravityforms/gravityforms.php')) {
			return $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}gf_form WHERE is_active = 1 AND is_trash = 0");
        }

	}
}
