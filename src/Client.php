<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use LinkSoft\SocketClient\Callback\CallbackInterface;
use LinkSoft\SocketClient\Constants\Code;
use LinkSoft\SocketClient\Event\LinkSocketInitSuccessEvent;
use LinkSoft\SocketClient\Exception\ConnectException;
use LinkSoft\SocketClient\Exception\RequestException;
use LinkSoft\SocketClient\Message\RequestMessage;
use LinkSoft\SocketClient\Message\ResponseMessage;
use Exception;
use Hyperf\Contract\PackerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;

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
     * @var RequestMessage[]
     */
    private $request;

    /**
     * @var ResponseMessage[]
     */
    private $response;

    /**
     * @var PackerInterface
     */
    private $packer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function __construct() {
        $container = ApplicationContext::getContainer();
        $this->config = $container->get(ConfigInterface::class)->get('socket_manager');
        $this->packer = $container->get(PackerInterface::class);
        $this->logger = $container->get(LoggerFactory::class)->get();
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);

        // 创建客户端
        $this->client = $this->createClient();
        // 注册数据接收者
        $this->registerDataProcessor();
        // 注册僵尸协程清理者
        $this->registerZombieSweeper();
        // 注册心跳检测
        $this->connectionStatusMonitor();
    }

    private function __clone() {}

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
     * @return ResponseMessage
     * @throws RequestException
     */
    public function send(RequestMessage $message): ResponseMessage
    {
        $this->request[$message->getRequestId()] = $message;
        $this->client->send($this->packer->pack($message));
        // 等待数据接收者唤醒
        Coroutine::yield();
        // defer 清理当前协程的内存占用
        defer(function () use ($message) {
            unset($this->request[$message->getRequestId()]);
            unset($this->response[$message->getRequestId()]);
        });
        if (!isset($this->response[$message->getRequestId()])) {
            throw new RequestException('request fail.', Code::REQUEST_FAIL);
        }
        return $this->response[$message->getRequestId()];
    }

    /**
     * 创建一个 socket 连接
     * @return Socket
     */
    private function createClient(): Socket
    {
        $this->request = [];
        $this->response = [];

        $client = new Socket(AF_INET, SOCK_STREAM, STREAM_IPPROTO_IP);
        $client->setProtocol([
            'open_length_check'     => true,
            'package_max_length'    => 1024 * 1024 * 5,
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            'package_length_func'   => function ($data) {
                if (strlen($data) < 4) {
                    return 0;
                }
                // 获取头部位，转16进制
                $lengthHex = bin2hex(substr($data, 0, 4));
                $split = str_split($lengthHex, 2);
                $length = 4;
                $multiple = 1;
                for ($i = 0; $i < 4; $i++) {
                    $length += hexdec($split[$i]) * $multiple;
                    $multiple *= 256;
                }
                if ($length > 1024 * 1024 * 5) {
                    return 0;
                }
                // 最终返回的结果要加上头部本身的 4 位
                return $length;
            }
        ]);
        return $client;
    }

    /**
     * 连接到服务端
     * @return void
     * @throws Exception
     */
    private function connect(): void
    {
        if (!$this->client->connect($this->config['host'], (int)$this->config['port'])) {
            throw new ConnectException('socket connect failed, err: ' . $this->client->errMsg, Code::CONNECT_FAIL);
        }
        // 触发连接事件
        $this->eventDispatcher->dispatch(new LinkSocketInitSuccessEvent());
    }

    /**
     * 开启协程，循环接收服务端返回数据
     */
    private function registerDataProcessor()
    {
        go(function () {
            while (true) {
                $this->recv();
            }
        });
    }

    /**
     * 僵尸协程清理者
     */
    private function registerZombieSweeper()
    {
        go(function () {
            $timeout = $this->config['client']['coroutines_timeout'];
            while (true) {
                $time = time();
                foreach ($this->request as $key => $request) {
                    if ($request->getTime() + $timeout < $time) {
                        unset($this->request[$key]);
                        if (Coroutine::exists($request->getCid())) {
                            Coroutine::resume($request->getCid());
                        }
                    // 新进来的请求时间不会比旧请求更靠前
                    } else {
                        break;
                    }
                }
                Coroutine::sleep(1);
            }
        });
    }

    /**
     * 连接状态检测
     */
    private function connectionStatusMonitor()
    {
        go(function () {
            while (true) {
                if (!$this->client->checkLiveness()) {
                    $this->connect();
                }
                Coroutine::sleep(5);
            }
        });
    }

    /**
     * 此处处理单条数据被接收后的情况
     */
    private function recv()
    {
        $response = $this->client->recvPacket();
        // 返回数据不是string，说明超时，交给僵尸协程清理者处理
        if (!is_string($response)) {
            return;
        }
        try {
            /* @var $message ResponseMessage */
            $message = $this->packer->unpack($response);
            if (isset($this->request[$message->getRequestId()])) {
                $request = $this->request[$message->getRequestId()];
                // 如果请求协程还在，将其恢复
                if (Coroutine::exists($request->getCid())) {
                    $this->response[$message->getRequestId()] = $message;
                    Coroutine::resume($request->getCid());
                } else {
                    // 清除请求信息
                    unset($this->request[$message->getRequestId()]);
                }
            // 已经结束的协程请求数据，和服务端主动推送数据，最终都会在这里被处理
            } else {
                go(function () use ($message) {
                    try {
                        $callback = ApplicationContext::getContainer()->get(CallbackInterface::class);
                        $callback->handle($message);
                    } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                        $this->logger->error('recv err: ' . $e->getMessage());
                    }
                });
            }
        } catch (Exception $e) {
            $this->logger->error('recv err: ' . $e->getMessage());
        }
    }
}