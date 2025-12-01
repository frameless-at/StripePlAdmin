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
- **Advanced Search & Filtering**:
  - Boolean search operators (AND/OR)
  - Quoted phrase search
  - '+' prefix for required terms
  - Filter by email, date ranges, products, revenue, and more
- **Interactive Modals**:
  - Purchase Details: Click on product titles to view full purchase information
  - Product Purchases: Click on products to see all purchases with filtering
  - Customer Purchases: Click on customer names to see their purchase history
- **Smart Date Filtering**:
  - Purchase Date filtering
  - Period End filtering (subscription end dates)
  - Last Renewal filtering
  - Automatically excludes non-subscriptions when using subscription-specific filters
- **Stripe Session Data**: Displays complete Stripe session information stored by StripePaymentLinks module
- **Subscription Management**: Track subscription status, renewals, and period end dates
- **Renewal Display**: See renewal counts and amounts directly in purchase details
- **Data Export**: Export purchase, product, or customer data to CSV format
- **Statistical Insights**: Revenue reports with totals, purchase summaries, and renewal analytics
- **UI Hints**: Helpful info modal explaining different tab perspectives
- **Responsive Design**: Works seamlessly across desktop and mobile devices

## Requirements

- ProcessWire 3.x or higher
- `StripePaymentLinks` module (dependency)
- PHP 7.4 or higher (compatible with ProcessWire framework requirements)

## How It Works

This module is a **viewing and analysis tool** for Stripe data already stored in ProcessWire:

1. The `StripePaymentLinks` module handles Stripe API integration and stores purchase data in ProcessWire user fields
2. This `StripePlAdmin` module reads that stored data and provides:
   - Advanced filtering and search capabilities
   - Multiple view perspectives (Purchases, Products, Customers)
   - Interactive modals for detailed information
   - CSV export functionality
   - Statistical analysis and reporting

**Important**: This module does not make direct Stripe API calls. It displays data synchronized by the StripePaymentLinks module. For real-time Stripe data, ensure the StripePaymentLinks module is properly configured and receiving webhook events from Stripe.

## Installation

1. Clone this repository or download the latest version
2. Copy the `StripePlAdmin` folder to your `/site/modules/` directory
3. In the ProcessWire admin, go to **Modules > Refresh**
4. Find "Stripe PL Admin" and click **Install**
5. The module will automatically create an admin page at `/processwire/stripe-pl-admin/`

## Configuration

After installation, configure the module by navigating to **Admin > Setup > Modules > Configure > Stripe PL Admin**:

### Purchases Tab Configuration

- **Columns**: Select which columns to display (e.g., User Email, Purchase Date, Product Titles, Amount Total, Subscription Status, Period End, Last Renewal)
- **Filters**: Choose which filters to make available for data filtering

### Products Tab Configuration

- **Columns**: Configure product view columns (e.g., Product Name, Purchases, Quantity, Revenue, Last Purchase, Renewals)
- **Filters**: Enable filters for product analysis (e.g., Revenue range, Purchase period)

### Customers Tab Configuration

- **Columns**: Set customer view columns (e.g., Name, Email, Total Purchases, Total Revenue, First Purchase, Last Activity)
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
6. Click on a customer name to see their complete purchase history

### Example 2: Analyzing Product Performance

1. Go to the **Products** tab
2. Use the "Purchase Period" filter to select the last quarter
3. Sort by "Revenue" to see top-performing products
4. Click on a product row to see detailed purchase information with filtering
5. Export the filtered data for reporting

### Example 3: Tracking Subscription Renewals

1. Open the **Purchases** tab
2. Add the "Subscription Status" and "Period End" columns
3. Filter by "Period End" date range for upcoming renewals
4. Review customers whose subscriptions are expiring soon
5. Click on product titles to see full purchase details including renewal history

### Example 4: Finding Subscriptions Ending This Month

1. In the **Purchases** tab, enable the "Period End" filter
2. Set date range to current month (e.g., 01.12.2025 - 31.12.2025)
3. The filter automatically excludes non-subscription purchases
4. Sort by "Period End" to see which subscriptions end first
5. Click on customer names to see their full purchase history

### Example 5: Advanced Search Examples

Use the search functionality with boolean operators:

- **AND Search**: `john +smith` or `john AND smith` - finds purchases with both terms
- **OR Search**: `john OR jane` - finds purchases with either term
- **Exact Phrase**: `"John Smith"` - finds exact phrase match
- **Combined**: `"Premium Plan" +active` - finds "Premium Plan" that also contains "active"

### Example 6: Revenue Analysis with Renewals

1. Navigate to **Purchases** tab
2. Add "Amount Total" and "Renewal Count" columns
3. Filter by date range for your analysis period
4. Click on product titles to see purchase details modal showing:
   - Original purchase amount
   - All renewal dates and amounts
   - Total revenue per purchase (original + all renewals)
5. Export for financial reporting

### Example 7: Customer Purchase History

1. In the **Customers** tab, find your customer
2. Click on the customer name or purchases count
3. A modal opens showing all their purchases with:
   - Purchase dates and products
   - Amounts and payment status
   - Subscription information
   - Filterable and sortable data

### Example 8: Finding Inactive Customers

1. Go to the **Customers** tab
2. Add "Last Activity" column
3. Filter by "Last Activity" date range (e.g., more than 6 months ago)
4. Sort by "Total Revenue" to prioritize re-engagement efforts
5. Export the list for your marketing team

## Available Data Columns

### Purchases View

- User Email, User Name
- Purchase Date
- Customer Name (from Stripe) - clickable to show customer purchases
- Session ID, Customer ID
- Currency, Amount Total
- Product Titles - clickable to show purchase details with renewals
- Product IDs
- Subscription ID, Status, Period End
- Shipping Name, Shipping Address
- Line Items Count
- Renewal Count, Last Renewal

### Products View

- Product Name - clickable to show all purchases of this product
- Stripe Product ID, Page ID
- Total Purchases, Quantity Sold
- Total Revenue
- Last Purchase Date
- Renewal Count

### Customers View

- Name - clickable to show customer purchases
- Email
- Total Purchases - clickable to show customer purchases
- Total Revenue
- First Purchase Date
- Last Activity Date

## Advanced Features

### Boolean Search Functionality

The search field supports advanced operators:

- **AND Operator**: Use `AND` or `+` prefix to require terms
  - Example: `john AND smith` or `+john +smith`
- **OR Operator**: Use `OR` to find either term
  - Example: `john OR jane`
- **Quoted Phrases**: Use quotes for exact matches
  - Example: `"Premium Subscription"`
- **Combined Operators**: Mix operators for complex searches
  - Example: `"Premium Plan" +active OR "Basic Plan" +active`

### Smart Date Filtering

The module intelligently handles date filters:

- **Purchase Date**: Filters all purchases by purchase date
- **Period End**: Only shows subscriptions with period end dates in the selected range
  - Automatically excludes non-subscription purchases
  - Useful for finding expiring subscriptions
- **Last Renewal**: Only shows subscriptions with renewals in the selected range
  - Automatically excludes purchases without renewals
  - Useful for analyzing renewal patterns

### Interactive Modals

Three types of interactive modals provide detailed information:

1. **Purchase Details Modal**
   - Triggered by clicking on product titles in Purchases view
   - Shows complete purchase information including:
     - All line items with quantities and amounts
     - Renewal history with dates and amounts
     - Total revenue calculation (purchase + renewals)
     - Customer and session information

2. **Product Purchases Modal**
   - Triggered by clicking on product names in Products view
   - Shows all purchases of that specific product
   - Includes customer information
   - Supports date filtering to analyze specific periods
   - Shows renewal information per purchase

3. **Customer Purchases Modal**
   - Triggered by clicking on customer names or purchase counts
   - Shows complete purchase history for that customer
   - Sortable and filterable
   - Displays all products, amounts, and dates

### Subscription & Renewal Tracking

- **Subscription Status**: See active, canceled, or paused subscriptions
- **Period End Dates**: Track when subscriptions end or renew
- **Renewal Count**: See how many times a subscription has renewed
- **Last Renewal**: Track the most recent renewal date
- **Renewal Display**: Purchase details modal shows all renewal dates and amounts
- **Revenue Calculation**: Automatic totaling of original purchase + all renewals

### Adaptive Filtering

The module includes an intelligent filtering system that:
- Automatically adjusts filter inputs based on data type (text, number, date)
- Supports range filtering for numeric and date fields
- Provides dropdown selection for status fields
- Remembers filter settings during your session
- Excludes irrelevant data (e.g., non-subscriptions when filtering by Period End)

### Data Export

Export filtered data in CSV format for:
- Financial reporting
- Customer segmentation
- Marketing campaign planning
- Business intelligence tools integration
- Includes all visible columns and respects active filters

### UI Enhancements

- **Info Modal**: Click the info icon to learn about each tab's perspective
- **Revenue Totals**: Automatic calculation of totals at the bottom of tables
- **Bold Labels**: Important labels like "Total:" are highlighted
- **Child Element Clicks**: Modal triggers work even when clicking on nested elements

## Tips and Best Practices

1. **Start with default columns** and add more as needed to avoid information overload
2. **Use date range filters** to analyze specific time periods
3. **Combine multiple filters** for precise data segmentation (e.g., Period End + Product)
4. **Use boolean search** for complex queries across multiple fields
5. **Click on interactive elements** (product titles, customer names) for detailed views
6. **Export filtered data** regularly for offline analysis and backup
7. **Monitor subscription period end dates** to reduce churn and plan renewals
8. **Track renewal patterns** to forecast recurring revenue
9. **Use the Period End filter** to find subscriptions expiring soon
10. **Check the Info modal** in each tab to understand what data you're viewing

## Recent Updates & Improvements

### Version 1.1.0 (December 2025)

**New Features:**
- ✅ Boolean search with AND/OR operators and quoted phrases
- ✅ Interactive purchase details modal with renewal display
- ✅ Customer purchases modal for complete purchase history
- ✅ Product purchases modal with date filtering
- ✅ UI hints and info modal explaining tab perspectives
- ✅ Renewal count display in purchases and products views

**Bug Fixes:**
- ✅ Period End filter now correctly excludes non-subscription purchases
- ✅ Last Renewal filter now correctly excludes purchases without renewals
- ✅ Modal click handlers now work when clicking on child elements (e.g., `<small>` tags)
- ✅ Purchase details modal total calculation includes renewals
- ✅ Date filters now properly applied to product purchase modals

**Improvements:**
- ✅ Better subscription status display filtering
- ✅ Standardized column naming across all views
- ✅ Bold "Total:" label in info modals
- ✅ Comprehensive documentation updates

## Technical Details

This module uses dynamic filtering, tab-based views, and modern PHP patterns for maintaining clear, flexible code. The architecture supports:
- Configurable column definitions with meta path mapping
- Computed columns for complex data transformations
- Adaptive filter rendering based on data types
- Efficient database queries with pagination
- AJAX-powered modals for detailed data views
- Event delegation for robust click handling
- Smart filter logic that understands data relationships

## Troubleshooting

### Modals not opening when clicking on links
- This has been fixed in version 1.1.0
- Modal triggers now use `closest()` to work with child elements

### Period End filter showing all purchases
- This has been fixed in version 1.1.0
- The filter now correctly excludes non-subscription purchases

### Search not finding expected results
- Use quotes for exact phrases: `"Premium Plan"`
- Use `+` or `AND` for required terms: `+john +smith`
- Check that you're searching in the correct tab (Purchases/Products/Customers)

## Contribution

Contributions are welcome! If you find issues or have ideas to enhance this module, please submit an issue or a pull request.

## Author

**frameless Media**
[office@frameless.at](mailto:office@frameless.at)

## Version

1.1.0 (December 2025)

## License

This module is licensed under the MIT License. Please see the file `LICENSE` for more details.

---

**Framework**: [ProcessWire](https://processwire.com)
**Stripe Integration**: [Stripe Payment Links](https://stripe.com)
