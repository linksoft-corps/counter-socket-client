<?php

return [
    'client' => [
        'domain'          => env('LINK_SOCKET_DOMAIN', AF_INET),
        'type'            => env('LINK_SOCKET_TYPE', SOCK_STREAM),
        'protocol'        => env('LINK_SOCKET_PROTOCOL', STREAM_IPPROTO_IP),
        'protocol_config' => [
            'open_length_check'     => env('LINK_SOCKET_LENGTH_CHECK', true),
            'package_max_length'    => env('LINK_SOCKET_PACKAGE_MAX_LENGTH', 1024 * 1024 * 5),
            'package_length_type'   => env('LINK_SOCKET_PACKAGE_LENGTH_TYPE', 'N'),
            'package_length_offset' => env('LINK_SOCKET_PACKAGE_LENGTH_OFFSET', 0),
            'package_body_offset'   => env('LINK_SOCKET_PACKAGE_BODY_OFFSET', 4),
        ],
        'coroutines_timeout' => env('LINK_SOCKET_COROUTINES_TIMEOUT', 60)
    ],
    'server' => [
        'host' => env('LINK_SOCKET_SERVER_HOST', 'localhost'),
        'port' => env('LINK_SOCKET_SERVER_PORT', 8080)
    ]
];