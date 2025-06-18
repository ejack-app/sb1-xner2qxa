<?php
require_once __DIR__ . '/database_connection.php';

// --- Privacy Policy Functions ---

function get_published_privacy_policy() {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT content FROM privacy_policy WHERE is_published = TRUE ORDER BY published_at DESC, id DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function add_privacy_policy_version($content, $version) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO privacy_policy (content, version, created_at, updated_at) VALUES (:content, :version, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute(['content' => $content, 'version' => $version]);
    } catch (PDOException $e) {
        error_log('Add Privacy Policy Error: ' . $e->getMessage());
        return false;
    }
}

function publish_privacy_policy_version($id) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        // Unpublish all other versions
        $stmt_unpublish = $pdo->prepare("UPDATE privacy_policy SET is_published = FALSE, published_at = NULL WHERE id != :id");
        $stmt_unpublish->execute(['id' => $id]);

        // Publish the selected version
        $stmt_publish = $pdo->prepare("UPDATE privacy_policy SET is_published = TRUE, published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt_publish->execute(['id' => $id]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Publish Privacy Policy Error: ' . $e->getMessage());
        return false;
    }
}

function get_all_privacy_policy_versions() {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, version, content, is_published, published_at, created_at FROM privacy_policy ORDER BY id DESC");
    return $stmt->fetchAll();
}

// --- Terms & Conditions Functions ---

function get_published_terms() {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT content FROM terms_and_conditions WHERE is_published = TRUE ORDER BY published_at DESC, id DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function add_terms_version($content, $version) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO terms_and_conditions (content, version, created_at, updated_at) VALUES (:content, :version, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute(['content' => $content, 'version' => $version]);
    } catch (PDOException $e) {
        error_log('Add Terms Error: ' . $e->getMessage());
        return false;
    }
}

function publish_terms_version($id) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        // Unpublish all other versions
        $stmt_unpublish = $pdo->prepare("UPDATE terms_and_conditions SET is_published = FALSE, published_at = NULL WHERE id != :id");
        $stmt_unpublish->execute(['id' => $id]);

        // Publish the selected version
        $stmt_publish = $pdo->prepare("UPDATE terms_and_conditions SET is_published = TRUE, published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt_publish->execute(['id' => $id]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Publish Terms Error: ' . $e->getMessage());
        return false;
    }
}

function get_all_terms_versions() {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, version, content, is_published, published_at, created_at FROM terms_and_conditions ORDER BY id DESC");
    return $stmt->fetchAll();
}
?>
