<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php';

$filters = [
    'sku'   => $_GET['sku'] ?? null,
    'name'  => $_GET['name'] ?? null,
    'is_active' => $_GET['is_active'] ?? null, // '1' for active, '0' for inactive, '' for all
];
$filters = array_filter($filters, function($value) { return $value !== null && ($value !== '' || is_bool($value) || is_numeric($value)); });


$sort_by = $_GET['sort_by'] ?? 'i.name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Items per page
$offset = ($page - 1) * $limit;

$result = get_all_items($filters, $sort_by, $sort_order, $limit, $offset);
$items = $result['items'];
$total_items = $result['total_count'];
$total_pages = ceil($total_items / $limit);

function get_item_sort_link($column_name, $display_text, $current_sort_by, $current_sort_order, $current_filters) {
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
    <title>Admin - Inventory Items List</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1200px; margin: auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .action-bar { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .action-bar .add-new-btn { background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        .action-bar .add-new-btn:hover { background-color: #218838; }
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form input[type="text"], .filters-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filters-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters-form button:hover { background-color: #0056b3; }
        .filters-form .reset-button { background-color: #6c757d; }
        .filters-form .reset-button:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        th a { color: #333; text-decoration:none; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 3px;}
        .pagination strong { background-color: #007bff; color: white; border-color: #007bff; }
        .action-links a { margin-right: 8px; text-decoration: none; color: #007bff;}
        .action-links a:hover {text-decoration: underline;}
        .no-items { text-align:center; padding: 20px; color: #777; }
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
            <a href="admin_add_item.php">Add New Item</a> |
            <a href="admin_orders_list.php">Orders List</a> |
            <a href="admin_create_order.php">Create Order</a>
        </nav>
        <h1>Inventory Items List</h1>

        <div class="action-bar">
            <a href="admin_add_item.php" class="add-new-btn">Add New Item</a>
            <span><!-- Can add other global actions here --></span>
        </div>

        <form action="admin_items_list.php" method="GET" class="filters-form">
            <div>
                <label for="sku">SKU:</label>
                <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($filters['sku'] ?? ''); ?>">
            </div>
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($filters['name'] ?? ''); ?>">
            </div>
            <div>
               <label for="is_active">Status:</label>
               <select id="is_active" name="is_active">
                   <option value="" <?php echo (!isset($filters['is_active']) || $filters['is_active'] === '') ? 'selected' : ''; ?>>All</option>
                   <option value="1" <?php echo (isset($filters['is_active']) && $filters['is_active'] === '1') ? 'selected' : ''; ?>>Active</option>
                   <option value="0" <?php echo (isset($filters['is_active']) && $filters['is_active'] === '0') ? 'selected' : ''; ?>>Inactive</option>
               </select>
            </div>
            <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
            <div><label>&nbsp;</label><button type="submit">Filter</button></div>
            <div><label>&nbsp;</label><button type="button" class="reset-button" onclick="window.location.href='admin_items_list.php'">Reset</button></div>
        </form>

        <?php if (empty($items)): ?>
            <p class="no-items">No items found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><?php echo get_item_sort_link('i.sku', 'SKU', $sort_by, $sort_order, $filters); ?></th>
                        <th><?php echo get_item_sort_link('i.name', 'Name', $sort_by, $sort_order, $filters); ?></th>
                        <th>Description</th>
                        <th>Qty on Hand</th>
                        <th>Qty Allocated</th>
                        <th><?php echo get_item_sort_link('quantity_available', 'Qty Available', $sort_by, $sort_order, $filters); ?></th>
                        <th>Location</th>
                        <th><?php echo get_item_sort_link('i.is_active', 'Active', $sort_by, $sort_order, $filters); ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 50) . (strlen($item['description'] ?? '') > 50 ? '...' : '')); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity_on_hand']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity_allocated']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity_available']); ?></td>
                            <td><?php echo htmlspecialchars($item['location_in_warehouse'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['is_active'] ? 'Yes' : 'No'; ?></td>
                            <td class="action-links">
                                <a href="admin_edit_item.php?item_id=<?php echo $item['id']; ?>">Edit</a>
                                <a href="admin_adjust_stock.php?item_id=<?php echo $item['id']; ?>">Adjust Stock</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters,['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page - 1])); ?>">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?><strong><?php echo $i; ?></strong><?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($filters,['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $i])); ?>"><?php echo $i; ?></a>
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
