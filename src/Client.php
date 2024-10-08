<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use LinkSoft\SocketClient\Callback\CallbackInterface;
use LinkSoft\SocketClient\Constants\Code;
use LinkSoft\SocketClient\Event\LinkSocketInitSuccessEvent;
use LinkSoft\SocketClient\Exception\ConnectException;
use LinkSoft\SocketClient\Exception\RequestException;
use LinkSoft\SocketClient\Message\RequestMessage;
use LinkSoft\SocketClient\Message\ResponseMessage;
use LinkSoft\SocketClient\Packer\PackerInterface;
use LinkSoft\SocketClient\Processor\ResponseProcessorInterface;
use LinkSoft\SocketClient\Util\ResponseMessageManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;
use Swoole\Timer;

class Client
{
    /**
     * @var Client
     */
    private static $instance;

    /**
     * @var Socket
     */
    private $client;

    /**
     * @var StdoutLoggerInterface|mixed
     */
    private $logger;

    /**
     * 预备发送队列
     * @var RequestMessage[]
     */
    private $prepareRequest = [];

    /**
     * 已发队列
     * @var RequestMessage[]
     */
    private $sendRequest = [];

    /**
     * 接收队列
     * @var ResponseMessage[]
     */
    private $response = [];

    /**
     * @var PackerInterface
     */
    private $packer;

    /**
     * @var ResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $connectStatus;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->config = $container->get(ConfigInterface::class)->get('link_socket_client');
        $this->packer = $container->get(PackerInterface::class);
        $this->responseProcessor = $container->get(ResponseProcessorInterface::class);
        $this->logger = $container->get(LoggerFactory::class)->get();
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        // 注册心跳检测
        $this->connectionStatusMonitor();
        $this->logger->info('Connection status monitor success.');
        // 注册数据接收者
        $this->registerDataReceiver();
        $this->logger->info('Register data receiver success.');
        // 注册数据发送者
        $this->registerDataSender();
        $this->logger->info('Register data sender success.');
    }

    private function __clone()
    {
    }

    /**
     * 初始化实例
     * @return Client
     */
    public static function initInstance(): Client
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取当前实例
     * @return Client
     */
    public static function getInstance(): Client
    {
        return self::$instance;
    }

    /**
     * 发送请求，并接收返回数据
     * @param RequestMessage $message
     * @param int $timeout
     * @return ResponseMessage
     * @throws RequestException
     */
    public function send(RequestMessage $message, ?int $timeout = null): ResponseMessage
    {
        if ($timeout == null) {
            $timeout = $this->config['client']['coroutines_timeout'] ?? 60;
        }

        // 获取requestId
        $requestId = $message->getRequestId();

        // 无连接，直接返回
        if (!$this->isConnect()) {
            $response = ResponseMessageManager::newErrResponse($requestId, Code::REQUEST_FAIL, 'request fail.');
        } else {
            // 预备发送请求
            $this->prepareRequest[$requestId] = $message;
            if (!$message->getIsWait()) {
                $response = ResponseMessageManager::newSuccessResponse($requestId, '', true);
            } else {
                // 等待唤醒
                $message->wait($timeout);
                // 处理返回
                $response = $this->response[$requestId] ?? ResponseMessageManager::newErrResponse($requestId, Code::REQUEST_FAIL, 'request fail.');
                $this->clearRequest($requestId);
            }
        }

        if ($response->getErrCode() != Code::REQUEST_SUCCESS) {
            throw new RequestException($response->getErrMsg(), $response->getErrCode());
        }
        return $response;
    }

    /**
     * 创建一个 socket 连接
     * @return Socket
     */
    private function createClient(): Socket
    {
        $config = $this->config['client'];
        $client = new Socket($config['domain'], $config['type'], $config['protocol']);
        $client->setProtocol($config['protocol_config']);
        return $client;
    }

    /**
     * 连接到服务端
     * @return void
     * @throws ConnectException
     */
    private function connect()
    {
        $config = $this->config['server'];
        if (!$this->client->connect($config['host'], (int)$config['port'])) {
            throw new ConnectException('socket connect failed, err: ' . $this->client->errMsg, Code::CONNECT_FAIL);
        }
        $this->connectStatus = true;
        $this->logger->info('Connected.');
        // 触发连接事件
        $this->eventDispatcher->dispatch(new LinkSocketInitSuccessEvent());
    }

    /**
     * 开启协程，循环向服务端发送数据
     */
    private function registerDataSender()
    {
        go(function () {
            while (true) {
                if ($this->isConnect() && $message = array_shift($this->prepareRequest)) {
                    // 要发送的数据，判断发送数据长度，以确保发送一定成功
                    $data = $this->packer->pack($message);
                    $size = strlen($data);
                    $res = $this->client->send($data);
                    if (!$res || $res != $size) {
                        $message->done();
                    } else {
                        // 设置已发队列
                        if ($message->getIsWait()) {
                            $this->sendRequest[$message->getRequestId()] = $message;
                        }
                    }
                } else {
                    // 休眠降低cpu空转消耗
                    $pollSleepTime = floatval($this->config['client']['poll_sleep_time'] ?? 0.2);
                    Coroutine::sleep($pollSleepTime);
                }
            }
        });
    }

    /**
     * 开启协程，循环接收服务端返回数据
     */
    private function registerDataReceiver()
    {
        go(function () {
            while (true) {
                if ($this->isConnect() && $response = $this->client->recvPacket()) {
                    // 返回数据不是 string 不处理，发送者会自动过期唤醒
                    if (!is_string($response)) {
                        return;
                    }
                    $this->recv($response);
                } else {
                    // 休眠降低cpu空转消耗
                    $pollSleepTime = floatval($this->config['client']['poll_sleep_time'] ?? 0.2);
                    Coroutine::sleep($pollSleepTime);
                }
            }
        });
    }

    /**
     * 连接状态检测
     */
    private function connectionStatusMonitor()
    {
        Timer::tick(5 * 1000, function () {
            if ($this->isConnect() && $this->client->checkLiveness()) {
                $this->logger->info('Connection alive.');
            } else {
                try {
                    $this->connectStatus = false;
                    if (null !== $this->client) {
                        $this->client->close();
                    }
                    $this->client = $this->createClient();
                    $this->connect();
                } catch (ConnectException $ce) {
                    $this->logger->error($ce->getMessage(), [
                        'code'  => $ce->getCode(),
                        'file'  => $ce->getFile(),
                        'line'  => $ce->getLine(),
                        'trace' => $ce->getTraceAsString()
                    ]);
                }
            }
        });
    }

    /**
     * 此处处理单条数据被接收后的情况
     */
    private function recv($response)
    {
        try {
            /* @var $newResponse ResponseMessage */
            $newResponse = $this->packer->unpack($response);
            $requestId = $newResponse->getRequestId();
            // 如果是我方请求
            if (isset($this->sendRequest[$requestId])) {
                // 20220711 新增：支持服务端连续对某个请求推送多个数据包，包处理交给实现端完成
                $stockResponse = $this->response[$requestId] ?? ResponseMessageManager::newSuccessResponse($requestId, '', false);
                $this->response[$requestId] = $this->responseProcessor->handle($stockResponse, $newResponse);
                if ($this->response[$requestId]->getIsEnd()) {
                    $this->sendRequest[$requestId]->done();
                }
            } else {
                // 已经结束的协程请求数据，和服务端主动推送数据，最终都会在这里被处理
                try {
                    $callback = ApplicationContext::getContainer()->get(CallbackInterface::class);
                    $callback->handle($newResponse);
                } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                    $this->logger->error('Recv err: ' . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Recv err: ' . $e->getMessage());
        }
    }

    /**
     * 清理指定请求
     * @param $requestId
     */
    private function clearRequest($requestId)
    {
        unset($this->prepareRequest[$requestId]);
        unset($this->sendRequest[$requestId]);
        unset($this->response[$requestId]);
    }

    /**
     * 是否已建立连接
     * @return bool
     */
    private function isConnect(): bool
    {
        return !empty($this->connectStatus);
    }

    /**
     * 获取内存中的数据
     * @return array
     */
    public function getMemoryData(): array
    {
        $res = [];
        foreach ($this->prepareRequest as $requestMessage) {
            $res['prepare'][] = [
                'requestId' => $requestMessage->getRequestId(),
                'content'   => $requestMessage->getContent(),
                'time'      => $requestMessage->getTime(),
                'isWait'    => $requestMessage->getIsWait()
            ];
        }
        foreach ($this->sendRequest as $requestMessage) {
            $res['send'][] = [
                'requestId' => $requestMessage->getRequestId(),
                'content'   => $requestMessage->getContent(),
                'time'      => $requestMessage->getTime(),
                'isWait'    => $requestMessage->getIsWait()
            ];
        }
        foreach ($this->response as $responseMessage) {
            $res['response'][] = [
                'requestId' => $responseMessage->getRequestId(),
                'content'   => $responseMessage->getContent(),
                'errCode'   => $responseMessage->getErrCode(),
                'errMsg'    => $responseMessage->getErrMsg(),
                'isEnd'     => $responseMessage->getIsEnd()
            ];
        }
        return $res;
    }
}