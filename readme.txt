=== Formward Forms ===
Contributors: formward
Tags: contact form, forms, gdpr, eu hosting, privacy
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

EU-hosted, GDPR-clean contact forms for WordPress. Submissions are processed on servers in Sweden. No US data transfer, no tracking.

== Description ==

Formward Forms adds a plug-and-play contact form to your WordPress site, backed by [Formward](https://formward.eu), a European form backend hosted in Sweden.

Drop the `[formward_form]` shortcode on any page or post and it renders an accessible HTML contact form (name, email, message) that posts directly to your Formward endpoint. Your submissions land in the Formward dashboard, with email notifications, spam filtering, and a full export and erasure trail.

**Why Formward**

* **EU data residency.** Form submissions are processed on servers in Sweden. Your data never leaves the EU/EEA.
* **GDPR-clean by default.** A Data Processing Agreement (DPA) is available, sub-processors are documented, and submitter IPs are hashed on receipt.
* **AI stays in Europe.** Optional AI enrichment on paid plans runs via Mistral in France.
* **No lock-in.** The plugin posts to a standard HTML form endpoint. There is no SDK and nothing proprietary in your theme.

**What you get**

* An accessible HTML contact form via the `[formward_form]` shortcode.
* A built-in honeypot (`_gotcha`) for spam reduction.
* Optional redirect to your own thank-you page after submit.
* Per-form overrides: use one default Form ID, or pass a different `id` per shortcode.
* **Forms list in WP admin.** Paste a read-only API key and the **Formward → Forms** screen lists every form on your account with its Form ID, so you can copy an ID without leaving WordPress.
* **Submissions viewer.** **Formward → Submissions** shows recent submissions for any form in a sortable WordPress list table (received date, status, and a compact preview of the payload). Read-only: nothing is changed or deleted.

This plugin is a thin, open-source client. It stores only your Form ID, endpoint base, and an optional read-only API key in the WordPress options table. It does not phone home and adds no tracking scripts.

== Installation ==

1. Create a form in your Formward dashboard at [formward.eu](https://formward.eu) and copy its **Form ID**.
2. Upload the `formward-forms` folder to `/wp-content/plugins/`, or install the ZIP from **Plugins → Add New → Upload Plugin**.
3. Activate **Formward Forms** through the **Plugins** menu in WordPress.
4. Go to **Settings → Formward** and paste your **Form ID** (leave the endpoint base as the default unless Formward gave you a custom one). Save.
5. Optional: create a read-only **API key** in your Formward dashboard under **API keys** (scopes `forms:read` and `submissions:read`) and paste it into the **Formward API key** field on the same settings screen. This unlocks the **Formward → Forms** and **Formward → Submissions** admin pages so you can browse your forms and recent submissions inside WordPress.
6. Add the shortcode `[formward_form]` to any page or post.

== Frequently Asked Questions ==

= Where do submissions go? =

To your Formward dashboard. The form posts to `https://forms.formward.eu/f/<your-form-id>`. You configure email notifications, webhooks, and exports inside Formward.

= Where is my data stored? =

On Formward's servers in Sweden. Form-submission data is designed to stay within the EU/EEA. A DPA is available on request from privacy@formward.eu.

= How do I use a different form on a specific page? =

Pass the `id` attribute: `[formward_form id="abc123"]`. It overrides the default Form ID from the settings page for that one form.

= Can I redirect visitors to a thank-you page after they submit? =

Yes. Add `redirect_url`: `[formward_form redirect_url="/thank-you"]`. The plugin maps it to Formward's hidden `_redirect` field.

= Can I change the submit button label? =

Yes: `[formward_form button="Get in touch"]`.

= Do I need an account? =

Yes, a free Formward account. The free plan includes 100 submissions per month. Paid plans (Personal, Professional, Business) add higher limits, file uploads, and AI enrichment.

= Is there any tracking or third-party JavaScript? =

No. The plugin outputs a plain HTML form. There are no analytics or marketing scripts.

== Changelog ==

= 0.2.1 =
* Text domain is now formward-forms, matching the plugin slug in the WordPress.org directory.
* Plugin URI points at the WordPress guide on formward.eu.
* Tested with WordPress 7.1.

= 0.2.0 =
* Added a **Formward API key** setting (read-only key, stored via the options API and shown masked) alongside an optional API base URL.
* New **Formward → Forms** admin page: lists your account's forms (name + Form ID + created date) via the REST API so you can copy a Form ID without leaving WordPress.
* New **Formward → Submissions** admin page: pick a form and view its recent submissions in a WordPress list table (received date, status, spam score, and a compact payload preview). Read-only.
* Missing or invalid API keys are handled gracefully with a clear admin notice.

= 0.1.0 =
* Initial release.
* Settings page under Settings → Formward (default Form ID and optional endpoint base, stored via the options API).
* `[formward_form]` shortcode rendering an accessible name/email/message form with the `_gotcha` honeypot.
* `id` attribute to override the default Form ID per form.
* `redirect_url` attribute mapped to the hidden `_redirect` field.
* `button` and `class` attributes for label and styling.

== Upgrade Notice ==

= 0.2.1 =
First WordPress.org release. The main plugin file is now formward-forms.php. If you installed 0.2.0 by hand from GitHub, deactivate and delete that copy before installing 0.2.1, then reactivate; WordPress does not remap a renamed main file on upgrade. Settings and the shortcode are unchanged.

= 0.2.0 =
Adds an optional read-only API key so you can browse your forms and recent submissions inside WP admin. The shortcode is unchanged.

= 0.1.0 =
First release.
