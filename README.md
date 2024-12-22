# ![Forms Bridge](./assets/logo.png)

Bridge WP form builder plugins to any backend over HTTP requests.

Forms Bridge has integrations for [GravityForms](https://www.gravityforms.com)
, [Contact Form 7](https://contactform7.com/) and [WP Forms](https://wpforms.com/).

The plugin allow comunication with any backend over REST or JSON-RPC API protocols.

## Installation

Download the [latest release](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge/-/releases/permalink/latest/downloads/plugins/bridges/forms-bridge.zip)
as a zipfile. Once downloaded, go to your site plugins page and upload the zip file
as a new plugin, WordPress will do the rest.

> Go to the [releases](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge/-/releases)
to find previous versions.

If you have access to a console on your server, you can install it with `wp-cli` with
the next command:

```shell
wp plugin install https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge/-/releases/permalink/latest/downloads/plugins/bridges/forms-bridge.zip
```

## Getting started

Install your preferred form builder from the available integrations and build your web
forms. Once done, go to `Settings > Forms Bridge` to bridge your forms. The settings page
has three main sections:

1. General
	* **Notification receiver**: Email address receiver of failed submission notifications.
	* **Backends**: List of configured backend connections. Each backend needs a unique
	name, a base URL, and, optional, a map of HTTP headers.
2. REST API
	* **Form Hooks**: A list of hooked forms and it's relation with your backend endpoints.
	Each relation needs a unique name, a form ID, a backend, a HTTP method, and an endpoint.
	Submission will be sent as encoded JSON data.
3. Odoo JSON-RPC
	* **RPC API endpoint**: Entry point of your Odoo JSON-RPC external API.
	* **API user login**: Login of the Odoo user to use on the API authentication requests.
	* **User password**: Password or APIKEY of the user.
	* **Database name**: Database  name to be used.
	* **Form Hooks**: A list of hooked forms and it's relation with your backend models.
	Each relation needs a unique name, a from ID, a backend, and a model. Submission will
	be sent encoded as JSON-RPC payloads.

Once configured, try to submit data with one of your hooked forms and watch the magic
happen 🙌!

## Backends

Forms Bridge use [Http Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/http-bridge/)
backends as a foundational part of its system. With this feature, Forms Bridge can be configured
with many backend connexions configured to establish HTTP requests against.

Each backend needs a unique name that identifies it and a base URL. The base URL will be
prepended to your form hook endpoints to build the URLs from the backend HTTP API.

To each backend you can set a collection of HTTP headers be sent on each request. In addition,
Http Bridge will add some default headers to the request.

### Content type

With the `Content-Type` header you can modify how Forms Bridge encode your submission data
before is sent. Supported content types are: `application/json`, `application/x-www-form-urlencoded`
and `multipart/form-data`. **JSON is the default encoding schema if there is no** `Content-Type`
**header on the backend configuration**.

If you needs any other encoding schema, you have to use `forms_bridge_payload` filter to encode
your submission as string. When data comes as string, Forms Bridge skips the encoding step and
sets the unmodified payload as the body of the request.  

## Form Hooks

Forms Bridge use form hooks to link your form submissions to your backends. There are two
types of form hooks:

1. `REST hooks`: Form submissions will be sent to the backend as HTTP REST requests. You
   can select which HTTP method to use and a backend configuration to build the request.
2. `JSON-RPC hooks`: Form submissions will be sent to the backend as JSON-RPC calls. To
   allow this kind of communication, Forms Bridge needs to establish a session with the
   backend and use some credentials: The target database, the username, the password, and
   the target model. Data will be sent as POST requests encoded as JSON data.

> 🚩 For REST HTTP methods GET and DELETE, the request has no body and your data will be sent
> as URL query params.

## Attachments

In Forms Bridge, attachments are files that has to be sent with your submission data to a
backend.

By default, Forms Bridge will send this files as binary data using the `multipart/form-data`
encoding schema unless your backend connexions has a different `Content-Type` HTTP header.
Forms Bridge will check to the form hook's backend for this header. If it exists and is
not `multipart/form-data`, Forms Bridge will include this files as base64 encoded strings to
your payload.

> 🚩 On JSON-RPC hooks attachments will always be encoded as base64 content and included to
> your submission data. This is needed because JSON-RPC API does not support `multipart/form-data`
> content types.

## Form Pipes

Each hooked form can be configured with transform pipes. With this pipes, you can transform
your form submissions into your backend API schemas. Form pipes allows you to rename
variables, force primitive types casting and mutate data structures. If your form submission
model does not fit your backend API schema, mutate it with pipes before its sendend over
the network.

Generaly, form submissions where stored as a plain associative array of fields and values.
**If do you need nested data structures, us JSON fingers to achive it**.

If you need more complex transformations, use the plugin's hooks to transform form submissions
before they were sent. See the [filters](./docs/API.md#filters) documentation to get more
informatinon.

### JSON Fingers

The form pipes supports JSON Fingers as payload attribute names. A JSON Finger
is a hierarchical pointer to array attributes like `children[0].name.rendered`. The former
will point to the attribute `rendered` from the array `name` inside the first `child`
in the array `children`. Use this fingers to set your payload attributes from your form's
submissions.

For example, if your backend waits for an payload like this:

```php
$payload = [
	'name' => 'Bob',
	'address' => [
		'street' => 'Carrer de Balmes, 250',
		'city' => 'Barcelona'
	],
];`
```

Then you can rename your form fields `street` and `city` as `address.street` and `address.city`
and cast them as strings. JSON fingers will create the nested array on your form submission
payload and remove the original fields.

## Developers

The plugin offers some hooks to expose its internal API. Go to [documentation](./docs/API.md)
to see more details about the hooks.

### Local development

The repository handles dependencies as [git submodules](https://www.atlassian.com/git/tutorials/git-submodule).
In order to work local, you have to clone this repository and initialize its submodules
with this command:

```bash
git submodule update --init --recursive
```

Once done, you will need to install frontend dependencies with `npm install`. To build
the admin's react client, run `npm run dev` for development, or `npm run build` for
production builts.

> We work WordPress with docker. See our [development setup](https://github.com/codeccoop/wp-development/)
> if you are interested.

## Dependencies

This plugin relays on [Http Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/http-bridge/)
and [Wpct i18n](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/i18n/) as depenendencies,
as well as the [Wpct Plugin Abstracts](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/plugin-abstracts)
snippets. The plugin comes with its dependencies bundled in its releases, so you should
not worry about its managment. You can see this plugins documentation to know more about
its APIs.
