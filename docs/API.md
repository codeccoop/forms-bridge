# API

## Table of Contents

1. [Getters](#getters)
2. [Filters](#filters)
3. [Actions](#actions)

## Getters

### `forms_bridge_form`

Get the current form.

#### Arguments

1. `any $default`: Default value.
2. `integer $form_id`: If declared, try to return form by ID.

#### Returns

1. `array|null $form_data`: Form data.

#### Example

```php
$form_data = apply_filters('forms_bridge_form', null);
if (!empty($form)) {
	// do something
}
```

### `forms_bridge_forms`

Get available forms.

#### Arguments

1. `any $default`: Default value.

#### Returns

1. `array $forms_data`: Available forms as list of form data.

#### Example

```php
$forms_data = apply_filters('forms_bridge_forms', []);
foreach ($forms_data as $form_data) {
	// do something
}
```

### `forms_bridge_form_hooks`

Get active hooks for the current form.

#### Arguments

1. `any $default`: Default value.
2. `integer $form_id`: If declared, try to return form hooks by ID.

#### Returns

1. `array $hooks`: List of given form active hooks.

#### Example

```php
$hooks = apply_filters('forms_bridge_form_hooks', [], 13);
foreach ($hooks as $hook) {
	// do something
}
```

### `forms_bridge_is_hooked`

Check if current form is hooked to a given hook.

#### Arguments

1. `any $default`: Default value.
2. `string $hook_name`: Needle hook name.

#### Returns

1. `boolean $is_hooked`: True if form is hooked to the given hook, false otherwise.

#### Example

```php
$is_hooked = apply_filters('forms_bridge_is_hooked', false, 'CRM Lead');
if ($is_hooked) {
	// do something
}
```

### `forms_bridge_submission`

Get the current form submission.

#### Arguments

1. `any $default`: Default value.

#### Returns

1. `array|null $submission`: Current form submission.

#### Example

```php
$submission = apply_filters('forms_bridge_submission', null);
if ($submission) {
	// do something
}
```

### `forms_bridge_uploads`

Get the current form submission uploaded files.

#### Arguments

1. `any $default`: Default value.

#### Returns

1. `array|null`: Current form submission uploaded files.

#### Example

```php
$uploads = apply_filters('forms_bridge_uploads', []);
foreach ($uploads as $uplad) {
	// do something
}
```

## Filters

### `forms_bridge_payload`

Filters the submission data to be sent to the backend.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $uploads`: Submission uploaded files.
3. `array $form_data`: Form data.
4. `array $hook`: Hook data.

#### Example

```php
add_filter('forms_bridge_payload', function ($payload, $uploads, $form_data, $form_hook) {
	return $payload;
}, 10, 4);
```

### `forms_bridge_payload_{$hook_name}`

Filters the submission data to be sent to the backend for a given form hook.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $uploads`: Submission uploaded files.
3. `array $form_data`: Form data.

#### Example

```php
add_filter('forms_bridge_payload_contact', function ($payload, $uploads, $form_data) {
	return $payload;
}, 10, 3);
```

### `forms_bridge_attachments`

Filters attached files to be sent to the backend.

#### Arguments

1. `array $uploads`: Submission attached files.
2. `array $form_data`: Form data.
3. `array $form_hook`: Form hook data.

#### Example

```php
add_filter('forms_bridge_attachments', function ($attachments, $form_data, $form_hook) {
	return $attachments;
}, 10, 3);
```

### `forms_bridge_attachments_{$hook_name}`

Filters attached files to be sent to the backend for a given form hook.

#### Arguments

1. `array $uploads`: Submission attached files.
2. `array $form_data`: Form data.

#### Example

```php
add_filter('forms_bridge_attachments_contact', function ($attachments, $form_data) {
	return $attachments;
}, 10, 2);
```

### `forms_bridge_rpc_login`

Filters the JSON-RPC login payload.

#### Arguments

1. `array $payload`: Login payload.

#### Example

```php
add_filter('forms_bridge_rpc_login', function ($payload) {
	return $payload;
}, 10, 1);
```

### `forms_bridge_rpc_payload`

Filters the submission data to be sent to the backend as a JSON-RPC call.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

#### Example

```php
add_filter('forms_bridge_rpc_payload', function ($payload, $attachments, $form_data) {
	return $payload;
}, 10, 3);
```

### `forms_bridge_private_upload`

Filter if form uploaded files should be stored in a private folder.

#### Arguments

1. `boolan $is_private`: Default as true, controls uploads privacy.
2. `integer $form_id`: Current form ID.

#### Example

```php
add_filter('forms_bridge_private_upload', function ($is_private, $form_id) {
	return true;
}, 10, 2);
```

### `forms_bridge_upload_path`

Filter private upload path.

#### Arguments

1. `string $path`: Path to store uploaded files.

#### Example

```php
add_filter('forms_bridge_upload_path', function ($path) {
	return $path;
}, 10, 1);
```

## Actions

### `forms_bridge_before_submission`

Action to do just before submission has been sent to the backend.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

#### Example

```php
add_action('forms_bridge_before_submission', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```

### `forms_bridge_after_submission`

Action to do after the submission has been succesfuly sent to the backend.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

#### Example

```php
add_action('forms_bridge_after_submission', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```

### `forms_bridge_on_failure`

Action to do after a request connexion error with the backend.

#### Arguments

1. `array $payload`: Submission payload.
2. `array $attachments`: Submission attached files.
3. `array $form_data`: Form data.

#### Example

```php
add_action('forms_bridge_on_failure', function ($payload, $attachments, $form_data) {
	// do something
}, 10, 3);
```
