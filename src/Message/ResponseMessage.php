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

    /**
     * @var int
     */
    private $errCode;

    /**
     * @var string
     */
    private $errMsg;

    /**
     * @var bool
     */
    private $isEnd = false;

    public function __construct($requestId)
    {
        $this->requestId = $requestId;
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

    /**
     * 获取返回码
     * @return int
     */
    public function getErrCode(): int
    {
        return $this->errCode;
    }

    /**
     * 获取返回错误信息
     * @return string
     */
    public function getErrMsg(): string
    {
        return $this->errMsg;
    }

    /**
     * 是否接收完成
     * @return bool
     */
    public function getIsEnd(): bool
    {
        return $this->isEnd;
    }

    /**
     * 设置返回内容
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * 设置返回错误码
     * @param int $errCode
     */
    public function setErrCode(int $errCode)
    {
        $this->errCode = $errCode;
    }

    /**
     * 设置错误信息
     * @param string $errMsg
     */
    public function setErrMsg(string $errMsg)
    {
        $this->errMsg = $errMsg;
    }

    /**
     * 设置已经接收完成
     */
    public function setIsEnd()
    {
        $this->isEnd = true;
    }
}