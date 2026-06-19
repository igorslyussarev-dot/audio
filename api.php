<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to SQLite database dialogs.db
    $db = new PDO('sqlite:dialogs.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get mode from query string (default: azs)
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'azs';
    
    // Query joined dialogs and transcripts grouped into a JSON array
    $stmt = $db->prepare('
        SELECT d.id, d.mode, d.emp, d.time, d.topic, d.script, d.tone, d.lost_profit as lostProfit,
               (
                   SELECT json_group_array(json_object(\'speaker\', t.speaker, \'text\', t.text))
                   FROM transcripts t
                   WHERE t.dialog_id = d.id
                   ORDER BY t.sequence_order ASC
               ) as transcript
        FROM dialogs d
        WHERE d.mode = :mode
        ORDER BY d.id DESC
    ');
    
    $stmt->execute(['mode' => $mode]);
    $dialogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restructure/cast numeric properties and parse transcript JSON array
    foreach ($dialogs as &$d) {
        $d['id'] = (int)$d['id'];
        $d['lostProfit'] = (int)$d['lostProfit'];
        $d['transcript'] = json_decode($d['transcript'], true);
    }
    
    echo json_encode($dialogs, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
