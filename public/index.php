<?php
/**
 * AI Recruitment Agent-Entry point
 *
 */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

error_reporting(E_ALL);
ini_set('display_error', $_ENV['APP_DEBUG'] === 'true' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

$log = new Monolog\Logger('app');
$log->pushHandler(new Monolog\Handler\StreamHandler(
	__DIR__ . '/../logs/app.log',
	$_ENV['LOG_LEVEL'] === 'debug' ? Monolog\Logger::DEBUG : Monolog\Logger::INFO
));

$path = $_SERVER['PATH_INFO'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $path === '/webhook') {
    require __DIR__ . '/../src/webhook_handler.php';
    exit;
}

if ($method === 'POST' && $path === '/submit') {
    require __DIR__ . '/../src/form_handler.php';
    exit;
}

if ($method === 'GET' && $path === '/health') {
    header('Contet-Type: application/json');
    echo json_encode(['status' => 'ok', 'timestamp' => time()]);
    exit;
}

require __DIR__ . '/../src/views/application_form.php';

