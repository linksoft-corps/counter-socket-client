<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Util;

use LinkSoft\SocketClient\Constants\Code;
use LinkSoft\SocketClient\Message\ResponseMessage;

class ResponseMessageManager
{
    /**
     * 返回一个请求失败的响应
     * @param $requestId
     * @param $code
     * @param $msg
     * @return ResponseMessage
     */
    public static function newErrResponse($requestId, $code, $msg): ResponseMessage
    {
        $responseMessage = new ResponseMessage($requestId);
        $responseMessage->setErrCode($code);
        $responseMessage->setErrMsg($msg);
        $responseMessage->setIsEnd();
        return $responseMessage;
    }

    /**
     * 返回一个请求成功的响应
     * @param $requestId
     * @param $content
     * @param bool $isEnd 是否接收完成
     * @return ResponseMessage
     */
    public static function newSuccessResponse($requestId, $content, bool $isEnd = true): ResponseMessage
    {
        $responseMessage = new ResponseMessage($requestId);
        $responseMessage->setErrCode(Code::REQUEST_SUCCESS);
        $responseMessage->setContent($content);
        if ($isEnd) {
            $responseMessage->setIsEnd();
        }
        return $responseMessage;
    }
}