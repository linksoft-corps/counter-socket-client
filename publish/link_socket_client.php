<?php

return [
    'client' => [
        'domain'             => env('LINK_SOCKET_DOMAIN', AF_INET),
        'type'               => env('LINK_SOCKET_TYPE', SOCK_STREAM),
        'protocol'           => env('LINK_SOCKET_PROTOCOL', STREAM_IPPROTO_IP),
        'protocol_config'    => [
            'open_length_check'     => env('LINK_SOCKET_LENGTH_CHECK', true),
            'package_max_length'    => env('LINK_SOCKET_PACKAGE_MAX_LENGTH', 1024 * 1024 * 5),
            'package_length_type'   => env('LINK_SOCKET_PACKAGE_LENGTH_TYPE', 'N'),
            'package_length_offset' => env('LINK_SOCKET_PACKAGE_LENGTH_OFFSET', 0),
            'package_body_offset'   => env('LINK_SOCKET_PACKAGE_BODY_OFFSET', 4),
        ],
        'coroutines_timeout' => env('LINK_SOCKET_COROUTINES_TIMEOUT', 60),
        // 这个参数用于控制没有新的收/发包进来时，下一次轮询开始前的休眠时间
        // 使用者可以适当调整这个参数，在cpu利用率和包嗅探灵敏度上做出合适的选择，但是不能过小，可能会导致cpu调度阻塞！
        'poll_sleep_time'    => env('LINK_SOCKET_POLL_SLEEP_TIME', 0.2)
    ],
    'server' => [
        'host' => env('LINK_SOCKET_SERVER_HOST', 'localhost'),
        'port' => env('LINK_SOCKET_SERVER_PORT', 8080)
    ]
];