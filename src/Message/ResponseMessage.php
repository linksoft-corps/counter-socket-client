<?php
declare(strict_types=1);

namespace LinksoftCorps\CounterSocketClient\Message;

class ResponseMessage
{

    /**
     * @var mixed
     */
    protected $requestId;

    /**
     * @var mixed
     */
    protected $content;

    public function __construct($requestId, $content)
    {
        $this->requestId = $requestId;
        $this->content = $content;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getContent()
    {
        return $this->content;
    }
}