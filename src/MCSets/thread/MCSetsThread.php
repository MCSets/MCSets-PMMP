<?php

declare(strict_types=1);

namespace MCSets\thread;

use pocketmine\thread\Thread;
use pmmp\thread\ThreadSafeArray;

class MCSetsThread extends Thread
{
    private bool $running = true;
    private ThreadSafeArray $requests;
    private ThreadSafeArray $responses;
    private int $requestId = 0;
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private int $pollingInterval;
    private float $lastPollTime = 0.0;
    private bool $verifySsl;

    public function __construct(string $apiKey, string $baseUrl, int $timeout, int $pollingInterval, bool $verifySsl)
    {
        $this->requests = new ThreadSafeArray();
        $this->responses = new ThreadSafeArray();
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->pollingInterval = $pollingInterval;
        $this->verifySsl = $verifySsl;
    }

    public function onRun(): void
    {
        $this->registerClassLoaders();

        while ($this->running) {
            while (($request = $this->requests->shift()) !== null) {
                $request = igbinary_unserialize($request);
                $this->executeRequest($request);
            }

            $currentTime = microtime(true);
            if ($currentTime - $this->lastPollTime >= $this->pollingInterval) {
                $this->pollQueue();
                $this->lastPollTime = $currentTime;
            }

            $this->sleep();
        }
    }

    private function makeCurlRequest(string $url, string $method = "GET", array $data = []): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "MCSets-PMMP/0.0.1");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-API-Key: " . $this->apiKey
        ]);

        switch (strtoupper($method)) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ["success" => false, "error" => $error, "http_code" => $httpCode];
        }

        $decoded = json_decode($response, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                "success" => false,
                "error" => "Invalid JSON response: " . json_last_error_msg(),
                "http_code" => $httpCode
            ];
        }

        if (isset($decoded["success"]) && $decoded["success"] === false) {
            $errorMessage = $decoded["message"] ?? $decoded["error"] ?? "Unknown error";
            return [
                "success" => false,
                "error" => $errorMessage,
                "http_code" => $httpCode,
                "data" => $decoded
            ];
        }

        return ["success" => true, "data" => $decoded, "http_code" => $httpCode];
    }

    private function executeRequest(array $request): void
    {
        $url = $this->baseUrl . $request["endpoint"];
        $result = $this->makeCurlRequest($url, $request["method"], $request["data"]);

        $this->responses[] = igbinary_serialize([
            "id" => $request["id"],
            "result" => $result,
            "type" => "request"
        ]);
    }

    private function pollQueue(): void
    {
        $url = $this->baseUrl . "/queue";
        $result = $this->makeCurlRequest($url, "GET", []);

        if (!$result["success"]) {
            return;
        }

        $this->responses[] = igbinary_serialize([
            "id" => -1,
            "result" => $result,
            "type" => "queue_poll"
        ]);
    }

    public function submitRequest(string $endpoint, string $method, array $data, ?callable $callback = null): void
    {
        $request = [
            "endpoint" => $endpoint,
            "method" => $method,
            "data" => $data,
            "id" => ++$this->requestId
        ];
        ThreadCallableCache::$callables[$request["id"]] = $callback;
        $this->requests[] = igbinary_serialize($request);
        $this->synchronized(function (): void {
            $this->notify();
        });
    }

    public function checkResponses(): void
    {
        while (($response = $this->responses->shift()) !== null) {
            $response = igbinary_unserialize($response);

            if ($response["type"] === "queue_poll") {
                $callable = ThreadCallableCache::$queuePollCallback ?? null;
                if ($callable !== null) {
                    $callable($response["result"]);
                }
            } else {
                $callable = ThreadCallableCache::$callables[$response["id"]] ?? null;
                if ($callable !== null) {
                    $callable($response["result"]);
                    unset(ThreadCallableCache::$callables[$response["id"]]);
                }
            }
        }
    }

    public function quit(): void
    {
        $this->running = false;
        $this->synchronized(function (): void {
            $this->notify();
        });
        parent::quit();
    }

    private function sleep(): void
    {
        $this->synchronized(function (): void {
            if ($this->running) {
                $this->wait(1000000);
            }
        });
    }
}
