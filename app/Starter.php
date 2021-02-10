<?php 

namespace App;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Carbon\Carbon;
use PHPtricks\Orm\Database;
use Noodlehaus\Config;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Flash\Messages as Flash;
use \App\Auth;


use \App\Models\{Options , User , SentLists , Lists , Product , Post  };
use \App\Helpers\{ Cash , Listing , Revenue , Search , Stats , Helper , Api };


defined('BASEPATH') OR exit('No direct script access allowed');

class Starter { 
    

    public function __construct($container){

                // set the session 
                $this->startSession();

                // connect to database
                $this->config($container);

                // set Local lang for time and set the time zone
                $this->setLocal();
                $this->SetTimeZone();

                // show errors
                $this->activateDebugMode();

                // Memory Limit
                $this->memorySettings();

                // connect to database
                $this->connectDB($container);
                
                // Register Twig View
                $this->registerTwigView($container);
                
                // Register Flash Messages
                $this->registerFlashMessages($container);

                // connect to database
                $this->setLang($container);

                // set 404 error page
                $this->SetNotFound($container);

                // Load the controllers 
                $this->loadControllers($container);
                
                // add all type of guard to container
                $this->safeGuard($container);

                // add assets to container
                $this->addAssetsToContainer($container);
                
                // add assets to container
                $this->AdminGlobal($container);   
    }



    protected $capsule;

    public function addAssetsToContainer ($container){

        $container['view']->getEnvironment()->addGlobal('assets', $container['conf']['url.assets']);
        $container['view']->getEnvironment()->addGlobal('config', $container['conf']['app']); 
        $container['view']->getEnvironment()->addGlobal('url', $container['conf']['url']); 
        $container['view']->getEnvironment()->addGlobal('dir', $container['conf']['dir']); 
        $container['view']->getEnvironment()->addGlobal('ALLPRODUCTS', \App\Models\Product::all()); 
        $container['view']->getEnvironment()->addGlobal('ALLCITIES', \App\Models\Cities::all()); 
        $container['view']->getEnvironment()->addGlobal('ALLEMPLOYEES', \App\Models\User::where('role','employee')->get()); 
        $container['view']->getEnvironment()->addGlobal('ALLCATEGORIES', \App\Models\ProductCategories::all('id','name')); 
        $container['view']->getEnvironment()->addGlobal('ALLDELIVERS', \App\Models\User::where('role','deliver')->get()); 
        $options = (new \App\Controllers\SettingsController($container))->getOptions();
        $container['view']->getEnvironment()->addGlobal('options', $options); 

    }


    public function options(){
      
    }



    public function safeGuard ($container){

            if(isset($_SESSION['auth-logged'])) {   
                $container['view']->getEnvironment()->addGlobal('auth',$_SESSION['auth-logged']);
            }


            if(isset($_SESSION['auth-admin'])) {   
                $container['view']->getEnvironment()->addGlobal('admin',$this->capsule->table('users')->find($_SESSION['auth-admin']) );
            }
            if(isset($_SESSION['auth-data'])) {   
                $container['view']->getEnvironment()->addGlobal('datauser',$this->capsule->table('users')->find($_SESSION['auth-data']) );
            }
            if(isset($_SESSION['auth-employee'])) {   
                $container['view']->getEnvironment()->addGlobal('employee',$this->capsule->table('users')->find($_SESSION['auth-employee']) );
            }
            if(isset($_SESSION['auth-deliver'])) {   
                $container['view']->getEnvironment()->addGlobal('deliver',$this->capsule->table('users')->find($_SESSION['auth-deliver']) );
            }


    }

    public function memorySettings(){
        ini_set('memory_limit', '1024M');
    }



    public function SetNotFound($container){
            $container['notFoundHandler'] = function ($container) {
                return function ($request, $response) use ($container) {
                    global $container;
                    echo " 404";
                    exit;
                    return $response->withHeader('Location', '/');
                };
            };
    }


	public function connectDB($container){
		
           // Connect To DataBase
            $capsule = new Capsule;
              
            $capsule->addConnection([
                'driver'    => $container['conf']['db_live.driver'],
                'host'      => $container['conf']['db_live.host'],
                'database'  => $container['conf']['db_live.name'],
                'username'  => $container['conf']['db_live.username'],
                'password'  => $container['conf']['db_live.password'],
                'charset'   => $container['conf']['db_live.charset'],
                'collation' => $container['conf']['db_live.collation'],
                'prefix'    => '',
                'strict' => false
            ]);
             
                  
                   // Make this Capsule instance available globally via static methods... (optional)
                    $capsule->setAsGlobal();

                    // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
                    $capsule->bootEloquent();

                    $this->capsule = $capsule;

                try {
                    Capsule::connection()->getPdo();
                } catch (\Exception $e) {
                    die("Could not connect to the database.  Please check your configuration. "  );
                }


	}

    public function setLang($container){
        $file = BASEPATH.'/app/lang/admin/ar.php';
        $container['view']->getEnvironment()->addGlobal('l', Config::load($file));
        $_SESSION['l'] = include ($file);
    }


    public function config($container){
                // Get All the settings Frpm Config File
       return $container['conf'] = function () {
            return Config::load(INC_ROOT.'/app/config.php');
        };

    }


    public function pushToContainer(){


    }

    public function activateDebugMode(){
       return Helper::setDevelepment();
    }

	public function startSession(){
        if (session_status() == PHP_SESSION_NONE) {
          return session_start();
        }
	}

	public function setLocal(){
          return  Carbon::setLocale('ar');
	}

	public function SetTimeZone(){
           return  date_default_timezone_set('Africa/Casablanca');
	}

    public function loadControllers($container){
       return  Helper::setController($container);
    }

    public function confirmation(){
       return (new \App\Helpers\Stats())->confirmation();
    }

    public function statsEmployees( ){
       return (new \App\Helpers\Stats())->employees();
    }

    public function statsDelivers( ){
       return (new \App\Helpers\Stats())->delivers(); 
    }

    public function newOrders(){
        return (new \App\Helpers\Stats())->newOrders(); 
    }


    public function AdminGlobal($container){
        if(isset($_SESSION['auth-admin'])){
            
            $container['view']->getEnvironment()->addGlobal('statsemployee',  $this->statsEmployees()); 
            $container['view']->getEnvironment()->addGlobal('statsdelivers',  $this->statsDelivers()); 
            $container['view']->getEnvironment()->addGlobal('newOrders',      $this->newOrders());
            $container['view']->getEnvironment()->addGlobal('confirmation',   $this->confirmation()); 
            
        }
        elseif(isset($_SESSION['auth-data'])) {
            $container['view']->getEnvironment()->addGlobal('NewOrdersStats', $this->newOrders());
        }
    }


    


    // Register Twig View helper
    public function registerFlashMessages($container){
        // Register Flash Messages
        $container['flash'] = function ($container) {
            return new \Slim\Flash\Messages();
        };
    }
    // Register Twig View helper
    public function registerTwigView($container){

            // Register Twig View helper
            $container['view'] = function ($c) {
                $view = new \Slim\Views\Twig('../app/Views', [
                   // 'cache' => false,
                ]);
                
                // Instantiate and add Slim specific extension
                $router = $c->get('router');
                $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
                $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
                    
                
                $view->addExtension(new \Knlv\Slim\Views\TwigMessages(
                new \Slim\Flash\Messages()
                ));
                $view->getEnvironment()->addglobal('flash',$c->flash);
                
                

                $filter = new \Twig_SimpleFilter('redirectURL', function ($url) {
                    return ltrim(rtrim($_GET['returnURI'], ")"),'(');
                });
                $view->getEnvironment()->addFilter($filter);


                
                $filter = new \Twig_SimpleFilter('dateOnly', function ($username) {
                    $date = date('Y-m-d', strtotime($username));
                    return $date;
                });
                $view->getEnvironment()->addFilter($filter);
                   


				$filter = new \Twig_SimpleFilter('listcitiesQuantity', function ($username) {
					
					
					$list = $username->toArray();
					$html = '';
					foreach($list as $item):
						$html .= '<tr>';
						$html .= '<td>'.$item['quantity'].'</td>';
						$html .= '<td>'.\App\Models\Cities::find($item['cityID'])->city_name.'</td>';
						$html .= '</tr>';
					 endforeach;
					 
					echo $html;
					
					
				});
				$view->getEnvironment()->addFilter($filter);
								   
                                

                $filter = new \Twig_SimpleFilter('navAvatar', function ($gender) {
                });
                $view->getEnvironment()->addFilter($filter);

                $filter = new \Twig_SimpleFilter('st', function ($username) {
                    return st($username);
                });
                $view->getEnvironment()->addFilter($filter);
                
                return $view;
            };

    }
       


  

}




