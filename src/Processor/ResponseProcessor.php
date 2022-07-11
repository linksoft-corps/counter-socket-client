<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Processor;

use LinkSoft\SocketClient\Message\ResponseMessage;

class ResponseProcessor implements ResponseProcessorInterface
{
    /**
     * 处理可能存在的多包接收的情况
     * @param ResponseMessage $stockResponse
     * @param ResponseMessage $newResponse
     * @return ResponseMessage
     */
    public function handle(ResponseMessage $stockResponse, ResponseMessage $newResponse): ResponseMessage
    {
        $stockResponse->setContent($newResponse->getContent());
        $stockResponse->setErrCode($newResponse->getErrCode());
        $stockResponse->setErrMsg($newResponse->getErrMsg());
        if ($newResponse->getIsEnd()) {
            $stockResponse->setIsEnd();
        }
        return $stockResponse;
    }
}