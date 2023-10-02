<?php

namespace Saulmoralespa\Dialog360;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Utils;
use Exception;

class Client
{
    const API_BASE = "https://waba.360dialog.io/";
    const SANDBOX_API_BASE = "https://waba-sandbox.360dialog.io/";
    const API_VERSION = "v1";

    protected static bool $_sandbox = false;
    private string $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function sandboxMode($status = false)
    {
        self::$_sandbox = $status;
    }

    public static function getBaseUrl(): string
    {
        if (self::$_sandbox){
            $url = self::SANDBOX_API_BASE;
        }else{
            $url = self::API_BASE;
        }

        $url .= self::API_VERSION . "/";

        return $url;
    }

    public function client(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => self::getBaseUrl()
        ]);
    }

    /**
     * @param string $to
     * @param string $templateName
     * @param array $components
     * @param string $language
     * @return array|bool|float|int|object|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function sendTemplate(string $to, string $templateName, array $components, string $language = 'es')
    {
        try {
            $parameters = [];
            foreach ($components as $value){
                $parameters[] = [
                    "type" => "text",
                    "text" => $value
                ];
            }

            $response = $this->client()->post("messages",
                [
                    "headers" => [
                        "D360-API-KEY" => $this->apiKey,
                        "Content-Type" => "application/json"
                    ],
                    "json" => [
                        "to" => $to,
                        "type" => "template",
                        "template" => [
                            "language" => [
                                "policy" => "deterministic",
                                "code" => $language
                            ],
                            "name" => $templateName,
                            "components" => [
                                [
                                    "type" => "body",
                                    "parameters" => $parameters
                                ]
                            ]
                        ]
                    ]
                ]
            );

            return self::responseJson($response);

        }catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new Exception($responseBodyAsString);
        }
    }

    /**
     * @param string $phone
     * @return array|bool|float|int|object|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function checkContact(string $phone)
    {
        try {
            $response = $this->client()->post("contacts",
                [
                    "headers" => [
                        "D360-API-KEY" => $this->apiKey,
                        "Content-Type" => "application/json"
                    ],
                    "json" => [
                        "blocking" => "wait",
                        "contacts" => [
                            "+$phone"
                        ],
                        "force_check" => true
                    ]
                ]
            );

            return self::responseJson($response);

        }catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new Exception($responseBodyAsString);
        }
    }

    /**
     * @return array|bool|float|int|object|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function getTemplateList()
    {
        try {
            $response = $this->client()->get("configs/templates",
                [
                    "headers" => [
                        "D360-API-KEY" => $this->apiKey,
                        "Content-Type" => "application/json"
                    ]
                ]
            );

            return self::responseJson($response);

        }catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new Exception($responseBodyAsString);
        }
    }

    /**
     * @return array|bool|float|int|object|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function setWebhook($url)
    {
        try {
            $response = $this->client()->post("configs/webhook",
                [
                    "headers" => [
                        "D360-API-KEY" => $this->apiKey,
                        "Content-Type" => "application/json"
                    ],
                    "json" => [
                        "url" => $url
                    ]
                ]
            );

            return self::responseJson($response);

        }catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new Exception($responseBodyAsString);
        }
    }

    /**
     * @return array|bool|float|int|object|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function getWebhook()
    {
        try {
            $response = $this->client()->get("configs/webhook",
                [
                    "headers" => [
                        "D360-API-KEY" => $this->apiKey,
                        "Content-Type" => "application/json"
                    ]
                ]
            );

            return self::responseJson($response);

        }catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            throw new Exception($responseBodyAsString);
        }
    }

    public static function responseJson($response)
    {
        return Utils::jsonDecode(
            $response->getBody()->getContents()
        );
    }
}