<?php

namespace Zan\Framework\Network\Connection\Factory;

use ZanPHP\Contracts\ConnectionPool\ConnectionFactory;
use ZanPHP\NovaConnectionPool\NovaConnection;

class NovaClient implements ConnectionFactory
{
    private $NovaClient;

    public function __construct(array $config)
    {
        $this->NovaClient = new \ZanPHP\NovaConnectionPool\NovaClientFactory($config);
    }

    public function create()
    {
        $this->NovaClient->create();
    }

    public function getConnectTimeoutCallback(NovaConnection $connection)
    {
        $this->NovaClient->getConnectTimeoutCallback($connection);
    }

    public function close()
    {
        $this->NovaClient->close();
    }

}
