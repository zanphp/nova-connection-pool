<?php
namespace Zan\Framework\Network\Connection;

use ZanPHP\Contracts\ConnectionPool\Connection;

class NovaClientPool
{
    private $NovaClientPool;

    public function __construct($appName, array $config, $loadBalancingStrategy)
    {
        $this->NovaClientPool = new \ZanPHP\NovaConnectionPool\NovaClientPool($appName, $config, $loadBalancingStrategy);
    }

    public function createConnection($config)
    {
        $this->NovaClientPool->createConnection($config);
    }

    public function connecting(Connection $connection)
    {
        $this->NovaClientPool->connecting($connection);
    }

    public function updateLoadBalancingStrategy($pool)
    {
        $this->NovaClientPool->updateLoadBalancingStrategy($pool);
    }

    public function getConnections()
    {
        $this->NovaClientPool->getConnections();
    }

    public function getConfig()
    {
        $this->NovaClientPool->getConfig();
    }

    public function getConnectionByHostPort($host, $port)
    {
        $this->NovaClientPool->getConnectionByHostPort($host, $port);
    }

    public function get()
    {
        $this->NovaClientPool->get();
    }

    public function reload(array $config)
    {
        $this->NovaClientPool->reload($config);
    }

    public function remove(Connection $conn)
    {
        $this->NovaClientPool->remove($conn);
    }

    public function removeConfig($config)
    {
        $this->NovaClientPool->removeConfig($config);
    }

    public function addConfig($config)
    {
        $this->NovaClientPool->addConfig($config);
    }

    public function recycle(Connection $conn)
    {
        $this->NovaClientPool->recycle($conn);
    }

    public function resetReloadTime($config)
    {
        $this->NovaClientPool->resetReloadTime($config);
    }

    public function getReloadJobId($host, $port)
    {
        $this->NovaClientPool->getReloadJobId($host, $port);
    }
}