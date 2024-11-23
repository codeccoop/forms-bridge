# Forms Bridge

![Forms Bridge]()

Bridge WP form builder plugins to any backend over http requests.

Forms Bridge has integrations for [GravityForms](https://www.gravityforms.com)
, [Contact Form 7](https://contactform7.com/) and [WP Forms](https://wpforms.com/).

The plugin allow comunication with any backend over REST or JSON-RPC API protocols.

> Http requests will be sent with data encoded as `application/json` if there is no
uploads. Else if form submission contains files, the default behavior is to send data
as `multipart/formdata` encodec content type.

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
	Each relation needs a unique name, a form ID, a backend, and an endpoint. Submission
	will be sent as encoded JSON objects.
3. JSON-RPC API
	* **RPC API endpoint**: Entry point of your ERP JSON-RPC external API.
	* **API user login**: Login of the ERP's user to use on the API authentication requests.
	* **User password**: Password of the user.
	* **Database name**: Database  name to be used.
	* **Form Hooks**: A list of hooked forms and it's relation with your backend models.
	Each relation needs a unique name, a from ID, a backend, and a model. Submission will
	be sent encoded as JSON-RPC payloads.

## Developers

The plugin offers some hooks to expose its internal API. Go to [documentation](./docs/API.md)
to see more details about the hooks.

## Dependencies

This plugin relays on [HTTP Bridge](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/http-bridge/)
and [Wpct i18n](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/i18n/) as depenendencies,
as well as the [Wpct Plugin Abstracts](https://git.coopdevs.org/codeccoop/wp/plugins/wpct/plugin-abstracts)
snippets. The plugin comes with its dependencies bundled in its releases, so you should
not worry about its managment. You can see this plugins documentation to know more about
its APIs.

## Roadmap

1. [ ] More agonstic JSON-RPC support decoupled from Odoo JSON-RPC API.
2. [X] Rename plugin to Forms Bridge.
3. [ ] Publish on wordpress.org repositories.
4. [ ] Backend connectors as an opt-in list with Odoo JSON-RPC API suited integration.
5. [ ] Backend connectors as an opt-in list with Dolibarr REST API suited integration.
6. [ ] Add test coverage with phpunit.
