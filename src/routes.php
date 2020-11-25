<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use ProgrammingPraticeApp\Models\Tag;
use ProgrammingPraticeApp\Models\Problem;


function take_user_to_codechef_permissions_page($config){

    $params = array('response_type'=>'code', 'client_id'=> $config['client_id'], 'redirect_uri'=> $config['redirect_uri'], 'state'=> 'xyz');
    header('Location: ' . $config['authorization_code_endpoint'] . '?' . http_build_query($params));
    die();
}

function generate_access_token_first_time($config, $oauth_details){

    $oauth_config = array('grant_type' => 'authorization_code', 'code'=> $oauth_details['authorization_code'], 'client_id' => $config['client_id'],
                          'client_secret' => $config['client_secret'], 'redirect_uri'=> $config['redirect_uri']);

    $response = json_decode(make_curl_request($config['access_token_endpoint'],$oauth_config),true);
    $result = $response['result']['data'];
    
    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;
}

function generate_access_token_from_refresh_token($config, $oauth_details){
    $oauth_config = array('grant_type' => 'refresh_token', 'refresh_token'=> $oauth_details['refresh_token'], 'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']);
    
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;

}

function make_curl_request($url, $post = FALSE,$headers = array()) 
{
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }

    $headers[] = 'content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    
    return $response;
}

function make_api_request($oauth_config, $path){
   
    $headers[] = 'Authorization: Bearer ' . $oauth_config;
    return make_curl_request($path, false, $headers);
}



return function (App $app) {
    $container = $app->getContainer();

    $app->get('/tags/create',function(Request $request,Response $response,array $args){
        $path = "https://api.codechef.com/tags/problems?filter=&fields=&limit=100&offset=0";
        $res = make_api_request($_SESSION['access_token'],$path);
        $res = json_decode($res,true);
        foreach($res['result']['data']['content'] as $key=>$data){
            $tag = new Tag();
            $tag->name = $key;
            $tag->save();
        }
        return $response;
    });
        

    $app->get('/problems/create',function(Request $request,Response $response,array $args){

        $tag=$request->getQueryParams()['tag'];
        $path = "https://api.codechef.com/tags/problems?filter=$tag&fields=code, tags, author, solved, attempted, partiallySolved&limit=100&offset=0";
        $res = make_api_request($_SESSION['access_token'],$path);
        echo count($res);
        $res = json_decode($res,true);
        foreach($res['result']['data']['content'] as $key=>$data){
            $problem = new Problem();
            $problem->title = $key;
            $problem->problemcode = $key;
            $problem->author = $data['author'];
            $problem->submission = $data['solved'];
            $problem->save();

            $tagsId = [];
            foreach ($data['tags'] as $tag) 
                $tagsId[] = Tag::updateOrCreate(['name' => $tag], ['name' => $tag])->id;

            $problem->tags()->sync($tagsId);
        }
        
        return $response;

    });

    $app->get('/tags/search',function(Request $request,Response $response,array $args){

        $term = $request->getQueryParams()['term'];
        $list = Tag::where('name','like','%' . $term . '%')->get()->pluck('name')->take(10);
        return $response->withJson($list);

    });

    $app->get('/problem/search',function(Request $request,Response $response,array $args){
        $tagName = $request->getQueryParams()['term'];
        
        $problems = Problem::select('problemcode as Problem Code','author','submission')->whereHas('tags', function($query) use ($tagName) {
            $query->whereName($tagName);
          })->get();

        foreach($problems as $p)
            $p['tags'] = Problem::find(Problem::where('problemcode',$p['Problem Code'])->first()->id)->tags->select('name');

        if(count($problems)==0){
            $path = "https://api.codechef.com/tags/problems?filter=$tagName&fields=code, tags, author, solved, attempted, partiallySolved&limit=100&offset=0";
            $res = make_api_request($_SESSION['access_token'],$path);
            $res = json_decode($res,true);
            foreach($res['result']['data']['content'] as $key=>$data){
                $problem = new Problem();
                $problem->title = $key;
                $problem->problemcode = $key;
                $problem->author = $data['author'];
                $problem->submission = $data['solved'];
                $problem->save();
    
                $tagsId = [];
                foreach ($data['tags'] as $tag) 
                    $tagsId[] = Tag::updateOrCreate(['name' => $tag], ['name' => $tag])->id;            
                $problem->tags()->sync($tagsId);
            }

            $tagName = $request->getQueryParams()['term'];
            $problems = Problem::select('problemcode as Problem Code','author','submission')->whereHas('tags', function($query) use ($tagName) {
                $query->whereName($tagName);
            })->get();
              
        }
        if(count($problems)==0){
            $result['status_code']=404;
            $result['problems']="No problems found associate with this tags";
            
        }
        else{
            $result['status_code']=200;
            $result['problems']=$problems;
        }
        return $response->withJson($result);

    });
    

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {

        $oauth_details = array('authorization_code' => '',
        'access_token' => '',
        'refresh_token' => ''
        );
    
        $config = array('client_id'=> '21c0c6bf43fe831fa88386abf22155b5',
            'client_secret' => '9a41e4fdf2b23c854783062891b38734',
            'api_endpoint'=> 'https://api.codechef.com/',
            'authorization_code_endpoint'=> 'https://api.codechef.com/oauth/authorize',
            'access_token_endpoint'=> 'https://api.codechef.com/oauth/token',
            'redirect_uri'=> 'https://programming-pratice-app.herokuapp.com',
            'website_base_url' => 'https://programming-pratice-app.herokuapp.com'
        );
    
    
        if(isset($_GET['code'])){
        $oauth_details['authorization_code'] = $_GET['code'];
            $oauth = generate_access_token_first_time($config,$oauth_details);  
            $_SESSION['access_token'] = $oauth['access_token'];
            $_SESSION['refresh_token']=$oauth['refresh_token'];     
        } 
        else{
            take_user_to_codechef_permissions_page($config);
        }

        // Render index view
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });
};
