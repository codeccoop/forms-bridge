# Wpct ERP Forms

Bridge WP form builder plugins' submissions to remote backend over http requests.

Wpct ERP Forms has integrations with [GravityForms](https://www.gravityforms.com) and [Contact Form 7](https://contactform7.com/) and [WP Forms](https://wpforms.com/).

The plugin allow comunication with your ERP over REST or JSON-RPC API protocols.

> Http requests will be sent with data encoded as `application/json` if there is no uploads. Else if form
submission contains files, the default behavior is to send data as `multipart/formdata` encodec
content type.

## Installation

Download the [latest release](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases/permalink/latest/downloads/plugins/wpct-erp-forms.zip) as a zipfile.
Once downloaded, decompress and place its content on your WP instance `wp-content/plugins`'s directory.

> Go to the [releases](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases) to find previous versions.

You can install it with `wp-cli` with the next command:

```shell
wp plugin install https://git.coopdevs.org/codeccoop/wp/plugins/wpct-erp-forms/-/releases/permalink/latest/downloads/plugins/wpct-erp-forms.zip
```

## Settings

Go to `Settings > Wpct ERP Forms` to manage plugin settings. This page has three main sections:

1. General
	* **Notification receiver**: Email address receiver of failed submission notifications.
	* **ERP base URL**: Base URL of the ERP where submissions will be sent.
	* **ERP API key**: API key, if needed, to be sent with the request header `API-KEY`.
2. REST API
	* **Forms**: A list of hooked forms. With this list you can bind form submissions
	to your ERP REST API endpoints. Submission will be sent encoded as JSON objects.
3. RPC API
	* **RPC API endpoint**: Entry point of your ERP's RPC external API.
	* **API user login**: Login of the ERP's user to use use on the API authentication requests.
	* **User password**: Password of the user.
	* **Database name**: Database  name to be used.
	* **Model ID**: Target ERP model to be used when creating form submissions on the backend.
	* **Forms**: A list of hooked forms. With this list you can bind form submissions
	to your ERP RPC external API. Submission will be sent encoded as JSON-RPC payloads.

## Hooks

### Filters

#### `wpct_erp_forms_payload`

Filter the submission data to be sent to the backend.

Arguments:

1. `array $payload`: Associative array with form submission data.
2. `array $uploads`:Associative array with form submission uploaded files.
3. `array $form_data`: Associative array with form object information.

```php
add_filter('wpct_erp_forms_payload', function ($payload, $uploads, $form_data) {
	return $payload;
}, 10, 3);
```

#### `wpct_erp_forms_uploads`

Filters uploaded files to be sent to the backend.

Arguments:

1. `array $uploads`: Associative array with form submission uploaded files.
2. `array $form_data`: Associative array with form object information.

Example:

```php
add_filter('wpct_erp_forms_uploads', function ($uploads, $form_data) {
	return $uploads;
}, 10, 3);
```
#### `wpct_erp_forms_endpoints`

Filters the endpoints array to be used for each submission.

Arguments:
1. `array $endpoints`: Associative array with two positional arrays, one for each protocol, REST and RPC,
with endpoints as string values. It will trigger one http request for each endpoint on the lists.
_* Endpoints are relative to the **base_url** option defined on the options page.
2. `array $payload`: Associative array with form submission data.
3. `array $uploads`:  Associative array with form submission uploaded files.
4. `array $form_data`: Associative array with form object information.

Example:

```php
add_filter('wpct_erp_forms_endpoints', function ($endpoints, $payload, $files, $form_data) {
	return $endpoints;
}, 10, 4);
```

#### `wpct_erp_forms_rpc_login`

Filters the login payload of the JSON-RPC submissions.

Arguments:
1. `array $payload`: JSON-RPC Login payload

Example:

```php
add_filter('wpct_erp_forms_rpc_login', function ($payload) {
	return $payload;
});
```

#### `wpct_erp_forms_rpc_payload`

Filters the JSON-RPC form submission payload.

Arguments:
1. `array $payload`: Associative array with JSON-RPC payload containing form submission data.
2. `array $uploads`: Associative array with form submission uploaded files.
3. `array $form_data`: Associative array with form object information.

Example:

```php
add_filter('wpct_erp_forms_rcp_payload', function ($payload, $uploads, $form_data) {
	return $payload;
});
```

### Actions

#### `wpct_erp_forms_before_submission`

Action to do just before submission has been sent to the backend.

Arguments:

1. `array $payload`: Associative array with form submission data.
2. `array $uploads`:Associative array with form submission uploaded files.
3. `array $form_data`: Associative array with form object information.

Example:

```php
add_action('wpct_erp_forms_before_submission', function ($payload, $files, $form_data) {
	// do something
}, 10, 3);
```

#### `wpct_erp_forms_after_submission`

Action to do after the submission has been succesfuly sent to the backend.

Arguments:

1. `array $payload`: Associative array with form submission data.
2. `array $uploads`:Associative array with form submission uploaded files.
3. `array $form`: Associative array with form object information.

Example:

```php
add_action('wpct_erp_forms_after_submission', function ($payload, $files, $form) {
	// do something
}, 10, 3);
```
#### `wpct_erp_forms_on_failure`

Action to do after a request connexion error with the backend.

Arguments:

1. `array $payload`: Associative array with form submission data.
2. `array $uploads`:Associative array with form submission uploaded files.
3. `array $form`: Associative array with form object information.

Example:

```php
add_action('wpct_erp_forms_on_failure', function ($payload, $files, $form) {
	// do something
}, 10, 3);
```

## Wpct HTTP Bridge

This plugins needs [Wpct HTTP Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-http-bridge/) to work.

With this plugins hooks you can customize the submission http requests. See it's [README.md](https://git.coopdevs.org/codeccoop/wp/plugins/wpct-http-bridge/-/blob/main/README.md?ref_type=heads)
for more information.

Example:

```php
// Add Authorization header to the request befor its sent
add_filter('wpct_http_headers', function ($headers, $method, $url) {
	if ($url === '/custom/endpoints' && $method === 'POST') {
		$headers['Authorization'] = 'Bearer ' . getenv('BACKEND_TOKEN');
	}

	return $headers;
}, 10, 3);
```
