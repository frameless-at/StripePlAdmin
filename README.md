# Stripe Payment Links Admin (StripePlAdmin)

This module provides a comprehensive way to manage and view customer purchases and Stripe session data directly from the admin panel. It is a ProcessWire module designed to improve the visibility and management of user purchases and subscriptions.

## Features

- **Purchase Overview**: Displays metadata including purchase details, user/session information, and product data.
- **Configurable Columns**: Administrators can customize the columns visible for purchases, products, and customers.
- **Filters & Search**: Dynamically filter data (e.g., by product, date range, session status).
- **Data Export**: Easily export purchase, product, or customer data to CSV format.
- **Statistical Insights**: Includes revenue reports, purchase summaries, and more.
- **Renewals Management**: Insights into subscriptions, renewals, and subscription status.

## Requirements

- This module requires the `StripePaymentLinks` module as a dependency.
- PHP version compatible with the ProcessWire framework.

## Installation

1. Clone this repository or download the latest version.
2. Place `StripePlAdmin.module.php` in the correct ProcessWire modules directory.
3. Install the module via the ProcessWire admin interface.
4. Configure the module from the settings.

## Configuration and Usage

### Module Configuration
- Navigate to `Admin > Setup > Modules > Configure`.
- Customize columns for displaying data:
  - Purchases: `email`, `amount_total`, `status`, etc.
  - Products: Names, purchases, revenue, etc.
  - Customers: Emails, first/last purchase, purchases count.

### Table Views
- **Tabs**:
  - **Purchases**: Displays transactions, including meta details from Stripe sessions.
  - **Products**: Aggregates product purchases and renewals.
  - **Customers**: Consolidates activity like customer-specific spending and subscriptions.

### Export Data
Utilize the “Export CSV” button in any tab to download the displayed data for further analysis.

### Code Details
This module uses dynamic filtering, tab-based views, and modern PHP patterns for maintaining clear, flexible code.

## Contribution

Contributions are welcome! If you find issues or have ideas to enhance this module, submit an issue or a pull request.

## License

This module is licensed under the MIT License. Please see the file `LICENSE` for more details.

---
**Framework**: [ProcessWire](https://processwire.com)  
**Stripe Integration**: [Stripe Payment Links](https://stripe.com)