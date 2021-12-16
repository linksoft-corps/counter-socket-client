<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Callback;

use LinkSoft\SocketClient\Message\ResponseMessage;

interface CallbackInterface
{
    /**
     * 回调事件处理器
     * @param ResponseMessage $message
     * @return mixed
     */
    public function handle(ResponseMessage $message);
}