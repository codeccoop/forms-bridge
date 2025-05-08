=== Forms Bridge ===
Contributors: codeccoop
Tags: forms, api, integration, crm, erp
Donate link: https://buymeacoffee.com/codeccoop
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable Tag: 3.4.1
Tested up to: 6.8

Bridge your WordPress forms without code, add custom fields, set up a workflow and make your data flow to your backend.

== Integrations ==

Integrations are well known plugins that add forms to WordPress. We do bridges, let them do the forms and then work together to make your business work with ease.

Forms Bridge has integrations with:

* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
* [GravityForms](https://www.gravityforms.com)
* [WP Forms](https://wpforms.com/)
* [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce)

== Addons ==

Forms Bridge comes with free addons. Each addon adds to the plugin new bridges to work with specific APIs, new workflow jobs and bridge templates.

Forms Bridge has core support for generic REST API bridges and addons for:

* [Bigin](https://www.bigin.com/developer/docs/apis/v2/?source=developer)
* [Brevo](https://developers.brevo.com/)
* [Dolibarr](https://wiki.dolibarr.org/index.php/Module_Web_Services_API_REST_(developer))
* [FinanCoop](https://somit.coop/financoop/)
* [Google Sheets](https://workspace.google.com/products/sheets/)
* [Holded](https://developers.holded.com/reference/api-key)
* [Listmonk](https://listmonk.app/docs/apis/apis/)
* [Mailchimp](https://mailchimp.com/developer/)
* [Odoo](https://www.odoo.com/)
* [Zoho CRM](https://www.zoho.com/developer/rest-api.html)

== Docs ==

Browse the plugin's documentation on [formsbridge.codeccoop.org](https://formsbridge.codeccoop.org/documentation/)

== Links ==

* [Official website](https://formsbridge.codeccoop.org/)
* [Gitlab](https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge/)
* [Còdec](https://www.codeccoop.org)
* [Other plugins](https://profiles.wordpress.org/codeccoop/#content-plugins)

== Screenshots ==

1. Settings page
2. Backends
3. Custom fields
4. Workflows
5. Template wizard
6. Debug console

== Changelog ==

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
