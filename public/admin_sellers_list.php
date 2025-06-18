<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/seller_functions.php';

$page_title = "Admin - Sellers List";

$filters = [
    'search_term'  => $_GET['search_term'] ?? null,
    'is_active'    => $_GET['is_active'] ?? null,
];
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

$sort_by = $_GET['sort_by'] ?? 's.seller_name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$result = get_all_sellers($filters, $sort_by, $sort_order, $limit, $offset);
$sellers = $result['sellers'];
$total_sellers = $result['total_count'];
$total_pages = ceil($total_sellers / $limit);

function get_seller_sort_link($column_name, $display_text, $current_sort_by, $current_sort_order, $current_filters) {
    $new_sort_order = ($current_sort_by === $column_name && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($current_sort_by === $column_name) {
        $arrow = $current_sort_order === 'ASC' ? ' &uarr;' : ' &darr;';
    }
    $query_params = array_merge($current_filters, ['sort_by' => $column_name, 'sort_order' => $new_sort_order, 'page' => 1]);
    return '<a href="?' . http_build_query($query_params) . '">' . htmlspecialchars($display_text) . $arrow . '</a>';
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        .action-bar { margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; }
        .action-bar .add-new-btn {
            background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size: 0.95rem;
        }
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form input[type="text"], .filters-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9rem; }
        .filters-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        th a { color: #333; text-decoration:none; }
        .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 3px;}
        .pagination strong { background-color: #007bff; color: white; border-color: #007bff; }
        .no-records { text-align:center; padding: 20px; color: #777;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724;}
        .message.info { background-color: #d1ecf1; color: #0c5460; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Seller Management</span>
        <div><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>
        <div class="action-bar"><a href="admin_add_seller.php" class="add-new-btn">Add New Seller</a></div>

        <form action="admin_sellers_list.php" method="GET" class="filters-form">
            <div><label for="search_term">Search:</label><input type="text" id="search_term" name="search_term" value="<?php echo htmlspecialchars($filters['search_term'] ?? ''); ?>" placeholder="Name, Email, Contact"></div>
            <div><label for="is_active">Status:</label><select id="is_active" name="is_active">
                    <option value="" <?php echo (!isset($filters['is_active']) || $filters['is_active'] === '') ? 'selected' : ''; ?>>All</option>
                    <option value="1" <?php echo (isset($filters['is_active']) && $filters['is_active'] === '1') ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo (isset($filters['is_active']) && $filters['is_active'] === '0') ? 'selected' : ''; ?>>Inactive</option>
            </select></div>
            <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
            <div><label>&nbsp;</label><button type="submit">Filter</button> <a href="admin_sellers_list.php" class="reset-button button-like" style="text-decoration:none;padding:8px 15px;">Reset</a></div>
        </form>

        <?php if (empty($sellers)): ?>
            <p class="no-records">No sellers found.</p>
        <?php else: ?>
            <table><thead><tr>
                <th><?php echo get_seller_sort_link('s.seller_name', 'Seller Name', $sort_by, $sort_order, $filters); ?></th>
                <th>Contact Person</th>
                <th><?php echo get_seller_sort_link('s.email', 'Email', $sort_by, $sort_order, $filters); ?></th>
                <th>Phone</th>
                <th><?php echo get_seller_sort_link('linked_username', 'Linked User', $sort_by, $sort_order, $filters); ?></th>
                <th><?php echo get_seller_sort_link('s.is_active', 'Active', $sort_by, $sort_order, $filters); ?></th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
                <?php foreach ($sellers as $seller): ?>
                <tr>
                    <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($seller['contact_person'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($seller['email']); ?></td>
                    <td><?php echo htmlspecialchars($seller['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($seller['linked_username'] ?? 'N/A'); ?></td>
                    <td><?php echo $seller['is_active'] ? 'Yes' : 'No'; ?></td>
                    <td class="action-links"><a href="admin_edit_seller.php?id=<?php echo $seller['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page - 1])); ?>">Previous</a><?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?><?php if ($i == $page): ?><strong><?php echo $i; ?></strong><?php else: ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?><?php endfor; ?>
                <?php if ($page < $total_pages): ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page + 1])); ?>">Next</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
