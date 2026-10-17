<?php
/* kci_db.php — single shared database connection per request.
 * Includes a read-through cache for data that rarely changes
 * (site_settings, ministries, events) so the same table is never
 * queried twice on one page load.
 */

if (getenv('DB_HOST')) {
    // ONLINE - Aiven on Render
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');
    $port = getenv('DB_PORT');
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, __DIR__ . '/ca.pem', NULL, NULL);
    mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    // LOCAL - Your WAMP
    $conn = mysqli_connect("localhost", "root", "", "kingdomite");
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/* ----------------------------------------------------------------------
   Query cache for rarely-changing public data.
   ---------------------------------------------------------------------- */
$kci_query_cache = array();

/* Run a SELECT query, caching the result so the same SQL is never
   executed twice in one request. Returns the result object or false. */
function kci_query_once(mysqli $conn, string $sql, array $params = array(), string $types = '') {
    global $kci_query_cache;
    $key = $sql . '|' . $types . '|' . implode(',', $params);
    if (isset($kci_query_cache[$key])) {
        return $kci_query_cache[$key];
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }
    if ($params !== array()) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = array();
    if ($res !== false) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $res->close();
    }
    $stmt->close();
    $kci_query_cache[$key] = $rows;
    return $rows;
}

/* Return all active ministries (id, name, slug, category, icon, image,
   description, meeting, status) — cached once per request. */
function kci_get_ministries(mysqli $conn): array {
    return kci_query_once($conn,
        "SELECT id, name, slug, category, icon, image, description, meeting, status FROM ministries WHERE status = 'active' ORDER BY id ASC");
}

/* Return upcoming + recent events (title, slug, date) — cached once per request. */
function kci_get_events(mysqli $conn): array {
    return kci_query_once($conn,
        "SELECT title, slug, date FROM events ORDER BY date ASC");
}

/* Return all site settings as key => value — cached once per request. */
function kci_get_settings(mysqli $conn): array {
    $rows = kci_query_once($conn,
        "SELECT setting_key, setting_value FROM site_settings");
    $out = array();
    foreach ($rows as $row) {
        $out[(string)$row['setting_key']] = (string)$row['setting_value'];
    }
    return $out;
}
?>