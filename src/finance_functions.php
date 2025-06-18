<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// --- Service Type Functions ---
function create_service_type($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO service_types (service_code, name, description, unit, is_active)
            VALUES (:service_code, :name, :description, :unit, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':service_code' => $data['service_code'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':unit' => $data['unit'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Service Type code already exists.'; }
        else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); }
        error_log("Create Service Type Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}
function get_service_type_by_id($id){
    $pdo = get_db_connection(); $stmt = $pdo->prepare("SELECT * FROM service_types WHERE id = :id");
    $stmt->execute([':id' => $id]); return $stmt->fetch();
}
function get_all_service_types($active_only = false){
    $pdo = get_db_connection(); $sql = "SELECT * FROM service_types";
    if ($active_only) $sql .= " WHERE is_active = TRUE";
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function update_service_type($id, $data){
    $pdo = get_db_connection();
    $sql = "UPDATE service_types SET service_code=:service_code, name=:name, description=:description, unit=:unit, is_active=:is_active, updated_at=CURRENT_TIMESTAMP WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([
           ':id' => $id, ':service_code' => $data['service_code'], ':name' => $data['name'],
           ':description' => $data['description'] ?? null, ':unit' => $data['unit'] ?? null, ':is_active' => $data['is_active'] ?? true
        ]);
    } catch (PDOException $e) { if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Service Type code already exists for another entry.'; } else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); } error_log("Update Service Type Error: " . $e->getMessage()); return false; }
}

// --- Rate Card Functions ---
function create_rate_card($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO rate_cards (name, description, is_active, valid_from, valid_to)
            VALUES (:name, :description, :is_active, :valid_from, :valid_to)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':valid_from' => empty($data['valid_from']) ? null : $data['valid_from'],
            ':valid_to' => empty($data['valid_to']) ? null : $data['valid_to'],
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Rate Card name already exists.'; }
        else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); }
        error_log("Create Rate Card Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}
function get_rate_card_by_id($id){
    $pdo = get_db_connection(); $stmt = $pdo->prepare("SELECT * FROM rate_cards WHERE id = :id");
    $stmt->execute([':id' => $id]); return $stmt->fetch();
}
function get_all_rate_cards($active_only = false){
    $pdo = get_db_connection(); $sql = "SELECT * FROM rate_cards";
    if ($active_only) $sql .= " WHERE is_active = TRUE"; // Consider date validity too if needed here
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function update_rate_card($id, $data){
    $pdo = get_db_connection();
    $sql = "UPDATE rate_cards SET name=:name, description=:description, is_active=:is_active, valid_from=:valid_from, valid_to=:valid_to, updated_at=CURRENT_TIMESTAMP WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([
           ':id' => $id, ':name' => $data['name'], ':description' => $data['description'] ?? null,
           ':is_active' => $data['is_active'] ?? true,
           ':valid_from' => empty($data['valid_from']) ? null : $data['valid_from'],
           ':valid_to' => empty($data['valid_to']) ? null : $data['valid_to']
        ]);
    } catch (PDOException $e) { if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Rate Card name already exists for another entry.'; } else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); } error_log("Update Rate Card Error: " . $e->getMessage()); return false; }
}


// --- Rate Definition Functions ---
function create_rate_definition($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO rate_definitions (rate_card_id, service_type_id, rate_type, fixed_rate,
                                          min_weight_kg, max_weight_kg, destination_zone_pattern,
                                          rate_per_unit_or_tier, additional_kg_rate, currency_code, priority,
                                          description_notes, is_active)
            VALUES (:rate_card_id, :service_type_id, :rate_type, :fixed_rate,
                    :min_weight_kg, :max_weight_kg, :destination_zone_pattern,
                    :rate_per_unit_or_tier, :additional_kg_rate, :currency_code, :priority,
                    :description_notes, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':rate_card_id' => $data['rate_card_id'],
            ':service_type_id' => $data['service_type_id'],
            ':rate_type' => $data['rate_type'] ?? 'FIXED',
            ':fixed_rate' => (($data['rate_type'] ?? 'FIXED') === 'FIXED' && !empty($data['fixed_rate'])) ? (float)$data['fixed_rate'] : null,
            ':min_weight_kg' => empty($data['min_weight_kg']) ? null : (float)$data['min_weight_kg'],
            ':max_weight_kg' => empty($data['max_weight_kg']) ? null : (float)$data['max_weight_kg'],
            ':destination_zone_pattern' => empty($data['destination_zone_pattern']) ? null : $data['destination_zone_pattern'],
            ':rate_per_unit_or_tier' => empty($data['rate_per_unit_or_tier']) ? null : (float)$data['rate_per_unit_or_tier'],
            ':additional_kg_rate' => empty($data['additional_kg_rate']) ? null : (float)$data['additional_kg_rate'],
            ':currency_code' => $data['currency_code'] ?? 'SAR',
            ':priority' => $data['priority'] ?? 0,
            ':description_notes' => $data['description_notes'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'DB Error creating rate definition: ' . $e->getMessage();
        error_log("Create Rate Definition Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

function get_rate_definitions_for_card($rate_card_id, $active_only = false) {
    $pdo = get_db_connection();
    $sql = "SELECT rd.*, st.name as service_type_name, st.service_code
            FROM rate_definitions rd
            JOIN service_types st ON rd.service_type_id = st.id
            WHERE rd.rate_card_id = :rate_card_id";
    if ($active_only) {
        $sql .= " AND rd.is_active = TRUE AND st.is_active = TRUE";
    }
    $sql .= " ORDER BY rd.priority ASC, st.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rate_card_id' => $rate_card_id]);
    return $stmt->fetchAll();
}

function get_rate_definition_by_id($id){
    $pdo = get_db_connection();
    $sql = "SELECT rd.*, st.name as service_type_name, st.service_code
            FROM rate_definitions rd
            JOIN service_types st ON rd.service_type_id = st.id
            WHERE rd.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]); return $stmt->fetch();
}

function update_rate_definition($id, $data){
    $pdo = get_db_connection();
    $sql = "UPDATE rate_definitions SET
                rate_card_id = :rate_card_id, service_type_id = :service_type_id, rate_type = :rate_type,
                fixed_rate = :fixed_rate, min_weight_kg = :min_weight_kg, max_weight_kg = :max_weight_kg,
                destination_zone_pattern = :destination_zone_pattern, rate_per_unit_or_tier = :rate_per_unit_or_tier,
                additional_kg_rate = :additional_kg_rate, currency_code = :currency_code, priority = :priority,
                description_notes = :description_notes, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
       $params = [
           ':id' => $id,
           ':rate_card_id' => $data['rate_card_id'],
           ':service_type_id' => $data['service_type_id'],
           ':rate_type' => $data['rate_type'] ?? 'FIXED',
           ':fixed_rate' => (($data['rate_type'] ?? 'FIXED') === 'FIXED' && !empty($data['fixed_rate'])) ? (float)$data['fixed_rate'] : null,
           ':min_weight_kg' => empty($data['min_weight_kg']) ? null : (float)$data['min_weight_kg'],
           ':max_weight_kg' => empty($data['max_weight_kg']) ? null : (float)$data['max_weight_kg'],
           ':destination_zone_pattern' => empty($data['destination_zone_pattern']) ? null : $data['destination_zone_pattern'],
           ':rate_per_unit_or_tier' => empty($data['rate_per_unit_or_tier']) ? null : (float)$data['rate_per_unit_or_tier'],
           ':additional_kg_rate' => empty($data['additional_kg_rate']) ? null : (float)$data['additional_kg_rate'],
           ':currency_code' => $data['currency_code'] ?? 'SAR',
           ':priority' => $data['priority'] ?? 0,
           ':description_notes' => $data['description_notes'] ?? null,
           ':is_active' => $data['is_active'] ?? true,
       ];
       return $stmt->execute($params);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'DB Error updating rate definition: ' . $e->getMessage();
        error_log("Update Rate Definition Error: ID {$id}, " . $e->getMessage());
        return false;
    }
}

function delete_rate_definition($id){
    $pdo = get_db_connection(); $sql = "DELETE FROM rate_definitions WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try { return $stmt->execute([':id' => $id]); }
    catch (PDOException $e) { $_SESSION['error_message'] = 'DB Error deleting rate definition: ' . $e->getMessage(); return false; }
}

function calculate_charge($service_type_code, $rate_card_id, $params = []) {
    $pdo = get_db_connection();
    $stmt_st = $pdo->prepare("SELECT id FROM service_types WHERE service_code = :code AND is_active = TRUE");
    $stmt_st->execute([':code' => $service_type_code]);
    $service_type = $stmt_st->fetch();
    if (!$service_type) {
        error_log("Calculate Charge: Service type '{$service_type_code}' not found or inactive.");
        return null;
    }
    $service_type_id = $service_type['id'];

    $sql_rates = "SELECT * FROM rate_definitions
                  WHERE rate_card_id = :rate_card_id
                    AND service_type_id = :service_type_id
                    AND is_active = TRUE
                  ORDER BY priority ASC, id DESC";
    $stmt_rates = $pdo->prepare($sql_rates);
    $stmt_rates->execute([':rate_card_id' => $rate_card_id, ':service_type_id' => $service_type_id]);
    $applicable_rates = $stmt_rates->fetchAll();

    if (empty($applicable_rates)) {
        error_log("Calculate Charge: No active rate definitions for service '{$service_type_code}' in rate card ID {$rate_card_id}.");
        return null;
    }

    $weight_kg = $params['weight_kg'] ?? 0;
    $destination_zone = strtolower($params['destination_zone'] ?? '*');

    foreach ($applicable_rates as $rate_rule) {
        $zone_pattern = strtolower($rate_rule['destination_zone_pattern'] ?? '*');
        $zone_match = ($zone_pattern === '*' || $zone_pattern === $destination_zone);

        if (!$zone_match) continue; // If zone doesn't match, skip this rule

        if ($rate_rule['rate_type'] === 'FIXED') {
            return (float)$rate_rule['fixed_rate'];
        } elseif ($rate_rule['rate_type'] === 'WEIGHT_TIER') {
            $min_w = $rate_rule['min_weight_kg'];
            $max_w = $rate_rule['max_weight_kg'];
            $weight_match = false;

            if ($min_w !== null && $max_w !== null) {
                $weight_match = ($weight_kg >= $min_w && $weight_kg <= $max_w);
            } elseif ($min_w !== null && $max_w === null) {
                $weight_match = ($weight_kg >= $min_w);
            } elseif ($min_w === null && $max_w !== null) {
                $weight_match = ($weight_kg <= $max_w);
            } elseif ($min_w === null && $max_w === null) {
                $weight_match = true;
            }

            if ($weight_match) {
                $charge = (float)$rate_rule['rate_per_unit_or_tier'];
                if ($rate_rule['additional_kg_rate'] !== null && $weight_kg > ($min_w ?? 0)) {
                     $charge += ($weight_kg - ($min_w ?? 0)) * (float)$rate_rule['additional_kg_rate'];
                }
                return $charge;
            }
        }
        // Add more rate_type handlers: ZONE_WEIGHT_TIER, CBM_BASED etc.
    }

    error_log("Calculate Charge: No matching rate rule found for service '{$service_type_code}', card ID {$rate_card_id}, params: " . json_encode($params));
    return null;
}
?>
