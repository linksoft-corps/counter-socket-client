<?php
declare(strict_types=1);

namespace LinkSoft\SocketClient\Constants;

class Code
{
    /**
     * 请求成功
     */
    const REQUEST_SUCCESS = 1;
    /**
     * 连接服务端失败
     */
    const CONNECT_FAIL = 1111;

    /**
     * 数据获取失败
     */
    const REQUEST_FAIL = 1112;

    /**
     * 数据请求超时
     */
    const REQUEST_TIMEOUT = 1113;
}