=== Pillow Mockup Generator ===
Contributors: aviad
Tags: ai, openrouter, mockup, lead generation, image generation
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.4
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
