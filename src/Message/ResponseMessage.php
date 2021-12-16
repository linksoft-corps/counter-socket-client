<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Message;

class ResponseMessage
{

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $content;

    public function __construct($requestId, $content)
    {
        $this->requestId = $requestId;
        $this->content = $content;
    }

    /**
     * 获取本次响应对应的请求id
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * 获取响应内容
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}