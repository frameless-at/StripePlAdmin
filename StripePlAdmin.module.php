<?php namespace ProcessWire;

/**
 * Stripe Payment Links Admin
 *
 * Admin page for viewing customer purchases with configurable columns.
 * Displays purchase metadata including Stripe session data.
 */
class StripePlAdmin extends Process implements Module, ConfigurableModule {

	public static function getModuleInfo(): array {
		return [
			'title'       => 'Stripe PL Admin',
			'version'     => '1.0.0',
			'summary'     => 'View customer purchases with configurable metadata columns.',
			'author'      => 'frameless Media',
			'icon'        => 'table',
			'requires'    => ['StripePaymentLinks'],
			'page'        => [
				'name'   => 'stripe-pl-admin',
				'parent' => 'admin',
				'title'  => 'Stripe PL Admin',
			],
		];
	}

	/**
	 * Available column definitions with meta path and label
	 */
	protected array $availableColumns = [
		// Basic info
		'user_email'        => ['label' => 'User Email', 'type' => 'user'],
		'user_name'         => ['label' => 'User Name', 'type' => 'user'],
		'purchase_date'     => ['label' => 'Purchase Date', 'type' => 'field'],
		'purchase_lines'    => ['label' => 'Purchase Lines', 'type' => 'field'],

		// Stripe session meta
		'session_id'        => ['label' => 'Session ID', 'path' => ['stripe_session', 'id']],
		'customer_id'       => ['label' => 'Customer ID', 'path' => ['stripe_session', 'customer', 'id']],
		'customer_name'     => ['label' => 'Customer Name', 'type' => 'computed', 'compute' => 'computeCustomerName'],
		'payment_status'    => ['label' => 'Payment Status', 'path' => ['stripe_session', 'payment_status']],
		'currency'          => ['label' => 'Currency', 'path' => ['stripe_session', 'currency']],
		'amount_total'      => ['label' => 'Amount Total', 'type' => 'computed', 'compute' => 'computeAmountTotal'],
		'subscription_id'   => ['label' => 'Subscription ID', 'path' => ['stripe_session', 'subscription']],
		'shipping_name'     => ['label' => 'Shipping Name', 'path' => ['stripe_session', 'shipping', 'name']],
		'shipping_address'  => ['label' => 'Shipping Address', 'type' => 'computed', 'compute' => 'computeShippingAddress'],

		// Product mapping
		'product_ids'       => ['label' => 'Product IDs', 'type' => 'meta_array', 'key' => 'product_ids'],
		'product_titles'    => ['label' => 'Product Titles', 'type' => 'computed', 'compute' => 'computeProductTitles'],

		// Subscription status
		'subscription_status' => ['label' => 'Subscription Status', 'type' => 'computed', 'compute' => 'computeSubscriptionStatus'],
		'period_end'        => ['label' => 'Period End', 'type' => 'computed', 'compute' => 'computePeriodEnd'],

		// Line items detail
		'line_items_count'  => ['label' => 'Items Count', 'type' => 'computed', 'compute' => 'computeLineItemsCount'],

		// Renewals
		'renewal_count'     => ['label' => 'Renewal Count', 'type' => 'computed', 'compute' => 'computeRenewalCount'],
		'last_renewal'      => ['label' => 'Last Renewal', 'type' => 'computed', 'compute' => 'computeLastRenewal'],
	];

	/**
	 * Default columns and filters to show
	 */
	public static function getDefaults(): array {
		return [
			'purchasesColumns' => ['user_email', 'purchase_date', 'product_titles', 'amount_total', 'payment_status'],
			'productsColumns' => ['name', 'purchases', 'quantity', 'revenue', 'last_purchase'],
			'customersColumns' => ['name', 'email', 'total_purchases', 'total_revenue', 'first_purchase', 'last_activity'],
			'purchasesFilters' => ['user_email', 'user_name', 'purchase_date', 'product_titles', 'amount_total'],
			'productsFilters' => ['name', 'revenue', 'purchases', 'quantity', 'last_purchase'],
			'customersFilters' => ['name', 'email', 'total_revenue', 'total_purchases', 'first_purchase'],
			'itemsPerPage' => 25,
		];
	}

	/**
	 * Get translatable column labels
	 */
	protected function getColumnLabels(): array {
		return [
			'user_email' => $this->_('User Email'),
			'user_name' => $this->_('User Name'),
			'purchase_date' => $this->_('Purchase Date'),
			'purchase_lines' => $this->_('Purchase Lines'),
			'session_id' => $this->_('Session ID'),
			'customer_id' => $this->_('Customer ID'),
			'customer_name' => $this->_('Customer Name'),
			'payment_status' => $this->_('Payment Status'),
			'currency' => $this->_('Currency'),
			'amount_total' => $this->_('Amount Total'),
			'subscription_id' => $this->_('Subscription ID'),
			'shipping_name' => $this->_('Shipping Name'),
			'shipping_address' => $this->_('Shipping Address'),
			'product_ids' => $this->_('Product IDs'),
			'product_titles' => $this->_('Product Titles'),
			'subscription_status' => $this->_('Subscription Status'),
			'period_end' => $this->_('Period End'),
			'line_items_count' => $this->_('Items Count'),
			'renewal_count' => $this->_('Renewal Count'),
			'last_renewal' => $this->_('Last Renewal'),
		];
	}

	/**
	 * Available columns for Products tab
	 */
	protected array $availableProductsColumns = [
		'name'            => ['label' => 'Product Name'],
		'purchases'       => ['label' => 'Purchases'],
		'quantity'        => ['label' => 'Quantity'],
		'revenue'         => ['label' => 'Revenue'],
		'last_purchase'   => ['label' => 'Last Purchase'],
		'renewals'        => ['label' => 'Renewals'],
		'page_id'         => ['label' => 'Page ID'],
		'stripe_id'       => ['label' => 'Stripe Product ID'],
	];

	/**
	 * Get translatable product column labels
	 */
	protected function getProductColumnLabels(): array {
		return [
			'name' => $this->_('Product Name'),
			'purchases' => $this->_('Purchases'),
			'quantity' => $this->_('Quantity'),
			'revenue' => $this->_('Revenue'),
			'last_purchase' => $this->_('Last Purchase'),
			'renewals' => $this->_('Renewals'),
			'page_id' => $this->_('Page ID'),
			'stripe_id' => $this->_('Stripe Product ID'),
		];
	}

	/**
	 * Available columns for Customers tab
	 */
	protected array $availableCustomersColumns = [
		'name'            => ['label' => 'Name'],
		'email'           => ['label' => 'Email'],
		'total_purchases' => ['label' => 'Total Purchases'],
		'total_revenue'   => ['label' => 'Total Revenue'],
		'first_purchase'  => ['label' => 'First Purchase'],
		'last_activity'   => ['label' => 'Last Activity'],
	];

	/**
	 * Get translatable customer column labels
	 */
	protected function getCustomerColumnLabels(): array {
		return [
			'name' => $this->_('Name'),
			'email' => $this->_('Email'),
			'total_purchases' => $this->_('Total Purchases'),
			'total_revenue' => $this->_('Total Revenue'),
			'first_purchase' => $this->_('First Purchase'),
			'last_activity' => $this->_('Last Activity'),
		];
	}

	/**
	 * Get all available filters for Purchases tab
	 */
	protected function getAvailablePurchasesFilters(): array {
		return [
			'user_email' => $this->_('User Email'),
			'user_name' => $this->_('User Name'),
			'customer_name' => $this->_('Customer Name'),
			'purchase_date' => $this->_('Purchase Date'),
			'product_titles' => $this->_('Products'),
			'amount_total' => $this->_('Amount Total'),
			'payment_status' => $this->_('Payment Status'),
			'period_end' => $this->_('Period End'),
			'last_renewal' => $this->_('Last Renewal'),
		];
	}

	/**
	 * Get all available filters for Products tab
	 */
	protected function getAvailableProductsFilters(): array {
		return [
			'name' => $this->_('Product Name'),
			'revenue' => $this->_('Revenue'),
			'purchases' => $this->_('Purchases'),
			'quantity' => $this->_('Quantity'),
			'renewals' => $this->_('Renewals'),
			'last_purchase' => $this->_('Last Purchase'),
		];
	}

	/**
	 * Get all available filters for Customers tab
	 */
	protected function getAvailableCustomersFilters(): array {
		return [
			'name' => $this->_('Name'),
			'email' => $this->_('Email'),
			'total_revenue' => $this->_('Revenue'),
			'total_purchases' => $this->_('Purchases'),
			'first_purchase' => $this->_('First Purchase'),
		];
	}

	/**
	 * Module configuration
	 */
	public static function getModuleConfigInputfields(array $data): InputfieldWrapper {
		$modules = wire('modules');
		$wrapper = new InputfieldWrapper();

		$defaults = self::getDefaults();
		$data = array_merge($defaults, $data);

		$instance = new self();
		$columnLabels = $instance->getColumnLabels();
		$productLabels = $instance->getProductColumnLabels();
		$customerLabels = $instance->getCustomerColumnLabels();

		// Purchases Tab
		$tab1 = $modules->get('InputfieldFieldset');
		$tab1->label = $instance->_('Purchases');
		$tab1->collapsed = Inputfield::collapsedNo;

		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'purchasesColumns';
		$f->label = $instance->_('Columns for Purchases tab');
		$f->description = $instance->_('Select and order the columns to show in the purchases table.');

		foreach ($instance->availableColumns as $key => $col) {
			$f->addOption($key, $columnLabels[$key] ?? $col['label']);
		}
		$f->value = $data['purchasesColumns'] ?? $data['adminColumns'] ?? [];
		$tab1->add($f);

		// Filters for Purchases tab
		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'purchasesFilters';
		$f->label = $instance->_('Filters for Purchases tab');
		$f->description = $instance->_('Select which filters should be available. Only filters matching selected columns will be shown.');

		foreach ($instance->getAvailablePurchasesFilters() as $key => $label) {
			$f->addOption($key, $label);
		}
		$f->value = $data['purchasesFilters'] ?? [];
		$tab1->add($f);

		$wrapper->add($tab1);

		// Products Tab
		$tab2 = $modules->get('InputfieldFieldset');
		$tab2->label = $instance->_('Products');
		$tab2->collapsed = Inputfield::collapsedNo;

		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'productsColumns';
		$f->label = $instance->_('Columns for Products tab');
		$f->description = $instance->_('Select and order the columns to show in the products table.');

		foreach ($instance->availableProductsColumns as $key => $col) {
			$f->addOption($key, $productLabels[$key] ?? $col['label']);
		}
		$f->value = $data['productsColumns'] ?? [];
		$tab2->add($f);

		// Filters for Products tab
		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'productsFilters';
		$f->label = $instance->_('Filters for Products tab');
		$f->description = $instance->_('Select which filters should be available. Only filters matching selected columns will be shown.');

		foreach ($instance->getAvailableProductsFilters() as $key => $label) {
			$f->addOption($key, $label);
		}
		$f->value = $data['productsFilters'] ?? [];
		$tab2->add($f);

		$wrapper->add($tab2);

		// Customers Tab
		$tab3 = $modules->get('InputfieldFieldset');
		$tab3->label = $instance->_('Customers');
		$tab3->collapsed = Inputfield::collapsedNo;

		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'customersColumns';
		$f->label = $instance->_('Columns for Customers tab');
		$f->description = $instance->_('Select and order the columns to show in the customers table.');

		foreach ($instance->availableCustomersColumns as $key => $col) {
			$f->addOption($key, $customerLabels[$key] ?? $col['label']);
		}
		$f->value = $data['customersColumns'] ?? [];
		$tab3->add($f);

		// Filters for Customers tab
		$f = $modules->get('InputfieldAsmSelect');
		$f->name = 'customersFilters';
		$f->label = $instance->_('Filters for Customers tab');
		$f->description = $instance->_('Select which filters should be available. Only filters matching selected columns will be shown.');

		foreach ($instance->getAvailableCustomersFilters() as $key => $label) {
			$f->addOption($key, $label);
		}
		$f->value = $data['customersFilters'] ?? [];
		$tab3->add($f);

		$wrapper->add($tab3);

		// General settings
		$tab4 = $modules->get('InputfieldFieldset');
		$tab4->label = $instance->_('General');
		$tab4->collapsed = Inputfield::collapsedNo;

		$f = $modules->get('InputfieldInteger');
		$f->name = 'itemsPerPage';
		$f->label = $instance->_('Items per page');
		$f->value = $data['itemsPerPage'];
		$f->max = 500;
		$tab4->add($f);

		$wrapper->add($tab4);

		return $wrapper;
	}

	/**
	 * Main execute method - renders the purchases table
	 */
	public function ___execute(): string {
		$this->headline($this->_('Purchases Overview'));
		$this->browserTitle($this->_('Purchases'));

		// Tab navigation
		$out = $this->renderTabs('purchases');

		$input = $this->wire('input');
		$users = $this->wire('users');
		$sanitizer = $this->wire('sanitizer');

		// Get config
		$columns = $this->purchasesColumns ?: self::getDefaults()['purchasesColumns'];
		$perPage = (int)($this->itemsPerPage ?: 25);

		// Render dynamic filter form
		$out .= $this->renderDynamicFilterForm($columns, 'purchases');

		// Collect all purchases
		$allPurchases = [];
		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$allPurchases[] = [
					'user' => $user,
					'item' => $item,
					'date' => (int)$item->get('purchase_date'),
				];
			}
		}

		// Apply dynamic filters
		$allPurchases = $this->applyDynamicFilters($allPurchases, $columns, 'purchases');

		// Sort by first selected column
		$firstColumn = $columns[0] ?? 'purchase_date';
		$descending = $this->shouldSortDescending($firstColumn);

		usort($allPurchases, function($a, $b) use ($firstColumn, $descending) {
			// Get values for comparison
			$valA = $this->getColumnSortValue($a['user'], $a['item'], $firstColumn);
			$valB = $this->getColumnSortValue($b['user'], $b['item'], $firstColumn);

			// Compare
			$cmp = $valA <=> $valB;
			return $descending ? -$cmp : $cmp;
		});

		// Pagination
		$total = count($allPurchases);
		$page = max(1, (int)$input->get('pg'));
		$offset = ($page - 1) * $perPage;
		$paginated = array_slice($allPurchases, $offset, $perPage);

		// Render table
		$out .= $this->renderTable($paginated, $columns);

		// Pagination and Export in same row
		$out .= $this->renderPaginationRow($total, $perPage, $page);

		return $out;
	}

	/**
	 * Export to CSV
	 */
	public function ___executeExport(): void {
		$users = $this->wire('users');

		$columns = $this->purchasesColumns ?: self::getDefaults()['purchasesColumns'];

		// Collect all purchases
		$allPurchases = [];
		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$allPurchases[] = [
					'user' => $user,
					'item' => $item,
					'date' => (int)$item->get('purchase_date'),
				];
			}
		}

		// Apply dynamic filters
		$allPurchases = $this->applyDynamicFilters($allPurchases, $columns, 'purchases');

		// Sort by first selected column
		$firstColumn = $columns[0] ?? 'purchase_date';
		$descending = $this->shouldSortDescending($firstColumn);

		usort($allPurchases, function($a, $b) use ($firstColumn, $descending) {
			$valA = $this->getColumnSortValue($a['user'], $a['item'], $firstColumn);
			$valB = $this->getColumnSortValue($b['user'], $b['item'], $firstColumn);
			$cmp = $valA <=> $valB;
			return $descending ? -$cmp : $cmp;
		});

		// Output CSV
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="purchases-' . date('Y-m-d-His') . '.csv"');

		$fp = fopen('php://output', 'w');

		// Header row
		$headers = [];
		$columnLabels = $this->getColumnLabels();
		foreach ($columns as $col) {
			$headers[] = $columnLabels[$col] ?? $this->availableColumns[$col]['label'] ?? $col;
		}
		fputcsv($fp, $headers);

		// Data rows
		foreach ($allPurchases as $purchase) {
			$row = [];
			foreach ($columns as $col) {
				$value = $this->getColumnValue($purchase['user'], $purchase['item'], $col);
				$row[] = strip_tags($value);
			}
			fputcsv($fp, $row);
		}

		fclose($fp);
		exit;
	}

	/**
	 * Render the purchases table
	 */
	protected function renderTable(array $purchases, array $columns): string {
		if (empty($purchases)) {
			return "<p>" . $this->_('No purchases found.') . "</p>";
		}

		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->setSortable(true);

		// Header
		$headerRow = [];
		$columnLabels = $this->getColumnLabels();
		foreach ($columns as $col) {
			$headerRow[] = $columnLabels[$col] ?? $this->availableColumns[$col]['label'] ?? $col;
		}
		$table->headerRow($headerRow);

		// Rows
		foreach ($purchases as $purchase) {
			$row = [];
			foreach ($columns as $col) {
				// Handle amount_total directly to avoid escaping issues
				if ($col === 'amount_total') {
					$session = (array)$purchase['item']->meta('stripe_session');
					$lineItems = $session['line_items']['data'] ?? [];
					$total = 0;
					$currency = '';
					foreach ($lineItems as $li) {
						$total += (int)($li['amount_total'] ?? 0);
						if (!$currency) $currency = strtoupper($li['currency'] ?? $session['currency'] ?? '');
					}
					// Add renewal amounts
					$renewals = (array)$purchase['item']->meta('renewals');
					foreach ($renewals as $scopeRenewals) {
						foreach ((array)$scopeRenewals as $renewal) {
							$total += (int)($renewal['amount'] ?? 0);
						}
					}
					$row[] = $total > 0 ? $this->formatPrice($total, $currency) : '';
				} else {
					$row[] = $this->getColumnValue($purchase['user'], $purchase['item'], $col);
				}
			}
			$table->row($row);
		}

		return $table->render();
	}

	/**
	 * Get value for a specific column
	 */
	protected function getColumnValue(User $user, Page $item, string $column): string {
		$colDef = $this->availableColumns[$column] ?? null;
		if (!$colDef) return '';

		$type = $colDef['type'] ?? 'meta_path';

		switch ($type) {
			case 'user':
				if ($column === 'user_email') {
					return $this->renderUserEmail((string)$user->email);
				}
				if ($column === 'user_name') return (string)$user->title;
				break;

			case 'field':
				$val = $item->get($column);
				if ($column === 'purchase_date' && is_numeric($val)) {
					return date('Y-m-d H:i', (int)$val);
				}
				if ($column === 'purchase_lines') {
					return str_replace("\n", " | ", trim((string)$val));
				}
				return (string)$val;

			case 'meta_array':
				$key = $colDef['key'] ?? $column;
				$arr = (array)$item->meta($key);
				return implode(', ', $arr);

			case 'computed':
				$method = $colDef['compute'] ?? '';
				if ($method && method_exists($this, $method)) {
					return $this->$method($user, $item);
				}
				break;

			default:
				// meta_path
				if (isset($colDef['path'])) {
					return $this->getMetaPath($item, $colDef['path']);
				}
		}

		return '';
	}

	/**
	 * Get nested meta value by path
	 */
	protected function getMetaPath(Page $item, array $path): string {
		if (empty($path)) return '';

		$key = array_shift($path);
		$data = $item->meta($key);

		if ($data === null) return '';

		foreach ($path as $k) {
			if (is_array($data) && isset($data[$k])) {
				$data = $data[$k];
			} elseif (is_object($data) && isset($data->$k)) {
				$data = $data->$k;
			} else {
				return '';
			}
		}

		if (is_array($data) || is_object($data)) {
			return json_encode($data);
		}

		return (string)$data;
	}

	/**
	 * Compute total amount from line items
	 */
	protected function computeAmountTotal(User $user, Page $item): string {
		$session = (array)$item->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];

		$total = 0;
		$currency = '';
		foreach ($lineItems as $li) {
			$total += (int)($li['amount_total'] ?? 0);
			if (!$currency) $currency = strtoupper($li['currency'] ?? $session['currency'] ?? '');
		}

		if ($total === 0) return '';

		return $this->formatPrice($total, $currency);
	}

	/**
	 * Format price for display
	 * Format: € 3409,00 (EUR) or $ 3409.00 (USD) or CURRENCY 3409.00
	 */
	protected function formatPrice(int $cents, string $currency): string {
		$amount = $cents / 100;

		if ($currency === 'EUR') {
			return '€ ' . number_format($amount, 2, ',', '');
		} elseif ($currency === 'USD') {
			return '$ ' . number_format($amount, 2, '.', '');
		} else {
			return $currency . ' ' . number_format($amount, 2, '.', '');
		}
	}

	/**
	 * Determine if a column should be sorted descending (dates, money, numbers) or ascending (strings)
	 */
	protected function shouldSortDescending(string $column): bool {
		// Columns that should be sorted descending (dates, money, numeric values)
		$descendingColumns = [
			// Dates
			'purchase_date', 'period_end', 'last_renewal', 'last_purchase', 'first_purchase', 'last_activity',
			// Money
			'amount_total', 'revenue', 'total_revenue',
			// Numeric
			'purchases', 'quantity', 'total_purchases', 'renewals', 'line_items_count', 'renewal_count',
		];

		return in_array($column, $descendingColumns, true);
	}

	/**
	 * Get sortable value for a column (for Purchases table)
	 */
	protected function getColumnSortValue(User $user, Page $item, string $column) {
		// Special handling for specific columns
		if ($column === 'purchase_date') {
			return (int)$item->get('purchase_date');
		}

		if ($column === 'amount_total') {
			$session = (array)$item->meta('stripe_session');
			$lineItems = $session['line_items']['data'] ?? [];
			$total = 0;
			foreach ($lineItems as $li) {
				$total += (int)($li['amount_total'] ?? 0);
			}
			// Add renewal amounts
			$renewals = (array)$item->meta('renewals');
			foreach ($renewals as $scopeRenewals) {
				foreach ((array)$scopeRenewals as $renewal) {
					$total += (int)($renewal['amount'] ?? 0);
				}
			}
			return $total;
		}

		if ($column === 'user_email') {
			return strtolower($user->email);
		}

		if ($column === 'user_name') {
			return strtolower($user->title ?: $user->name);
		}

		// For all other columns, get the display value and convert to lowercase for string sorting
		$value = $this->getColumnValue($user, $item, $column);
		return is_numeric($value) ? (float)$value : strtolower($value);
	}

	/**
	 * Render user email with mailto link
	 */
	protected function renderUserEmail(string $email): string {
		if (!$email) return '';
		return "<a href='mailto:{$email}'>{$email}</a>";
	}

	/**
	 * Render customer name with link to user
	 */
	protected function renderCustomerName(string $customerName, int $userId): string {
		if (!$customerName) return '';
		$editUrl = $this->wire('config')->urls->admin . "access/users/edit/?id={$userId}";
		return "<a href='{$editUrl}'>{$customerName}</a>";
	}

	/**
	 * Compute shipping address
	 */
	protected function computeShippingAddress(User $user, Page $item): string {
		$session = (array)$item->meta('stripe_session');
		$shipping = $session['shipping']['address'] ?? [];

		if (empty($shipping)) return '';

		$parts = array_filter([
			$shipping['line1'] ?? '',
			$shipping['line2'] ?? '',
			$shipping['postal_code'] ?? '',
			$shipping['city'] ?? '',
			$shipping['country'] ?? '',
		]);

		return implode(', ', $parts);
	}

	/**
	 * Compute customer name with link to user
	 */
	protected function computeCustomerName(User $user, Page $item): string {
		$customerName = $user->title ?: $user->name;
		return $this->renderCustomerName($customerName, $user->id);
	}

	/**
	 * Compute product titles from IDs and stripe session line items
	 */
	protected function computeProductTitles(User $user, Page $item): string {
		$pages = $this->wire('pages');
		$productIds = (array)$item->meta('product_ids');
		$session = (array)$item->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];

		$titles = [];
		$mappedStripeIds = [];

		// First get titles from mapped product IDs
		foreach ($productIds as $pid) {
			$pid = (int)$pid;
			if ($pid === 0) continue;
			$p = $pages->get($pid);
			if ($p && $p->id) {
				$titles[] = $p->title;
				// Track the stripe_product_id if available
				if ($p->hasField('stripe_product_id') && $p->stripe_product_id) {
					$mappedStripeIds[] = $p->stripe_product_id;
				}
			}
		}

		// Then add unmapped products from line items
		foreach ($lineItems as $li) {
			$stripeProductId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
			if (is_array($stripeProductId)) $stripeProductId = $stripeProductId['id'] ?? '';

			// Skip if already mapped
			if (in_array($stripeProductId, $mappedStripeIds)) continue;

			// Get product name from line item
			$name = $li['price']['product']['name']
				?? $li['description']
				?? $li['price']['nickname']
				?? '';

			if ($name && !in_array($name, $titles)) {
				$titles[] = $name;
			}
		}

		return implode(', ', $titles);
	}

	/**
	 * Compute subscription status
	 */
	protected function computeSubscriptionStatus(User $user, Page $item): string {
		$map = (array)$item->meta('period_end_map');

		if (empty($map)) {
			return '–';
		}

		$statuses = [];
		$processedKeys = [];

		// Iterate over all keys in period_end_map
		foreach ($map as $key => $val) {
			// Skip flag keys
			if (strpos($key, '_paused') !== false || strpos($key, '_canceled') !== false) {
				continue;
			}

			$processedKeys[] = $key;
			$pausedKey = $key . '_paused';
			$canceledKey = $key . '_canceled';

			if (isset($map[$canceledKey])) {
				$statuses[] = 'canceled';
			} elseif (isset($map[$pausedKey])) {
				$statuses[] = 'paused';
			} elseif (is_numeric($val)) {
				$end = (int)$val;
				if ($end < time()) {
					$statuses[] = 'expired';
				} else {
					$statuses[] = 'active';
				}
			}
		}

		if (empty($statuses)) {
			return '–';
		}

		$statuses = array_unique($statuses);
		return implode(', ', $statuses);
	}

	/**
	 * Compute period end dates
	 */
	protected function computePeriodEnd(User $user, Page $item): string {
		$map = (array)$item->meta('period_end_map');

		$dates = [];
		foreach ($map as $key => $val) {
			// Skip flag keys
			if (strpos($key, '_paused') !== false || strpos($key, '_canceled') !== false) {
				continue;
			}
			if (is_numeric($val)) {
				$dates[] = date('Y-m-d', (int)$val);
			}
		}

		return implode(', ', array_unique($dates));
	}

	/**
	 * Compute line items count
	 */
	protected function computeLineItemsCount(User $user, Page $item): string {
		$session = (array)$item->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];
		return (string)count($lineItems);
	}

	/**
	 * Compute renewal count
	 */
	protected function computeRenewalCount(User $user, Page $item): string {
		$renewals = (array)$item->meta('renewals');
		$count = 0;
		foreach ($renewals as $scopeRenewals) {
			$count += count((array)$scopeRenewals);
		}
		return $count > 0 ? (string)$count : '';
	}

	/**
	 * Compute last renewal date
	 */
	protected function computeLastRenewal(User $user, Page $item): string {
		$renewals = (array)$item->meta('renewals');
		$lastDate = 0;

		foreach ($renewals as $scopeRenewals) {
			foreach ((array)$scopeRenewals as $renewal) {
				$date = (int)($renewal['date'] ?? 0);
				if ($date > $lastDate) $lastDate = $date;
			}
		}

		return $lastDate > 0 ? date('Y-m-d', $lastDate) : '';
	}

	/**
	 * =======================================================================
	 * DYNAMIC FILTER SYSTEM
	 * =======================================================================
	 */

	/**
	 * Get filter configuration for a column
	 * Returns filter type and settings based on column name/type
	 */
	protected function getFilterConfigForColumn(string $column, string $context = 'purchases'): ?array {
		// Map of columns to filter configurations
		$filterMap = [
			// Text/Search filters
			'user_email' => ['type' => 'search', 'label' => $this->_('Email'), 'fields' => ['user_email']],
			'user_name' => ['type' => 'search', 'label' => $this->_('Name'), 'fields' => ['user_name']],
			'customer_name' => ['type' => 'search', 'label' => $this->_('Customer'), 'fields' => ['customer_name']],
			'name' => ['type' => 'search', 'label' => $this->_('Name'), 'fields' => ['name']],
			'email' => ['type' => 'search', 'label' => $this->_('Email'), 'fields' => ['email']],
			'payment_status' => ['type' => 'search', 'label' => $this->_('Status'), 'fields' => ['payment_status']],

			// Date filters
			'purchase_date' => ['type' => 'date_range', 'label' => $this->_('Purchase Date')],
			'last_purchase' => ['type' => 'date_range', 'label' => $this->_('Last Purchase')],
			'first_purchase' => ['type' => 'date_range', 'label' => $this->_('First Purchase')],
			'period_end' => ['type' => 'date_range', 'label' => $this->_('Period End')],
			'last_renewal' => ['type' => 'date_range', 'label' => $this->_('Last Renewal')],

			// Number filters (min/max)
			'amount_total' => ['type' => 'number_range', 'label' => $this->_('Amount')],
			'revenue' => ['type' => 'number_range', 'label' => $this->_('Revenue')],
			'total_revenue' => ['type' => 'number_range', 'label' => $this->_('Revenue')],
			'quantity' => ['type' => 'number_range', 'label' => $this->_('Quantity')],
			'purchases' => ['type' => 'number_range', 'label' => $this->_('Purchases')],
			'total_purchases' => ['type' => 'number_range', 'label' => $this->_('Purchases')],
			'renewal_count' => ['type' => 'number_range', 'label' => $this->_('Renewals')],
			'renewals' => ['type' => 'number_range', 'label' => $this->_('Renewals')],
			'line_items_count' => ['type' => 'number_range', 'label' => $this->_('Items')],

			// Special filters
			'product_titles' => ['type' => 'product_multiselect', 'label' => $this->_('Products')],
		];

		return $filterMap[$column] ?? null;
	}

	/**
	 * Build dynamic filter form based on selected columns
	 */
	protected function renderDynamicFilterForm(array $columns, string $context = 'purchases'): string {
		$modules = $this->wire('modules');
		$input = $this->wire('input');

		// Get configured filters for this context
		$configuredFilters = [];
		if ($context === 'purchases') {
			$configuredFilters = $this->purchasesFilters ?: self::getDefaults()['purchasesFilters'];
		} elseif ($context === 'products') {
			$configuredFilters = $this->productsFilters ?: self::getDefaults()['productsFilters'];
		} elseif ($context === 'customers') {
			$configuredFilters = $this->customersFilters ?: self::getDefaults()['customersFilters'];
		}

		// Build form
		/** @var InputfieldForm $form */
		$form = $modules->get('InputfieldForm');
		$form->method = 'get';
		$form->action = './';

		// Collect filter configs for selected columns
		$filterConfigs = [];
		$addedFilters = []; // Track which filters we've already added

		foreach ($columns as $column) {
			// Check if filter is enabled in config
			if (!in_array($column, $configuredFilters, true)) {
				continue;
			}

			$config = $this->getFilterConfigForColumn($column, $context);
			if (!$config) continue;

			// Create unique key for this filter type
			$filterKey = $config['type'] . '_' . ($config['fields'][0] ?? $column);

			// Skip if we already added this filter
			if (isset($addedFilters[$filterKey])) {
				// Merge search fields if it's a search filter
				if ($config['type'] === 'search' && isset($config['fields'])) {
					$addedFilters[$filterKey]['fields'] = array_merge(
						$addedFilters[$filterKey]['fields'] ?? [],
						$config['fields']
					);
				}
				continue;
			}

			$addedFilters[$filterKey] = $config;
			$filterConfigs[] = $config;
		}

		// Combine search filters into one field
		$searchFields = [];
		$otherFilters = [];
		foreach ($filterConfigs as $config) {
			if ($config['type'] === 'search') {
				$searchFields = array_merge($searchFields, $config['fields'] ?? []);
			} else {
				$otherFilters[] = $config;
			}
		}

		// Add search field if we have any search columns
		if (!empty($searchFields)) {
			/** @var InputfieldText $f */
			$f = $modules->get('InputfieldText');
			$f->name = 'filter_search';
			$f->label = $this->_('Search');
			$f->columnWidth = 30;
			$f->value = $input->get('filter_search');
			$f->collapsed = Inputfield::collapsedNever;
			$form->add($f);
		}

		// Add other filters
		foreach ($otherFilters as $config) {
			switch ($config['type']) {
				case 'date_range':
					// From date
					/** @var InputfieldText $f */
					$f = $modules->get('InputfieldText');
					$f->name = 'filter_' . strtolower(str_replace(' ', '_', $config['label'])) . '_from';
					$f->label = $config['label'] . ' ' . $this->_('From');
					$f->attr('type', 'date');
					$f->columnWidth = 20;
					$f->value = $input->get($f->name);
					$form->add($f);

					// To date
					/** @var InputfieldText $f */
					$f = $modules->get('InputfieldText');
					$f->name = 'filter_' . strtolower(str_replace(' ', '_', $config['label'])) . '_to';
					$f->label = $config['label'] . ' ' . $this->_('To');
					$f->attr('type', 'date');
					$f->columnWidth = 20;
					$f->value = $input->get($f->name);
					$form->add($f);
					break;

				case 'number_range':
					// Min
					/** @var InputfieldText $f */
					$f = $modules->get('InputfieldText');
					$f->name = 'filter_' . strtolower(str_replace(' ', '_', $config['label'])) . '_min';
					$f->label = $config['label'] . ' ' . $this->_('Min');
					$f->attr('type', 'number');
					$f->columnWidth = 15;
					$f->value = $input->get($f->name);
					$form->add($f);

					// Max
					/** @var InputfieldText $f */
					$f = $modules->get('InputfieldText');
					$f->name = 'filter_' . strtolower(str_replace(' ', '_', $config['label'])) . '_max';
					$f->label = $config['label'] . ' ' . $this->_('Max');
					$f->attr('type', 'number');
					$f->columnWidth = 15;
					$f->value = $input->get($f->name);
					$form->add($f);
					break;

				case 'product_multiselect':
					/** @var InputfieldAsmSelect $f */
					$f = $modules->get('InputfieldAsmSelect');
					$f->name = 'filter_products';
					$f->label = $config['label'];
					$f->columnWidth = 30;

					// Get product options
					$productOptions = $this->getProductFilterOptions();
					foreach ($productOptions as $value => $label) {
						$f->addOption($value, $label);
					}

					$filterProducts = $input->get('filter_products');
					if (!is_array($filterProducts)) {
						$filterProducts = $filterProducts ? [$filterProducts] : [];
					}
					$f->value = $filterProducts;
					$form->add($f);
					break;
			}
		}

		// Buttons
		$remainingWidth = 100 - (count($form->children()) * 15);
		if ($remainingWidth < 10) $remainingWidth = 100;

		/** @var InputfieldMarkup $f */
		$f = $modules->get('InputfieldMarkup');
		$f->name = 'buttons';
		$f->label = ' ';
		$f->columnWidth = max(10, min(20, $remainingWidth));
		$f->value = "<button type='submit' class='ui-button ui-state-default'><span class='ui-button-text'>" . $this->_('Filter') . "</span></button> ";
		$f->value .= "<a href='{$this->page->url}' class='ui-button ui-state-default ui-priority-secondary'><span class='ui-button-text'>" . $this->_('Reset') . "</span></a>";
		$form->add($f);

		return $form->render();
	}

	/**
	 * Get product options for filter
	 */
	protected function getProductFilterOptions(): array {
		$pages = $this->wire('pages');
		$users = $this->wire('users');
		$productOptions = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');

				// Process mapped products
				foreach ($productIds as $pid) {
					$pid = (int)$pid;
					if ($pid === 0) continue;
					$p = $pages->get($pid);
					if ($p && $p->id) {
						$productOptions[$p->id] = $p->title;
					}
				}

				// Process unmapped products
				foreach ($lineItems as $li) {
					$stripeProductId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
					if (is_array($stripeProductId)) $stripeProductId = $stripeProductId['id'] ?? '';

					$productName = $li['price']['product']['name']
						?? $li['description']
						?? $li['price']['nickname']
						?? '';

					if ($productName) {
						$isMapped = false;
						foreach ($productIds as $pid) {
							$pid = (int)$pid;
							if ($pid === 0) continue;
							$p = $pages->get($pid);
							if ($p && $p->id && $p->hasField('stripe_product_id') && $p->stripe_product_id === $stripeProductId) {
								$isMapped = true;
								break;
							}
						}

						if (!$isMapped) {
							$key = 'stripe:' . $productName;
							$productOptions[$key] = $productName;
						}
					}
				}
			}
		}

		asort($productOptions);
		return $productOptions;
	}

	/**
	 * Apply dynamic filters to data
	 */
	protected function applyDynamicFilters(array $data, array $columns, string $context = 'purchases'): array {
		$input = $this->wire('input');
		$sanitizer = $this->wire('sanitizer');

		// Get search filter
		$search = $sanitizer->text($input->get('filter_search'));

		// Get all filter values
		$filters = [];
		foreach ($columns as $column) {
			$config = $this->getFilterConfigForColumn($column, $context);
			if (!$config) continue;

			$label = strtolower(str_replace(' ', '_', $config['label']));

			switch ($config['type']) {
				case 'date_range':
					$from = $sanitizer->text($input->get('filter_' . $label . '_from'));
					$to = $sanitizer->text($input->get('filter_' . $label . '_to'));
					if ($from || $to) {
						$filters[$column] = ['type' => 'date_range', 'from' => $from, 'to' => $to];
					}
					break;

				case 'number_range':
					$min = $input->get('filter_' . $label . '_min');
					$max = $input->get('filter_' . $label . '_max');
					if (($min !== '' && $min !== null) || ($max !== '' && $max !== null)) {
						$filters[$column] = ['type' => 'number_range', 'min' => $min, 'max' => $max];
					}
					break;

				case 'product_multiselect':
					$products = $input->get('filter_products');
					if (!is_array($products)) {
						$products = $products ? [$products] : [];
					}
					$products = array_map([$sanitizer, 'text'], $products);
					if (!empty($products)) {
						$filters[$column] = ['type' => 'product_multiselect', 'values' => $products];
					}
					break;
			}
		}

		// Apply filters based on context
		if ($context === 'purchases') {
			return $this->applyPurchasesFilters($data, $search, $filters);
		} elseif ($context === 'products') {
			return $this->applyProductsFilters($data, $search, $filters);
		} elseif ($context === 'customers') {
			return $this->applyCustomersFilters($data, $search, $filters);
		}

		return $data;
	}

	/**
	 * Apply filters to purchases data
	 */
	protected function applyPurchasesFilters(array $purchases, string $search, array $filters): array {
		$filtered = [];

		foreach ($purchases as $purchase) {
			$user = $purchase['user'];
			$item = $purchase['item'];

			// Search filter
			if ($search) {
				$searchLower = strtolower($search);
				$userEmail = strtolower($user->email);
				$userName = strtolower($user->title ?: $user->name);

				$matchFound = (strpos($userEmail, $searchLower) !== false) || (strpos($userName, $searchLower) !== false);
				if (!$matchFound) continue;
			}

			// Apply column-specific filters
			$skip = false;
			foreach ($filters as $column => $filter) {
				switch ($filter['type']) {
					case 'date_range':
						$value = null;
						if ($column === 'purchase_date') {
							$value = (int)$item->get('purchase_date');
						}

						if ($value !== null) {
							if ($filter['from']) {
								$fromTs = strtotime($filter['from']);
								if ($fromTs && $value < $fromTs) {
									$skip = true;
									break 2;
								}
							}
							if ($filter['to']) {
								$toTs = strtotime($filter['to'] . ' 23:59:59');
								if ($toTs && $value > $toTs) {
									$skip = true;
									break 2;
								}
							}
						}
						break;

					case 'number_range':
						$value = null;
						if ($column === 'amount_total') {
							$value = $this->getColumnSortValue($user, $item, 'amount_total');
						}

						if ($value !== null) {
							if ($filter['min'] !== '' && $value < (float)$filter['min']) {
								$skip = true;
								break 2;
							}
							if ($filter['max'] !== '' && $value > (float)$filter['max']) {
								$skip = true;
								break 2;
							}
						}
						break;

					case 'product_multiselect':
						$found = false;
						$productIds = (array)$item->meta('product_ids');
						$session = (array)$item->meta('stripe_session');
						$lineItems = $session['line_items']['data'] ?? [];

						foreach ($filter['values'] as $filterProduct) {
							if (is_numeric($filterProduct)) {
								if (in_array((int)$filterProduct, array_map('intval', $productIds))) {
									$found = true;
									break;
								}
							} elseif (strpos($filterProduct, 'stripe:') === 0) {
								$searchName = substr($filterProduct, 7);
								foreach ($lineItems as $li) {
									$productName = $li['price']['product']['name']
										?? $li['description']
										?? $li['price']['nickname']
										?? '';
									if ($productName === $searchName) {
										$found = true;
										break 2;
									}
								}
							}
						}

						if (!$found) {
							$skip = true;
							break 2;
						}
						break;
				}
			}

			if (!$skip) {
				$filtered[] = $purchase;
			}
		}

		return $filtered;
	}

	/**
	 * Apply filters to products data
	 */
	protected function applyProductsFilters(array $products, string $search, array $filters): array {
		$filtered = [];

		foreach ($products as $key => $product) {
			// Search filter
			if ($search) {
				$searchLower = strtolower($search);
				$productName = strtolower($product['name']);

				if (strpos($productName, $searchLower) === false) continue;
			}

			// Apply column-specific filters
			$skip = false;
			foreach ($filters as $column => $filter) {
				switch ($filter['type']) {
					case 'date_range':
						$value = null;
						if ($column === 'last_purchase') {
							$value = $product['last_purchase'];
						}

						if ($value !== null && $value > 0) {
							if ($filter['from']) {
								$fromTs = strtotime($filter['from']);
								if ($fromTs && $value < $fromTs) {
									$skip = true;
									break 2;
								}
							}
							if ($filter['to']) {
								$toTs = strtotime($filter['to'] . ' 23:59:59');
								if ($toTs && $value > $toTs) {
									$skip = true;
									break 2;
								}
							}
						}
						break;

					case 'number_range':
						$value = null;
						if ($column === 'revenue') $value = $product['revenue'];
						elseif ($column === 'quantity') $value = $product['quantity'];
						elseif ($column === 'purchases') $value = $product['count'];
						elseif ($column === 'renewals') $value = $product['renewals'];

						if ($value !== null) {
							if ($filter['min'] !== '' && $value < (float)$filter['min']) {
								$skip = true;
								break 2;
							}
							if ($filter['max'] !== '' && $value > (float)$filter['max']) {
								$skip = true;
								break 2;
							}
						}
						break;
				}
			}

			if (!$skip) {
				$filtered[$key] = $product;
			}
		}

		return $filtered;
	}

	/**
	 * Apply filters to customers data
	 */
	protected function applyCustomersFilters(array $customers, string $search, array $filters): array {
		$filtered = [];

		foreach ($customers as $customer) {
			// Search filter
			if ($search) {
				$searchLower = strtolower($search);
				$customerName = strtolower($customer['name']);
				$customerEmail = strtolower($customer['email']);

				$matchFound = (strpos($customerName, $searchLower) !== false) || (strpos($customerEmail, $searchLower) !== false);
				if (!$matchFound) continue;
			}

			// Apply column-specific filters
			$skip = false;
			foreach ($filters as $column => $filter) {
				switch ($filter['type']) {
					case 'date_range':
						$value = null;
						if ($column === 'first_purchase') $value = $customer['first_purchase'];
						elseif ($column === 'last_purchase') $value = $customer['last_purchase'];

						if ($value !== null && $value > 0) {
							if ($filter['from']) {
								$fromTs = strtotime($filter['from']);
								if ($fromTs && $value < $fromTs) {
									$skip = true;
									break 2;
								}
							}
							if ($filter['to']) {
								$toTs = strtotime($filter['to'] . ' 23:59:59');
								if ($toTs && $value > $toTs) {
									$skip = true;
									break 2;
								}
							}
						}
						break;

					case 'number_range':
						$value = null;
						if ($column === 'total_revenue') $value = $customer['total_revenue'];
						elseif ($column === 'total_purchases') $value = $customer['total_purchases'];

						if ($value !== null) {
							if ($filter['min'] !== '' && $value < (float)$filter['min']) {
								$skip = true;
								break 2;
							}
							if ($filter['max'] !== '' && $value > (float)$filter['max']) {
								$skip = true;
								break 2;
							}
						}
						break;
				}
			}

			if (!$skip) {
				$filtered[] = $customer;
			}
		}

		return $filtered;
	}

	/**
	 * Render pagination row with pager and export button
	 */
	protected function renderPaginationRow(int $total, int $perPage, int $currentPage, string $exportAction = 'export'): string {
		$input = $this->wire('input');

		$out = "<div style='display:flex;justify-content:space-between;align-items:center;'>";

		// Left side: Pagination
		if ($total > $perPage) {
			$totalPages = (int)ceil($total / $perPage);
			$baseParams = $input->get->getArray();
			unset($baseParams['pg']);

			$out .= "<ul class='uk-pagination' style='margin-left:0;'>";

			// Previous
			if ($currentPage > 1) {
				$baseParams['pg'] = $currentPage - 1;
				$url = './?' . http_build_query($baseParams);
				$out .= "<li aria-label='Vorherige Seite' class='MarkupPagerNavPrevious'><a href='{$url}'><span><i class='fa fa-angle-left'></i></span></a></li>";
			}

			// Page numbers
			$range = 2;
			$start = max(1, $currentPage - $range);
			$end = min($totalPages, $currentPage + $range);

			// First page
			if ($start > 1) {
				$baseParams['pg'] = 1;
				$url = './?' . http_build_query($baseParams);
				$out .= "<li aria-label='Seite 1' class='MarkupPagerNavFirst'><a href='{$url}'><span>1</span></a></li>";
				if ($start > 2) {
					$out .= "<li class='MarkupPagerNavSeparator'><span>&hellip;</span></li>";
				}
			}

			// Middle pages
			for ($i = $start; $i <= $end; $i++) {
				$classes = [];
				if ($i == $currentPage) $classes[] = 'uk-active MarkupPagerNavOn';
				if ($i == 1) $classes[] = 'MarkupPagerNavFirstNum';
				if ($i == $totalPages) $classes[] = 'MarkupPagerNavLastNum';
				$classStr = implode(' ', $classes);
				$aria = ($i == $currentPage) ? " aria-current='true'" : '';

				$baseParams['pg'] = $i;
				$url = './?' . http_build_query($baseParams);
				$out .= "<li aria-label='Seite {$i}'" . ($classStr ? " class='{$classStr}'" : '') . "{$aria}><a href='{$url}'><span>{$i}</span></a></li>";
			}

			// Last page
			if ($end < $totalPages) {
				if ($end < $totalPages - 1) {
					$out .= "<li class='MarkupPagerNavSeparator'><span>&hellip;</span></li>";
				}
				$baseParams['pg'] = $totalPages;
				$url = './?' . http_build_query($baseParams);
				$out .= "<li aria-label='Seite {$totalPages}' class='MarkupPagerNavLast'><a href='{$url}'><span>{$totalPages}</span></a></li>";
			}

			// Next
			if ($currentPage < $totalPages) {
				$baseParams['pg'] = $currentPage + 1;
				$url = './?' . http_build_query($baseParams);
				$out .= "<li aria-label='Nächste Seite' class='MarkupPagerNavNext'><a href='{$url}'><span><i class='fa fa-angle-right'></i></span></a></li>";
			}

			$out .= "</ul>";
		} else {
			$out .= "<div></div>";
		}

		// Right side: Export button
		$exportParams = $input->get->getArray();
		unset($exportParams['pg']);
		$exportUrl = $this->page->url . $exportAction . '/' . (!empty($exportParams) ? '?' . http_build_query($exportParams) : '');
		$out .= "<a href='{$exportUrl}' class='uk-button uk-button uk-button-secondary'><i class='fa fa-download'></i> Export CSV</a>";

		$out .= "</div>";

		return $out;
	}

	/**
	 * Render tab navigation
	 */
	protected function renderTabs(string $active): string {
		$baseUrl = $this->page->url;
		$configUrl = $this->wire('config')->urls->admin . 'module/edit/?name=' . $this->className();

		$tabs = [
			'purchases' => ['url' => $baseUrl, 'label' => 'Purchases'],
			'products' => ['url' => $baseUrl . 'products/', 'label' => 'Products'],
			'customers' => ['url' => $baseUrl . 'customers/', 'label' => 'Customers'],
		];

		$out = "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:20px'>";

		$out .= "<ul class='WireTabs uk-tab' role='tablist' style='margin:0'>";
		foreach ($tabs as $key => $tab) {
			$liClass = ($key === $active) ? 'uk-active' : '';
			$aClass = ($key === $active) ? 'pw-active' : '';
			$out .= "<li class='{$liClass}' role='presentation'><a href='{$tab['url']}' class='{$aClass}' role='tab'>{$tab['label']}</a></li>";
		}
		$out .= "</ul>";

		$out .= "<a href='{$configUrl}&collapse_info=1' class='ui-link'><i class='fa fa-cog'></i> Columns</a>";

		$out .= "</div>";

		return $out;
	}

	/**
	 * Products overview - aggregated by product
	 */
	public function ___executeProducts(): string {
		$this->headline($this->_('Products Overview'));
		$this->browserTitle($this->_('Products'));

		$out = $this->renderTabs('products');

		$input = $this->wire('input');
		$users = $this->wire('users');
		$pages = $this->wire('pages');
		$sanitizer = $this->wire('sanitizer');

		// Aggregate data per product
		$productData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');
				$purchaseDate = (int)$item->get('purchase_date');

				// Process each line item
				foreach ($lineItems as $li) {
					$stripeProductId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
					if (is_array($stripeProductId)) $stripeProductId = $stripeProductId['id'] ?? '';

					$productName = $li['price']['product']['name']
						?? $li['description']
						?? $li['price']['nickname']
						?? 'Unknown';

					$amount = (int)($li['amount_total'] ?? 0);
					$currency = strtoupper($li['currency'] ?? $session['currency'] ?? 'EUR');
					$quantity = (int)($li['quantity'] ?? 1);

					// Find mapped page ID
					$pageId = 0;
					foreach ($productIds as $pid) {
						$pid = (int)$pid;
						if ($pid === 0) continue;
						$p = $pages->get($pid);
						if ($p && $p->id && $p->hasField('stripe_product_id') && $p->stripe_product_id === $stripeProductId) {
							$pageId = $pid;
							$productName = $p->title;
							break;
						}
					}

					// Use stripe product ID as key for unmapped
					$key = $pageId ?: ('stripe:' . $stripeProductId);

					if (!isset($productData[$key])) {
						$productData[$key] = [
							'name' => $productName,
							'page_id' => $pageId,
							'stripe_id' => $stripeProductId,
							'count' => 0,
							'quantity' => 0,
							'revenue' => 0,
							'currency' => $currency,
							'last_purchase' => 0,
							'renewals' => 0,
						];
					}

					$productData[$key]['count']++;
					$productData[$key]['quantity'] += $quantity;
					$productData[$key]['revenue'] += $amount;
					if ($purchaseDate > $productData[$key]['last_purchase']) {
						$productData[$key]['last_purchase'] = $purchaseDate;
					}
				}

				// Aggregate renewals - add to revenue
				$renewals = (array)$item->meta('renewals');
				foreach ($renewals as $scopeKey => $scopeRenewals) {
					// Match scope key to product key
					$renewalKey = null;
					if (is_numeric($scopeKey) && (int)$scopeKey > 0) {
						$renewalKey = (int)$scopeKey;
					} elseif (strpos($scopeKey, '0#') === 0) {
						$renewalKey = 'stripe:' . substr($scopeKey, 2);
					}

					if ($renewalKey && isset($productData[$renewalKey])) {
						foreach ((array)$scopeRenewals as $renewal) {
							$productData[$renewalKey]['renewals']++;
							$productData[$renewalKey]['revenue'] += (int)($renewal['amount'] ?? 0);
						}
					}
				}
			}
		}

		// Get configured columns
		$columns = $this->productsColumns ?: self::getDefaults()['productsColumns'];

		// Render dynamic filter form (before pagination)
		$filterForm = $this->renderDynamicFilterForm($columns, 'products');

		// Apply dynamic filters
		$productData = $this->applyProductsFilters($productData, $sanitizer->text($input->get('filter_search')), []);

		// Parse filter values for products
		$filters = [];
		foreach ($columns as $column) {
			$config = $this->getFilterConfigForColumn($column, 'products');
			if (!$config) continue;

			$label = strtolower(str_replace(' ', '_', $config['label']));

			switch ($config['type']) {
				case 'date_range':
					$from = $sanitizer->text($input->get('filter_' . $label . '_from'));
					$to = $sanitizer->text($input->get('filter_' . $label . '_to'));
					if ($from || $to) {
						$filters[$column] = ['type' => 'date_range', 'from' => $from, 'to' => $to];
					}
					break;

				case 'number_range':
					$min = $input->get('filter_' . $label . '_min');
					$max = $input->get('filter_' . $label . '_max');
					if (($min !== '' && $min !== null) || ($max !== '' && $max !== null)) {
						$filters[$column] = ['type' => 'number_range', 'min' => $min, 'max' => $max];
					}
					break;
			}
		}

		// Apply filters
		if (!empty($filters) || $sanitizer->text($input->get('filter_search'))) {
			$productData = $this->applyProductsFilters($productData, $sanitizer->text($input->get('filter_search')), $filters);
		}

		// Sort by first selected column
		$firstColumn = $columns[0] ?? 'name';
		$descending = $this->shouldSortDescending($firstColumn);

		uasort($productData, function($a, $b) use ($firstColumn, $descending) {
			// Map column name to data key
			$dataKey = $firstColumn === 'purchases' ? 'count' : $firstColumn;

			// Get values for comparison
			$valA = $a[$dataKey] ?? '';
			$valB = $b[$dataKey] ?? '';

			// Convert strings to lowercase for case-insensitive sorting
			if (is_string($valA)) $valA = strtolower($valA);
			if (is_string($valB)) $valB = strtolower($valB);

			// Compare
			$cmp = $valA <=> $valB;
			return $descending ? -$cmp : $cmp;
		});
		$perPage = (int)($this->itemsPerPage ?: 25);

		// Add filter form to output
		$out .= $filterForm;

		// Pagination
		$total = count($productData);
		$page = max(1, (int)$input->get('pg'));
		$offset = ($page - 1) * $perPage;
		$paginatedData = array_slice($productData, $offset, $perPage, true);

		// Render table
		if (empty($productData)) {
			$out .= "<p>" . $this->_('No products found.') . "</p>";
		} else {
			$table = $this->modules->get('MarkupAdminDataTable');
			$table->setEncodeEntities(false);
			$table->setSortable(true);

			// Dynamic header
			$headers = [];
			$productLabels = $this->getProductColumnLabels();
			foreach ($columns as $col) {
				$headers[] = $productLabels[$col] ?? $this->availableProductsColumns[$col]['label'] ?? $col;
			}
			$table->headerRow($headers);

			// Dynamic rows
			foreach ($paginatedData as $key => $data) {
				$row = [];
				foreach ($columns as $col) {
					switch ($col) {
						case 'name':
							$name = htmlspecialchars($data['name']);
							if ($data['page_id']) {
								$editUrl = $this->wire('config')->urls->admin . "page/edit/?id={$data['page_id']}";
								$name = "<a href='{$editUrl}'>{$name}</a>";
							}
							$row[] = $name;
							break;
						case 'purchases':
							$count = $data['count'];
							$productKey = htmlspecialchars($key);
							$row[] = "<a href='#' class='show-product-purchases' data-product-key='{$productKey}'>{$count}</a>";
							break;
						case 'quantity':
							$row[] = $data['quantity'];
							break;
						case 'revenue':
							$row[] = $this->formatPrice($data['revenue'], $data['currency']);
							break;
						case 'last_purchase':
							$row[] = $data['last_purchase'] ? date('Y-m-d', $data['last_purchase']) : '-';
							break;
						case 'renewals':
							$row[] = $data['renewals'] ?: '-';
							break;
						case 'page_id':
							$row[] = $data['page_id'] ?: '-';
							break;
						case 'stripe_id':
							$row[] = $data['stripe_id'] ?: '-';
							break;
						default:
							$row[] = '';
					}
				}
				$table->row($row);
			}

			$out .= "<div style='margin-top:-1px'>" . $table->render() . "</div>";
		}

		// Pagination and Export
		$out .= $this->renderPaginationRow($total, $perPage, $page, 'exportProducts');

		// Add modal placeholder
		$out .= $this->renderProductPurchasesModal();

		return $out;
	}

	/**
	 * Export products to CSV
	 */
	public function ___executeExportProducts(): void {
		$users = $this->wire('users');
		$pages = $this->wire('pages');

		// Aggregate data (same as executeProducts)
		$productData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');
				$purchaseDate = (int)$item->get('purchase_date');

				foreach ($lineItems as $li) {
					$stripeProductId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
					if (is_array($stripeProductId)) $stripeProductId = $stripeProductId['id'] ?? '';

					$productName = $li['price']['product']['name']
						?? $li['description']
						?? $li['price']['nickname']
						?? 'Unknown';

					$amount = (int)($li['amount_total'] ?? 0);
					$currency = strtoupper($li['currency'] ?? $session['currency'] ?? 'EUR');
					$quantity = (int)($li['quantity'] ?? 1);

					$pageId = 0;
					foreach ($productIds as $pid) {
						$pid = (int)$pid;
						if ($pid === 0) continue;
						$p = $pages->get($pid);
						if ($p && $p->id && $p->hasField('stripe_product_id') && $p->stripe_product_id === $stripeProductId) {
							$pageId = $pid;
							$productName = $p->title;
							break;
						}
					}

					$key = $pageId ?: ('stripe:' . $stripeProductId);

					if (!isset($productData[$key])) {
						$productData[$key] = [
							'name' => $productName,
							'page_id' => $pageId,
							'stripe_id' => $stripeProductId,
							'count' => 0,
							'quantity' => 0,
							'revenue' => 0,
							'currency' => $currency,
							'last_purchase' => 0,
							'renewals' => 0,
						];
					}

					$productData[$key]['count']++;
					$productData[$key]['quantity'] += $quantity;
					$productData[$key]['revenue'] += $amount;
					if ($purchaseDate > $productData[$key]['last_purchase']) {
						$productData[$key]['last_purchase'] = $purchaseDate;
					}
				}

				// Aggregate renewals - add to revenue
				$renewals = (array)$item->meta('renewals');
				foreach ($renewals as $scopeKey => $scopeRenewals) {
					$renewalKey = null;
					if (is_numeric($scopeKey) && (int)$scopeKey > 0) {
						$renewalKey = (int)$scopeKey;
					} elseif (strpos($scopeKey, '0#') === 0) {
						$renewalKey = 'stripe:' . substr($scopeKey, 2);
					}

					if ($renewalKey && isset($productData[$renewalKey])) {
						foreach ((array)$scopeRenewals as $renewal) {
							$productData[$renewalKey]['renewals']++;
							$productData[$renewalKey]['revenue'] += (int)($renewal['amount'] ?? 0);
						}
					}
				}
			}
		}

		uasort($productData, fn($a, $b) => $b['count'] <=> $a['count']);

		$columns = $this->productsColumns ?: self::getDefaults()['productsColumns'];

		// Output CSV
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="products-' . date('Y-m-d-His') . '.csv"');

		$fp = fopen('php://output', 'w');

		// Header row
		$headers = [];
		$productLabels = $this->getProductColumnLabels();
		foreach ($columns as $col) {
			$headers[] = $productLabels[$col] ?? $this->availableProductsColumns[$col]['label'] ?? $col;
		}
		fputcsv($fp, $headers);

		// Data rows
		foreach ($productData as $data) {
			$row = [];
			foreach ($columns as $col) {
				switch ($col) {
					case 'name':
						$row[] = $data['name'];
						break;
					case 'purchases':
						$row[] = $data['count'];
						break;
					case 'quantity':
						$row[] = $data['quantity'];
						break;
					case 'revenue':
						$cents = $data['revenue'];
						$symbol = ($data['currency'] === 'EUR') ? '€' : $data['currency'];
						$row[] = $symbol . ' ' . number_format($cents / 100, 2, ',', '');
						break;
					case 'last_purchase':
						$row[] = $data['last_purchase'] ? date('Y-m-d', $data['last_purchase']) : '';
						break;
					case 'renewals':
						$row[] = $data['renewals'] ?: '';
						break;
					case 'page_id':
						$row[] = $data['page_id'] ?: '';
						break;
					case 'stripe_id':
						$row[] = $data['stripe_id'] ?: '';
						break;
					default:
						$row[] = '';
				}
			}
			fputcsv($fp, $row);
		}

		fclose($fp);
		exit;
	}

	/**
	 * Customers overview - aggregated by customer
	 */
	public function ___executeCustomers(): string {
		$this->headline($this->_('Customers Overview'));
		$this->browserTitle($this->_('Customers'));

		$out = $this->renderTabs('customers');

		$input = $this->wire('input');
		$users = $this->wire('users');
		$pages = $this->wire('pages');
		$sanitizer = $this->wire('sanitizer');

		// Aggregate data per customer
		$customerData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			$totalRevenue = 0;
			$totalPurchases = 0;
			$firstPurchase = PHP_INT_MAX;
			$lastPurchase = 0;

			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];

				$totalPurchases++;
				if ($purchaseDate < $firstPurchase) $firstPurchase = $purchaseDate;
				if ($purchaseDate > $lastPurchase) $lastPurchase = $purchaseDate;

				// Calculate revenue from line items
				foreach ($lineItems as $li) {
					$totalRevenue += (int)($li['amount_total'] ?? 0);
				}

				// Add renewals
				$renewals = (array)$item->meta('renewals');
				foreach ($renewals as $scopeRenewals) {
					foreach ((array)$scopeRenewals as $renewal) {
						$totalRevenue += (int)($renewal['amount'] ?? 0);
					}
				}
			}

			if ($firstPurchase === PHP_INT_MAX) $firstPurchase = 0;

			$customerData[] = [
				'user' => $user,
				'name' => $user->title ?: $user->name,
				'email' => $user->email,
				'total_purchases' => $totalPurchases,
				'total_revenue' => $totalRevenue,
				'first_purchase' => $firstPurchase,
				'last_purchase' => $lastPurchase,
			];
		}

		// Get configured columns
		$columns = $this->customersColumns ?: self::getDefaults()['customersColumns'];

		// Render dynamic filter form
		$filterForm = $this->renderDynamicFilterForm($columns, 'customers');

		// Parse filter values for customers
		$filters = [];
		$search = $sanitizer->text($input->get('filter_search'));

		foreach ($columns as $column) {
			$config = $this->getFilterConfigForColumn($column, 'customers');
			if (!$config) continue;

			$label = strtolower(str_replace(' ', '_', $config['label']));

			switch ($config['type']) {
				case 'date_range':
					$from = $sanitizer->text($input->get('filter_' . $label . '_from'));
					$to = $sanitizer->text($input->get('filter_' . $label . '_to'));
					if ($from || $to) {
						$filters[$column] = ['type' => 'date_range', 'from' => $from, 'to' => $to];
					}
					break;

				case 'number_range':
					$min = $input->get('filter_' . $label . '_min');
					$max = $input->get('filter_' . $label . '_max');
					if (($min !== '' && $min !== null) || ($max !== '' && $max !== null)) {
						$filters[$column] = ['type' => 'number_range', 'min' => $min, 'max' => $max];
					}
					break;
			}
		}

		// Apply filters
		if (!empty($filters) || $search) {
			$customerData = $this->applyCustomersFilters($customerData, $search, $filters);
		}

		// Sort by first selected column
		$firstColumn = $columns[0] ?? 'name';
		$descending = $this->shouldSortDescending($firstColumn);

		usort($customerData, function($a, $b) use ($firstColumn, $descending) {
			// Map column name to data key (last_activity is computed from last_purchase)
			$dataKey = $firstColumn === 'last_activity' ? 'last_purchase' : $firstColumn;

			// Get values for comparison
			$valA = $a[$dataKey] ?? '';
			$valB = $b[$dataKey] ?? '';

			// Convert strings to lowercase for case-insensitive sorting
			if (is_string($valA)) $valA = strtolower($valA);
			if (is_string($valB)) $valB = strtolower($valB);

			// Compare
			$cmp = $valA <=> $valB;
			return $descending ? -$cmp : $cmp;
		});
		$perPage = (int)($this->itemsPerPage ?: 25);

		// Add filter form to output
		$out .= $filterForm;

		// Pagination
		$total = count($customerData);
		$page = max(1, (int)$input->get('pg'));
		$offset = ($page - 1) * $perPage;
		$paginatedData = array_slice($customerData, $offset, $perPage);

		// Render table
		if (empty($customerData)) {
			$out .= "<p>" . $this->_('No customers found.') . "</p>";
		} else {
			$table = $this->modules->get('MarkupAdminDataTable');
			$table->setEncodeEntities(false);
			$table->setSortable(true);

			// Dynamic header
			$headers = [];
			$customerLabels = $this->getCustomerColumnLabels();
			foreach ($columns as $col) {
				$headers[] = $customerLabels[$col] ?? $this->availableCustomersColumns[$col]['label'] ?? $col;
			}
			$table->headerRow($headers);

			// Dynamic rows
			foreach ($paginatedData as $data) {
				$row = [];
				foreach ($columns as $col) {
					switch ($col) {
						case 'name':
							$row[] = $this->renderCustomerName($data['name'], $data['user']->id);
							break;
						case 'email':
							$row[] = $this->renderUserEmail($data['email']);
							break;
						case 'total_purchases':
							$purchasesHtml = '<a href="#" class="show-customer-purchases" data-user-id="' . $data['user']->id . '" data-user-name="' . htmlspecialchars($data['name']) . '">' .
								$data['total_purchases'] . '</a>';
							$row[] = $purchasesHtml;
							break;
						case 'total_revenue':
							$currency = $data['user']->spl_purchases->first() ?
								strtoupper(((array)$data['user']->spl_purchases->first()->meta('stripe_session'))['currency'] ?? 'EUR') :
								'EUR';
							$row[] = $this->formatPrice($data['total_revenue'], $currency);
							break;
						case 'first_purchase':
							$row[] = $data['first_purchase'] ? date('Y-m-d', $data['first_purchase']) : '-';
							break;
						case 'last_activity':
							$days = $data['last_purchase'] ? floor((time() - $data['last_purchase']) / 86400) : '-';
							$row[] = $days !== '-' ? sprintf($this->_('%d days ago'), $days) : '-';
							break;
						default:
							$row[] = '';
					}
				}
				$table->row($row);
			}

			$out .= "<div style='margin-top:-1px'>" . $table->render() . "</div>";
		}

		// Pagination and Export
		$out .= $this->renderPaginationRow($total, $perPage, $page, 'exportCustomers');

		// Add modal placeholder
		$out .= $this->renderCustomerProductsModal();

		return $out;
	}

	/**
	 * Export customers to CSV
	 */
	public function ___executeExportCustomers(): void {
		$users = $this->wire('users');
		$pages = $this->wire('pages');

		// Aggregate data (same as executeCustomers)
		$customerData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			$totalRevenue = 0;
			$totalPurchases = 0;
			$firstPurchase = PHP_INT_MAX;
			$lastPurchase = 0;

			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];

				$totalPurchases++;
				if ($purchaseDate < $firstPurchase) $firstPurchase = $purchaseDate;
				if ($purchaseDate > $lastPurchase) $lastPurchase = $purchaseDate;

				foreach ($lineItems as $li) {
					$totalRevenue += (int)($li['amount_total'] ?? 0);
				}

				$renewals = (array)$item->meta('renewals');
				foreach ($renewals as $scopeRenewals) {
					foreach ((array)$scopeRenewals as $renewal) {
						$totalRevenue += (int)($renewal['amount'] ?? 0);
					}
				}
			}

			if ($firstPurchase === PHP_INT_MAX) $firstPurchase = 0;

			$customerData[] = [
				'user' => $user,
				'name' => $user->title ?: $user->name,
				'email' => $user->email,
				'total_purchases' => $totalPurchases,
				'total_revenue' => $totalRevenue,
				'first_purchase' => $firstPurchase,
				'last_purchase' => $lastPurchase,
			];
		}

		usort($customerData, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

		$columns = $this->customersColumns ?: self::getDefaults()['customersColumns'];

		// Output CSV
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="customers-' . date('Y-m-d-His') . '.csv"');

		$fp = fopen('php://output', 'w');

		// Header row
		$headers = [];
		$customerLabels = $this->getCustomerColumnLabels();
		foreach ($columns as $col) {
			$headers[] = $customerLabels[$col] ?? $this->availableCustomersColumns[$col]['label'] ?? $col;
		}
		fputcsv($fp, $headers);

		// Data rows
		foreach ($customerData as $data) {
			$row = [];
			foreach ($columns as $col) {
				switch ($col) {
					case 'name':
						$row[] = $data['name'];
						break;
					case 'email':
						$row[] = $data['email'];
						break;
					case 'total_purchases':
						$row[] = $data['total_purchases'];
						break;
					case 'total_revenue':
						$currency = $data['user']->spl_purchases->first() ?
							strtoupper(((array)$data['user']->spl_purchases->first()->meta('stripe_session'))['currency'] ?? 'EUR') :
							'EUR';
						$cents = $data['total_revenue'];
						$symbol = ($currency === 'EUR') ? '€' : $currency;
						$row[] = $symbol . ' ' . number_format($cents / 100, 2, ',', '');
						break;
					case 'first_purchase':
						$row[] = $data['first_purchase'] ? date('Y-m-d', $data['first_purchase']) : '';
						break;
					case 'last_activity':
						$days = $data['last_purchase'] ? floor((time() - $data['last_purchase']) / 86400) : '';
						$row[] = $days !== '' ? sprintf($this->_('%d days ago'), $days) : '';
						break;
					default:
						$row[] = '';
				}
			}
			fputcsv($fp, $row);
		}

		fclose($fp);
		exit;
	}

	/**
	 * AJAX endpoint to get customer purchases
	 */
	public function ___executeCustomerPurchases(): void {
		$input = $this->wire('input');
		$users = $this->wire('users');
		$pages = $this->wire('pages');

		$userId = (int)$input->get('user_id');
		if (!$userId) {
			echo '<p style="color:red;">' . $this->_('No user ID provided') . '</p>';
			exit;
		}

		$user = $users->get($userId);
		if (!$user || !$user->id) {
			echo '<p style="color:red;">' . $this->_('User not found') . '</p>';
			exit;
		}

		$purchasesData = [];
		$purchaseCount = 0;
		$renewalCount = 0;

		foreach ($user->spl_purchases as $item) {
			$purchaseDate = (int)$item->get('purchase_date');
			$session = (array)$item->meta('stripe_session');
			$lineItems = $session['line_items']['data'] ?? [];
			$productIds = (array)$item->meta('product_ids');
			$currency = strtoupper($session['currency'] ?? 'EUR');

			// Process initial purchase items
			foreach ($lineItems as $li) {
				$stripeProductId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
				if (is_array($stripeProductId)) $stripeProductId = $stripeProductId['id'] ?? '';

				$productName = $li['price']['product']['name']
					?? $li['description']
					?? $li['price']['nickname']
					?? 'Unknown';

				// Find mapped page
				$pageId = 0;
				foreach ($productIds as $pid) {
					$pid = (int)$pid;
					if ($pid === 0) continue;
					$p = $pages->get($pid);
					if ($p && $p->id && $p->hasField('stripe_product_id') && $p->stripe_product_id === $stripeProductId) {
						$pageId = $pid;
						$productName = $p->title;
						break;
					}
				}

				// Get subscription status
				$periodEndMap = (array)$item->meta('period_end_map');
				$status = '-';
				$periodEnd = '-';

				$scopeKey = $pageId ?: ('0#' . $stripeProductId);
				if (isset($periodEndMap[$scopeKey])) {
					$periodEndTs = (int)$periodEndMap[$scopeKey];
					$periodEnd = $periodEndTs ? date('Y-m-d', $periodEndTs) : '-';

					if (isset($periodEndMap[$scopeKey . '_canceled'])) {
						$status = 'Canceled';
					} elseif (isset($periodEndMap[$scopeKey . '_paused'])) {
						$status = 'Paused';
					} elseif ($periodEndTs < time()) {
						$status = 'Expired';
					} else {
						$status = 'Active';
					}
				}

				$amount = (int)($li['amount_total'] ?? 0);
				$quantity = (int)($li['quantity'] ?? 1);

				// Determine type: if status is set (not '-'), it's a subscription
				$type = ($status !== '-') ? 'Subscription' : 'Purchase';

				$purchasesData[] = [
					'date' => date('Y-m-d H:i', $purchaseDate),
					'product' => $productName,
					'type' => $type,
					'status' => $status,
					'period_end' => $periodEnd,
					'timestamp' => $purchaseDate,
					'amount' => $amount,
					'currency' => $currency,
					'quantity' => $quantity,
				];

				$purchaseCount++;
			}

			// Process renewals
			$renewals = (array)$item->meta('renewals');
			foreach ($renewals as $scopeKey => $scopeRenewals) {
				// Determine product name from scope key
				$productName = 'Unknown';
				if (is_numeric($scopeKey) && (int)$scopeKey > 0) {
					$p = $pages->get((int)$scopeKey);
					if ($p && $p->id) {
						$productName = $p->title;
					}
				} elseif (strpos($scopeKey, '0#') === 0) {
					$stripeProductId = substr($scopeKey, 2);
					// Try to find in line items
					foreach ($lineItems as $li) {
						$liStripeId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
						if (is_array($liStripeId)) $liStripeId = $liStripeId['id'] ?? '';
						if ($liStripeId === $stripeProductId) {
							$productName = $li['price']['product']['name']
								?? $li['description']
								?? $li['price']['nickname']
								?? 'Unknown';
							break;
						}
					}
				}

				foreach ((array)$scopeRenewals as $renewal) {
					$renewalDate = (int)($renewal['date'] ?? 0);
					$renewalAmount = (int)($renewal['amount'] ?? 0);

					// Get subscription status
					$periodEndMap = (array)$item->meta('period_end_map');
					$status = '-';
					$periodEnd = '-';

					if (isset($periodEndMap[$scopeKey])) {
						$periodEndTs = (int)$periodEndMap[$scopeKey];
						$periodEnd = $periodEndTs ? date('Y-m-d', $periodEndTs) : '-';

						if (isset($periodEndMap[$scopeKey . '_canceled'])) {
							$status = 'Canceled';
						} elseif (isset($periodEndMap[$scopeKey . '_paused'])) {
							$status = 'Paused';
						} elseif ($periodEndTs < time()) {
							$status = 'Expired';
						} else {
							$status = 'Active';
						}
					}

					$purchasesData[] = [
						'date' => $renewalDate ? date('Y-m-d H:i', $renewalDate) : '-',
						'product' => $productName,
						'type' => 'Renewal',
						'status' => $status,
						'period_end' => $periodEnd,
						'timestamp' => $renewalDate,
						'amount' => $renewalAmount,
						'currency' => $currency,
						'quantity' => 1,
					];

					$renewalCount++;
				}
			}
		}

		// Sort by timestamp descending
		usort($purchasesData, function($a, $b) {
			return $b['timestamp'] <=> $a['timestamp'];
		});

		if (empty($purchasesData)) {
			echo '<p>' . $this->_('No purchases found for this customer.') . '</p>';
			exit;
		}

		// Build title with counts
		$userName = $user->title ?: $user->name;
		$title = sprintf($this->_('Purchases - %s (%d Purchases, %d Renewals)'), $userName, $purchaseCount, $renewalCount);

		// Render table using ProcessWire MarkupAdminDataTable
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->setSortable(true);
		$table->setClass('uk-table-divider uk-table-small');

		// Header
		$table->headerRow([$this->_('Date'), $this->_('Product'), $this->_('Quantity'), $this->_('Amount'), $this->_('Type'), $this->_('Status'), $this->_('Period End')]);

		// Rows
		foreach ($purchasesData as $purchase) {
			$table->row([
				$purchase['date'],
				htmlspecialchars($purchase['product']),
				$purchase['quantity'],
				$this->formatPrice($purchase['amount'], $purchase['currency']),
				$purchase['type'],
				$purchase['status'],
				$purchase['period_end'],
			]);
		}

		// Output title + table wrapped in UIkit structure
		$out = '<h3 class="uk-modal-title">' . htmlspecialchars($title) . '</h3>';
		$out .= $table->render();

		echo $out;
		exit;
	}

	/**
	 * AJAX endpoint to get all purchases for a specific product
	 */
	public function ___executeProductPurchases(): void {
		$input = $this->wire('input');
		$users = $this->wire('users');
		$pages = $this->wire('pages');

		$productKey = $input->get->text('product_key');
		if (!$productKey) {
			echo '<p style="color:red;">' . $this->_('No product key provided') . '</p>';
			exit;
		}

		// Determine if it's a page ID or stripe product
		$isPageId = is_numeric($productKey);
		$pageId = $isPageId ? (int)$productKey : 0;
		$stripeProductId = $isPageId ? '' : (strpos($productKey, 'stripe:') === 0 ? substr($productKey, 7) : $productKey);

		// Get product name
		$productName = $this->_('Unknown Product');
		if ($pageId > 0) {
			$p = $pages->get($pageId);
			if ($p && $p->id) {
				$productName = $p->title;
			}
		}

		$purchasesData = [];
		$purchaseCount = 0;
		$renewalCount = 0;

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');
				$currency = strtoupper($session['currency'] ?? 'EUR');

				// Check if this purchase contains our product
				foreach ($lineItems as $li) {
					$liStripeId = $li['price']['product']['id'] ?? ($li['price']['product'] ?? '');
					if (is_array($liStripeId)) $liStripeId = $liStripeId['id'] ?? '';

					// Check if this line item matches our product
					$matches = false;
					if ($pageId > 0) {
						// Check if page ID is in product_ids and stripe ID matches
						foreach ($productIds as $pid) {
							$pid = (int)$pid;
							if ($pid === $pageId) {
								$p = $pages->get($pid);
								if ($p && $p->id && $p->hasField('stripe_product_id') && $p->stripe_product_id === $liStripeId) {
									$matches = true;
									$productName = $p->title;
									break;
								}
							}
						}
					} else {
						// Check stripe product ID
						if ($liStripeId === $stripeProductId) {
							$matches = true;
							$productName = $li['price']['product']['name']
								?? $li['description']
								?? $li['price']['nickname']
								?? 'Unknown';
						}
					}

					if ($matches) {
						$amount = (int)($li['amount_total'] ?? 0);
						$quantity = (int)($li['quantity'] ?? 1);

						// Get subscription status
						$periodEndMap = (array)$item->meta('period_end_map');
						$status = '-';
						$scopeKey = $pageId ?: ('0#' . $liStripeId);
						if (isset($periodEndMap[$scopeKey])) {
							$periodEndTs = (int)$periodEndMap[$scopeKey];
							if (isset($periodEndMap[$scopeKey . '_canceled'])) {
								$status = 'Canceled';
							} elseif (isset($periodEndMap[$scopeKey . '_paused'])) {
								$status = 'Paused';
							} elseif ($periodEndTs < time()) {
								$status = 'Expired';
							} else {
								$status = 'Active';
							}
						}

						// Determine type
						$type = ($status !== '-') ? 'Subscription' : 'Purchase';

						$purchasesData[] = [
							'customer' => $user->title ?: $user->name,
							'user_id' => $user->id,
							'date' => date('Y-m-d H:i', $purchaseDate),
							'amount' => $amount,
							'currency' => $currency,
							'type' => $type,
							'timestamp' => $purchaseDate,
						];

						$purchaseCount++;
					}
				}

				// Process renewals for this product
				$renewals = (array)$item->meta('renewals');
				$scopeKey = $pageId ?: ('0#' . $stripeProductId);

				if (isset($renewals[$scopeKey])) {
					foreach ((array)$renewals[$scopeKey] as $renewal) {
						$renewalDate = (int)($renewal['date'] ?? 0);
						$renewalAmount = (int)($renewal['amount'] ?? 0);

						$purchasesData[] = [
							'customer' => $user->title ?: $user->name,
							'user_id' => $user->id,
							'date' => $renewalDate ? date('Y-m-d H:i', $renewalDate) : '-',
							'amount' => $renewalAmount,
							'currency' => $currency,
							'type' => 'Renewal',
							'timestamp' => $renewalDate,
						];

						$renewalCount++;
					}
				}
			}
		}

		// Sort by timestamp descending
		usort($purchasesData, function($a, $b) {
			return $b['timestamp'] <=> $a['timestamp'];
		});

		if (empty($purchasesData)) {
			echo '<p>' . $this->_('No purchases found for this product.') . '</p>';
			exit;
		}

		// Build title with counts
		$title = sprintf($this->_('Purchases - %s (%d Purchases, %d Renewals)'), $productName, $purchaseCount, $renewalCount);

		// Render table using ProcessWire MarkupAdminDataTable
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->setSortable(true);
		$table->setClass('uk-table-divider uk-table-small');

		// Header
		$table->headerRow([$this->_('Customer'), $this->_('Date'), $this->_('Amount'), $this->_('Type')]);

		// Rows
		foreach ($purchasesData as $purchase) {
			$table->row([
				$this->renderCustomerName($purchase['customer'], $purchase['user_id']),
				$purchase['date'],
				$this->formatPrice($purchase['amount'], $purchase['currency']),
				$purchase['type'],
			]);
		}

		// Output title + table
		$out = '<h3 class="uk-modal-title">' . htmlspecialchars($title) . '</h3>';
		$out .= $table->render();

		echo $out;
		exit;
	}

	/**
	 * Render modal placeholder for customer purchases
	 */
	protected function renderCustomerProductsModal(): string {
		$baseUrl = $this->page->url;
		$modalId = 'modal_' . uniqid();
		$loading = $this->_('Loading...');
		$loadingPurchases = $this->_('Loading purchases...');
		$error = $this->_('Error');
		$errorLoading = $this->_('Error loading purchases');

		return <<<HTML
		<div id="{$modalId}" class="uk-modal-container" uk-modal>
			<div class="uk-modal-dialog uk-modal-body">
				<button class="uk-modal-close-default" type="button" uk-close></button>
				<div id="customer-purchases-content">
					<h3 class="uk-modal-title">{$loading}</h3>
					<p>{$loadingPurchases}</p>
				</div>
			</div>
		</div>
		<script>
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('show-customer-purchases')) {
				e.preventDefault();
				var userId = e.target.getAttribute('data-user-id');

				// Show modal
				UIkit.modal('#{$modalId}').show();

				// Set loading state
				document.getElementById('customer-purchases-content').innerHTML = '<h3 class="uk-modal-title">{$loading}</h3><p>{$loadingPurchases}</p>';

				// Fetch purchases via AJAX (returns title + table)
				fetch('{$baseUrl}customerPurchases/?user_id=' + userId)
					.then(function(response) { return response.text(); })
					.then(function(html) {
						document.getElementById('customer-purchases-content').innerHTML = html;

						// Initialize tablesorter on the dynamically loaded table
						var table = document.querySelector('#customer-purchases-content table');
						if (table && typeof jQuery !== 'undefined' && jQuery.fn.tablesorter) {
							jQuery(table).tablesorter();
						}
					})
					.catch(function(error) {
						document.getElementById('customer-purchases-content').innerHTML = '<h3 class="uk-modal-title">{$error}</h3><p style="color:red;">{$errorLoading}</p>';
					});
			}
		});
		</script>
		HTML;
	}

	/**
	 * Render modal for product purchases
	 */
	protected function renderProductPurchasesModal(): string {
		$baseUrl = $this->page->url;
		$modalId = 'modal_product_' . uniqid();
		$loading = $this->_('Loading...');
		$loadingPurchases = $this->_('Loading purchases...');
		$error = $this->_('Error');
		$errorLoading = $this->_('Error loading purchases');

		return <<<HTML
		<div id="{$modalId}" class="uk-modal-container" uk-modal>
			<div class="uk-modal-dialog uk-modal-body">
				<button class="uk-modal-close-default" type="button" uk-close></button>
				<div id="product-purchases-content">
					<h3 class="uk-modal-title">{$loading}</h3>
					<p>{$loadingPurchases}</p>
				</div>
			</div>
		</div>
		<script>
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('show-product-purchases')) {
				e.preventDefault();
				var productKey = e.target.getAttribute('data-product-key');

				// Show modal
				UIkit.modal('#{$modalId}').show();

				// Set loading state
				document.getElementById('product-purchases-content').innerHTML = '<h3 class="uk-modal-title">{$loading}</h3><p>{$loadingPurchases}</p>';

				// Fetch purchases via AJAX (returns title + table)
				fetch('{$baseUrl}productPurchases/?product_key=' + encodeURIComponent(productKey))
					.then(function(response) { return response.text(); })
					.then(function(html) {
						document.getElementById('product-purchases-content').innerHTML = html;

						// Initialize tablesorter on the dynamically loaded table
						var table = document.querySelector('#product-purchases-content table');
						if (table && typeof jQuery !== 'undefined' && jQuery.fn.tablesorter) {
							jQuery(table).tablesorter();
						}
					})
					.catch(function(error) {
						document.getElementById('product-purchases-content').innerHTML = '<h3 class="uk-modal-title">{$error}</h3><p style="color:red;">{$errorLoading}</p>';
					});
			}
		});
		</script>
		HTML;
	}
}
