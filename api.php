<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to SQLite database dialogs.db
    $db = new PDO('sqlite:dialogs.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get mode from query string (default: azs)
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'azs';
    
    // Query joined dialogs and transcripts
    $stmt = $db->prepare('
        SELECT d.id, d.mode, d.emp, d.time, d.topic, d.script, d.tone, d.lost_profit as lostProfit, t.content as transcript
        FROM dialogs d
        INNER JOIN transcripts t ON d.id = t.dialog_id
        WHERE d.mode = :mode
        ORDER BY d.id DESC
    ');
    
    $stmt->execute(['mode' => $mode]);
    $dialogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restructure/cast numeric properties
    foreach ($dialogs as &$d) {
        $d['id'] = (int)$d['id'];
        $d['lostProfit'] = (int)$d['lostProfit'];
    }
    
    echo json_encode($dialogs, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
