![Forms Bridge](./assets/icon-256x256.png)

Bridge WP form builder plugins to any backend or service over HTTP requests.

Forms Bridge has integrations for [GravityForms](https://www.gravityforms.com)
, [Contact Form 7](https://contactform7.com/), [WP Forms](https://wpforms.com/)
and [Ninja Forms](https://wordpress.org/plugins/ninja-forms/).

## Getting started

Install your preferred form builder from the available integrations and build your web
forms. Once done, go to `Settings > Forms Bridge` to bridge your forms. The settings page
is divided by tabs. By default, the **General** and **REST-API** tabs will be visible.
As you activate addons, new tabs will be shown.

1. General
	* **Notification receiver**: Email address receiver of failed submission notifications.
	* **Backends**: List of configured backend connections.
	* **Addons**: Panel to manage addons. See the [addons](#addons) section to get know
	which addons are available.
	* **Debug**: Activate the logging console to see what's going on on inside WordPress.
	This feature allow you to debug your form submissions while you are configuring your
	hooks.
	* **Export/Import**: Export and import Forms Bridge configurations as json files.
2. REST API
	* **Bridges**: Panel with configured form bridges.

Once configured, try to submit data with one of your hooked forms and watch the magic
happen 🙌!

## Addons

Addons are moduls that allow Forms Bridge to be connected with special backends. This addons
are available:

1. **REST API**: The default addon, always active. With this addon you can bridge your form
   submissions over the REST API of your backends.
1. **Odoo**: With this addon you can bridge your forms to Odoo over the JSON-RPC API. Fill the
   gap between your CMS and your ERP and scale up your business.
2. **Google Sheets**: With this addon you can get your form submissions synchronized with google
   spreadsheets. Focus on your data, share it with your team and don't bother them with the
   wordpress admin page.
3. **FinanCoop**: Addon to bridge forms to the FinanCoop Odoo's module. This module is intended to
   manage subscription and loan requests for cooperatives and non-profit organizations.

To get more details about this addons, go the the [documentation](./docs/Addons.md).

## Bridges

The key concept of this plugin is the **bridge**. A bridge is a configured connexion between
a form and a backend or service with a unique name. Each addon of the Forms Bridge allows to
create a new kind of bridge, with this bridges you can connect your forms to multiple HTTP APIs.

As we've mentioned, a bridge is a connexion between a backend and a form. In addition, and
based on the addon the bridge comes from, this connexion will need some other fields. For
example, for a REST API bridge, each connexion will need an endpoint and an HTTP method.

### Templates

Forms Bridge comes with multiple bridge templates for each addon. With templates you can
automate the from and backend creation and its subsequent bound on a bridge. When you
use templates, Forms Bridge will guide you over a simple and assisted process of steps
where the template will require you to inform some fields. Once the process is completed,
Forms Bridge will create a form on the integration you choose (GF, WPCF7, WPForms, etc)
with a default layout and fields, configure a new backend connexion and bound them on a
new bridge ready to use.

## Backends

Forms Bridge use [Http Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/http-bridge/)
backends as a foundational part of its system. With this feature, Forms Bridge can be configured
with many backend connexions to send submissions.

Each backend needs a unique name that identifies it and a base URL. The base URL will be
prepended to your form hook endpoints to build the URLs from the backend HTTP API.

To each backend you can set a collection of HTTP headers to be sent on each request. In addition,
Http Bridge will add some default headers to the request.

### Content type

With the `Content-Type` header you can modify how Forms Bridge encode your submission data
before is sent. Supported content types are: `application/json`, `application/x-www-form-urlencoded`
and `multipart/form-data`. **JSON is the default encoding schema if there is no** `Content-Type`
**header on the backend configuration**.

If you needs any other encoding schema, you have to use `forms_bridge_payload` filter to encode
your submission as string. When data comes as string, Forms Bridge skips the encoding step and
sets the unmodified payload as the body of the request.

> 🚩 For HTTP methods GET and DELETE, the request has no body and your data will be sent as URL
> query params.

## Attachments

In Forms Bridge, attachments are files that has to be sent with your submission data to a
backend.

By default, Forms Bridge will send this files as binary data using the `multipart/form-data`
encoding schema unless your backend connexions has a different `Content-Type` HTTP header.
Forms Bridge will check to the form bridge's backend for this header. If it exists and is
not `multipart/form-data`, Forms Bridge will include this files as base64 encoded strings to
your payload.

## Form Pipes

Each bridged form can be configured with transform pipes. With this pipes, you can transform
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
git submodule sync
git submodule update --init
```

Once done, you will need to install frontend dependencies with `npm install`. To build
the admin's react client, run `npm run dev` for development, or `npm run build` for
production builts.

The last step is to install google-sheets addon dependencies with composer:

```bash
cd addons/google-sheets
composer install
```

> We work WordPress with docker. See our [development setup](https://github.com/codeccoop/wp-development/)
> if you are interested.

## Dependencies

This plugin relays on [Http Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/http-bridge/)
and [Wpct i18n](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/i18n/) as depenendencies,
as well as the [Wpct Plugin Abstracts](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/plugin-abstracts)
snippets. The plugin comes with its dependencies bundled in its releases, so you should
not worry about its managment. You can see this plugins documentation to know more about
its APIs.
