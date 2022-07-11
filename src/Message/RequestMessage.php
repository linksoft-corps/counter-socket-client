<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Message;

use Hyperf\Utils\Coordinator\Coordinator;

class RequestMessage
{
    /**
     * @var Coordinator
     */
    private $coordinator;

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $content;

    /**
     * @var int
     */
    private $time;

    public function __construct($requestId, $content)
    {
        $this->coordinator = new Coordinator();
        $this->time = time();
        $this->requestId = $requestId;
        $this->content = $content;
    }

    /**
     * 等待返回
     * @param int $timeout
     * @return bool
     */
    public function wait(int $timeout = 10): bool
    {
        $res = $this->coordinator->yield($timeout);
        $this->done();
        return $res;
    }

    /**
     * 设置请求已完成
     */
    public function done()
    {
        $this->coordinator->resume();
    }

    /**
     * 获取本次的请求 id
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * 获取请求内容
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * 获取请求发起时间
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }
}