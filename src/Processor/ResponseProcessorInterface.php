<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Processor;

use LinkSoft\SocketClient\Message\ResponseMessage;

interface ResponseProcessorInterface
{
    public function handle(ResponseMessage $stockResponse, ResponseMessage $newResponse): ResponseMessage;
}