<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Packer;


use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use LinkSoft\SocketClient\Message\RequestMessage;
use LinkSoft\SocketClient\Message\ResponseMessage;

class Packer implements PackerInterface
{
    /**
     * @var StdoutLoggerInterface|mixed
     */
    protected $logger;

    public function __construct() {
        $container = ApplicationContext::getContainer();
        $this->logger = $container->get(LoggerFactory::class)->get();
    }

    public function pack(RequestMessage $data): string
    {
        return $data->getContent();
    }

    public function unpack(string $data): ResponseMessage
    {
        return new ResponseMessage(0, $data);
    }
}