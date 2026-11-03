# Custom Cart Settings

A WooCommerce plugin that only lets customers check out when the number of products in their cart is a **multiple of X**: for example boxes of 6 bottles of wine. Products in selected categories (gift boxes, corkscrews, etc.) can be left out of the count.

## Features

- Customers can only check out when the number of counted products is a multiple of the number you set (for example 6, 12, 18…).
- Exclude product categories from the count. Products in those categories can always be added.
- Shows your own message in the cart and at checkout, with `%s` replaced by the multiplier.
- Works with both the **block-based** Cart and Checkout and the **classic** (shortcode) cart and checkout.
- The rule is enforced on the server: the checkout button is greyed out, the checkout page redirects back to the cart, and placing an order is refused while the cart doesn't meet the rule.
- Compatible with WooCommerce High-Performance Order Storage (HPOS).
- Dutch translation included.

## Requirements

| | Minimum |
|---|---|
| WordPress | 6.5 |
| WooCommerce | 8.0 (tested up to 11.2) |
| PHP | 8.1 |

## Installation

1. Download `rsdev-custom-cart-settings-<version>.zip` from the [latest release](../../releases/latest).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip and click **Install Now**.
3. Click **Activate**.
4. Go to **WooCommerce → Settings → Order Quantities** to set up the rule.

WooCommerce must be installed and active; WordPress won't activate the plugin without it.

**Updating from 1.0.x:** upload the new zip and choose *Replace current with uploaded*. Your settings are kept, and the old *Settings → Custom Cart Settings* page redirects to the new location.

## Settings

All settings are under **WooCommerce → Settings → Order Quantities**, and only administrators can see and change them. The *Settings* link in the Plugins list goes there too.

| Setting | What it does |
|---|---|
| **Enable plugin** | Switches the rule on or off. On by default. |
| **Product multiplier** | The number the cart quantity must be a multiple of, e.g. `6`. Must be a whole number of 1 or higher; `1` allows any quantity, so the rule has no effect. The page shows a warning in that case. |
| **Excluded categories** | Pick categories from the list; each one shows as a tag with a × to remove it. Products in these categories don't count towards the multiple. |
| **Error message text** | The message customers see when their cart doesn't meet the rule. Use `%s` for the multiplier, e.g. `Wine is sold per box of %s bottles.` Leave empty to use the default, *"You can only order in multiples of %s."* A live preview shows the result as you type. |

## How the count works

- The plugin adds up the quantities of all products in the cart, **except** products that belong to at least one excluded category.
- Product variations use the categories of their parent product.
- Products without any category are counted.
- The cart is valid when that total is a multiple of the multiplier. An empty cart, or a cart with only excluded products, is always valid.

**Example** with multiplier 6 and *Misc* excluded:

| Cart | Counted | Result |
|---|---|---|
| 5 × red wine | 5 | ✗ Checkout blocked |
| 5 × red wine, 1 × white wine | 6 | ✓ Checkout allowed |
| 6 × wine, 1 × corkscrew (*Misc*) | 6 | ✓ Checkout allowed |

## What customers see

When the cart doesn't meet the rule:

- **Cart page:** the error message is shown and the *Proceed to checkout* button is greyed out.
- **Checkout page:** opening it (from the mini-cart, a menu link or by typing the address) redirects back to the cart.
- **Placing an order:** refused with the error message, as a final safety check.

As soon as the quantity is corrected, the message disappears and the checkout button works again, without reloading the page.

## Translations

The plugin is translation-ready (text domain `rsdev-custom-cart-settings`) and includes a Dutch (`nl_NL`) translation. To add another language, use `languages/rsdev-custom-cart-settings.pot` with a tool such as [Poedit](https://poedit.net/) and save the `.po` and `.mo` files in the `languages` folder.

The error message text is stored as you type it in the settings, so it is not translated automatically.

## Uninstalling

Deactivating the plugin keeps your settings. Deleting it via **Plugins → Delete** also removes its settings from the database.

## For developers

```
rsdev-custom-cart-settings.php              Plugin header, bootstrap, WooCommerce compatibility declarations
uninstall.php                               Removes the plugin options
includes/
  class-rsdev-ccs-settings.php              Reads the options with safe defaults
  class-rsdev-ccs-cart-validator.php        The cart rule: Store API errors, classic notices, checkout redirect
  class-rsdev-ccs-admin.php                 Registers the settings tab, assets, Plugins-list link
  class-rsdev-ccs-settings-page.php         WooCommerce > Settings > Order Quantities tab
assets/
  js/cart-block.js, css/cart-block.css      Greys out the block cart's checkout button
  js/admin.js, css/admin.css                Live message preview and warning on the settings tab
languages/                                  .pot template and Dutch translation
tools/build-translations.py                 Rebuilds the translation files (not included in the plugin zip)
```

**Updating translations** (needs the GNU gettext tools: `brew install gettext`):

```
python3 tools/build-translations.py
```

This updates the `.pot` template from the code and plugin header, adds new strings to each `.po` file (existing translations are kept), compiles the `.mo` files and lists the strings that still need a translation. Translate those in the `.po` file (or with Poedit) and run the script again.

**Building a release zip:**

```
git archive --format=zip --prefix=rsdev-custom-cart-settings/ -o rsdev-custom-cart-settings-<version>.zip HEAD
```

Files marked `export-ignore` in `.gitattributes` (the `tools` folder, Git and editor files) are left out automatically.

- **Options** (`wp_options`): `ccsp_enable_plugin` (`yes`/`no`; `1`/empty from older versions is also accepted), `ccsp_multiplier`, `ccsp_error_message`, `ccsp_excluded_cats` (comma-separated category IDs).
- **Hooks used:** `woocommerce_store_api_cart_errors` (block cart and checkout), `woocommerce_check_cart_items` (classic cart and checkout), `template_redirect` (checkout redirect).
- **Error code:** `rsdev_ccs_invalid_quantity`, in Store API cart errors and classic notices.

## Changelog

### 1.4.1
- The plugin's website link (*Visit plugin site*) and update ID now point to this GitHub repository.
- Added this README.

### 1.4.0
- Fixed a fatal error on the cart page when no multiplier had been saved; the multiplier now defaults to 1.
- The rule now works with the block-based Cart and Checkout, and is enforced on the server: the checkout page redirects to the cart and orders are refused while the cart is invalid.
- The checkout button in the block cart is greyed out while the cart is invalid.
- Settings moved to **WooCommerce → Settings → Order Quantities** (administrators only), with a category picker, a live message preview and a warning when the multiplier is 1.
- The *Enable plugin* setting now actually switches the rule on and off.
- Security: all output escaped, input validated, capability checks.
- Category IDs with spaces and products without categories are now handled correctly.
- Declared compatibility with HPOS and the cart/checkout blocks; settings are removed on uninstall.
- Updated Dutch translation.

### 1.0.1
- Version number update.

### 1.0.0
- First release.

## License

[GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html). © Ronald Smit, [rsdev.nl](https://www.rsdev.nl/)
