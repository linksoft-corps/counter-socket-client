### 该拓展用于 websocket 同步收发包

#### 注意事项：

- 项目必须处于 hyperf 协程风格模式下，不能是进程模式。
- 当前收发包并没有设置超时时间，可能由于 tcp 连接不健康导致收发包响应缓慢，后续考虑增加收发包超时控制。

#### 使用方法

- 发布配置文件

```shell
php bin/hyperf.php vendor:publish linksoft/socket-client
```

- 在连接被建立时做一些初始化操作，包内定义了 LinkSocketInitSuccessEvent 事件，在连接被建立时会触发，调用者只需定义一个 listener，监听该事件，并做操作即可，连接异常断开重连也会触发该事件。

```php
<?php
declare(strict_types=1);

use LinkSoft\SocketClient\Event\LinkSocketInitSuccessEvent;
use LinkSoft\SocketClient\Client;

class LinkSocketClientConnectListener implements ListenerInterface
{
    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [LinkSocketInitSuccessEvent::class];
    }
    
    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        $client = Client::getInstance();
        // TODO: some thing...
    }
}

```

- 使用本包主动向服务端发起请求

```php
use LinkSoft\SocketClient\Client;
use LinkSoft\SocketClient\Message\RequestMessage;

$requestId = snowflake_id();
$message = "your message";

// 请求内容必须是一个 RequestMessage 对象
$requestMessage = new RequestMessage($requestId, $message);

// 返回内容是一个 ResponseMessage 对象
$res = Client::getInstance()->send($requestMessage);

var_dump($res);
```

- 自定义 packer

```php
<?php
declare(strict_types=1);

namespace App\Utils;

use App\Component\Parser;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Codec\Json;
use LinkSoft\SocketClient\Message\RequestMessage;
use LinkSoft\SocketClient\Message\ResponseMessage;
use LinkSoft\SocketClient\Packer\PackerInterface;
use LinkSoft\SocketClient\Util\ResponseMessageManager;use phpseclib3\Crypt\Blowfish;

class Packer implements PackerInterface
{
    public function __construct() {
        // TODO: init packer
    }

    public function pack(RequestMessage $data): string
    {
        $content = $data->getContent();
        // TODO: 定义你自己的打包器，返回内容为 socket 实际传输内容
    }

    public function unpack(string $data): ResponseMessage
    {
        // $data 为服务端推送过来的内容，你需要将这一部分内容转换成一个 ResponseMessage 对象，
        // 并保证这个对象一定可以有一个与你之前发送的 RequestMessage->requestId 一样的 requestId.
        return ResponseMessageManager::newSuccessResponse($requestId, $message);
    }
}
```

- 使用你自定义的 packer

```php
// 在你项目配置目录 config->autoload->dependencies.php 下，替换掉项目提供的 packer

// dependencies.php
use LinkSoft\SocketClient\Packer\PackerInterface;
use YourPackerNamespace\Packer;

return [
    // other code...
    PackerInterface::class => Packer::class,
];
```

- 自定义 callback，对服务端主动推送的数据做处理，同时，客户端主动请求但处理失败的数据，最终也会到这里，使用者请注意。

```php
// 定义一个 callback，代码类似如下：

use LinkSoft\SocketClient\Callback\CallbackInterface;
use LinkSoft\SocketClient\Message\ResponseMessage;

class Callback implements CallbackInterface
{
    /**
     * 回调事件处理器
     * @param ResponseMessage $message
     * @return mixed
     */
    public function handle(ResponseMessage $message)
    {
        var_dump('im callback.');
        var_dump($message->getContent());
        return 11;
    }
}

// 在你项目配置目录 config->autoload->dependencies.php 下，替换掉项目提供的 callback

// dependencies.php
use LinkSoft\SocketClient\Callback\CallbackInterface;
use YourCallbackNamespace\Callback;

return [
    // other code...
    CallbackInterface::class => Callback::class,
];
```