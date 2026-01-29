# Forms Bridge

[![Plugin version](https://img.shields.io/wordpress/plugin/v/forms-bridge)](https://wordpress.org/plugins/forms-bridge/)
![GitHub Actions Tests Workflow Status](https://img.shields.io/github/actions/workflow/status/codeccoop/forms-bridge/tests.yml?label=tests)

Seamlessly connect WordPress forms to CRMs, ERPs, and APIs — no coding required. Automate data flow with field mappers, custom fields, and workflows.

## Bridges

Think of a bridge as a pipeline through which your form submissions data flows
to your backend or service. In the middle, you can add custom fields to the form
submissions, use field mappers to rename and mutate your form responses, or use
workflow jobs to process the data before it is sent over the wire. With bridges you
can connect your WordPress forms to any kind of backend, it doesn't matter if it
is a CRM, an ERP, a booking system or an email marketing platform, the only requirement
is an HTTP API. If it has an API it can be bridged!

## Form builders

Form builders are well known plugins that add forms to WordPress. We do bridges,
let them do the forms and then work together to make your business work with ease.

Forms Bridge supports the following form builders:

- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
- [Formidable Forms](https://wordpress.org/plugins/formidable/)
- [GravityForms](https://www.gravityforms.com)
- [WP Forms](https://wpforms.com/)
- [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)
- [WooCommerce](https://wordpress.org/plugins/woocommerce)

## Add-ons

Forms Bridge comes with free add-ons. Each add-on adds to the plugin new bridges
to work with specific APIs, new workflow jobs and bridge templates.

Forms Bridge has the following add-ons:

**📊 CRM & Sales**

- [Bigin](https://formsbridge.codeccoop.org/documentation/bigin/)
- [SuiteCRM](https://formsbridge.codeccoop.org/documentation/suitecrm/)
- [Vtiger](https://formsbridge.codeccoop.org/documentation/vtiger/)
- [Zoho CRM](https://formsbridge.codeccoop.org/documentation/zoho-crm/)

**📦 ERP & Accounting**

- [Dolibarr](https://formsbridge.codeccoop.org/documentation/dolibarr/)
- [FinanCoop](https://formsbridge.codeccoop.org/documentation/financoop/)
- [Holded](https://formsbridge.codeccoop.org/documentation/holded/)
- [Odoo](https://formsbridge.codeccoop.org/documentation/odoo/)

**📧 Marketing & Email**

- [Brevo](https://formsbridge.codeccoop.org/documentation/brevo/)
- [Mailchimp](https://formsbridge.codeccoop.org/documentation/mailchimp/)
- [Listmonk](https://formsbridge.codeccoop.org/documentation/listmonk/)

**🗓️ Productivity**

- [Airtable](https://formsbridge.codeccoop.org/documentation/airtable)
- [Google Calendar](https://formsbridge.codeccoop.org/documentation/google-calendar/)
- [Google Sheets](https://formsbridge.codeccoop.org/documentation/google-sheets/)
- [Nextcloud](https://formsbridge.codeccoop.org/documentation/nextcloud/)

**📨 Messaging & Collaboration**

- [Rocket.Chat](https://formsbridge.codeccoop.org/documentation/rocket-chat/)
- [Slack](https://formsbridge.codeccoop.org/documentation/slack/)
- [Zulip](https://formsbridge.codeccoop.org/documentation/zulip/)

**Need a custom integration?**

Connect to any custom API using standard HTTP methods and authentication using the abstract **REST API** add-on. Perfect for integrating with proprietary or less common systems.

## Backends

In Forms Bridge, a backend is a set of configurations that handles the
information required to get your form submissions bridged over HTTP requests
to remote systems.

To register a new backend you only have to set 3 fields:

1. A unique name for the new connection
2. The URL of your backend
3. An array of HTTP headers with connection metadata and credentials
4. Optional, an HTTP authentication credential (Basic, Bearer, etc)

Once registered, you can reuse your backend connection on your form bridges.

## Custom fields

Custom fields are data that will be added the bridge payload. Use them to store
private data you don’t want to place on your public forms, like user emails, or
config values, like product IDs or lead tags.

## Field mappers

Field mappers are mutations with which you can rename your form submission
fields and transform its values. Use them to make your form submissions to
fit your backend API endpoint interface.

## Workflows

Make your form submissions flow through a chain of jobs that pre-process the
data before it was sent over the wire. Think of workflow as a system to set up
automations to run on each form submission.

## Templates

To streamline the bridge setup process, Forms Bridge comes packed with templates. Templates are blueprints of bridges you can use to set up your form integrations in a matter of minutes.

## Docs

Browse the plugin's documentation on [formsbridge.codeccoop.org](https://formsbridge.codeccoop.org/documentation/)

## Links

- [Official website](https://formsbridge.codeccoop.org/)
- [GitHub](https://github.com/codeccoop/forms-bridge/)
- [Còdec](https://www.codeccoop.org)
- [Other plugins](https://profiles.wordpress.org/codeccoop/#content-plugins)

## Development

### API

The plugin offers some hooks to expose its internal API. Go to
[documentation](https://formsbridge.codeccoop.org/documentation/#api) to see
more details about the hooks.

### Dependencies

The repository handles dependencies as [git submodules](https://www.atlassian.com/git/tutorials/git-submodule).
In order to work local, you have to clone this repository and initialize its submodules
with this command:

```
git submodule sync
git submodule update --init
```

Once done, install JS dependenices with `npm install` and PHP dependencies with
`composer install`.

### Build

Frontend builds are made with [esbuild](https://esbuild.github.io/). Once you
have your JS dependencies installed you can run `npm run dev` to perform
a live build, or `npm run build` to get a production build.

### Lint and format

For JavaScript the project uses [prettier](https://prettier.io/) as a formatter
[eslint](https://eslint.org/) as the linter.

For PHP the project uses [phpcs](https://github.com/squizlabs/PHP_CodeSniffer)
as the linter and formatter.

Lint and format will be applied to staged files before each commit. In addition,
merge requests performs a lint test in order to be accepted.

### Tests

To run the projects test you have to execute the script `bin/install-wp-tests.sh`
in order to get the WordPress test suit installed in your local machine. Once done,
run `composer run test` to run project's unit tests.

If you have docker on your local machine, you can run tests in an ephemeral environment
with the script `bin/test-on-docker.sh`.
