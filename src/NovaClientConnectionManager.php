<?php

namespace ZanPHP\NovaConnectionPool;


use ZanPHP\Contracts\Config\Repository;
use ZanPHP\Contracts\ConnectionPool\Connection;
use ZanPHP\Coroutine\Condition;
use ZanPHP\Coroutine\Exception\ConditionException;
use ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaClientPoolException;
use ZanPHP\NovaConnectionPool\Exception\CanNotFindNovaServiceNameMethodException;
use ZanPHP\Support\Arr;
use ZanPHP\Support\Singleton;

class NovaClientConnectionManager
{
    use Singleton;

    /**
     * serviceKey => server info
     * @var array
     */
    private $serviceMap;

    /**
     * appName => NovaClientPool
     * @var NovaClientPool[]
     */
    private $poolMap;

    private $novaConfig;

    private $dubboConfig;

    public function __construct()
    {
        $this->serviceMap = [];
        $this->poolMap = [];

        $this->novaConfig = make(Repository::class)->get("connection.nova", []);
        if (!isset($this->novaConfig["load_balancing_strategy"])) {
            $this->novaConfig["load_balancing_strategy"] = "roundRobin";
        }

        $this->dubboConfig = make(Repository::class)->get("connection.dubbo", []);
        $this->dubboConfig = Arr::merge($this->dubboConfig, [
            "engine" => "dubboCleint",
            "timeout" => 5000,
            "persistent" => true,
            "heartbeat-time" => 30000,
            "load_balancing_strategy" => "roundRobin",
            "config" => [
                "open_length_check" => true,
                "package_length_type" => "N",
                "package_length_offset" => 12, // 0xdabb + flag(2bytes) + 1bytes + id(8bytes) + 4bytes(body_len)
                "package_body_offset" => 16, // 固定16byte包头
                "package_max_length" => 1024 * 1024 * 2,
            ]
        ]);
    }

    public function get($protocol, $domain, $service, $method)
    {
        $serviceKey = $this->serviceKey($protocol, $domain, $service);
        while (!isset($this->serviceMap[$serviceKey])) {
            try {
                yield new Condition($serviceKey, 2000);
            } catch (ConditionException $ex) {
                throw new CanNotFindNovaClientPoolException("proto=$protocol, domain=$domain, service=$service, method=$method");
            }
        }

        $serviceMap = $this->serviceMap[$serviceKey];
        if (in_array($method, $serviceMap["methods"], true)) {
            $appName = $serviceMap["app_name"];
            $pool = $this->getPool($appName);

            yield setContext("RPC::appName", $appName);
            yield setContext("RPC::protocol", $protocol);
            yield setContext("RPC::domain", $domain);
            yield setContext("RPC::service", $service);
            yield setContext("RPC::method", $method);

            yield $pool->get();
        } else {
            throw new CanNotFindNovaServiceNameMethodException("service=$service, method=$method");
        }
    }

    private function getPool($appName, array $servers = [])
    {
        if (!isset($this->poolMap[$appName]) && $servers) {
            $this->work($appName, $servers);
        }

        if (isset($this->poolMap[$appName])) {
            return $this->poolMap[$appName];
        } else {
            throw new CanNotFindNovaClientPoolException("app_name=$appName");
        }
    }

    public function work($appName, array $servers)
    {
        $toWakeUpKeys = [];
        $config = [];
        foreach ($servers as $server) {
            $protocol = $server["protocol"];
            $domain = $server["namespace"];

            foreach ($server["services"] as $service) {
                $serviceKey = $this->serviceKey($protocol, $domain, $service["service"]);
                $this->serviceMap[$serviceKey] = $service + $server;
                $toWakeUpKeys[$serviceKey] = true;
            }

            list($key, $novaConfig) = $this->makeConfig($server);
            $config[$key] = $novaConfig;
        }

        $pool = new NovaClientPool($appName, $config, $this->novaConfig["load_balancing_strategy"]);;
        $this->poolMap[$appName] = $pool;

        foreach ($toWakeUpKeys as $serviceKey => $_) {
            Condition::wakeUp($serviceKey);
        }
    }

    public function addOnline($appName, array $servers)
    {
        $pool = $this->getPool($appName, $servers);

        foreach ($servers as $server) {
            $protocol = $server["protocol"];
            $domain = $server["namespace"];

            sys_echo("nova client online " . $this->serverInfo($server));

            foreach ($server["services"] as $service) {
                $serviceKey = $this->serviceKey($protocol, $domain, $service["service"]);
                $this->serviceMap[$serviceKey] = $service + $server;
            }

            list(, $novaConfig) = $this->makeConfig($server);
            $pool->createConnection($novaConfig);
            $pool->addConfig($novaConfig);
            $pool->updateLoadBalancingStrategy($pool);
        }
    }

    public function update($appName, array $servers)
    {
        $pool = $this->getPool($appName, $servers);

        foreach ($servers as $server) {
            $protocol = $server["protocol"];
            $domain = $server["namespace"];

            sys_echo("nova client update " . $this->serverInfo($server));

            foreach ($server["services"] as $service) {
                $serviceKey = $this->serviceKey($protocol, $domain, $service["service"]);
                $this->serviceMap[$serviceKey] = $service + $server;
            }

            list(, $novaConfig) = $this->makeConfig($server);
            $pool->addConfig($novaConfig);
            $pool->updateLoadBalancingStrategy($pool);
        }
    }

    public function offline($appName, array $servers)
    {
        $pool = $this->getPool($appName, $servers);

        foreach ($servers as $server) {
            $protocol = $server["protocol"];
            $domain = $server["namespace"];

            sys_echo("nova client offline " . $this->serverInfo($server));

            foreach ($server["services"] as $service) {
                $serviceKey = $this->serviceKey($protocol, $domain, $service["service"]);

                if (isset($this->serviceMap[$serviceKey])) {

                    $connection = $pool->getConnectionByHostPort($server["host"], $server["port"]);
                    if (null !== $connection && $connection instanceof Connection) {
                        $pool->remove($connection);
                    }

                    $pool->removeConfig($server);
                    $pool->updateLoadBalancingStrategy($pool);

                    if (empty($pool->getConfig())) {
                        unset($this->serviceMap[$serviceKey]);
                    }
                }
            }
        }
    }

    public function getServersFromAppNameToServerMap($appName)
    {
        $map = [];
        foreach ($this->serviceMap as $key => $server) {
            if ($appName === $server["app_name"]) {
                $pool = $this->getPool($appName);
                $config = $pool->getConfig();
                // 同一个service 可能有多个节点
                foreach ($config as $hostPort => $item) {
                    $map[$hostPort] = $item["server"];
                }
            }
        }
        return $map;
    }

    private function serviceKey($protocol, $domain, $service)
    {
        // return "$protocol:$domain:$service";
        // 无法获取客户端调用domain信息, 忽略
        return "$protocol::$service";
    }

    private function makeConfig($server)
    {
        $protocol = Arr::get($server, "protocol", "nova");

        $config = [];
        if ($protocol === "nova") {
            $config = $this->novaConfig;
        } else if ($protocol === "dubbo") {
            $config = $this->dubboConfig;
        }

        $key = "{$server["host"]}:{$server["port"]}";
        $value = [
                "host" => $server["host"],
                "port" => $server["port"],
                "weight" => isset($server["weight"]) ? $server["weight"] : 100,
                "server" => $server, // extra info for debug
            ] + $config;

        return [$key, $value];
    }

    private function serverInfo($server)
    {
        $info = [];
        foreach ($server as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            $info[] = "$k=$v";
        }
        return '[' . implode(", ", $info) . ']';
    }
}