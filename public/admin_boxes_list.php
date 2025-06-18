<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/box_functions.php';

$page_title = "Admin - Box Definitions List";

$filters = [
    'name_search' => $_GET['name_search'] ?? null,
    'is_active'   => $_GET['is_active'] ?? null,
];
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$result = get_all_box_definitions($filters, $sort_by, $sort_order, $limit, $offset);
$boxes = $result['boxes'];
$total_boxes = $result['total_count'];
$total_pages = ceil($total_boxes / $limit);

function get_box_sort_link($column_name, $display_text, $current_sort_by, $current_sort_order, $current_filters) {
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
            transition: background-color 0.2s;
        }
        .action-bar .add-new-btn:hover { background-color: #218838; }
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form input[type="text"], .filters-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9rem; }
        .filters-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters-form button:hover { background-color: #0056b3; }
        .filters-form .reset-button { background-color: #6c757d; text-decoration:none; color:white; }
        .filters-form .reset-button:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        th a { color: #333; text-decoration:none; }
        td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
        .action-links a:hover {text-decoration:underline;}
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 3px;}
        .pagination strong { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination a:hover { background-color: #eee; }
        .no-records { text-align:center; padding: 20px; color: #777; background-color:#f8f9fa; border:1px solid #ddd; border-radius:5px;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size: 0.95rem; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
        .top-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Box Management</span>
        <div>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
        </div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="admin_add_box.php" class="add-new-btn">Add New Box Definition</a>
        </div>

        <form action="admin_boxes_list.php" method="GET" class="filters-form">
            <div>
                <label for="name_search">Search Name:</label>
                <input type="text" id="name_search" name="name_search" value="<?php echo htmlspecialchars($filters['name_search'] ?? ''); ?>">
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
            <div><label>&nbsp;</label><a href="admin_boxes_list.php" class="reset-button button-like">Reset</a></div>
        </form>

        <?php if (empty($boxes)): ?>
            <p class="no-records">No box definitions found. <a href="admin_add_box.php">Add one now!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><?php echo get_box_sort_link('name', 'Name', $sort_by, $sort_order, $filters); ?></th>
                        <th>Dimensions (LxWxH cm)</th>
                        <th>Max Weight (kg)</th>
                        <th>Empty Box Weight (kg)</th>
                        <th><?php echo get_box_sort_link('is_active', 'Active', $sort_by, $sort_order, $filters); ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($boxes as $box): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($box['name']); ?></td>
                            <td><?php echo htmlspecialchars($box['length_cm'] . 'x' . $box['width_cm'] . 'x' . $box['height_cm']); ?></td>
                            <td><?php echo htmlspecialchars($box['max_weight_kg'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($box['empty_box_weight_kg'] ?? 'N/A'); ?></td>
                            <td><?php echo $box['is_active'] ? 'Yes' : 'No'; ?></td>
                            <td class="action-links">
                                <a href="admin_edit_box.php?id=<?php echo $box['id']; ?>">Edit</a>
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
                    <?php if ($i == $page): ?><strong><?php echo $i; ?></strong><?php else: ?>
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
