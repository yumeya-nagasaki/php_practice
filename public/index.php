<?php
declare(strict_types=1);

use App\Application\TaskService;
use App\Infrastructure\PdoTaskRepository;

require dirname(__DIR__) . '/bootstrap.php';

function respond(mixed $body, int $status = 200): never
{
    $json = $status === 204 ? '' : json_encode(
        $body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    http_response_code($status);
    if ($status !== 204) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo $json;
    exit;
}

/** @return array<string, mixed> */
function readJsonObject(): array
{
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type !== 'application/json') {
        respond(['error' => 'Content-Type must be application/json'], 415);
    }
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        throw new RuntimeException('Cannot read request body');
    }
    $body = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
    if (!$body instanceof stdClass) {
        respond(['error' => 'JSON object required'], 400);
    }
    return get_object_vars($body);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

try {
    $service = new TaskService(new PdoTaskRepository(openDatabase()));
    if ($path === '/tasks') {
        if ($method === 'GET') {
            respond(array_map(fn($task): array => $task->toArray(), $service->all()));
        }
        if ($method === 'POST') {
            $body = readJsonObject();
            if (!isset($body['title']) || !is_string($body['title'])) {
                respond(['error' => 'title must be a string'], 422);
            }
            $task = $service->create($body['title']);
            header('Location: /tasks/' . $task->id);
            respond($task->toArray(), 201);
        }
        header('Allow: GET, POST');
        respond(['error' => 'Method not allowed'], 405);
    }

    if (is_string($path) && preg_match('#^/tasks/([1-9][0-9]*)$#D', $path, $matches)) {
        $id = filter_var($matches[1], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false) {
            respond(['error' => 'Task not found'], 404);
        }
        if ($method === 'GET') {
            $task = $service->find($id);
            if ($task === null) {
                respond(['error' => 'Task not found'], 404);
            }
            respond($task->toArray());
        }
        if ($method === 'PATCH') {
            $body = readJsonObject();
            if (!array_key_exists('done', $body) || !is_bool($body['done'])) {
                respond(['error' => 'done must be boolean'], 422);
            }
            $task = $service->setDone($id, $body['done']);
            if ($task === null) {
                respond(['error' => 'Task not found'], 404);
            }
            respond($task->toArray());
        }
        if ($method === 'DELETE') {
            if (!$service->delete($id)) {
                respond(['error' => 'Task not found'], 404);
            }
            respond(null, 204);
        }
        header('Allow: GET, PATCH, DELETE');
        respond(['error' => 'Method not allowed'], 405);
    }
    respond(['error' => 'Route not found'], 404);
} catch (JsonException $e) {
    respond(['error' => 'Invalid JSON'], 400);
} catch (InvalidArgumentException $e) {
    respond(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('[training-api] ' . $e->getMessage());
    respond(['error' => 'Internal server error'], 500);
}
