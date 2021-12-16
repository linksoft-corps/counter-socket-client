<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace LinkSoft\SocketClient;

use LinkSoft\SocketClient\Callback\Callback;
use LinkSoft\SocketClient\Callback\CallbackInterface;
use LinkSoft\SocketClient\Listener\RegisterSocketListener;
use LinkSoft\SocketClient\Packer\Packer;
use LinkSoft\SocketClient\Packer\PackerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                CallbackInterface::class => Callback::class,
                PackerInterface::class => Packer::class
            ],
            'commands' => [
            ],
            'listeners' => [
                RegisterSocketListener::class
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'main config by this package', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/../publish/link_socket_client.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/link_socket_client.php', // 复制为这个路径下的该文件
                ]
            ]
        ];
    }
}
