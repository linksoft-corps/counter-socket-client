<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Message;

use Swoole\Coroutine;

class RequestMessage
{
    /**
     * @var int
     */
    protected $cid;

    /**
     * @var mixed
     */
    protected $requestId;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var int
     */
    protected $time;

    public function __construct($requestId, $content)
    {
        $this->cid = Coroutine::getCid();
        $this->time = time();
        $this->requestId = $requestId;
        $this->content = $content;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getTime(): int
    {
        return $this->time;
    }
}