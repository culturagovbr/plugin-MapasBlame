<?php

namespace MapasBlame;

use MapasCulturais\App;
use MapasCulturais\i;

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
            'request.logData.PATCH' => function ($data) {
                return [];
            },
            'request.logData.DELETE' => function ($data) {
                return [];
            }
        ];

        parent::__construct($config);
    }

    /**
     * @return void
     */
    function register() {
        $app = App::i();  
        $app->registerController('blame', Controller::class);
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
                // 
                $app->hook("<<$request_types>>(<<$routes>>):before", function () use($plugin, $request) {
                    $request_uri = $_SERVER['REQUEST_URI'];
                    $action = "{$this->method} {$request_uri} ({$this->id}.{$this->action})";
                    
                    $metadata = [
                        'URL' => $plugin->getRequestData($this, 'URL'),
                        'GET' => $plugin->getRequestData($this, 'GET'),
                    ];

                    if(in_array($this->method, ['POST', 'PUT', 'DELETE', 'PATCH'])){
                        $metadata[$this->method] = $plugin->getRequestData($this, 'POST');
                    }
    
                    $request->log($action, $metadata);
                });
            }
        });

        $app->hook('API(blame.<<*>>):before', function() use($app) {
            if (!$app->user->is('admin')) {
                $app->halt(403,  i::__('Permissao negada'));
            }
        });

        $app->hook('template(panel.userManagement.tabs):end', function() {
            $this->part( 'tab', ['id' => 'user-log', 'label' => i::__('Log de acesso')] );
        });

        $app->hook('template(panel.userManagement.tabs-content):end', function() use ($app) {
            $this->jsObject['MapasBlame'] = [];
            $this->part( 'blame/user-log' );
        });

        $app->view->enqueueStyle('app','blame-filter','css/blame.css');
    }

}
