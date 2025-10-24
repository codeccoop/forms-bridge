<?php

use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Addon;
use FORMS_BRIDGE\Form_Bridge;
use FORMS_BRIDGE\Form_Bridge_Template;
use FORMS_BRIDGE\Integration;
use FORMS_BRIDGE\Job;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class FBAPI {

	/**
	 * Gets an addon instance by name.
	 *
	 * @param string $name Addon name.
	 *
	 * @return Addon|null
	 */
	public static function get_addon( $name ) {
		return Addon::addon( $name );
	}

	/**
	 * Gets an integration instance by name.
	 *
	 * @param string $name Integration name.
	 *
	 * @return Integration|null
	 */
	public static function get_integration( $name ) {
		return Integration::integration( $name );
	}

	/**
	 * Gets the current submitted form data.
	 *
	 * @return array|null
	 */
	public static function get_current_form() {
		return apply_filters( 'forms_bridge_form', null );
	}

	/**
	 * Gets form data by ID.
	 *
	 * @param string $form_id Form ID. If the ID does not come with the integration prefix,
	 * the integration parameter is required.
	 * @param string $integration Integration name.
	 *
	 * @return array|null
	 */
	public static function get_form_by_id( $form_id, $integration = null ) {
		return apply_filters( 'forms_bridge_form', null, $form_id, $integration );
	}

	/**
	 * Gets the collection of available forms.
	 *
	 * @return array
	 */
	public static function get_forms() {
		return apply_filters( 'forms_bridge_forms', array() );
	}

	/**
	 * Gets the collection of available forms filtered by integration name.
	 *
	 * @param string $integration Integration name.
	 *
	 * @return array
	 */
	public static function get_integration_forms( $integration ) {
		return apply_filters( 'forms_bridge_forms', array(), $integration );
	}

	/**
	 * Gets the current form submission's data.
	 *
	 * @return array|null.
	 */
	public static function get_submission() {
		return apply_filters( 'forms_bridge_submission', null );
	}

	/**
	 * Gets the current form submission's ID.
	 *
	 * @return string|null
	 */
	public static function get_submission_id() {
		return apply_filters( 'forms_bridge_submission_id', null );
	}

	/**
	 * Gets the current form submission's uploads.
	 *
	 * @return array|null
	 */
	public static function get_uploads() {
		return apply_filters( 'forms_bridge_uploads', array() );
	}

	/**
	 * Gets a bridge by name and addon.
	 *
	 * @param string $name Bridge name.
	 * @param string $addon Addon name.
	 *
	 * @return Form_Bridge|null
	 */
	public static function get_bridge( $name, $addon ) {
		$bridges = self::get_addon_bridges( $addon );
		foreach ( $bridges as $bridge ) {
			if ( $bridge->name === $name ) {
				return $bridge;
			}
		}
	}

	/**
	 * Gets the current bridge from the bridges loop.
	 *
	 * @return Form_Bridge|null
	 */
	public static function get_current_bridge() {
		return Forms_Bridge::current_bridge();
	}

	/**
	 * Gets the collection of available bridges.
	 *
	 * @return Form_Bridge[]
	 */
	public static function get_bridges() {
		return apply_filters( 'forms_bridge_bridges', array() );
	}

	/**
	 * Gets the collection of available bridges filtered by form ID.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return Form_Bridge[]
	 */
	public static function get_form_bridges( $form_id, $integration = null ) {
		$bridges = apply_filters( 'forms_bridge_bridges', array() );

		if ( preg_match( '/^(\w+):(\d+)$/', $form_id, $matches ) ) {
			[, $integration, $form_id] = $matches;
			$form_id                   = (int) $form_id;
		} elseif ( empty( $integration ) ) {
			return array();
		}

		$form_id = "{$integration}:{$form_id}";

		$form_bridges = array();
		foreach ( $bridges as $bridge ) {
			if ( $bridge->form_id === $form_id ) {
				$form_bridges[] = $bridge;
			}
		}

		return $form_bridges;
	}

	/**
	 * Gets the collection of available bridges filtered by addon name.
	 *
	 * @param string $addon Addon name.
	 *
	 * @return Form_Bridge[]
	 */
	public static function get_addon_bridges( $addon ) {
		return apply_filters( 'forms_bridge_bridges', array(), $addon );
	}

	/**
	 * Inserts or updates the bridge data to the database.
	 *
	 * @param array  $data Bridge data.
	 * @param string $addon Addon name.
	 *
	 * @return boolean
	 */
	public static function save_bridge( $data, $addon ) {
		$addon = self::get_addon( $addon );
		if ( ! $addon ) {
			return false;
		}

		$bridge_class = $addon::bridge_class;
		$bridge       = new $bridge_class( $data );

		if ( ! $bridge->is_valid ) {
			return false;
		}

		return $bridge->save();
	}

	/**
	 * Deletes the bridge data from the database.
	 *
	 * @param string $name Bridge name.
	 * @param string $addon Addon name.
	 *
	 * @return boolean
	 */
	public static function delete_bridge( $name, $addon ) {
		$bridge = self::get_bridge( $name, $addon );

		if ( ! $bridge ) {
			return false;
		}

		return $bridge->delete();
	}

	/**
	 * Gets the bridge schema for a given addon.
	 *
	 * @param string $name Addon name.
	 *
	 * @return array|null
	 */
	public static function get_bridge_schema( $addon ) {
		$addon = Addon::addon( $addon );

		if ( ! $addon ) {
			return;
		}

		return Form_Bridge::schema( $addon::name );
	}

	/**
	 * Gets the collection of available templates.
	 *
	 * @return Form_Bridge_Template[]
	 */
	public static function get_templates() {
		return apply_filters( 'forms_bridge_templates', array() );
	}

	/**
	 * Gets the collection of available templates filtered by addon name.
	 *
	 * @param string $addon Addon name.
	 *
	 * @return Form_Bridge_Template[]
	 */
	public static function get_addon_templates( $addon ) {
		return apply_filters( 'forms_bridge_templates', array(), $addon );
	}

	/**
	 * Gets a template instance by name and addon.
	 *
	 * @param string $name Template name.
	 * @param string $addon Addon name.
	 *
	 * @return Form_Bridge_Template|null
	 */
	public static function get_template( $name, $addon ) {
		$templates = self::get_addon_templates( $addon );

		foreach ( $templates as $template ) {
			if ( $template->name === $name ) {
				return $template;
			}
		}
	}

	/**
	 * Inserts or updates the template data on the database.
	 *
	 * @param array  $data Template data.
	 * @param string $addon Addon name.
	 *
	 * @return integer|boolean Post ID or false.
	 */
	public static function save_template( $data, $addon ) {
		$template = new Form_Bridge_Template( $data, $addon );

		if ( ! $template->is_valid ) {
			return false;
		}

		return $template->save();
	}

	/**
	 * Delete the template data from the database.
	 *
	 * @param string $name Template name.
	 * @param string $addon Addon name.
	 *
	 * @return boolean
	 */
	public static function reset_template( $name, $addon ) {
		$template = self::get_template( $name, $addon );

		if ( ! $template ) {
			return false;
		}

		return $template->reset();
	}

	/**
	 * Gets the template schema for a given addon.
	 *
	 * @param string $addon Addon name.
	 *
	 * @return array|null
	 */
	public static function get_template_schema( $addon ) {
		return Form_Bridge_Template::schema( $addon );
	}

	/**
	 * Gets the collection of available jobs.
	 *
	 * @return Job[]
	 */
	public static function get_jobs() {
		return apply_filters( 'forms_bridge_jobs', array() );
	}

	/**
	 * Gets the collection of available jobs filtered by addon name.
	 *
	 * @param string $addon Addon name.
	 *
	 * @return Job[]
	 */
	public static function get_addon_jobs( $addon ) {
		return apply_filters( 'forms_bridge_jobs', array(), $addon );
	}

	/**
	 * Gets a job instance by name and addon.
	 *
	 * @param string $name Job name.
	 * @param string $addon Addon name.
	 *
	 * @return Job|null
	 */
	public static function get_job( $name, $addon ) {
		$jobs = self::get_addon_jobs( $addon );

		foreach ( $jobs as $job ) {
			if ( $job->name === $name ) {
				return $job;
			}
		}
	}

	/**
	 * Inserts or updates the job data on the database.
	 *
	 * @param array  $data Job data.
	 * @param string $addon Addon name.
	 *
	 * @return integer|boolean Post ID or false.
	 */
	public static function save_job( $data, $addon ) {
		$job = new Job( $data, $addon );

		if ( ! $job->is_valid ) {
			return false;
		}

		return $job->save();
	}

	/**
	 * Deletes the job data from the database.
	 *
	 * @param string $name Job name.
	 * @param string $addon Addon name.
	 *
	 * @return boolean
	 */
	public static function reset_job( $name, $addon ) {
		$job = self::get_job( $name, $addon );

		if ( ! $job ) {
			return false;
		}

		return $job->reset();
	}

	/**
	 * Gets the job schema.
	 *
	 * @return array
	 */
	public static function get_job_schema() {
		return Job::schema();
	}

	/**
	 * Gets the collection of available backends.
	 *
	 * @return Backend[]
	 */
	public static function get_backends() {
		return apply_filters( 'http_bridge_backends', array() );
	}

	/**
	 * Gets a backend instance by name.
	 *
	 * @param string $name Backend name.
	 *
	 * @return Backend|null
	 */
	public static function get_backend( $name ) {
		$backends = self::get_backends();

		foreach ( $backends as $backend ) {
			if ( $backend->name === $name ) {
				return $backend;
			}
		}
	}

	/**
	 * Inserts or updates the backend data on the database.
	 *
	 * @param array Backend data.
	 *
	 * @return boolean
	 */
	public static function save_backend( $data ) {
		$backend = new Backend( $data );

		if ( ! $backend->is_valid ) {
			return false;
		}

		return $backend->save();
	}

	/**
	 * Delete the backend data from the database.
	 *
	 * @param string $name Backend name.
	 *
	 * @return boolean
	 */
	public static function delete_backend( $name ) {
		$backend = self::get_backend( $name );

		if ( ! $backend ) {
			return false;
		}

		return $backend->remove();
	}

	/**
	 * Gets the backend schema.
	 *
	 * @return array
	 */
	public static function get_backend_schema() {
		return Backend::schema();
	}

	/**
	 * Gets the collection of available credentials.
	 *
	 * @return Credential[]
	 */
	public static function get_credentials() {
		return apply_filters( 'http_bridge_credentials', array() );
	}

	/**
	 * Gets a credential instance by name and addon.
	 *
	 * @param string $name Credential name.
	 *
	 * @return Credential|null
	 */
	public static function get_credential( $name ) {
		$credentials = self::get_credentials();

		foreach ( $credentials as $credential ) {
			if ( $credential->name === $name ) {
				return $credential;
			}
		}
	}

	/**
	 * Inserts or updates the credential data to the database.
	 *
	 * @param array $data Credential data.
	 *
	 * @return boolean
	 */
	public static function save_credential( $data ) {
		$credential = new Credential( $data );

		if ( ! $credential->is_valid ) {
			return false;
		}

		return $credential->save();
	}

	/**
	 * Deletes the credential data from the database.
	 *
	 * @param string $name Credential name.
	 *
	 * @return boolean
	 */
	public static function delete_credential( $name ) {
		$credential = self::get_credential( $name );

		if ( ! $credential ) {
			return false;
		}

		return $credential->delete();
	}

	/**
	 * Gets the credential schema for a given addon.
	 *
	 * @return array
	 */
	public static function get_credential_schema() {
		return Credential::schema();
	}
}
