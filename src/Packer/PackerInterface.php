<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Packer;

use LinkSoft\SocketClient\Message\RequestMessage;
use LinkSoft\SocketClient\Message\ResponseMessage;

interface PackerInterface
{
    /**
     * pack a RequestMessage to string
     * @param RequestMessage $data
     * @return string
     */
    public function pack(RequestMessage $data): string;

    /**
     * unpack string to ResponseMessage
     * @param string $data
     * @return ResponseMessage
     */
    public function unpack(string $data): ResponseMessage;
}