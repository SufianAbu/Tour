<?php




class queryData {
	public $start;
	public $recordsTotal;
	public $recordsFiltered;
	public $data;

	function queryData() {
	}
}

use Silex\Application;

$app = new Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/../web/views',
));
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
	'translator.messages' => array(),
));
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(

		'dbs.options' => array(
			'db' => array(
				'driver'   => 'pdo_mysql',
				'dbname'   => 'Tennis_Tournament',
				'host'     => 'localhost',
				'user'     => 'root',
				'password' => '',
				'charset'  => 'utf8',
			),
		)
));

$app['asset_path'] = '/Tournois/web/resources';
$app['debug'] = true;
	// array of REGEX column name to display for foreigner key insted of ID
	// default used :'name','title','e?mail','username'
$app['usr_search_names_foreigner_key'] = array();

return $app;
