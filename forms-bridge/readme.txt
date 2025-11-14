=== Forms Bridge - Infinite integrations ===
Contributors: codeccoop
Tags: odoo, dolibarr, listmonk, forms, woocommerce
Donate link: https://buymeacoffee.com/codeccoop
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable Tag: 4.1.2
Tested up to: 6.8

Bridge your WordPress forms without code, add custom fields, use field mappers, set up a workflow and make your data flow seamlessly to your backend.

== Bridges ==

Think of a bridge as a pipeline through which your form submissions data flows to your backend or service. In the middle, you can add custom fields to the form submissions, use field mappers to rename and mutate your form responses, or use workflow jobs to process the data before it is sent over the wire. With bridges you can connect your WordPress forms to any kind of backend, it doesn't matter if it is a CRM, an ERP, a booking system or an email marketing platform, the only requirement is an HTTP API. If it has an API it can be bridged!

== Form builders ==

Form builders are well known plugins that add forms to WordPress. We do bridges, let them do the forms and then work together to make your business work with ease.

Forms Bridge supports the following form builders:

* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
* [GravityForms](https://www.gravityforms.com)
* [WP Forms (PRO)](https://wpforms.com/)
* [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce)

== Addons ==

Forms Bridge comes with free addons. Each addon adds to the plugin new bridges to work with specific APIs, new workflow jobs and bridge templates.

Forms Bridge has the following addons:

* [REST API](https://en.wikipedia.org/wiki/REST)
* [Bigin](https://www.bigin.com/developer/docs/apis/v2/?source=developer)
* [Brevo](https://developers.brevo.com/)
* [Dolibarr](https://wiki.dolibarr.org/index.php/Module_Web_Services_API_REST_(developer))
* [FinanCoop](https://somit.coop/financoop/)
* [Google Sheets](https://workspace.google.com/products/sheets/)
* [Holded](https://developers.holded.com/reference/api-key)
* [Listmonk](https://listmonk.app/docs/apis/apis/)
* [Nextcloud](https://docs.nextcloud.com/server/20/user_manual/en/files/access_webdav.html)
* [Mailchimp](https://mailchimp.com/developer/)
* [Odoo](https://www.odoo.com/)
* [Zoho CRM](https://www.zoho.com/developer/rest-api.html)

== Backends ==

In Forms Bridge, a backend is a set of configurations that handles the information required to get your form submissions bridged over HTTP requests to remote systems.

To register a new backend you only have to set 3 fields:

1. A unique name for the new connection
2. The URL of your backend
3. An array of HTTP headers with connection metadata and credentials
4. Optional, an HTTP authentication credential (Basic, Bearer, etc)

Once registered, you can reuse your backend connection on your form bridges.

== Custom fields ==

Custom fields are data that will be added the bridge payload. Use them to store private data you don’t want to place on your public forms, like user emails, or config values, like product IDs or lead tags.

== Field Mappers ==

Field mappers are mutations with which you can rename your form submission fields and transform its values. Use them to make your form submissions to fit your backend API endpoint interface.

== Workflows ==

Make your form submissions flow through a chain of jobs that pre-process the data before it was sent over the wire. Think of workflow as a system to set up automations to run on each form submission.

== Templates ==

To streamline the bridge setup process, Forms Bridge comes packed with templates. Templates are blueprints of bridges you can use to set up your form integrations in a matter of minutes.

== Docs ==

Browse the plugin's documentation on [formsbridge.codeccoop.org](https://formsbridge.codeccoop.org/documentation/)

== Links ==

* [Official website](https://formsbridge.codeccoop.org/)
* [GitHub](https://github.com/codeccoop/forms-bridge/)
* [Còdec](https://www.codeccoop.org)
* [Other plugins](https://profiles.wordpress.org/codeccoop/#content-plugins)

== Screenshots ==

1. Settings page
2. Backends
3. Bridges
4. Custom fields
5. Field mappers
6. Workflows
7. Job editor
8. Template wizard
9. Debug console

== Changelog ==

= 4.1.1 =
* feat: bearer credential authorization based on access tokens + expiration date
* fix: gravityforms subfields serialization
* fix: theme space jobs loading
* fix: misleading nextcloud wizard fields descriptions

= 4.1.0 =
* feat: php unit testing
* feat: wordpress conding standards
* feat: http bridge refactor
* feat: repository directory refactor
* feat: test and lint ci jobs
* feat: github actions workflows

= 4.0.6 =
* fix: field serialization based on field type
* feat: add accpet-language http header on odoo's rpc api calls
* feat: new odoo job position and helpdesk ticket templates
* feat: new odoo workflow jobs
* feat: include textarea to the standard field types
= 4.0.5 =
* fix: prepare mappers loop introduced in 4.0.4
= 4.0.4 =
* feat: add remuneration_type form field on financoop subscription request template
* fix: minnor frontend fixes and improvements
* fix: financoop shortcode, templates and jobs
* fix: template config loading race conditions

= 4.0.3 =
* fix: load text domain warnings

= 4.0.2 =
* feat: mailchimp api url selector

= 4.0.1 =
* feat: rename form integrations to form builders
* feat: oauth grant as GET requests
* feat: dolibarr, odoo and holded sync products templates
* feat: brevo woo orders template
* fix: migrations enqueuement loop

= 4.0.0 =
* feat: Workflow jobs editor
* feat: Nextcloud addon
* fix: Edge case of mutations and fingers
* feat: HTTP authentication
* feat: Admin UI refactor
* feat: Wipe config button
* feat: Settings API refactor
* feat: Zoho and Google Oauth web based credentials
* feat: drop support for Google service credentials
* feat: drop support for Zoho Self Client credentials

= 3.5.4 =
* fix: use conditional mappers on stringify attachments
* fix: cast value type for join mutations

= 3.5.3 =
* feat: nename gsheet default backend
* fix: bridge request filter callback removal

= 3.5.2 =
* feat: new google sheets woocomerce orders template
* feat: disable default payload prune for gsheet bridges
* feat: update gsheet composer dependencies
* fix: remove php warnings on zoho and listmonk addons

= 3.5.1 =
* feat: improve dolibarr next code and product search api calls
* feat: add is_bridged woocommerce order meta data
* feat: new validate order job and template for the dolibarr addon
* feat: new delivered order template for the odoo addon
* feat: changes on the holded woocommerce template

= 3.5.0 =
* fix: woocommerce payload schema
* feat: woocommerce bridge templates support
* feat: woocommerce templates for odoo, dolibarr, holded, bigin, brevo, mailchimp and zoho
* feat: jon finger expansions
* feat: conditional json finger pointers
* feat: improvements on the workflows panel UI
* feat: backend and bridges json exports

= 3.4.3 =
* feat: bridge template descriptions
* feat: listmonk skip subscription job

= 3.4.2 =
* fix: holded appointments template jobs
* fix: typos from odoo workflow job descriptions
* feat: add new chapters to the plugin's readme
* feat: settings sanitization with defaults recovery

= 3.4.1 =
* feat: holded quotation templates
* feat: holded API introspection based on swagger data
* fix: bridge api schema invalidation
* feat: api fields button with disabled state

= 3.4.0 =
* feat: odoo quotation templates
* feat: dolibarr quotation templates
* feat: country id odoo workflow job
* feat: gmt date tags
* feat: addons data autoload
* feat: odoo state id job
* feat: skip email list subscription jobs
* fix: firefox backend state updates on firefox

= 3.3.5 =
* feat: support for ninja file fields and conditionals

= 3.3.4 =
* fix: does not skip empty array submissions on submission filter
* feat: remove gf private uploads module
* fix: scroll to bottom on mutations/custom fields tables

= 3.3.3 =
* feat: remove minLength constraint from bridge schema
* feat: set null value on mappers with nowhere jsonfinger pointers

= 3.3.2 =
* feat: update plugin urls and readme
* feat: remote assets from gitlab
* fix: mailchimp template wizard
* feat: update credits, donation link and screenshots

= 3.3.1 =
* fix: odoo api function bridge patches
* feat: plugin screenshots
* feat: update readme and plugin official url

= 3.3.0 =
* feat: introspection api
