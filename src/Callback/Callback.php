<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Callback;

use Hyperf\Logger\LoggerFactory;
use LinkSoft\SocketClient\Message\ResponseMessage;
use Psr\Log\LoggerInterface;

class Callback implements CallbackInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get();
    }

    /**
     * 回调事件处理器
     * @param ResponseMessage $message
     * @return void
     */
    public function handle(ResponseMessage $message)
    {
        $this->logger->info('server push data: ' . json_encode($message->getContent()));
    }
}