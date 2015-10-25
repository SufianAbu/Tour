<?php



require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';


require_once __DIR__.'/courts/index.php';
require_once __DIR__.'/tournaments/index.php';
require_once __DIR__.'/users/index.php';



$app->match('/staff', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');

$app->match('/', function () use ($app) {

    return $app['twig']->render('index.html.twig', array());
        
})
->bind('home');



$app->run();