<?php
declare(strict_types=1);

namespace LinksoftCorps\CounterSocketClient\Listener;


use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\Event\MainCoroutineServerStart;
use LinksoftCorps\CounterSocketClient\Client;
use LinksoftCorps\CounterSocketClient\Event\LinkSocketInitSuccessEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class RegisterSocketListener implements ListenerInterface
{
    /**
     * @var LoggerFactory
     */
    private $logger;

    public function __construct(LoggerFactory $logger)
    {
        $this->logger = $logger->get();
    }

    public function listen(): array
    {
        return [MainCoroutineServerStart::class];
    }

    public function process(object $event)
    {
        $this->logger->debug('LinkSoft Server Starting...');
        Client::initInstance();
        $this->logger->debug('LinkSoft Server Start Success.');
    }
}