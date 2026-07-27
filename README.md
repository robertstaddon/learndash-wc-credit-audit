# LearnDash WooCommerce Credit/Audit Purchase

WordPress plugin that adds a **WooCommerce Credit/Audit** course price type to LearnDash. Courses can offer separate **Audit** and **Credit** enrollment buttons, each linked to its own WooCommerce product.

## Features

- Adds a new LearnDash course price type: **WooCommerce Credit/Audit** (`wcca`)
- Per-course product selectors for:
  - **Audit Button Product** — WooCommerce product used for audit enrollment
  - **Credit Button Product** — WooCommerce product used for credit enrollment
- Replaces the standard “Take this Course” button with two add-to-cart buttons showing each product’s price (e.g. `$10 - Audit Course`, `$25 - Credit Course`)
- Displays a combined price range in the LearnDash course infobar and Course Grid ribbon (e.g. `$10 – $25`)
- Works with the LearnDash 3.x theme course infobar
- Compatible with the Boss theme payment button filter
- Allows HTML in LearnDash Course Grid ribbon text so WooCommerce price HTML renders correctly

## Requirements

| Dependency | Notes |
|---|---|
| [WordPress](https://wordpress.org/) | Current stable recommended |
| [LearnDash](https://www.learndash.com/) | Course enrollment settings / metabox APIs |
| [WooCommerce](https://woocommerce.com/) | Products and cart add-to-cart URLs |
| PHP | 7.4+ recommended |

Tested with WooCommerce up to 3.8 (see plugin header). Newer WooCommerce versions may work; verify on a staging site before production use.

## Installation

1. Download or clone this repository.
2. Upload the `learndash-wc-credit-audit` folder to `/wp-content/plugins/`.
3. Activate **LearnDash WooCommerce Credit/Audit Purchase** in **Plugins**.
4. Ensure LearnDash and WooCommerce are installed and active.

## Configuration

### 1. Create WooCommerce products

Create (or reuse) two WooCommerce products for the course — one for audit and one for credit. Configure pricing, tax, and any LearnDash/WooCommerce enrollment linking as you normally would for paid courses.

### 2. Set the course price type

1. Edit a LearnDash course.
2. Open the **Course Enrollment** / pricing settings.
3. Set the price type to **WooCommerce Credit/Audit**.
4. Choose:
   - **Audit Button Product**
   - **Credit Button Product**
5. Update/save the course.

When this price type is selected, the course remains closed until the student purchases a linked product or is manually enrolled.

## How it works

### Enrollment buttons

On the front end, for courses using the `wcca` price type, the plugin outputs one or both buttons:

- Links to the WooCommerce cart with `?add-to-cart={product_id}`
- Button label = product price HTML + “Audit Course” or “Credit Course”
- If neither product resolves, a “This course is currently closed” message is shown (LearnDash 3.x infobar)

### Price display

The saved course price is overwritten with a combined display string from the selected products’ `get_price_html()` values (audit and credit separated by an en dash). That supports:

- Course infobar price cell
- LearnDash Course Grid ribbon pricing
- Dynamic price / currency display from WooCommerce

### Admin settings integration

The plugin hooks into LearnDash settings metaboxes to:

- Register the `wcca` price type and product select fields
- Persist `course_price_type_wcca_audit_button_product_id` and `course_price_type_wcca_credit_button_product_id`
- Sync the combined price display into `course_price` on save

## Hooks used

| Hook | Purpose |
|---|---|
| `learndash_settings_fields` | Add Credit/Audit price type and product fields |
| `learndash_metabox_save_fields` | Save product ID settings |
| `learndash_settings_save_values` | Set combined `course_price` for `wcca` courses |
| `learndash-course-infobar-action-cell-before` | Output Audit/Credit buttons (LD3 theme) |
| `learndash_no_price_price_label` | Fallback price label when needed |
| `learndash_payment_button` | Replace payment buttons (e.g. Boss theme) |
| `learndash_course_grid_ribbon_text_allow_html` | Allow HTML in Course Grid ribbon |

## File structure

```
learndash-wc-credit-audit/
├── learndash-wc-credit-audit.php   # Main plugin file
└── README.md
```

## Text domain

`learndash-wc-credit-audit`

## Author

**Abundant Designs**  
[https://www.abundantdesigns.com](https://www.abundantdesigns.com)

## License

Proprietary / all rights reserved unless otherwise specified by Abundant Designs. Contact the author for licensing and distribution terms.

## Changelog

### 2.10

- Current release. See plugin header `Version` for the installed build number.
