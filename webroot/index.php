<?php
/**
 * Some constants
 */
define('FRIENDS_CACHE_PREFIX_KEY', 'chat:friends:{:userId}');
define('ONLINE_CACHE_PREFIX_KEY', 'chat:online:{:userId}');
header('Content-Type: application/json; charset=utf-8');

/**
 * Load composer libraries
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * Load .env
 */
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

/**
 * Load configuration
 */
$redisHost = getenv('REDIS_HOST');
$redisPort = getenv('REDIS_PORT');
$allowedDomains = explode(',', getenv('ALLOWED_DOMAINS'));
$allowBlankReferrer = getenv('ALLOW_BLANK_REFERRER') || false;

/**
 * Check configuration
 */
if (empty($redisHost) || empty($redisPort) || empty($allowedDomains) || !is_array($allowedDomains)) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server error, invalid configuration.']);
    exit();
}

/**
 * CORS check
 */
$httpOrigin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
if ($allowBlankReferrer || in_array($httpOrigin, $allowedDomains)) {
    header('Access-Control-Allow-Credentials: true');
    if ($httpOrigin) {
        header("Access-Control-Allow-Origin: $httpOrigin");
    }
} else {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Not a valid origin.']);
    exit();
}

/**
 * No cookie, no session ID.
 */
if (empty($_COOKIE['app'])) {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Not a valid session.']);
    exit();
}

try {
    // Create a new Redis connection
    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);

    if (!$redis->isConnected()) {
        http_response_code(500);
        echo json_encode(['error' => true, 'message' => 'Server error, can\'t connect.']);
        exit();
    }

    // Set Redis serialization strategy
    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

    $sessionHash = $_COOKIE['app'];
    $session = $redis->get(join(':', ['PHPREDIS_SESSION', $sessionHash]));

    // Don't set cookie, let's keep it lean
    header_remove('Set-Cookie');

    if (!empty($session['default']['id'])) {
        $friendsList = $redis->get(str_replace('{:userId}', $session['default']['id'], FRIENDS_CACHE_PREFIX_KEY));
        if (!$friendsList) {
            // No friends list yet.
            http_response_code(200);
            echo json_encode([]);
            exit();
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Friends list not available.']);
        exit();
    }

    $friendUserIds = $friendsList->getUserIds();

    if (!empty($friendUserIds)) {
        $keys = array_map(function ($userId) {
            return str_replace('{:userId}', $userId, ONLINE_CACHE_PREFIX_KEY);
        }, $friendUserIds);

        // multi-get for faster operations
        $result = $redis->mget($keys);

        $onlineUsers = array_filter(
            array_combine(
                $friendUserIds,
                $result
            )
        );

        if ($onlineUsers) {
            $friendsList->setOnline($onlineUsers);
        }
    }
    http_response_code(200);
    echo json_encode($friendsList->toArray());
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Unknown exception. ' . $e->getMessage()]);
    exit();
}
