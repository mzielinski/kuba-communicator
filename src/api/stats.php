<?php

// ============================================
// Usage statistics API
// Records button clicks and exposes per-user summaries
// ============================================

require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/credentials.php';
require_once __DIR__ . '/../core/usage-stats.php';

header('Content-Type: application/json');
setCorsHeaders();
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

initializeSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
	$input = json_decode(file_get_contents('php://input'), true) ?? [];

	if ($action === 'record' || !$action) {
		$stats = recordUsageEvent($input);
		http_response_code(200);
		echo json_encode(['success' => true, 'stats' => summarizeUsageStats($stats)]);
		exit;
	}

	http_response_code(400);
	echo json_encode(['error' => 'Invalid action']);
	exit;
}

if ($method === 'GET') {
	if ($action === 'current-summary' || !$action) {
		requireAuth();
		$stats = summarizeUsageStats(readUsageStatsByDataDir($_SESSION['data_dir'] ?? ''));
		http_response_code(200);
		echo json_encode(['success' => true, 'stats' => $stats]);
		exit;
	}

	http_response_code(400);
	echo json_encode(['error' => 'Invalid action']);
	exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

?>

