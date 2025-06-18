<?php
require_once __DIR__ . '/../src/auth_check.php'; // Handles session start and admin check
require_once __DIR__ . '/../src/order_functions.php'; // For get_all_orders

// Define available order statuses for filter dropdown
// These could be fetched from a helper function or defined globally if used elsewhere
$order_statuses_filter = ['PENDING', 'PROCESSING', 'AWAITING_PAYMENT', 'CONFIRMED', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'RETURNED', 'COMPLETED'];


// --- Filtering ---
$filters = [
    'order_number'   => $_GET['order_number'] ?? null,
    'customer_name'  => $_GET['customer_name'] ?? null, // Sender/Customer Name
    'recipient_name' => $_GET['recipient_name'] ?? null,
    'order_status'   => $_GET['order_status'] ?? null,
    'date_from'      => $_GET['date_from'] ?? null,
    'date_to'        => $_GET['date_to'] ?? null,
];
// Remove empty filters to avoid issues with query building if not needed
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

// --- Sorting ---
$sort_by = $_GET['sort_by'] ?? 'o.order_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// --- Pagination ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Orders per page
$offset = ($page - 1) * $limit;

$result = get_all_orders($filters, $sort_by, $sort_order, $limit, $offset);
$orders = $result['orders'];
$total_orders = $result['total_count'];
$total_pages = ceil($total_orders / $limit);

// Helper function to build sort links
function get_sort_link($column_name, $display_text, $current_sort_by, $current_sort_order, $current_filters) {
    $new_sort_order = ($current_sort_by === $column_name && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($current_sort_by === $column_name) {
        $arrow = $current_sort_order === 'ASC' ? ' &uarr;' : ' &darr;';
    }
    $query_params = array_merge($current_filters, ['sort_by' => $column_name, 'sort_order' => $new_sort_order, 'page' => 1]);
    return '<a href="?' . http_build_query($query_params) . '">' . htmlspecialchars($display_text) . $arrow . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Orders List</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1200px; margin: auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form input[type="text"], .filters-form input[type="date"], .filters-form select {
            padding: 8px; border: 1px solid #ddd; border-radius: 4px;
        }
        .filters-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters-form button:hover { background-color: #0056b3; }
        .filters-form .reset-button { background-color: #6c757d; }
        .filters-form .reset-button:hover { background-color: #5a6268; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        th a { color: #333; text-decoration: none; }
        th a:hover { text-decoration: underline; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 3px;}
        .pagination strong { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination a:hover { background-color: #eee; }
        .action-links a { margin-right: 8px; text-decoration: none; color: #007bff; }
        .action-links a:hover { text-decoration: underline; }
        .no-orders { text-align:center; padding: 20px; color: #777; }
        nav { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="logout.php" style="float: right;">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></strong> |
            <a href="admin_create_order.php">Create New Order</a> |
            <a href="admin_users.php">User Management</a> |
            <a href="admin_company_details.php">Company Details</a> |
            <a href="admin_privacy_policy.php">Privacy Policy</a> |
            <a href="admin_terms_conditions.php">Terms & Conditions</a>
        </nav>
        <h1>Orders List</h1>

        <form action="admin_orders_list.php" method="GET" class="filters-form">
            <div>
                <label for="order_number">Order #:</label>
                <input type="text" id="order_number" name="order_number" value="<?php echo htmlspecialchars($filters['order_number'] ?? ''); ?>">
            </div>
            <div>
                <label for="customer_name">Sender/Customer:</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($filters['customer_name'] ?? ''); ?>">
            </div>
            <div>
                <label for="recipient_name">Recipient:</label>
                <input type="text" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($filters['recipient_name'] ?? ''); ?>">
            </div>
            <div>
                <label for="order_status">Status:</label>
                <select id="order_status" name="order_status">
                    <option value="">All Statuses</option>
                    <?php foreach ($order_statuses_filter as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo (isset($filters['order_status']) && $filters['order_status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst(strtolower(str_replace('_', ' ', $status)))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date_from">Date From:</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
            </div>
            <div>
                <label for="date_to">Date To:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
            </div>
            <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
            <div>
               <label>&nbsp;</label> <!-- Spacer for alignment -->
               <button type="submit">Filter</button>
            </div>
            <div>
               <label>&nbsp;</label>
               <button type="button" class="reset-button" onclick="window.location.href='admin_orders_list.php'">Reset</button>
            </div>
        </form>

        <?php if (empty($orders)): ?>
            <p class="no-orders">No orders found matching your criteria.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><?php echo get_sort_link('o.order_number', 'Order #', $sort_by, $sort_order, $filters); ?></th>
                        <th><?php echo get_sort_link('o.order_date', 'Date', $sort_by, $sort_order, $filters); ?></th>
                        <th><?php echo get_sort_link('c.name', 'Sender (Customer)', $sort_by, $sort_order, $filters); ?></th>
                        <th><?php echo get_sort_link('o.recipient_name', 'Recipient', $sort_by, $sort_order, $filters); ?></th>
                        <th><?php echo get_sort_link('o.order_status', 'Status', $sort_by, $sort_order, $filters); ?></th>
                        <th>Payment Status</th>
                        <th><?php echo get_sort_link('o.total_order_value', 'Total', $sort_by, $sort_order, $filters); ?></th>
                        <th>Courier</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($order['order_date']))); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(strtolower(str_replace('_', ' ', $order['order_status'])))); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(strtolower(str_replace('_', ' ', $order['payment_status'])))); ?></td>
                            <td><?php echo htmlspecialchars(number_format($order['total_order_value'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($order['courier_company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['created_by_username'] ?? 'N/A'); ?></td>
                            <td class="action-links">
                                <a href="admin_order_details.php?order_id=<?php echo $order['order_id']; ?>">View</a>
                                <!-- More actions like Edit, Print Label etc. can be added later -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page - 1])); ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <strong><?php echo $i; ?></strong>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page + 1])); ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
