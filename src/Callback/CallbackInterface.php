<?php
declare(strict_types=1);

namespace LinksoftCorps\CounterSocketClient\Callback;

use LinksoftCorps\CounterSocketClient\Message\ResponseMessage;

interface CallbackInterface
{
    /**
     * 回调事件处理器
     * @param ResponseMessage $message
     * @return mixed
     */
    public function handle(ResponseMessage $message);
}