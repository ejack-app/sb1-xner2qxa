<?php
require_once __DIR__ . '/database_connection.php';

function get_customer_by_email_or_phone($email, $phone) {
    $pdo = get_db_connection();
    $sql = "SELECT * FROM customers WHERE email = :email OR phone = :phone LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email, ':phone' => $phone]);
    return $stmt->fetch();
}

function create_customer($name, $email, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country_code) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO customers (name, email, phone, address_line1, address_line2, city, state, postal_code, country_code, created_at, updated_at)
            VALUES (:name, :email, :phone, :address_line1, :address_line2, :city, :state, :postal_code, :country_code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address_line1' => $address_line1,
            ':address_line2' => $address_line2,
            ':city' => $city,
            ':state' => $state,
            ':postal_code' => $postal_code,
            ':country_code' => $country_code,
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create Customer Error: " . $e->getMessage());
        // Check for duplicate email (if email has a UNIQUE constraint and is not null)
        if ($e->getCode() == 23000 && strpos(strtolower($e->getMessage()), 'duplicate entry') !== false && strpos(strtolower($e->getMessage()), 'email') !== false) {
            throw new Exception("A customer with this email already exists.");
        }
        // Add more specific error handling if needed
        throw new Exception("Could not create customer profile: " . $e->getMessage());
    }
}

// Helper function to either get an existing customer or create a new one.
// $customer_data should be an associative array with keys like 'name', 'email', 'phone', etc.
function get_or_create_customer($customer_data) {
    $pdo = get_db_connection();

    // Try to find customer by email if provided and not empty
    if (!empty($customer_data['email'])) {
        $stmt_find = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
        $stmt_find->execute([':email' => $customer_data['email']]);
        $existing_customer = $stmt_find->fetch();
        if ($existing_customer) {
            return $existing_customer['id'];
        }
    }

    // Try to find customer by phone if provided and not empty, and email didn't match or wasn't provided
    if (!empty($customer_data['phone'])) {
        $stmt_find_phone = $pdo->prepare("SELECT id FROM customers WHERE phone = :phone LIMIT 1");
        $stmt_find_phone->execute([':phone' => $customer_data['phone']]);
        $existing_customer_phone = $stmt_find_phone->fetch();
        if ($existing_customer_phone) {
            return $existing_customer_phone['id'];
        }
    }

    // If not found, create a new one
    return create_customer(
        $customer_data['name'],
        $customer_data['email'] ?? null, // Email can be null
        $customer_data['phone'],
        $customer_data['address_line1'],
        $customer_data['address_line2'] ?? null,
        $customer_data['city'],
        $customer_data['state'] ?? null,
        $customer_data['postal_code'],
        $customer_data['country_code']
    );
}

function get_all_customers_for_select() {
   $pdo = get_db_connection();
   $stmt = $pdo->query("SELECT id, name, email, phone FROM customers ORDER BY name ASC");
   return $stmt->fetchAll();
}

?>
