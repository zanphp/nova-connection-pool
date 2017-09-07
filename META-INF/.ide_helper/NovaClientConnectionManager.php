<?php

namespace Zan\Framework\Network\Connection;

class NovaClientConnectionManager
{
    private $NovaClientConnectionManager;

    public function __construct()
    {
        $this->NovaClientConnectionManager = new \ZanPHP\NovaConnectionPool\NovaClientConnectionManager();
    }

    public function get($protocol, $domain, $service, $method)
    {
        $this->NovaClientConnectionManager->get($protocol, $domain, $service, $method);
    }

    public function work($appName, array $servers)
    {
        $this->NovaClientConnectionManager->work($appName, $servers);
    }

    public function addOnline($appName, array $servers)
    {
        $this->NovaClientConnectionManager->addOnline($appName, $servers);
    }

    public function update($appName, array $servers)
    {
        $this->NovaClientConnectionManager->update($appName, $servers);
    }

    public function offline($appName, array $servers)
    {
        $this->NovaClientConnectionManager->offline($appName, $servers);
    }

    public function getServersFromAppNameToServerMap($appName)
    {
        $this->NovaClientConnectionManager->getServersFromAppNameToServerMap($appName);
    }
}