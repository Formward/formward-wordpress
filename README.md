# Formward Forms (WordPress plugin)

A small WordPress plugin that adds a contact form backed by [Formward](https://formward.eu), the form backend hosted in Sweden, with one shortcode. No mail plugin, no SMTP settings, no third-party JavaScript: the plugin renders a plain HTML form that posts to your Formward endpoint, and Formward handles storage, spam filtering and notifications.

`readme.txt` is the WordPress.org directory readme; this file is the developer note.

## What it does

- Adds a settings page under **Settings, Formward** where the admin stores a default Formward **Form ID**, an optional endpoint base (default `https://forms.formward.eu`), an optional read-only **API key**, and an optional API base URL (default `https://app.formward.eu`).
- Registers a `[formward_form]` shortcode that renders an accessible name / email / message contact form. The form posts (`method="POST"`) to `https://forms.formward.eu/f/<form-id>` and includes the `_gotcha` honeypot field that must stay empty.
- Supports per-form overrides: `id` (form ID), `redirect_url` (mapped to the hidden `_redirect` field), `button` (submit label), and `class` (one or more space-separated CSS classes).
- With an API key set (scopes `forms:read` and `submissions:read`), adds a top-level **Formward** admin menu: **Forms** (your forms and IDs, fetched from the REST API) and **Submissions** (a read-only `WP_List_Table` of recent submissions per form).

All output is escaped (`esc_attr`, `esc_url`, `esc_html`, `wp_kses`), direct access is guarded (`if ( ! defined( 'ABSPATH' ) ) exit;`), and inputs are sanitised before being stored or echoed. Endpoint and API base URLs are HTTPS-only (http is allowed only for `localhost` and `127.0.0.1`). The saved API key is never rendered into the page: the field shows a masked hint and the real key stays in the options table (leave the field blank to keep it, tick "Clear" to remove it).

`redirect_url` accepts an external URL by design (for example an off-site thank-you or booking page). Shortcode authorship is already a trusted capability (editors and admins), so this is intentional, not an open redirect for untrusted input.

## Privacy and EU hosting

Submissions are processed on servers in Sweden; submitter IP addresses are pseudonymised on receipt; a Data Processing Agreement and the sub-processor list are public at [formward.eu/compliance](https://formward.eu/compliance). The plugin adds no tracking and loads no third-party script.

## Shortcode usage

```text
[formward_form]                                  Uses the saved default Form ID.
[formward_form id="abc123"]                      Overrides the Form ID for one form.
[formward_form redirect_url="/thank-you"]        Redirects after submit (hidden _redirect).
[formward_form button="Get in touch" class="my-form"]
```

## Files

| File | Purpose |
| --- | --- |
| `formward-forms.php` | Main plugin: header, settings page, `[formward_form]` shortcode, admin pages. |
| `readme.txt` | WordPress.org-format readme (shown on the plugin directory page). |
| `README.md` | This developer note. |

## Packaging for WordPress.org

WordPress expects the plugin files inside a folder named after the slug (`formward-forms`):

```bash
mkdir -p build/formward-forms-forms-forms-forms-forms
cp formward-forms.php readme.txt build/formward-forms-forms-forms-forms-forms/
cd build && zip -r formward-forms.zip formward-forms
```

Do not include `README.md` in the ZIP. Bump the version in both the plugin header (`Version:` in `formward-forms.php`) and `readme.txt` (`Stable tag:`) for each release, and add a `== Changelog ==` entry.

## Local testing

```bash
php -l formward-forms.php
```

For an integration test, drop the `formward` folder into a local WordPress install's `wp-content/plugins/`, activate it, set a Form ID under **Settings, Formward**, and place `[formward_form]` on a page.

## License

GPLv2 or later. See [LICENSE](./LICENSE).
