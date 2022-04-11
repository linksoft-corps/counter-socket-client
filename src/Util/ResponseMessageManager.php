<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Util;

use LinkSoft\SocketClient\Constants\Code;
use LinkSoft\SocketClient\Message\ResponseMessage;

class ResponseMessageManager
{
    /**
     * 返回一个请求失败的响应
     * @param $code
     * @param $msg
     * @return ResponseMessage
     */
    public static function newErrResponse($requestId, $code, $msg): ResponseMessage
    {
        $responseMessage = new ResponseMessage($requestId);
        $responseMessage->setErrCode($code);
        $responseMessage->setErrMsg($msg);
        return $responseMessage;
    }

    /**
     * 返回一个请求成功的响应
     * @param $requestId
     * @param $content
     * @return ResponseMessage
     */
    public static function newSuccessResponse($requestId, $content): ResponseMessage
    {
        $responseMessage = new ResponseMessage($requestId);
        $responseMessage->setErrCode(Code::REQUEST_SUCCESS);
        $responseMessage->setContent($content);
        return $responseMessage;
    }
}