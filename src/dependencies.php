<?php

use Slim\App;
use ProgrammingPraticeApp\Middleware\OptionalAuth;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;

return function (App $app) {
    $container = $app->getContainer();

    // Error Handler
    $container['errorHandler'] = function ($c) {
        return new \ProgrammingPraticeApp\Exceptions\ErrorHandler($c['settings']['displayErrorDetails']);
    };

    // App Service Providers
    $container->register(new \ProgrammingPraticeApp\Services\Database\EloquentServiceProvider());

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

    // Request Validator
    $container['validator'] = function ($c) {
        \Respect\Validation\Validator::with('\\ProgrammingPraticeApp\\Validation\\Rules');
        return new \ProgrammingPraticeApp\Validation\Validator();
    };

    // Fractal
    $container['fractal'] = function ($c) {
        $manager = new Manager();
        $manager->setSerializer(new ArraySerializer());
        return $manager;
    };
};
