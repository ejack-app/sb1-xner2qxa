<?php
require_once __DIR__ . '/database_connection.php';

function get_company_details() {
    $pdo = get_db_connection();
    // Assuming there's only one row or we fetch the first one.
    // In a multi-tenant app, this would need a company_id.
    $stmt = $pdo->query('SELECT * FROM company_details ORDER BY id LIMIT 1');
    return $stmt->fetch();
}

function update_company_details($data) {
    $pdo = get_db_connection();
    // Ensure all expected keys exist to prevent undefined index errors
    $fields = [
        'company_name' => $data['company_name'] ?? null,
        'address' => $data['address'] ?? null,
        'phone' => $data['phone'] ?? null,
        'email' => $data['email'] ?? null,
        'website' => $data['website'] ?? null,
        'logo_url' => $data['logo_url'] ?? null,
        'registration_number' => $data['registration_number'] ?? null,
        'vat_number' => $data['vat_number'] ?? null,
        'default_courier_company_id' => isset($data['default_courier_company_id']) && $data['default_courier_company_id'] !== '' ? (int)$data['default_courier_company_id'] : null,
    ];

    // Assuming ID 1 for simplicity, or that the table has only one row.
    // A more robust solution would be to fetch the existing ID or handle row creation if it doesn't exist.
    $sql_update = "UPDATE company_details SET
                company_name = :company_name,
                address = :address,
                phone = :phone,
                email = :email,
                website = :website,
                logo_url = :logo_url,
                registration_number = :registration_number,
                vat_number = :vat_number,
                default_courier_company_id = :default_courier_company_id,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";

    // Check if a record exists. If not, insert one.
    $stmt_check = $pdo->query('SELECT id FROM company_details ORDER BY id LIMIT 1');
    $existing_company = $stmt_check->fetch();

    if ($existing_company) {
        $fields['id'] = $existing_company['id'];
        $stmt = $pdo->prepare($sql_update);
    } else {
        // Insert if no record exists
        $sql_insert = "INSERT INTO company_details (company_name, address, phone, email, website, logo_url, registration_number, vat_number, default_courier_company_id, created_at, updated_at)
                       VALUES (:company_name, :address, :phone, :email, :website, :logo_url, :registration_number, :vat_number, :default_courier_company_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $pdo->prepare($sql_insert);
    }

    try {
        return $stmt->execute($fields);
    } catch (PDOException $e) {
        error_log('Update Company Details Error: ' . $e->getMessage());
        return false;
    }
}

function get_all_courier_companies() {
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT id, name FROM courier_companies WHERE is_active = TRUE ORDER BY name');
    return $stmt->fetchAll();
}
?>
