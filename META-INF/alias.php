<?php

return [

    \ZanPHP\NovaConnectionPool\Exception\CanNotFindLoadBalancingStrategeMapException::class => "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotFindLoadBalancingStrategeMapException",
    \ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaClientPoolNameByAppNameException::class =>  "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotFindNovaClientPoolNameByAppNameException",
    \ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaServiceNameMethodException::class =>  "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotFindNovaServiceNameMethodException",
    \ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaClientPoolException::class => "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotFindNovaClientPoolException",
    \ZanPHP\NovaConnectionPool\Exception\CanNotParseServiceNameException::class => "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotParseServiceNameException",
    \ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaServiceNameException::class => "\\Zan\\Framework\\Network\\Connection\\Exception\\CanNotFindNovaServiceNameException",
    \ZanPHP\NovaConnectionPool\Exception\NovaClientPingEncodeException::class => "\\Zan\\Framework\\Network\\Connection\\Exception\\NovaClientPingEncodeException",


    \ZanPHP\NovaConnectionPool\NovaConnection::class => "\\Zan\\Framework\\Network\\Connection\\Driver\\NovaClient",
    \ZanPHP\NovaConnectionPool\NovaClientFactory::class => "\\Zan\\Framework\\Network\\Connection\\Factory\\NovaClient",
    \ZanPHP\NovaConnectionPool\NovaClientConnectionManager::class => "\\Zan\\Framework\\Network\\Connection\\NovaClientConnectionManager",
    \ZanPHP\NovaConnectionPool\NovaClientPool::class => "\\Zan\\Framework\\Network\\Connection\\NovaClientPool",
];