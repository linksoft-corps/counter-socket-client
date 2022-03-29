<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Util;

use Swoole\Coroutine;
use Swoole\Timer;

class TimeoutManager
{
    /**
     * 在指定毫秒后，唤醒指定协程
     * @param int $seconds
     * @return int
     */
    public function set(int $seconds): int
    {
        // 如果 $seconds 不标准，按照默认时间执行
        if ($seconds <= 0 || $seconds > 86400) {
            $seconds = 10;
        }
        // 转毫秒
        $seconds *= 1000;
        $cid = Coroutine::getCid();
        return Timer::after($seconds, function () use ($cid) {
            if (Coroutine::exists($cid)) {
                Coroutine::resume($cid);
            }
        });
    }

    /**
     * 协程已被唤醒，清理超时定时器
     * @param int $timerId
     */
    public function clear(int $timerId)
    {
        Timer::clear($timerId);
    }
}