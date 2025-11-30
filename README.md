# Stripe Payment Links Admin (StripePlAdmin)

A ProcessWire module that provides a comprehensive admin interface for viewing, analyzing, and managing customer purchases from Stripe Payment Links directly in the ProcessWire admin panel.

## Why This Module Was Built

Customers with multiple active Stripe accounts wanted a better, more complete overview and evaluation capabilities across all accounts, which is not possible on the Stripe web platform. This module fills that gap by centralizing purchase data, providing advanced filtering and reporting features, and offering customizable views to meet specific business needs.

## Features

- **Multi-Account Support**: Manage and view data from multiple Stripe accounts in one place
- **Three Comprehensive Views**:
  - **Purchases**: Detailed transaction history with customer and payment information
  - **Products**: Aggregated product performance metrics
  - **Customers**: Customer lifetime value and activity tracking
- **Configurable Columns**: Customize which data columns are displayed in each view
- **Advanced Filtering**: Filter data by email, date ranges, products, revenue, payment status, and more
- **Real-Time Data**: Direct integration with Stripe API for up-to-date information
- **Subscription Management**: Track subscription status, renewals, and period end dates
- **Data Export**: Export purchase, product, or customer data to CSV format
- **Statistical Insights**: Revenue reports, purchase summaries, and renewal analytics
- **Responsive Design**: Works seamlessly across desktop and mobile devices

## Requirements

- ProcessWire 3.x or higher
- `StripePaymentLinks` module (dependency)
- PHP 7.4 or higher (compatible with ProcessWire framework requirements)

## Installation

1. Clone this repository or download the latest version
2. Copy the `StripePlAdmin` folder to your `/site/modules/` directory
3. In the ProcessWire admin, go to **Modules > Refresh**
4. Find "Stripe PL Admin" and click **Install**
5. The module will automatically create an admin page at `/processwire/stripe-pl-admin/`

## Configuration

After installation, configure the module by navigating to **Admin > Setup > Modules > Configure > Stripe PL Admin**:

### Purchases Tab Configuration

- **Columns**: Select which columns to display (e.g., User Email, Purchase Date, Product Titles, Amount Total, Payment Status)
- **Filters**: Choose which filters to make available for data filtering

### Products Tab Configuration

- **Columns**: Configure product view columns (e.g., Product Name, Purchases, Quantity, Revenue, Last Purchase)
- **Filters**: Enable filters for product analysis (e.g., Revenue range, Purchase period)

### Customers Tab Configuration

- **Columns**: Set customer view columns (e.g., Name, Email, Total Purchases, Total Revenue, First Purchase)
- **Filters**: Enable customer filters (e.g., Revenue range, Purchase count)

### Display Settings

- **Items Per Page**: Set the default number of entries to display per page (default: 25, max: 1000)

## Usage Examples

### Example 1: Finding High-Value Customers

1. Navigate to the **Customers** tab
2. Enable the "Total Revenue" filter
3. Set minimum revenue to €1000
4. Sort by "Total Revenue" (descending)
5. Review the list of your most valuable customers

### Example 2: Analyzing Product Performance

1. Go to the **Products** tab
2. Use the "Purchase Period" filter to select the last quarter
3. Sort by "Revenue" to see top-performing products
4. Click on a product row to see detailed purchase information
5. Export the filtered data for reporting

### Example 3: Tracking Subscription Renewals

1. Open the **Purchases** tab
2. Add the "Subscription Status" and "Period End" columns
3. Filter by "Payment Status" = "paid"
4. Filter by "Period End" date range for upcoming renewals
5. Review customers whose subscriptions are expiring soon

### Example 4: Customer Purchase History

1. In the **Purchases** tab, use the "User Email" filter
2. Enter the customer's email address
3. Sort by "Purchase Date" to see chronological order
4. Click on any purchase to view detailed Stripe session data
5. See all products purchased, amounts, and payment status

### Example 5: Revenue Analysis by Product

1. Navigate to **Products** tab
2. Enable "Revenue" filter and set your target range
3. Add "Renewals" column to see recurring revenue
4. Click on a product to open a modal showing:
   - All purchases of that product
   - Customer information for each purchase
   - Purchase dates and amounts
   - Renewal patterns

### Example 6: Finding Inactive Customers

1. Go to the **Customers** tab
2. Add "Last Activity" column
3. Filter by "Last Activity" date range (e.g., more than 6 months ago)
4. Sort by "Total Revenue" to prioritize re-engagement efforts
5. Export the list for your marketing team

## Available Data Columns

### Purchases View

- User Email, User Name
- Purchase Date
- Customer Name (from Stripe)
- Session ID, Customer ID
- Payment Status, Currency
- Amount Total
- Product Titles, Product IDs
- Subscription ID, Status, Period End
- Shipping Name, Shipping Address
- Line Items Count
- Renewal Count, Last Renewal

### Products View

- Product Name
- Stripe Product ID, Page ID
- Total Purchases, Quantity Sold
- Total Revenue
- Last Purchase Date
- Renewal Count

### Customers View

- Name, Email
- Total Purchases
- Total Revenue
- First Purchase Date
- Last Activity Date

## Advanced Features

### Adaptive Filtering

The module includes an intelligent filtering system that:
- Automatically adjusts filter inputs based on data type (text, number, date)
- Supports range filtering for numeric and date fields
- Provides dropdown selection for status fields
- Remembers filter settings during your session

### Product Purchase Details

Click on any product in the Products view to open a detailed modal showing:
- All purchases of that specific product
- Customer information for each purchase
- Purchase dates and amounts
- Filterable by the same criteria as the main Purchases view

### Data Export

Export filtered data in multiple formats for:
- Financial reporting
- Customer segmentation
- Marketing campaign planning
- Business intelligence tools integration

## Tips and Best Practices

1. **Start with default columns** and add more as needed to avoid information overload
2. **Use date range filters** to analyze specific time periods
3. **Combine multiple filters** for precise data segmentation
4. **Export filtered data** regularly for offline analysis and backup
5. **Monitor subscription period end dates** to reduce churn
6. **Track renewal patterns** to forecast recurring revenue

## Technical Details

This module uses dynamic filtering, tab-based views, and modern PHP patterns for maintaining clear, flexible code. The architecture supports:
- Configurable column definitions with meta path mapping
- Computed columns for complex data transformations
- Adaptive filter rendering based on data types
- Efficient database queries with pagination

## Contribution

Contributions are welcome! If you find issues or have ideas to enhance this module, please submit an issue or a pull request.

## Author

**frameless Media**

## Version

1.0.0

## License

This module is licensed under the MIT License. Please see the file `LICENSE` for more details.

---

**Framework**: [ProcessWire](https://processwire.com)
**Stripe Integration**: [Stripe Payment Links](https://stripe.com)
