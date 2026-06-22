=== Pillow Mockup Generator ===
Contributors: aviad
Tags: ai, openrouter, mockup, lead generation, image generation
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later

Mixtiles-style widget that turns a customer photo into a custom-shaped pillow mockup with OpenRouter, captures leads, and saves the original / mockup / print-ready cut-out images.

== Description ==

Drop the shortcode `[pillow_mockup]` anywhere. Visitors upload a photo, watch a Lottie loader, and get an AI-generated mockup of a die-cut pillow on a sofa.

Flow:

1. The first mockup is free.
2. To try other results or continue, the visitor enters name, phone and email. This emails you the lead and sends the visitor a confirmation.
3. After entering details the visitor gets a configurable number of extra tries.
4. When they choose their favourite, the plugin generates a print-ready, transparent die-cut cut-out and emails it to the admin.

Each registrant stores three files: the original photo, the chosen mockup, and the print-ready cut-out. The dashboard shows request counts and AI cost per stage.

== Features ==

* OpenRouter integration with selectable model (admin API key).
* Free first preview, then a details gate, then N retries.
* Lead/registrants admin area with the three saved files per lead.
* Request and cost tracking per stage and overall.
* Admin + customer email notifications.
* `[pillow_mockup]` shortcode, fully translatable front-end texts.
* No inline CSS on the front end.

== Installation ==

1. Upload the `pillow-mockup-generator` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to **Pillow Mockup → Settings** and add your OpenRouter API key, then pick a model (e.g. `google/gemini-2.5-flash-image`).
4. Add `[pillow_mockup]` to any page or post.

== Changelog ==

= 1.4.0 =
* New: size selection screen — after "I love it", visitors pick Small / Medium / Large with a sale price and crossed-out compare-at price (all editable under Settings → Sizes & pricing).
* New: the registration form now shows an order summary (chosen size + price), and the order's size and price are saved on the lead, shown in the admin order screen, and included in the customer/admin emails.
* Changed: visitors can now generate up to a configurable number of free mockups (default 3) via "Change photo" without registering; details are only collected at checkout.
* New: floating labels on all details-form fields (label sits inside the input and floats up on focus/fill).
* Changed: a failed mockup generation now shows a friendly inline message ("יצירת המוקאפ נכשלה. נסו שנית.") instead of a browser alert with the raw server response.

= 1.3.0 =
* New: visitors now keep their session between visits — returning to the page shows the mockups they already generated instead of starting over.
* New: a gallery of all generated mockups appears under the preview so visitors can pick their favourite before ordering.
* Changed: finishing an order is now instant — the "order received" email to the customer and the order notification to the admin are sent immediately on the final step (and only there).
* Changed: the print-ready cut-out is no longer generated during checkout; it is now produced on demand from the order screen (Registrants → open an order → Generate cut-out).
* Removed: the "another result" button; additional mockups are created by changing the photo.
* Changed: the details form "Back" button now reads "חזרה".

= 1.2.0 =
* New: a "Font family" setting (Settings → Appearance & texts) lets you type a custom CSS font-family that is applied to the whole widget. Leave it empty to inherit the site font.

= 1.1.1 =
* Changed: the preview action bar (change photo / try again / continue) now sticks to the bottom of the screen while scrolling, so the main actions are always within reach.

= 1.1.0 =
* Changed: removed the icons inside the details form inputs for a cleaner look; input text now aligns flush with the field padding.

= 1.0.9 =
* Fixed: theme styles (such as Elementor "kit" button rules) could override the widget's buttons and inputs, changing their colors, fonts and sizes. The widget now re-asserts its own styling with higher CSS specificity so it stays consistent across themes.

= 1.0.8 =
* New: a small boot loader is shown while the page loads, so visitors no longer see the widget elements jump into place.
* Changed: removed the inner padding around the preview frame so the image fills it.

= 1.0.7 =
* New: the generated mockup can now be enlarged — click the image (or the zoom badge) to open a full-screen lightbox, then click again to zoom in and move the cursor to pan around the details. Close with the X, the backdrop, or Escape.

= 1.0.6 =
* Changed: the details form now collects first name and last name as two separate fields (shown side by side). They are combined into the full name on the saved lead. Labels are editable in Settings.

= 1.0.5 =
* Changed: the front-end widget now ships with Hebrew texts by default and is forced RTL. Existing installs are migrated automatically (only untouched English defaults are replaced; customised texts are kept).
* Changed: the widget now inherits the site/theme font family instead of loading and forcing Heebo from Google Fonts.

= 1.0.4 =
* Improved: a more inviting details form — filled rounded inputs with leading icons, friendly focus glow, an encouraging subtitle, a privacy reassurance line, and softer buttons. The subtitle and privacy note are editable in Settings.

= 1.0.3 =
* New: redesigned the upload screen in a Mixtiles style — an empty framed placeholder with a pink "+" button that opens an upload popup ("Upload Photos"). Drag & drop still supported.

= 1.0.2 =
* New: choose the mockup and cut-out models from a dropdown of all Nano Banana models (Gemini 2.5 Flash Image, 2.5 Preview, 3.1 Flash Image, and Nano Banana Pro), with an "Other / custom model" option for any other OpenRouter image model.

= 1.0.1 =
* Fix: anonymous visitors on cached pages no longer hit a "not allowed" permissions error (the REST nonce is now refreshed and retried on 401 as well as 403).
* Fix: the loading screen no longer shows a duplicated Lottie/spinner.

= 1.0.0 =
* Initial release.
