<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Message;

use Swoole\Coroutine;

class RequestMessage
{
    /**
     * @var int
     */
    private $cid;

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
        $this->cid = Coroutine::getCid();
        $this->time = time();
        $this->requestId = $requestId;
        $this->content = $content;
    }

    /**
     * 获取本请求所属的协程 id
     * @return int
     */
    public function getCid(): int
    {
        return $this->cid;
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