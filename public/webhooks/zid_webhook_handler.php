<?php
// zid_webhook_handler.php
require_once __DIR__ . '/../../src/Integrations/ZidIntegration.php';
// require_once __DIR__ . '/../../config/integrations_config.php'; // Config loaded in ZidIntegration or here
require_once __DIR__ . '/../../src/item_functions.php'; // Required for direct calls if not handled in class

$zid_config = (require __DIR__ . '/../../config/integrations_config.php')['zid'];
// Zid might use a different mechanism for webhook verification, e.g., a static token in header or query param.
// $zid_webhook_token = $zid_config['webhook_token'] ?? null;
// $received_token = $_SERVER['HTTP_X_ZID_WEBHOOK_TOKEN'] ?? $_GET['token'] ?? null; // Example header
// if (!$zid_webhook_token || $received_token !== $zid_webhook_token) {
//     http_response_code(401);
//     error_log("Zid Webhook: Invalid or missing token.");
//     // Log actual and expected tokens for debugging if possible
//     exit('Unauthorized.');
// }
$payload_raw = file_get_contents('php://input');

$data = json_decode($payload_raw, true);
// Zid often uses 'event' or 'type' for event name, and 'data' or 'resource' for payload. Adjust based on actual Zid webhook structure.
// Assuming 'event_type' and 'payload' based on common patterns or previous notes.
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['event_type'])) {
    http_response_code(400);
    error_log("Zid Webhook: Invalid JSON payload or missing event_type. Payload: " . $payload_raw);
    exit('Invalid payload.');
}

error_log("Zid Webhook Received Event: " . $data['event_type'] . " - Data: " . json_encode($data['payload'] ?? []));

$zid_integration = new ZidIntegration(); // Assumes ZidIntegration can access item_functions or pass dependencies

switch ($data['event_type']) {
    case 'order.created': // Zid event names might differ, e.g., 'orders.created', 'order.create'
        // $zid_integration->processOrderCreatedWebhook($data['payload']); // Method to be added in ZidIntegration
        error_log("Zid Webhook: Placeholder for processOrderCreatedWebhook called. Data: " . json_encode($data['payload']));
        // Example direct processing:
        // $order_payload = $data['payload'] ?? [];
        // if (!empty($order_payload['products'])) { // Zid might use 'products' for items
        //     foreach($order_payload['products'] as $item_data) {
        //         $sku = $item_data['sku'] ?? ($item_data['product_sku'] ?? null); // Zid might have different SKU field names
        //         $quantity = $item_data['quantity'] ?? 0;
        //         if ($sku && $quantity > 0) {
        //             $local_item = get_item_by_sku($sku);
        //             if ($local_item) {
        //                 // Simplistic: allocate from first available location or default. Real logic needed.
        //                 error_log("Zid Webhook: Would attempt to allocate {$quantity} for SKU {$sku} (Item ID: {$local_item['id']})");
        //             } else {
        //                 error_log("Zid Webhook: SKU {$sku} from order not found locally.");
        //             }
        //         }
        //     }
        // }
        break;
    // Add more cases for Zid, e.g., order cancellation, inventory updates if Zid provides them
    default:
        error_log("Zid Webhook: Unhandled event type: " . $data['event_type']);
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook received.']);
exit;
?>
