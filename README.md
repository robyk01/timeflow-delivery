=== WooCommerce TimeFlow Delivery ===
Contributors: amore_roberto 
Tags: woocommerce, delivery, shipping, pickup, time slots, delivery date, delivery time, checkout
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.7
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows customers to select delivery date and time slots during WooCommerce checkout, including setting unavailable dates and time slot fees.

== Description ==

Enhance your WooCommerce checkout process by allowing customers to choose a specific delivery date and time slot that suits them.

**Features:**

*   Add a date picker to the checkout page.
*   Allow customers to select available time slots for their chosen date.
*   Create and manage delivery time slots (e.g., 9:00 AM - 11:00 AM, 2:00 PM - 4:00 PM) via a dedicated admin interface.
*   Set available days of the week for each time slot.
*   Optionally assign a fee to specific time slots.
*   Configure the range of future dates available for selection in the date picker.
*   Mark specific dates (e.g., holidays, weekends) as unavailable for delivery in the plugin settings (these dates will be greyed out).
*   Supports both Shipping and Local Pickup methods (time slots can be specific to a method).
*   Displays selected delivery details on the order confirmation page and in admin order details.


== Installation ==

1.  Upload the `woocommerce-timeflow-delivery` folder to the `/wp-content/plugins/` directory, OR install directly via the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to WooCommerce > TimeFlow Delivery to configure time slots and settings.


== Frequently Asked Questions ==

= Does this work with Local Pickup? =

Yes, you can configure time slots to be available for Shipping, Pickup, or both when creating/editing them.

= Can I set fees for specific time slots? =

Yes, when creating or editing a time slot, you can enter an optional delivery fee. This fee will be added to the cart when the customer selects that slot.

= How do I make certain days unavailable? =

Go to WooCommerce > TimeFlow Delivery > Settings tab. Use the 'Unavailable Dates' option to add specific dates when delivery should not be offered. These dates will be greyed out and unselectable on the checkout page calendar.


== Changelog ==

= 2.5.0 =
*   Initial release.
*   Feature: Added admin menu under WooCommerce -> TimeFlow Delivery.
*   Feature: Created Time Slots custom post type for managing slots.
*   Feature: Implemented basic admin UI for adding/viewing time slots (start/end time, days, fee, delivery method).
*   Feature: Added Settings tab with options for Date Range, Require Time Slot Selection, Default Fee, and Unavailable Dates.
*   Feature: Implemented frontend checkout form with date selection (Flatpickr) and time slot dropdown.
*   Feature: AJAX functionality to load available time slots based on selected date and delivery method.
*   Feature: Unavailable dates selected in settings are now disabled in the frontend date picker.
*   Feature: Date range setting now correctly limits the frontend date picker.
*   Feature: Time slot fees are added/removed correctly in the cart.
*   Fix: Resolved multiple issues with settings not saving correctly (checkboxes, unavailable dates).
*   Fix: Addressed conflict causing the "Save Changes" button to misbehave.
*   Fix: Corrected placeholder format for Default Delivery Fee.
*   Refactor: Rebuilt settings save mechanism to use manual admin-post handler instead of Settings API for increased reliability.



 
