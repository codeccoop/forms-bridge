# Wpct ERP Forms

Bridge WP form builder plugins to remote backend over http requests.

Wpct ERP Forms has integrations for [GravityForms](https://www.gravityforms.com)
, [Contact Form 7](https://contactform7.com/) and [WP Forms](https://wpforms.com/).

The plugin allow comunication with your ERP over REST or JSON-RPC API protocols.

> Http requests will be sent with data encoded as `application/json` if there is no uploads.
Else if form submission contains files, the default behavior is to send data as
`multipart/formdata` encodec content type.

## Installation

Download the [latest release](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases/permalink/latest/downloads/plugins/wpct-erp-forms.zip)
as a zipfile. Once downloaded, decompress and place its content on your WP instance
`wp-content/plugins`'s directory.

> Go to the [releases](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases) to find previous versions.

You can install it with `wp-cli` with the next command:

```shell
wp plugin install https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases/permalink/latest/downloads/plugins/wpct-erp-forms.zip
```

## Settings

Go to `Settings > Wpct ERP Forms` to manage plugin settings. This page has three main sections:

1. General
	* **Notification receiver**: Email address receiver of failed submission notifications.
	* **Backends**: List of configured backend connections. Each backend needs a unique name,
	a base URL, and, optional, a map of HTTP headers.
2. REST API
	* **Form Hooks**: A list of hooked forms and it's relation with your backend endpoints. Each
	relation needs a unique name, a form ID, a backend, and an endpoint. Submission will be sent as
	encoded JSON objects.
3. JSON-RPC API
	* **RPC API endpoint**: Entry point of your ERP JSON-RPC external API.
	* **API user login**: Login of the ERP's user to use on the API authentication requests.
	* **User password**: Password of the user.
	* **Database name**: Database  name to be used.
	* **Form Hooks**: A list of hooked forms and it's relation with your backend models. Each
	relation needs a unique name, a from ID, a backend, and a model. Submission will be sent encoded
	as JSON-RPC payloads.

## API

### Getters

#### `wpct_erp_forms_form`

Get the current form.

Arguments:

1. `any $default`: Default value.
2. `integer $form_id`: If declared, try to return form by ID.

Returns:

1. `array|null $form_data`: Form data.

Example:

```php
$form_data = apply_filters('wpct_erp_forms_form', null);
if (!empty($form)) {
	// do something
}
```

#### `wpct_erp_forms_forms`

Get available forms.

Arguments:

1. `any $default`: Default value.

Returns:

1. `array $forms_data`: Available forms as list of form data.

Example:

```php
$forms_data = apply_filters('wpct_erp_forms_forms', []);
foreach ($forms_data as $form_data) {
	// do something
}
```

#### `wpct_erp_forms_form_hooks`

Get active hooks for the current form.

Arguments:

1. `any $default`: Default value.
2. `integer $form_id`: If declared, try to return form hooks by ID.

Returns:

1. `array $hooks`: List of given form active hooks.

Example:

```php
$hooks = apply_filters('wpct_erp_forms_form_hooks', [], 13);
foreach ($hooks as $hook) {
	// do something
}
```

#### `wpct_erp_forms_is_hooked`

Check if current form is hooked to a given hook.

Arguments:

1. `any $default`: Default value.
2. `string $hook_name`: Needle hook name.

Returns:

1. `boolean $is_hooked`: True if form is hooked to the given hook, false otherwise.

Example:

```php
$is_hooked = apply_filters('wpct_erp_forms_is_hooked', false, 'CRM Lead');
if ($is_hooked) {
	// do something
}
```

#### `wpct_erp_forms_submission`

Get the current form submission.

Arguments:

1. `any $default`: Default value.

Returns:

1. `array|null $submission`: Current form submission.

Example:

```php
$submission = apply_filters('wpct_erp_forms_submission', null);
if ($submission) {
	// do something
}
```

#### `wpct_erp_forms_uploads`

Get the current form submission uploaded files.

Arguments:

1. `any $default`: Default value.

Returns:

1. `array|null`: Current form submission uploaded files.

Example:

```php
$uploads = apply_filters('wpct_erp_forms_uploads', []);
foreach ($uploads as $uplad) {
	// do something
}
```

### Filters

#### `wpct_erp_forms_payload`

Filters the submission data to be sent to the backend.

Arguments:

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

Example:

```php
add_filter('wpct_erp_forms_payload', function ($payload, $attachments, $form_data) {
	return $payload;
}, 10, 3);
```

#### `wpct_erp_forms_attachments`

Filters attached files to be sent to the backend.

Arguments:

1. `array $attachments`: Submission attached files.
2. `array $form_data`: Form data.

Example:

```php
add_filter('wpct_erp_forms_attachments', function ($attachments, $form_data) {
	return $attachments;
}, 10, 3);
```

#### `wpct_erp_forms_rpc_login`

Filters the JSON-RPC login payload.

Arguments:

1. `array $payload`: Login payload.

Example:

```php
add_filter('wpct_erp_forms_rpc_login', function ($payload) {
	return $payload;
}, 10, 1);
```

#### `wpct_erp_forms_rpc_payload`

Filters the submission data to be sent to the backend as a JSON-RPC call.

Arguments:

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

Example:

```php
add_filter('wpct_erp_forms_rpc_payload', function ($payload, $attachments, $form_data) {
	return $payload;
}, 10, 3);
```

#### `wpct_erp_forms_private_upload`

Filter if form uploaded files should be stored in a private folder.

Arguments:

1. `boolan $is_private`: Default as true, controls uploads privacy.
2. `integer $form_id`: Current form ID.

Example:

```php
add_filter('wpct_erp_forms_private_upload', function ($is_private, $form_id) {
	return true;
}, 10, 2);
```

#### `wpct_erp_forms_upload_path`

Filter private upload path.

Arguments:

1. `string $path`: Path to store uploaded files.

Example:

```php
add_filter('wpct_erp_forms_upload_path', function ($path) {
	return $path;
}, 10, 1);
```

### Actions

#### `wpct_erp_forms_before_submission`

Action to do just before submission has been sent to the backend.

Arguments:

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

Example:

```php
add_action('wpct_erp_forms_before_submission', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```

#### `wpct_erp_forms_after_submission`

Action to do after the submission has been succesfuly sent to the backend.

Arguments:

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

Example:

```php
add_action('wpct_erp_forms_after_submission', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```

#### `wpct_erp_forms_on_failure`

Action to do after a request connexion error with the backend.

Arguments:

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

Example:

```php
add_action('wpct_erp_forms_on_failure', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```

## Dependencies

This plugin relays on [Wpct HTTP Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-http-bridge/)
and [Wpct i18n](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-i18n/) as depenendencies,
as well as the [Wpct Plugin Abstracts](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-plugin-abstracts)
snippets. The plugin comes with its dependencies bundled in its releases, so you should not worry
about its managment. You can see this plugins documentation to know more about its APIs.

## Roadmap

1. [ ] More agonstic JSON-RPC support decoupled from Odoo JSON-RPC API.
2. [ ] Rename plugin to Forms Bridge and publish it on wordpress.org repositories.
3. [ ] Backend connectors as an opt-in list with Odoo JSON-RPC API suited integration.
3. [ ] Backend connectors as an opt-in list with Dolibarr REST API suited integration.
