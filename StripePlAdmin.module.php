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
		'session_id'        => ['label' => 'Session ID', 'type' => 'computed', 'compute' => 'computeSessionId'],
		'customer_id'       => ['label' => 'Customer ID', 'path' => ['stripe_session', 'customer', 'id']],
		'customer_name'     => ['label' => 'Customer Name', 'type' => 'computed', 'compute' => 'computeCustomerName'],
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
			'purchasesColumns' => ['user_email', 'purchase_date', 'product_titles', 'amount_total'],
			'productsColumns' => ['name', 'purchases', 'quantity', 'revenue', 'last_purchase'],
			'customersColumns' => ['name', 'email', 'total_purchases', 'total_revenue', 'first_purchase', 'last_activity'],
			'purchasesFilters' => ['user_email', 'user_name', 'purchase_date', 'product_titles', 'amount_total'],
			'productsFilters' => ['name', 'revenue', 'purchases', 'quantity', 'purchase_period'],
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
		'total_purchases' => ['label' => 'Purchases'],
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
			'total_purchases' => $this->_('Purchases'),
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
			'amount_total' => $this->_('Amount Total'),
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
			'purchase_period' => $this->_('Purchase Period'),
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
		$f->max = 1000;
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

		// Add modal for purchase details
		$out .= $this->renderPurchaseDetailsModal();

		// Add tab info modal
		$out .= $this->renderTabInfoModal('purchases');

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

		// Track summable values
		$sums = [];
		$currency = '';

		// Rows
		foreach ($purchases as $purchase) {
			$row = [];
			foreach ($columns as $col) {
				// Handle amount_total directly to avoid escaping issues
				if ($col === 'amount_total') {
					$session = (array)$purchase['item']->meta('stripe_session');
					$lineItems = $session['line_items']['data'] ?? [];
					$total = 0;
					if (!$currency) $currency = strtoupper($session['currency'] ?? 'EUR');
					foreach ($lineItems as $li) {
						$total += (int)($li['amount_total'] ?? 0);
						if (!$currency) $currency = strtoupper($li['currency'] ?? $session['currency'] ?? '');
					}

					// Track sum
					if (!isset($sums[$col])) $sums[$col] = 0;
					$sums[$col] += $total;

					$row[] = $total > 0 ? $this->formatPrice($total, $currency) : '';
				} else {
					$value = $this->getColumnValue($purchase['user'], $purchase['item'], $col);
					$row[] = $value;

					// Track sums for summable columns
					if ($this->isColumnSummable($col)) {
						$numericValue = strip_tags($value);
						$numericValue = (int)preg_replace('/[^0-9]/', '', $numericValue);
						if (!isset($sums[$col])) $sums[$col] = 0;
						$sums[$col] += $numericValue;
					}
				}
			}
			$table->row($row);
		}

		// Add summary row if we have summable data
		if (!empty($sums)) {
			$summaryRow = [];
			$firstCol = true;
			foreach ($columns as $col) {
				if ($firstCol) {
					$summaryRow[] = '<strong>' . $this->_('Total') . '</strong>';
					$firstCol = false;
				} elseif (isset($sums[$col])) {
					$value = $sums[$col];
					if ($this->getColumnSummaryType($col) === 'money') {
						$summaryRow[] = '<strong>' . $this->formatPrice($value, $currency) . '</strong>';
					} else {
						$summaryRow[] = '<strong>' . number_format($value, 0, ',', '.') . '</strong>';
					}
				} else {
					$summaryRow[] = '';
				}
			}
			$table->row($summaryRow);
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
	 * Check if a column is summable (money or count values)
	 */
	protected function isColumnSummable(string $column, string $context = 'purchases'): bool {
		$summableColumns = [
			// Money columns
			'amount_total', 'revenue', 'total_revenue',
			// Count/quantity columns
			'purchases', 'total_purchases', 'quantity', 'renewals', 'renewal_count', 'line_items_count',
		];

		return in_array($column, $summableColumns, true);
	}

	/**
	 * Get column type for summary (money or count)
	 */
	protected function getColumnSummaryType(string $column): string {
		$moneyColumns = ['amount_total', 'revenue', 'total_revenue'];
		return in_array($column, $moneyColumns, true) ? 'money' : 'count';
	}

	/**
	 * Check if column contains money values (needs Euro to Cent conversion)
	 */
	protected function isMoneyColumn(string $column): bool {
		$moneyColumns = ['amount_total', 'revenue', 'total_revenue'];
		return in_array($column, $moneyColumns, true);
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
	 * Compute session ID
	 */
	protected function computeSessionId(User $user, Page $item): string {
		$session = (array)$item->meta('stripe_session');
		$sessionId = $session['id'] ?? '';

		return $sessionId;
	}

	/**
	 * Compute product titles from IDs and stripe session line items
	 */
	protected function computeProductTitles(User $user, Page $item): string {
		$pages = $this->wire('pages');
		$productIds = (array)$item->meta('product_ids');
		$session = (array)$item->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];
		$sessionId = $session['id'] ?? '';

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

		$titleText = implode(', ', $titles);

		// Make the product titles clickable to show purchase details
		if (!empty($sessionId) && !empty($titleText)) {
			return "<a href='#' class='show-purchase-details' data-session-id='" . htmlspecialchars($sessionId, ENT_QUOTES) . "'>" . htmlspecialchars($titleText) . "</a>";
		}

		return $titleText;
	}

	/**
	 * Check if a purchase contains subscription products
	 */
	protected function hasSubscriptionProducts(Page $item): bool {
		$session = (array)$item->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];

		foreach ($lineItems as $li) {
			if (isset($li['price']['recurring']) && $li['price']['recurring']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compute subscription status
	 */
	protected function computeSubscriptionStatus(User $user, Page $item): string {
		// Only show status for purchases with subscription products
		if (!$this->hasSubscriptionProducts($item)) {
			return '–';
		}

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
		// Only show period end for purchases with subscription products
		if (!$this->hasSubscriptionProducts($item)) {
			return '–';
		}

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

			// Date filters
			'purchase_date' => ['type' => 'date_range', 'label' => $this->_('Purchase Date')],
			'purchase_period' => ['type' => 'date_range', 'label' => $this->_('Purchase Period')],
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

		// Process configured filters
		foreach ($configuredFilters as $filterName) {
			// Check if this filter has a matching column OR is a column-independent filter
			$hasMatchingColumn = in_array($filterName, $columns, true);
			$isIndependentFilter = in_array($filterName, ['purchase_period', 'purchase_date'], true); // Filters that don't require a column

			if (!$hasMatchingColumn && !$isIndependentFilter) {
				continue;
			}

			$config = $this->getFilterConfigForColumn($filterName, $context);
			if (!$config) continue;

			// Create unique key for this filter type
			$filterKey = $config['type'] . '_' . ($config['fields'][0] ?? $filterName);

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
			$f->columnWidth = 25;
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
					$f->columnWidth = 15;
					$f->value = $input->get($f->name);
					$form->add($f);

					// To date
					/** @var InputfieldText $f */
					$f = $modules->get('InputfieldText');
					$f->name = 'filter_' . strtolower(str_replace(' ', '_', $config['label'])) . '_to';
					$f->label = $config['label'] . ' ' . $this->_('To');
					$f->attr('type', 'date');
					$f->columnWidth = 15;
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
					$f->columnWidth = 25;

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
						// Convert Euro to Cent for money columns
						if ($this->isMoneyColumn($column)) {
							if ($min !== '' && $min !== null) $min = (float)$min * 100;
							if ($max !== '' && $max !== null) $max = (float)$max * 100;
						}
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
	 * Parse search query with AND/OR operators
	 *
	 * Supports:
	 * - AND/OR operators (e.g., "scale AND it AND up")
	 * - + as AND operator (e.g., "scale+it+up" or "scale + it + up")
	 * - Quoted phrases (e.g., "scale it up" to search for the exact phrase)
	 * - Spaces treated as OR when no explicit operators are used
	 *
	 * Returns array with 'terms' and 'operators'
	 */
	protected function parseSearchQuery(string $search): array {
		$originalSearch = trim($search);
		if (empty($originalSearch)) {
			return ['terms' => [], 'operators' => []];
		}

		$search = $originalSearch;

		// Extract quoted phrases first and replace with placeholders
		$quotedPhrases = [];
		$placeholder = '___QUOTED_PHRASE___';

		// Match both single and double quotes
		if (preg_match_all('/"([^"]+)"|\'([^\']+)\'/', $search, $matches)) {
			foreach ($matches[0] as $i => $fullMatch) {
				$phrase = $matches[1][$i] ?: $matches[2][$i];
				$quotedPhrases[] = $phrase;
				// Replace quoted phrase with placeholder
				$search = str_replace($fullMatch, $placeholder . $i . $placeholder, $search);
			}
		}

		// Replace + with AND (with or without spaces around +)
		$search = preg_replace('/\s*\+\s*/', ' AND ', $search);

		// Split by AND and OR operators (case-insensitive)
		$pattern = '/\s+(AND|OR)\s+/i';
		$parts = preg_split($pattern, $search, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$terms = [];
		$operators = [];

		for ($i = 0; $i < count($parts); $i++) {
			$part = trim($parts[$i]);
			if (empty($part)) continue;

			if (strtoupper($part) === 'AND' || strtoupper($part) === 'OR') {
				$operators[] = strtoupper($part);
			} else {
				// Check if this part contains a quoted phrase placeholder
				if (preg_match('/' . preg_quote($placeholder, '/') . '(\d+)' . preg_quote($placeholder, '/') . '/', $part, $match)) {
					$index = (int)$match[1];
					$terms[] = strtolower($quotedPhrases[$index]);
				} else {
					$terms[] = strtolower($part);
				}
			}
		}

		// If no operators found, treat spaces as OR (unless we only have quoted phrases)
		if (empty($operators) && strpos($originalSearch, ' ') !== false && empty($quotedPhrases)) {
			// Re-parse from original search
			$terms = array_map('trim', array_map('strtolower', explode(' ', $originalSearch)));
			$terms = array_filter($terms); // Remove empty strings
			$operators = array_fill(0, count($terms) - 1, 'OR');
		}

		return ['terms' => $terms, 'operators' => $operators];
	}

	/**
	 * Check if text matches search query with AND/OR logic
	 */
	protected function matchesSearchQuery(string $text, array $searchTerms): bool {
		if (empty($searchTerms)) return true;

		$terms = $searchTerms['terms'];
		$operators = $searchTerms['operators'];
		$textLower = strtolower($text);

		// Single term
		if (count($terms) === 1) {
			return strpos($textLower, $terms[0]) !== false;
		}

		// Multiple terms with operators
		$results = [];
		foreach ($terms as $term) {
			$results[] = strpos($textLower, $term) !== false;
		}

		// Evaluate with operators
		$finalResult = $results[0];
		for ($i = 0; $i < count($operators); $i++) {
			if ($operators[$i] === 'AND') {
				$finalResult = $finalResult && $results[$i + 1];
			} else { // OR
				$finalResult = $finalResult || $results[$i + 1];
			}
		}

		return $finalResult;
	}

	/**
	 * Check if multiple text fields match search query
	 */
	protected function matchesSearchQueryMultiple(array $texts, array $searchTerms): bool {
		if (empty($searchTerms)) return true;

		// Combine all texts into one for matching
		$combinedText = implode(' ', $texts);
		return $this->matchesSearchQuery($combinedText, $searchTerms);
	}

	/**
	 * Apply filters to purchases data
	 */
	protected function applyPurchasesFilters(array $purchases, string $search, array $filters): array {
		$filtered = [];

		foreach ($purchases as $purchase) {
			$user = $purchase['user'];
			$item = $purchase['item'];

			// Search filter with AND/OR support
			if ($search) {
				$searchTerms = $this->parseSearchQuery($search);
				$userEmail = $user->email;
				$userName = $user->title ?: $user->name;

				// Collect product names from line items
				$productNames = [];
				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				foreach ($lineItems as $li) {
					$productName = $li['price']['product']['name']
						?? $li['description']
						?? $li['price']['nickname']
						?? '';
					if ($productName) {
						$productNames[] = $productName;
					}
				}

				$matchFound = $this->matchesSearchQueryMultiple(array_merge([$userEmail, $userName], $productNames), $searchTerms);
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
						} elseif ($column === 'period_end') {
							// Get maximum period_end from period_end_map
							$map = (array)$item->meta('period_end_map');
							$maxPeriodEnd = 0;
							foreach ($map as $key => $val) {
								// Skip flag keys
								if (strpos($key, '_paused') !== false || strpos($key, '_canceled') !== false) {
									continue;
								}
								if (is_numeric($val) && (int)$val > $maxPeriodEnd) {
									$maxPeriodEnd = (int)$val;
								}
							}
							$value = $maxPeriodEnd > 0 ? $maxPeriodEnd : null;
						} elseif ($column === 'last_renewal') {
							// Get last renewal date
							$renewals = (array)$item->meta('renewals');
							$lastDate = 0;
							foreach ($renewals as $scopeRenewals) {
								foreach ((array)$scopeRenewals as $renewal) {
									$date = (int)($renewal['date'] ?? 0);
									if ($date > $lastDate) $lastDate = $date;
								}
							}
							$value = $lastDate > 0 ? $lastDate : null;
						}

						// For period_end and last_renewal: if value is null, exclude the purchase
						// (only show purchases that actually have this date field)
						if ($column === 'period_end' || $column === 'last_renewal') {
							if ($value === null) {
								$skip = true;
								break 2;
							}
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
			// Search filter with AND/OR support
			if ($search) {
				$searchTerms = $this->parseSearchQuery($search);
				$productName = $product['name'];

				$matchFound = $this->matchesSearchQuery($productName, $searchTerms);
				if (!$matchFound) continue;
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
			// Search filter with AND/OR support
			if ($search) {
				$searchTerms = $this->parseSearchQuery($search);
				$customerName = $customer['name'];
				$customerEmail = $customer['email'];

				$matchFound = $this->matchesSearchQueryMultiple([$customerName, $customerEmail], $searchTerms);
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

		$out .= "<div style='display:flex;gap:15px'>";
		$out .= "<a href='#' class='ui-link show-tab-info' data-tab='{$active}'><i class='fa fa-info-circle'></i> Info</a>";
		$out .= "<a href='{$configUrl}&collapse_info=1' class='ui-link'><i class='fa fa-cog'></i> Columns</a>";
		$out .= "</div>";

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

		// Get purchase period filter if set (support both 'purchase_period' and 'purchase_date')
		$periodFrom = $sanitizer->text($input->get('filter_purchase_period_from'))
			?: $sanitizer->text($input->get('filter_purchase_date_from'));
		$periodTo = $sanitizer->text($input->get('filter_purchase_period_to'))
			?: $sanitizer->text($input->get('filter_purchase_date_to'));
		$periodFromTs = $periodFrom ? strtotime($periodFrom) : null;
		$periodToTs = $periodTo ? strtotime($periodTo . ' 23:59:59') : null;

		// Aggregate data per product
		$productData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');

				// Apply purchase period filter early
				if ($periodFromTs && $purchaseDate < $periodFromTs) continue;
				if ($periodToTs && $purchaseDate > $periodToTs) continue;

				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');

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
			// Skip purchase_period and purchase_date - already handled in aggregation
			if ($column === 'purchase_period' || $column === 'purchase_date') continue;

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
						// Convert Euro to Cent for money columns
						if ($this->isMoneyColumn($column)) {
							if ($min !== '' && $min !== null) $min = (float)$min * 100;
							if ($max !== '' && $max !== null) $max = (float)$max * 100;
						}
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

			// Calculate sums from ALL products (not just paginated)
			$sums = [];
			$currency = '';
			foreach ($productData as $data) {
				if (!$currency && isset($data['currency'])) {
					$currency = $data['currency'];
				}

				// Track summable columns
				if (isset($data['revenue'])) {
					$sums['revenue'] = ($sums['revenue'] ?? 0) + $data['revenue'];
				}
				if (isset($data['count'])) {
					$sums['purchases'] = ($sums['purchases'] ?? 0) + $data['count'];
				}
				if (isset($data['quantity'])) {
					$sums['quantity'] = ($sums['quantity'] ?? 0) + $data['quantity'];
				}
				if (isset($data['renewals'])) {
					$sums['renewals'] = ($sums['renewals'] ?? 0) + $data['renewals'];
				}
			}

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
							$renewals = $data['renewals'] ?? 0;
							$productKey = htmlspecialchars($key);
							$display = $count;
							if ($renewals > 0) {
								$display .= " <small>(+{$renewals})</small>";
							}
							$row[] = "<a href='#' class='show-product-purchases' data-product-key='{$productKey}'>{$display}</a>";
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

			// Add summary row if we have summable data
			if (!empty($sums)) {
				$summaryRow = [];
				$firstCol = true;
				foreach ($columns as $col) {
					if ($firstCol) {
						$summaryRow[] = '<strong>' . $this->_('Total') . '</strong>';
						$firstCol = false;
					} elseif ($col === 'purchases' && isset($sums['purchases'])) {
						$summaryRow[] = '<strong>' . number_format($sums['purchases'], 0, ',', '.') . '</strong>';
					} elseif (isset($sums[$col])) {
						$value = $sums[$col];
						if ($this->getColumnSummaryType($col) === 'money') {
							$summaryRow[] = '<strong>' . $this->formatPrice($value, $currency) . '</strong>';
						} else {
							$summaryRow[] = '<strong>' . number_format($value, 0, ',', '.') . '</strong>';
						}
					} else {
						$summaryRow[] = '';
					}
				}
				$table->row($summaryRow);
			}

			$out .= "<div style='margin-top:-1px'>" . $table->render() . "</div>";
		}

		// Pagination and Export
		$out .= $this->renderPaginationRow($total, $perPage, $page, 'exportProducts');

		// Add modal placeholder
		$out .= $this->renderProductPurchasesModal();

		// Add tab info modal
		$out .= $this->renderTabInfoModal('products');

		return $out;
	}

	/**
	 * Export products to CSV
	 */
	public function ___executeExportProducts(): void {
		$input = $this->wire('input');
		$users = $this->wire('users');
		$pages = $this->wire('pages');
		$sanitizer = $this->wire('sanitizer');

		// Get purchase period filter if set (support both 'purchase_period' and 'purchase_date')
		$periodFrom = $sanitizer->text($input->get('filter_purchase_period_from'))
			?: $sanitizer->text($input->get('filter_purchase_date_from'));
		$periodTo = $sanitizer->text($input->get('filter_purchase_period_to'))
			?: $sanitizer->text($input->get('filter_purchase_date_to'));
		$periodFromTs = $periodFrom ? strtotime($periodFrom) : null;
		$periodToTs = $periodTo ? strtotime($periodTo . ' 23:59:59') : null;

		// Aggregate data (same as executeProducts)
		$productData = [];

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');

				// Apply purchase period filter early
				if ($periodFromTs && $purchaseDate < $periodFromTs) continue;
				if ($periodToTs && $purchaseDate > $periodToTs) continue;

				$session = (array)$item->meta('stripe_session');
				$lineItems = $session['line_items']['data'] ?? [];
				$productIds = (array)$item->meta('product_ids');

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
			$totalRenewals = 0;
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
						$totalRenewals++;
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
				'total_renewals' => $totalRenewals,
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
						// Convert Euro to Cent for money columns
						if ($this->isMoneyColumn($column)) {
							if ($min !== '' && $min !== null) $min = (float)$min * 100;
							if ($max !== '' && $max !== null) $max = (float)$max * 100;
						}
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

			// Calculate sums from ALL customers (not just paginated)
			$sums = [];
			$currency = 'EUR'; // Default currency
			foreach ($customerData as $data) {
				// Get currency from first purchase if available
				if (!$currency || $currency === 'EUR') {
					$firstPurchase = $data['user']->spl_purchases->first();
					if ($firstPurchase) {
						$currency = strtoupper(((array)$firstPurchase->meta('stripe_session'))['currency'] ?? 'EUR');
					}
				}

				// Track summable columns
				if (isset($data['total_revenue'])) {
					$sums['total_revenue'] = ($sums['total_revenue'] ?? 0) + $data['total_revenue'];
				}
				if (isset($data['total_purchases'])) {
					$sums['total_purchases'] = ($sums['total_purchases'] ?? 0) + $data['total_purchases'];
				}
			}

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
							$purchases = $data['total_purchases'];
							$renewals = $data['total_renewals'] ?? 0;
							$display = $purchases;
							if ($renewals > 0) {
								$display .= " <small>(+{$renewals})</small>";
							}
							$purchasesHtml = '<a href="#" class="show-customer-purchases" data-user-id="' . $data['user']->id . '" data-user-name="' . htmlspecialchars($data['name']) . '">' .
								$display . '</a>';
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

			// Add summary row if we have summable data
			if (!empty($sums)) {
				$summaryRow = [];
				$firstCol = true;
				foreach ($columns as $col) {
					if ($firstCol) {
						$summaryRow[] = '<strong>' . $this->_('Total') . '</strong>';
						$firstCol = false;
					} elseif (isset($sums[$col])) {
						$value = $sums[$col];
						if ($this->getColumnSummaryType($col) === 'money') {
							$summaryRow[] = '<strong>' . $this->formatPrice($value, $currency) . '</strong>';
						} else {
							$summaryRow[] = '<strong>' . number_format($value, 0, ',', '.') . '</strong>';
						}
					} else {
						$summaryRow[] = '';
					}
				}
				$table->row($summaryRow);
			}

			$out .= "<div style='margin-top:-1px'>" . $table->render() . "</div>";
		}

		// Pagination and Export
		$out .= $this->renderPaginationRow($total, $perPage, $page, 'exportCustomers');

		// Add modal placeholder
		$out .= $this->renderCustomerProductsModal();

		// Add tab info modal
		$out .= $this->renderTabInfoModal('customers');

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
	 * AJAX endpoint: Render purchase details for a specific session
	 */
	public function ___executePurchaseDetails(): void {
		$input = $this->wire('input');
		$users = $this->wire('users');

		$sessionId = $input->get->text('session_id');
		if (!$sessionId) {
			echo '<p style="color:red;">' . $this->_('No session ID provided') . '</p>';
			exit;
		}

		// Find the purchase with this session ID
		$foundPurchase = null;
		$foundUser = null;

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$session = (array)$item->meta('stripe_session');
				if (($session['id'] ?? '') === $sessionId) {
					$foundPurchase = $item;
					$foundUser = $user;
					break 2;
				}
			}
		}

		if (!$foundPurchase || !$foundUser) {
			echo '<p style="color:red;">' . $this->_('Purchase not found') . '</p>';
			exit;
		}

		// Render purchase details
		$session = (array)$foundPurchase->meta('stripe_session');
		$lineItems = $session['line_items']['data'] ?? [];
		$currency = strtoupper($session['currency'] ?? 'EUR');
		$renewals = (array)$foundPurchase->meta('renewals');

		echo '<h3 class="uk-modal-title">' . $this->_('Purchase Details') . '</h3>';

		// Line Items
		if (!empty($lineItems)) {
			echo '<table class="uk-table uk-table-small uk-table-divider">';
			echo '<thead><tr>';
			echo '<th>' . $this->_('Product') . '</th>';
			echo '<th>' . $this->_('Quantity') . '</th>';
			echo '<th>' . $this->_('Price') . '</th>';
			echo '<th>' . $this->_('Total') . '</th>';
			echo '</tr></thead><tbody>';

			$grandTotal = 0;
			foreach ($lineItems as $li) {
				$productName = $li['description'] ?? $li['price']['nickname'] ?? 'Unknown';
				if (isset($li['price']['product']['name'])) {
					$productName = $li['price']['product']['name'];
				}

				$quantity = (int)($li['quantity'] ?? 1);
				$unitAmount = (int)($li['price']['unit_amount'] ?? 0);
				$amountTotal = (int)($li['amount_total'] ?? 0);
				$grandTotal += $amountTotal;

				echo '<tr>';
				echo '<td>' . htmlspecialchars($productName) . '</td>';
				echo '<td>' . $quantity . '</td>';
				echo '<td>' . $this->formatPrice($unitAmount, $currency) . '</td>';
				echo '<td>' . $this->formatPrice($amountTotal, $currency) . '</td>';
				echo '</tr>';

				// Show renewals for this product (if it's a subscription)
				$isSubscription = isset($li['price']['recurring']) && $li['price']['recurring'];
				if ($isSubscription && !empty($renewals)) {
					// Get the Stripe product ID for matching
					$stripeProductId = '';
					if (isset($li['price']['product']['id'])) {
						$stripeProductId = $li['price']['product']['id'];
					} elseif (is_string($li['price']['product'])) {
						$stripeProductId = $li['price']['product'];
					}

					if ($stripeProductId) {
						// Look for renewals matching this product
						// Renewals can be stored with keys like "0#stripe_product_id" or as page IDs
						$scopeKey = '0#' . $stripeProductId;

						if (isset($renewals[$scopeKey])) {
							foreach ((array)$renewals[$scopeKey] as $renewal) {
								$renewalDate = (int)($renewal['date'] ?? 0);
								$renewalAmount = (int)($renewal['amount'] ?? 0);
								$grandTotal += $renewalAmount;

								$renewalDateStr = $renewalDate ? date('d.m.Y', $renewalDate) : '-';

								echo '<tr style="background-color: #f8f8f8;">';
								echo '<td style="padding-left: 2em; font-style: italic;">' .
									'Renewal am ' . htmlspecialchars($renewalDateStr) . '</td>';
								echo '<td>1</td>';
								echo '<td>' . $this->formatPrice($renewalAmount, $currency) . '</td>';
								echo '<td>' . $this->formatPrice($renewalAmount, $currency) . '</td>';
								echo '</tr>';
							}
						}
					}
				}
			}


			// Total row
			echo '<tr class="uk-text-bold">';
			echo '<td colspan="3">' . $this->_('Total') . '</td>';
			echo '<td>' . $this->formatPrice($grandTotal, $currency) . '</td>';
			echo '</tr>';

			echo '</tbody></table>';
		}

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
		$sanitizer = $this->wire('sanitizer');

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

		// Get purchase period filter if set (support both 'purchase_period' and 'purchase_date')
		$periodFrom = $sanitizer->text($input->get('filter_purchase_period_from'))
			?: $sanitizer->text($input->get('filter_purchase_date_from'));
		$periodTo = $sanitizer->text($input->get('filter_purchase_period_to'))
			?: $sanitizer->text($input->get('filter_purchase_date_to'));
		$periodFromTs = $periodFrom ? strtotime($periodFrom) : null;
		$periodToTs = $periodTo ? strtotime($periodTo . ' 23:59:59') : null;

		$purchasesData = [];
		$purchaseCount = 0;
		$renewalCount = 0;

		foreach ($users->find("spl_purchases.count>0") as $user) {
			foreach ($user->spl_purchases as $item) {
				$purchaseDate = (int)$item->get('purchase_date');

				// Apply purchase period filter early
				if ($periodFromTs && $purchaseDate < $periodFromTs) continue;
				if ($periodToTs && $purchaseDate > $periodToTs) continue;

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

						// Apply purchase period filter to renewals
						if ($periodFromTs && $renewalDate < $periodFromTs) continue;
						if ($periodToTs && $renewalDate > $periodToTs) continue;

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
	 * Render info modal for tabs
	 */
	protected function renderTabInfoModal(string $tab): string {
		$modalId = 'modal_tab_info_' . uniqid();

		// Tab-specific content
		$content = $this->getTabInfoContent($tab);

		return <<<HTML
		<div id="{$modalId}" class="uk-modal-container" uk-modal>
			<div class="uk-modal-dialog uk-modal-body" style="max-width:700px">
				<button class="uk-modal-close-default" type="button" uk-close></button>
				<div class="tab-info-content">
					{$content}
				</div>
			</div>
		</div>
		<script>
		document.addEventListener('click', function(e) {
			var target = e.target.closest('.show-tab-info');
			if (target) {
				e.preventDefault();
				var tab = target.getAttribute('data-tab');
				if (tab === '{$tab}') {
					UIkit.modal('#{$modalId}').show();
				}
			}
		});
		</script>
		HTML;
	}

	/**
	 * Get tab-specific info content
	 */
	protected function getTabInfoContent(string $tab): string {
		switch ($tab) {
			case 'purchases':
				$title = $this->_('Purchases Tab – Transaction View');
				$description = $this->_('Shows complete checkout sessions with all purchased products. Each row represents one transaction.');
				$totalExplanation = '<strong>' . $this->_('Total:') . '</strong> ' . $this->_('Sum of all complete transaction values (full cart amounts including all products purchased together).');
				$examples = [
					[
						'title' => $this->_('Find high-value transactions'),
						'steps' => $this->_('Use the "Amount Total" filter to find purchases above a certain value, e.g., >500€. Sort by amount to see largest orders first.')
					],
					[
						'title' => $this->_('Track subscription renewals'),
						'steps' => $this->_('Filter by "Period End" date range to see subscriptions expiring soon. Add "Subscription Status" column to monitor active subscriptions.')
					],
					[
						'title' => $this->_('Analyze product bundles'),
						'steps' => $this->_('Filter by a specific product to see which other products are frequently purchased together in the same transaction.')
					]
				];
				break;

			case 'products':
				$title = $this->_('Products Tab – Product Performance View');
				$description = $this->_('Shows aggregated metrics per product. Each row represents one product with its individual performance data.');
				$totalExplanation = '<strong>' . $this->_('Total:') . '</strong> ' . $this->_('Sum of revenue generated by each individual product (not transaction totals).');
				$examples = [
					[
						'title' => $this->_('Identify top-performing products'),
						'steps' => $this->_('Sort by "Revenue" column to see which products generate the most income. Check "Purchases" count to distinguish between high-price vs. high-volume products.')
					],
					[
						'title' => $this->_('Find underperforming products'),
						'steps' => $this->_('Use "Revenue" filter with a maximum value and "Purchase Period" to identify products that need marketing attention or price adjustments.')
					],
					[
						'title' => $this->_('Analyze subscription products'),
						'steps' => $this->_('Add "Renewals" column to see which products have recurring revenue. Compare initial purchases vs. renewal counts.')
					]
				];
				break;

			case 'customers':
				$title = $this->_('Customers Tab – Customer Overview');
				$description = $this->_('Shows aggregated customer data with lifetime value metrics. Each row represents one customer.');
				$totalExplanation = '<strong>' . $this->_('Total:') . '</strong> ' . $this->_('Sum of all customer lifetime values across all customers.');
				$examples = [
					[
						'title' => $this->_('Identify VIP customers'),
						'steps' => $this->_('Sort by "Total Revenue" to find your highest-value customers. Use "Total Purchases" to see repeat purchase behavior.')
					],
					[
						'title' => $this->_('Find inactive customers'),
						'steps' => $this->_('Use "Last Activity" filter to find customers who haven\'t purchased in 6+ months. Sort by "Total Revenue" to prioritize re-engagement.')
					],
					[
						'title' => $this->_('Segment by purchase behavior'),
						'steps' => $this->_('Filter by "Total Purchases" to find one-time buyers vs. loyal repeat customers. Analyze differences in product preferences.')
					]
				];
				break;

			default:
				return '';
		}

		// Build HTML
		$html = "<h2 class='uk-modal-title'>{$title}</h2>";
		$html .= "<p style='margin-bottom:20px'>{$description}</p>";
		$html .= "<p style='margin-bottom:20px;padding:10px;background:#f8f8f8;border-left:3px solid #0288d1'>{$totalExplanation}</p>";
		$html .= "<h3 style='margin-top:25px;margin-bottom:15px'>" . $this->_('Use Cases') . "</h3>";
		$html .= "<dl style='margin-left:0'>";

		foreach ($examples as $i => $example) {
			$num = $i + 1;
			$html .= "<dt style='font-weight:600;margin-top:" . ($i > 0 ? '15px' : '0') . ";margin-bottom:5px'>{$num}. {$example['title']}</dt>";
			$html .= "<dd style='margin-left:20px;color:#666'>{$example['steps']}</dd>";
		}

		$html .= "</dl>";

		return $html;
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
			var target = e.target.closest('.show-customer-purchases');
			if (target) {
				e.preventDefault();
				var userId = target.getAttribute('data-user-id');

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
			var target = e.target.closest('.show-product-purchases');
			if (target) {
				e.preventDefault();
				var productKey = target.getAttribute('data-product-key');

				// Show modal
				UIkit.modal('#{$modalId}').show();

				// Set loading state
				document.getElementById('product-purchases-content').innerHTML = '<h3 class="uk-modal-title">{$loading}</h3><p>{$loadingPurchases}</p>';

				// Collect current filter parameters from URL
				var urlParams = new URLSearchParams(window.location.search);
				var filterParams = '';

				// Add date filter parameters if they exist
				var dateFrom = urlParams.get('filter_purchase_period_from') || urlParams.get('filter_purchase_date_from');
				var dateTo = urlParams.get('filter_purchase_period_to') || urlParams.get('filter_purchase_date_to');

				if (dateFrom) {
					filterParams += '&filter_purchase_period_from=' + encodeURIComponent(dateFrom);
				}
				if (dateTo) {
					filterParams += '&filter_purchase_period_to=' + encodeURIComponent(dateTo);
				}

				// Fetch purchases via AJAX (returns title + table)
				fetch('{$baseUrl}productPurchases/?product_key=' + encodeURIComponent(productKey) + filterParams)
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

	/**
	 * Render modal for purchase details
	 */
	protected function renderPurchaseDetailsModal(): string {
		$baseUrl = $this->page->url;
		$modalId = 'modal_purchase_' . uniqid();
		$loading = $this->_('Loading...');
		$loadingDetails = $this->_('Loading purchase details...');
		$error = $this->_('Error');
		$errorLoading = $this->_('Error loading purchase details');

		return <<<HTML
		<div id="{$modalId}" class="uk-modal-container" uk-modal>
			<div class="uk-modal-dialog uk-modal-body">
				<button class="uk-modal-close-default" type="button" uk-close></button>
				<div id="purchase-details-content">
					<h3 class="uk-modal-title">{$loading}</h3>
					<p>{$loadingDetails}</p>
				</div>
			</div>
		</div>
		<script>
		document.addEventListener('click', function(e) {
			var target = e.target.closest('.show-purchase-details');
			if (target) {
				e.preventDefault();
				var sessionId = target.getAttribute('data-session-id');

				// Show modal
				UIkit.modal('#{$modalId}').show();

				// Set loading state
				document.getElementById('purchase-details-content').innerHTML = '<h3 class="uk-modal-title">{$loading}</h3><p>{$loadingDetails}</p>';

				// Fetch purchase details via AJAX
				fetch('{$baseUrl}purchaseDetails/?session_id=' + encodeURIComponent(sessionId))
					.then(function(response) { return response.text(); })
					.then(function(html) {
						document.getElementById('purchase-details-content').innerHTML = html;
					})
					.catch(function(error) {
						document.getElementById('purchase-details-content').innerHTML = '<h3 class="uk-modal-title">{$error}</h3><p style="color:red;">{$errorLoading}</p>';
					});
			}
		});
		</script>
		HTML;
	}
}
