<?php
// salla_webhook_handler.php
require_once __DIR__ . '/../../src/Integrations/SallaIntegration.php';
// require_once __DIR__ . '/../../config/integrations_config.php'; // For webhook secret - config is loaded in SallaIntegration or here directly
require_once __DIR__ . '/../../src/item_functions.php'; // Required for functions like get_item_by_sku, allocate_stock

$salla_config = (require __DIR__ . '/../../config/integrations_config.php')['salla'];
$webhook_secret = $salla_config['webhook_secret'] ?? null;

// 1. Verify Signature (Example - Salla uses HMAC SHA256)
// $request_signature = $_SERVER['HTTP_X_SALLA_SIGNATURE'] ?? '';
// $payload_raw = file_get_contents('php://input');
// if ($webhook_secret) {
//     $calculated_signature = hash_hmac('sha256', $payload_raw, $webhook_secret);
//     if (!hash_equals($calculated_signature, $request_signature)) {
//         http_response_code(401); // Unauthorized
//         error_log("Salla Webhook: Invalid signature.");
//         // Log actual and expected signatures for debugging if possible, but be careful with raw payload in prod logs
//         exit('Invalid signature.');
//     }
// } else {
//     error_log("Salla Webhook: Webhook secret not configured. Skipping signature validation. THIS IS INSECURE.");
//     // In a production environment, you should fail if the secret is not configured.
//     // http_response_code(500); exit('Webhook secret not configured.');
// }
$payload_raw = file_get_contents('php://input'); // Get payload

// 2. Decode Payload
$data = json_decode($payload_raw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['event'])) {
    http_response_code(400); // Bad Request
    error_log("Salla Webhook: Invalid JSON payload or missing event type. Payload: " . $payload_raw);
    exit('Invalid payload.');
}

error_log("Salla Webhook Received Event: " . $data['event'] . " - Data: " . json_encode($data['data'] ?? []));

$salla_integration = new SallaIntegration(); // Assumes SallaIntegration can access item_functions if needed, or pass dependencies

// 3. Route Event to Processor
switch ($data['event']) {
    case 'order.created':
        // $salla_integration->processOrderCreatedWebhook($data['data']); // Method to be added in SallaIntegration
        error_log("Salla Webhook: Placeholder for processOrderCreatedWebhook called. Data: " . json_encode($data['data']));
        // Example direct processing:
        // $order_payload = $data['data'] ?? [];
        // if (!empty($order_payload['items'])) {
        //     foreach($order_payload['items'] as $item_data) {
        //         $sku = $item_data['sku'] ?? null;
        //         $quantity = $item_data['quantity'] ?? 0;
        //         if ($sku && $quantity > 0) {
        //             $local_item = get_item_by_sku($sku);
        //             if ($local_item) {
        //                 // Simplistic: allocate from first available location or default. Real logic needed.
        //                 // This needs get_item_stock_levels_by_location and then picking one.
        //                 // allocate_stock($local_item['id'], $location_id_to_allocate_from, $quantity);
        //                 error_log("Salla Webhook: Would attempt to allocate {$quantity} for SKU {$sku} (Item ID: {$local_item['id']})");
        //             } else {
        //                 error_log("Salla Webhook: SKU {$sku} from order not found locally.");
        //             }
        //         }
        //     }
        // }
        break;
    case 'order.status.updated':
        // Example: if ($data['data']['status']['slug'] === 'canceled') { ... }
        error_log("Salla Webhook: Placeholder for order.status.updated (e.g. cancelled) called. Data: " . json_encode($data['data']));
        break;
    case 'product.created':
    case 'product.updated':
    case 'product.deleted':
        // $salla_integration->processProductUpdateWebhook($data['data']); // Method to be added
        error_log("Salla Webhook: Placeholder for product related event '{$data['event']}' called. Data: " . json_encode($data['data']));
        break;
    // Add more cases as needed based on Salla's webhook events
    default:
        error_log("Salla Webhook: Unhandled event type: " . $data['event']);
        break;
}

// 4. Respond to Salla
http_response_code(200); // Always respond with 200 OK quickly if event is acknowledged.
echo json_encode(['status' => 'success', 'message' => 'Webhook received.']);
exit;
?>
