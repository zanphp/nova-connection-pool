<?php

namespace ZanPHP\NovaConnectionPool;

use ZanPHP\Contracts\ConnectionPool\Base;
use ZanPHP\Contracts\ConnectionPool\Connection;
use ZanPHP\Contracts\ConnectionPool\Heartbeatable;
use ZanPHP\Contracts\LoadBalance\Node;
use ZanPHP\Coroutine\Task;
use ZanPHP\Support\Arr;
use ZanPHP\Support\Time;
use ZanPHP\Timer\Timer;
use swoole_client as SwooleClient;


class NovaConnection extends Base implements Connection, Node
{
    private $onReceive;
    private $onClose;

    protected $isAsync = true;

    private $serverInfo;

    public function __construct(array $serverInfo = [])
    {
        $this->serverInfo = $serverInfo;
    }

    protected function closeSocket()
    {
        try {
            $this->getSocket()->close();
        } catch (\Throwable $t) {
            echo_exception($t);
        } catch (\Exception $e) {
            echo_exception($e);
        }
    }

    public function init()
    {
        /** @var \swoole_client $socket */
        $socket = $this->getSocket();
        $socket->on('connect', [$this, 'onConnect']);
        $socket->on('receive', [$this, 'onReceive']);
        $socket->on('close', [$this, 'onClose']);
        $socket->on('error', [$this, 'onError']);
    }

    public function onConnect(SwooleClient $cli)
    {
        //put conn to active_pool
        Timer::clearAfterJob($this->getConnectTimeoutJobId());
        Timer::clearAfterJob($this->getHeartbeatingJobId());
        $this->release();
        /** @var $pool NovaClientPool */
        $pool = $this->getPool();
        $pool->connecting($this);
        $this->heartbeat();
        $this->inspect("connect to server", $cli);
    }

    public function onClose(SwooleClient $cli)
    {
        Timer::clearAfterJob($this->getConnectTimeoutJobId());
        Timer::clearAfterJob($this->getHeartbeatingJobId());
        $this->close();
        $this->inspect("close", $cli);
    }

    public function onReceive(SwooleClient $cli, $data)
    {
        if ($onReceive = $this->onReceive) {
            try {
                $onReceive($data);
            } catch (\Throwable $t) {
                echo_exception($t);
            } catch (\Exception $e) {
                echo_exception($e);
            }
        }
    }

    public function onError(SwooleClient $cli)
    {
        Timer::clearAfterJob($this->getConnectTimeoutJobId());
        Timer::clearAfterJob($this->getHeartbeatingJobId());
        $this->close();

        $this->inspect("error", $cli, true);
    }

    public function setOnReceive(callable $onReceive)
    {
        $this->onReceive = $onReceive;
    }

    public function setOnClose(callable $onClose)
    {
        $this->onClose = $onClose;
    }

    public function heartbeat()
    {
        Timer::after($this->config['heartbeat-time'], [$this, 'heartbeating'], $this->getHeartbeatingJobId());
    }

    public function heartbeating()
    {
        $time = (Time::current(true) - $this->lastUsedTime) * 1000;
        if ($time >= $this->config['heartbeat-time']) {
            $coroutine = $this->ping();
            Task::execute($coroutine);
        } else {
            Timer::after(($this->config['heartbeat-time'] - $time), [$this, 'heartbeating'], $this->getHeartbeatingJobId());
        }
    }

    public function ping()
    {
        $protocol = Arr::get($this->config, "server.protocol", "nova");
        try {
            /** @var Heartbeatable $heartbeatable */
            $heartbeatable = make("heartbeatable:$protocol", [$this]);
            yield $heartbeatable->ping();
        } catch (\Throwable $t) {
        } catch (\Exception $e) {
        }

        $this->heartbeat();
    }

    public function close()
    {
        // FIX connect 失败, 未收到 RST包, onClose回调触发,
        // 两次进入close, 导致两次触发reload, 连接成倍增长
        if ($this->isClose) {
            return;
        }
        $this->isClose = true;

        if ($onClose = $this->onClose) {
            try {
                $onClose();
            } catch (\Throwable $t) {
                echo_exception($t);
            } catch (\Exception $e) {
                echo_exception($e);
            }
        }

        $this->closeSocket();

        /** @var $pool NovaClientPool */
        $pool = $this->getPool();
        $pool->remove($this);
        $pool->reload($this->config);
    }

    public function release()
    {
        /** @var $pool NovaClientPool */
        $pool = $this->getPool();
        $pool->resetReloadTime($this->config);
    }

    public function setLastUsedTime()
    {
        $this->lastUsedTime = Time::current(true);
    }

    private function getHeartbeatingJobId()
    {
        return spl_object_hash($this) . 'heartbeat';
    }

    private function inspect($desc, SwooleClient $cli, $error = false)
    {
        $info = $this->serverInfo;
        if ($error) {
            $info += [
                "errno" => $cli->errCode,
                "error" => socket_strerror($cli->errCode),
            ];
        }

        $buffer = [];
        foreach ($info as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            $buffer[] = "$k=$v";
        }

        sys_echo("nova client $desc [" . implode(", ", $buffer) . "]");
    }

    /**
     * 0 ~ 100
     * @return int|null
     */
    public function getWeight()
    {
        return isset($this->config["weight"]) ? $this->config["weight"] : 100;
    }
}
