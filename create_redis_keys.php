<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv('.');
$dotenv->load();

$redis = new Redis();
$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
echo 'Saving key "PHPREDIS_SESSSION:hash" to Redis...' . PHP_EOL;
$redis->set('PHPREDIS_SESSSION:hash', ['default' => ['id' => 1]]);
echo 'Saving key "chat:online:176733" to Redis...' . PHP_EOL;
$redis->set('chat:online:176733', true);
echo 'Saving key "chat:friends:1" to Redis...' . PHP_EOL;
$redis->set('chat:friends:1', new app\domain\chat\FriendsList([
    [
        'id' => 1,
        'name' => 'Project 1',
        'threads' => [
            [
                'online' => false,
                'other_party' => [
                    'user_id' => 176733,
                ]
            ]
        ]
    ],
    [
        'id' => 2,
        'name' => 'Project 2',
        'threads' => [
            [
                'online' => false,
                'other_party' => [
                    'user_id' => 176733,
                ]
            ]
        ]
    ]
]));

echo 'Redis keys created' . PHP_EOL;
