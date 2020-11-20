<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use ProgrammingPraticeApp\Models\Tag;
use ProgrammingPraticeApp\Models\Problem;


return function (App $app) {
    $container = $app->getContainer();

    $app->get('/tags/search',function(Request $request,Response $response,array $args){
        $term = $request->getQueryParams()['term'];
        $list = Tag::where('name','like','%' . $term . '%')->get()->pluck('name')->take(10);
        
        return $response->withJson($list);

    });

    $app->get('/problem/search',function(Request $request,Response $response,array $args){
        
        $tagName = $request->getQueryParams()['term'];
        $problems = Problem::select('problemcode','author','submission')->whereHas('tags', function($query) use ($tagName) {
            $query->whereName($tagName);
          })->get();
        return $response->withJson($problems);

    });
    

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        
        // Render index view
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });
};
