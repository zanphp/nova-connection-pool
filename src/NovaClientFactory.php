<?php

namespace ZanPHP\NovaConnectionPool;

use swoole_client as SwooleClient;
use ZanPHP\Contracts\ConnectionPool\ConnectionFactory;
use ZanPHP\Timer\Timer;

class NovaClientFactory implements ConnectionFactory
{
    const CONNECT_TIMEOUT = 3000;

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create()
    {
        $clientFlags = SWOOLE_SOCK_TCP;
        $socket = new SwooleClient($clientFlags, SWOOLE_SOCK_ASYNC);
        $socket->set($this->config['config']);

        $serverInfo = isset($this->config["server"]) ? $this->config["server"] : [];

        $connection = new NovaConnection($serverInfo);
        $connection->setSocket($socket);
        $connection->setConfig($this->config);
        $connection->init();

        //call connect
        if (false === $socket->connect($this->config['host'], $this->config['port'])) {
            sys_error("NovaClient connect ".$this->config['host'].":".$this->config['port']. " failed");
            return null;
        }

        $connectTimeout = isset($this->config['connect_timeout']) ? $this->config['connect_timeout'] : self::CONNECT_TIMEOUT;
        Timer::after($connectTimeout, $this->getConnectTimeoutCallback($connection), $connection->getConnectTimeoutJobId());

        return $connection;
    }

    public function getConnectTimeoutCallback(NovaConnection $connection)
    {
        return function() use ($connection) {
            $connection->close();
            $connection->onConnectTimeout();
        };
    }

    public function close()
    {

    }

    public function getFactoryType()
    {
        return "NovaClient";
    }
}
