<?php
declare(strict_types=1);

namespace LinksoftCorps\CounterSocketClient\Packer;

use LinksoftCorps\CounterSocketClient\Message\RequestMessage;
use LinksoftCorps\CounterSocketClient\Message\ResponseMessage;

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