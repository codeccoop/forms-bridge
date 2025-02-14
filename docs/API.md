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
2. `integer $form_id`: If declared, try to return form by ID instead of the current form.

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

1. `mixed $default`: Fallback value.

#### Returns

1. `array $forms_data`: Available forms as list of form data.

#### Example

```php
$forms_data = apply_filters('forms_bridge_forms', []);
foreach ($forms_data as $form_data) {
	// do something
}
```

### `forms_bridge_bridges`

Get available bridges for the current form.

#### Arguments

1. `mixed $default`: Fallback value.
2. `integer $form_id`: If declared, try to return bridges from the form with this id instead of the bridges from the current form.

#### Returns

1. `array $bridges`: List of given form available bridges instances.

#### Example

```php
$bridges = apply_filters('forms_bridge_bridges', [], 12);
foreach ($bridges as $bridge) {
	// do something
}
```

### `forms_bridge_submission`

Gets the current form submission.

#### Arguments

1. `mixed $default`: Fallback value.

#### Returns

1. `array|null $submission`: Current form's submission data.

#### Example

```php
$submission = apply_filters('forms_bridge_submission', null);
if ($submission) {
	// do something
}
```

### `forms_bridge_uploads`

Gets the current form's submission uploaded files.

#### Arguments

1. `mixed $default`: Fallback value.

#### Returns

1. `array|null`: Current form's submission uploaded files.

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
4. `Form_Bridge $bridge`: Bridge instance.

#### Example

```php
add_filter('forms_bridge_payload', function ($payload, $bridge) {
	return $payload;
}, 10, 4);
```

### `forms_bridge_attachments`

Filters attached files to be sent to the backend.

#### Arguments

1. `array $uploads`: Submission attached files.
3. `Form_Bridge $bridge`: Bridge instance.

#### Example

```php
add_filter('forms_bridge_attachments', function ($attachments, $bridge) {
	return $attachments;
}, 10, 3);
```

### `forms_bridge_prune_empties`

Control if Forms Bridge should clean up the submission data and prune its empty fields.

#### Arguments

1. `boolean $prune`: False by default.
2. `Form_Bridge $bridge`: Bridge instance.

#### Example

```php
add_filter('forms_bridge_prune_empties', '__return_true');
```

### `forms_bridge_skip_submission`

Control if Forms Bridge should skip the form submission.

#### Arguments

1. `boolean $prune`: False by default.
2. `Form_Bridge $bridge`: Bridge instance.
3. `array $payload`: Payload data.
4. `array $attachments`: Attachments list.

#### Example

```php
add_filter('forms_bridge_skip_submission', '__return_true');
```


## Actions

### `forms_bridge_before_submission`

Action to do just before submission has been sent to the backend.

#### Arguments

1. `Form_Bridge $bridge`: Bridge instance.
2. `array $payload`: Payload data.
3. `array $attachments`: Attachments list.

#### Example

```php
add_action('forms_bridge_before_submission', function ($bridge, $payload, $attachments) {
	// do something
}, 10, 3);
```

### `forms_bridge_after_submission`

Action to do after the submission has been succesfuly sent to the backend.

#### Arguments

1. `Form_Bridge $bridge`: Bridge instance.
2. `array $payload`: Submission payload.
3. `array $attachments`: Attachments list.
4. `array $response`: HTTP response data.

#### Example

```php
add_action('forms_bridge_after_submission', function ($bridge, $payload, $attachments, $response) {
	// do something
}, 10, 1);
```

### `forms_bridge_on_failure`

Action to do after a request connexion error with the backend.

#### Arguments

1. `Form_Bridge $bridge`: Bridge instance.
2. `WP_Error $error`: HTTP response error.
3. `array $payload`: Submission payload.
4. `array $attachments`: Attachments list.

#### Example

```php
add_action('forms_bridge_on_failure', function ($bridge, $error, $payload, $attachments) {
	// do something
}, 10, 4);
```
