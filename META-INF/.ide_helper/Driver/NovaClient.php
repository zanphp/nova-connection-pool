<?php

namespace Zan\Framework\Network\Connection\Driver;

use ZanPHP\Contracts\ConnectionPool\Base;
use ZanPHP\Contracts\ConnectionPool\Connection;
use ZanPHP\Contracts\LoadBalance\Node;
use swoole_client as SwooleClient;

class NovaClient extends Base implements Connection, Node
{
    private $NovaClient;

    public function __construct(array $serverInfo = [])
    {
        $this->NovaClient = new \ZanPHP\NovaConnectionPool\NovaConnection($serverInfo);
    }

    protected function closeSocket()
    {
        $this->NovaClient->closeSocket();
    }

    public function init()
    {
        $this->NovaClient->init();
    }

    public function onConnect(SwooleClient $cli)
    {
        $this->NovaClient->onConnect($cli);
    }

    public function onClose(SwooleClient $cli)
    {
        $this->NovaClient->onClose($cli);
    }

    public function onReceive(SwooleClient $cli, $data)
    {
        $this->NovaClient->onReceive($cli, $data);
    }

    public function onError(SwooleClient $cli)
    {
        $this->NovaClient->onError($cli);
    }

    public function heartbeat()
    {
        $this->NovaClient->heartbeat();
    }

    public function heartbeating()
    {
        $this->NovaClient->heartbeating();
    }

    public function ping()
    {
        $this->NovaClient->ping();
    }

    public function close()
    {
        $this->NovaClient->close();
    }

    public function release()
    {
        $this->NovaClient->release();
    }

    public function setLastUsedTime()
    {
        $this->NovaClient->setLastUsedTime();
    }

    public function getWeight()
    {
        $this->NovaClient->getWeight();
    }
}
