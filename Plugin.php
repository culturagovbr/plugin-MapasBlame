<?php

namespace MapasBlame;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{

    function __construct(array $config = [])
    {
        $config += [
            'request.enable' => true,
            'request.types' => ['GET', 'DELETE', 'PATCH', 'POST', 'PUT', /* 'API' */ ],
            'request.routes' => '*',
            'request.logData.URL' => function ($data) {
                return $data;
            },
            'request.logData.GET' => function ($data) {
                return $data;
            },
            'request.logData.POST' => function ($data) {
                return [];
            },
            'request.logData.PUT' => function ($data) {
                return [];
            },
            'request.logData.DELETE' => function ($data) {
                return [];
            }
        ];

        parent::__construct($config);
    }

    function getRequestData($controller, $method) {
        $data = $controller->{strtolower($method) . 'Data'};

        return $this->config["request.logData.{$method}"]($data);
    }

    function _init()
    {
        $app = App::i();
        $plugin = $this;

        $app->hook('mapasculturais.run:before', function() use($app, $plugin) {
            $request = new Request;
            if ($plugin->config['request.enable']) {
                $request_types = implode('|', $plugin->config['request.types']);
                $routes = $plugin->config['request.routes'];
                $app->hook("<<$request_types>>(<<$routes>>):before", function () use($plugin, $request) {
                    $request_uri = $_SERVER['REQUEST_URI'];
                    $action = "{$this->method} {$request_uri} ({$this->id}.{$this->action})";
    
                    $metadata = [
                        'URL' => $plugin->getRequestData($this, 'URL'),
                        'GET' => $plugin->getRequestData($this, 'GET'),
                        'POST' => $plugin->getRequestData($this, 'POST'),
                        'PUT' => $plugin->getRequestData($this, 'PUT'),
                        'DELETE' => $plugin->getRequestData($this, 'DELETE'),
                    ];
    
                    $request->log($action, $metadata);
                });
            }
        });
    }

    function register()
    {
    }

}
