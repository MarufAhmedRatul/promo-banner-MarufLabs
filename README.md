# Promo Banner for WooCommerce — MarufLabs

[![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-blue?logo=wordpress)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-purple?logo=woocommerce)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange)](CHANGELOG.md)

> Animated, fully customisable promo banners for WooCommerce — target by page, category, or location hook. No page builder required.

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Uninstallation](#uninstallation)
5. [Plugin Structure](#plugin-structure)
6. [Usage Guide](#usage-guide)
   - [Creating a Banner](#creating-a-banner)
   - [Display Locations (Hooks)](#display-locations-hooks)
   - [Shortcodes](#shortcodes)
   - [Banner Groups](#banner-groups)
7. [Plugin Settings](#plugin-settings)
8. [Hooks & Filters Reference](#hooks--filters-reference)
9. [Changelog](#changelog)
10. [License](#license)

---

## Features

| Feature | Details |
|---------|---------|
| 🎨 **Custom Design** | Background colour, text colour, link colour, background image |
| ✨ **Animations** | Slide Down, Slide Up, Fade In, or None |
| 📍 **Flexible Placement** | 7 built-in WordPress/WooCommerce hook locations |
| 🔗 **Shortcode Support** | Embed banners anywhere with `[promo_banner]` |
| 📅 **Scheduling** | Set a start & end datetime for automatic display |
| 🎯 **Targeting** | Show on all pages, specific pages, blog categories, or WooCommerce product categories |
| ✕ **Dismissible** | Optional close button with configurable cookie duration |
| 🔢 **Priority System** | Control display order when multiple banners are active |
| ⚡ **Smart Asset Loading** | Load CSS/JS only on pages that have active banners |
| 📱 **Mobile Toggle** | Disable banners on mobile devices globally |
| 🗂️ **Banner Groups** | Organise banners by taxonomy for group-targeted shortcodes |

---

## Requirements

- WordPress **5.9** or higher
- PHP **7.4** or higher
- WooCommerce **7.0** or higher *(optional — non-WooCommerce hooks work without it)*

---

## Installation

### Method 1 — Upload via WordPress Admin

1. Download the plugin `.zip` file.
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**.
3. Select the `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2 — Manual Upload via FTP/SFTP

1. Unzip the plugin archive locally.
2. Upload the `promo-banner-plugin/` folder to `/wp-content/plugins/`.
3. In WordPress Admin, go to **Plugins** and activate **Promo Banner for WooCommerce**.

### After Activation

A welcome notice will appear with quick-access links to:
- **Create Your First Banner**
- **Plugin Settings**

If the notice does not appear, navigate to **Promo Banners** in the left admin menu.

---

## Uninstallation

> [!WARNING]
> **Deactivating** the plugin is completely safe — no data is removed.

> [!CAUTION]
> **Deleting** the plugin via the Plugins screen will permanently remove all data **only if** you have enabled the **"Delete All Data on Uninstall"** option in **Promo Banners → Settings → Advanced**. Otherwise, only the plugin's global options are removed; all banner posts remain in the database.

**To permanently remove all banner data:**
1. Go to **Promo Banners → Settings → Advanced**.
2. Check **Delete All Data on Uninstall**.
3. Save settings.
4. Go back to **Plugins**, deactivate, then **Delete** the plugin.

---

## Plugin Structure

```
promo-banner-plugin/
│
├── promo-banner.php                  ← Main plugin bootstrap file
├── uninstall.php                     ← WordPress uninstall hook entry point
├── README.md                         ← This file
│
├── includes/
│   ├── class-pb-installer.php        ← Activation / Deactivation / Uninstall logic
│   ├── class-pb-settings.php         ← Global Settings admin page
│   ├── class-pb-post-type.php        ← Custom Post Type & Taxonomy registration
│   ├── class-pb-admin.php            ← Admin meta boxes, columns, and asset loading
│   ├── class-pb-frontend.php         ← Frontend rendering, hook registration, AJAX
│   └── class-pb-shortcode.php        ← [promo_banner] shortcode handler
│
├── templates/
│   └── banner-template.php           ← HTML template for a single banner
│
└── assets/
    ├── css/
    │   └── pb-frontend.css           ← Frontend styles & animations
    └── js/
        ├── pb-admin.js               ← Admin UI interactions (colour picker, media, etc.)
        └── pb-frontend.js            ← Frontend animations, dismiss, floating padding
```

---

## Usage Guide

### Creating a Banner

1. Go to **Promo Banners → Add New Banner** in the WordPress admin.
2. Fill in the **Banner Content** meta box:
   - **Title** — the main headline.
   - **Subtitle** — optional supplemental text below the title.
   - **Button Text** / **Button URL** / **Link Target** — optional call-to-action.
3. Customise the **Design Settings**:
   - Background colour, text colour, link colour (colour pickers).
   - Optional background image (selected from the Media Library).
   - Animation type, font size, padding.
   - Enable the close button and set the cookie duration.
4. Configure **Display Location** (see [Display Locations](#display-locations-hooks)).
5. Optionally set a **Schedule** (start/end datetime) and **Priority**.
6. Click **Publish** — the banner will go live immediately (or on the scheduled start date).

---

### Display Locations (Hooks)

Select one or more hook locations in the **📍 Display Location** meta box:

| Location Slug | WordPress Hook | Description |
|---|---|---|
| `after_header` | `wp_body_open` | Immediately after `<body>` opens |
| `before_footer` | `wp_footer` | Before the closing `</body>` tag |
| `after_woo_notices` | `woocommerce_before_main_content` (priority 15) | After WooCommerce flash notices |
| `before_woo_content` | `woocommerce_before_main_content` (priority 5) | Before the main WooCommerce content area |
| `after_woo_content` | `woocommerce_after_main_content` | After the main WooCommerce content area |
| `before_woo_sidebar` | `woocommerce_sidebar` | Before the WooCommerce sidebar |
| `shortcode_only` | *(no hook)* | Banner is only rendered via `[promo_banner]` |

---

### Shortcodes

#### Render a single banner by ID
```
[promo_banner id="5"]
```

#### Render with a specific position class
```
[promo_banner id="5" position="floating"]
```

Available `position` values: `inline` *(default)*, `before_content`, `after_content`, `floating`

#### Render all banners assigned to a location slug
```
[promo_banner location="after_header"]
```

#### Render all banners in a Banner Group
```
[promo_banner group="summer-sale"]
```

---

### Banner Groups

Banner Groups are a built-in taxonomy that let you organise banners into named collections.

1. When editing a banner, find **Banner Groups** in the right sidebar.
2. Create or assign a term (e.g. `summer-sale`).
3. Use the `group` attribute in your shortcode to render all banners in that group:

```
[promo_banner group="summer-sale" position="before_content"]
```

---

## Plugin Settings

Navigate to **Promo Banners → Settings** in the admin menu.

### ⚡ Performance

| Setting | Options | Description |
|---------|---------|-------------|
| **Load CSS & JS** | Always / Smart | *Smart* loads assets only when at least one active banner exists on the current page — saves HTTP requests on pages without banners. |

### 🎨 Display Defaults

| Setting | Default | Description |
|---------|---------|-------------|
| **Show Banners on Mobile** | Enabled | Uncheck to globally hide all banners on mobile devices. |
| **Default Animation** | Slide Down | Applied to newly created banners. Overridable per banner. |
| **Default Cookie Duration** | 1 day | How long a dismissed banner stays hidden. `0` = current session only. |

### ⚠️ Advanced

| Setting | Default | Description |
|---------|---------|-------------|
| **Delete All Data on Uninstall** | Off | When enabled, deleting the plugin will permanently remove all banner posts, post-meta, and plugin options from the database. |

---

## Hooks & Filters Reference

The plugin is built for extensibility. Use the standard WordPress hooks system.

### Actions

```php
// Fires after the plugin is fully initialised.
do_action( 'pb_init' );
```

### Extending Banner Display Logic

You can override `should_display()` output via a filter (planned for v1.1.0).

### AJAX

The plugin registers two AJAX actions for the dismiss/cookie system:

| Action | Context |
|--------|---------|
| `wp_ajax_pb_dismiss` | Logged-in users |
| `wp_ajax_nopriv_pb_dismiss` | Guests |

---

## Changelog

### 1.0.0 — 2026-04-16

**Initial Release**

- `[NEW]` Custom Post Type `promo_banner` with full CRUD via WP Admin.
- `[NEW]` 7 display location hooks (After Header, Before Footer, WooCommerce locations).
- `[NEW]` `[promo_banner]` shortcode with `id`, `location`, `group`, and `position` attributes.
- `[NEW]` Slide Down, Slide Up, Fade In, and No-animation entrance effects.
- `[NEW]` Dismissible banners with configurable cookie duration.
- `[NEW]` Date/time scheduling — banners go live and expire automatically.
- `[NEW]` Priority system for multi-banner ordering.
- `[NEW]` Targeting by specific pages, blog categories, and WooCommerce product categories.
- `[NEW]` `PB_Installer` class — activation, deactivation, and uninstall routines.
- `[NEW]` Global Settings page (performance, mobile, animation defaults, safe uninstall).
- `[NEW]` Smart asset loading mode.
- `[NEW]` Banner Groups taxonomy for grouped shortcode rendering.
- `[NEW]` One-time activation admin notice with quick-action buttons.

---

## License

This plugin is licensed under the **GNU General Public License v2.0 or later**.

```
Promo Banner for WooCommerce — MarufLabs
Copyright (C) 2026  Maruf Ahmed (https://maruflabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

*Built with ❤️ by [MarufLabs](https://maruflabs.com)*
