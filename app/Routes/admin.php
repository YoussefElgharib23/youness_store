<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// make namespace short
use \App\Controllers\AuthController as auth;
use \App\Middleware\flashMiddleware as flash;
use \App\Middleware\OldInputMidddleware as old;
use \App\Middleware\logoutMiddleware as logout;
use \App\Controllers\StockGeneralController as StockGeneral;
use \App\Controllers\EmbalageController as Embalage;
use \App\Controllers\ApiController as Api;
use \App\Controllers\SettingsController as settings;
use \App\Controllers\SliderController as slider;
use \App\Controllers\ProductsCategoriesController as productscats  ;
use \App\Controllers\PagesSystemController as pages;




// security , disable direct access
defined('BASEPATH') or exit('No direct script access allowed');





$app->get('[/]', Web::class.':index')->setName('website.index');
$app->get('/product/{id}', Web::class.':product')->setName('website.product');
$app->get('/thank-you', Web::class.':thankyou')->setName('website.thankyou');
$app->get('/categories/{slug}', Web::class.':categories')->setName('website.categories');


$app->get('/page/{id}', pages::class .':page')->setName('website.page');



$app->get('/cuisine', Web::class.':cuisine')->setName('website.cuisine');
$app->get('/cosmetic', Web::class.':cosmetic')->setName('website.cosmetic');
$app->get('/sport', Web::class.':sport')->setName('website.sport');
$app->get('/voiture', Web::class.':voiture')->setName('website.voiture');
$app->get('/accessoires', Web::class.':accessoires')->setName('website.accessoires');
$app->get('/clouthing', Web::class.':clouthing')->setName('website.clouthing');


$app->get('/bache', Web::class.':bache')->setName('website.bache');





$app->post('/login[/]', auth::class .':login')->setName('login');
$app->get('/logout[/]', auth::class .':logout')->setName('logout')->add( new logout($container) );





$app->group('/admin', function ($container) use($app) {





    // Dashboard index
    $this->get('[/]','Data:index')->setName('admin.index')->add( new App\Middleware\adminMiddleware($container));
   
    $this->post('/save/bank','Data:bank')->setName('save.bank');
   
    $this->get('/change/statue','Data:index')->setName('admin.index')->add( new App\Middleware\adminMiddleware($container));
    $this->post('/load/list','Data:load')->setName('admin.load');
    $this->post('/list/update','Data:update')->setName('admin.update');
    $this->post('/list/create','Data:create')->setName('admin.create');
    $this->post('/export/excel','ExcelExporter:exportData')->setName('admin.update');


    $this->post('/remove/item',function($request){
        $id = $_POST['id'];
        $list = \App\Models\NewOrders::find($id);
        $list->delete();
    });

    $this->post('/change/statue',function(){
        
        $id = $_POST['id'];
        $statue = trim($_POST['statue']);
        
        $list = \App\Models\NewOrders::find($id);
        $list->statue = $statue;
        $list->save();
        
        
    });


  
    // Slider System
    $this->group('/slider', function (){
       $this->any('[/]', slider::class .':index')->setName('slider');
       $this->any('/create', slider::class .':create')->setName('slider.create');
       $this->any('/edit/{id}[/]', slider::class .':edit')->setName('slider.edit');
       $this->get('/delete/{id}[/]', slider::class .':delete')->setName('slider.delete');
       $this->any('/beside-slider[/]', settings::class .':slider')->setName('beside-slider');
    });
 
    // products cateogies
    $this->group('/categories', function (){
        $this->any('[/]', productscats::class .':index')->setName('products.categories');
        $this->any('/edit/{id}[/]', productscats::class .':edit')->setName('products.categories.edit');
        $this->get('/delete/{id}[/]', productscats::class .':delete')->setName('products.categories.delete');
    });


    // new orders system
    $this->get('/data', 'Data:index')->setName('data');
    $this->get('/settings', settings::class.':index')->setName('settings.index');
    $this->post('/settings', settings::class.':update')->setName('settings.update');
    $this->post('/profile', settings::class.':profile')->setName('settings.profile');

    
    // Products system
    $this->group('/products', function (){
        $this->get('[/]', 'Products:index')->setName('products');
        $this->any('/create[/]', 'Products:create')->setName('products.create');
        $this->any('/edit/{id}[/]', 'Products:edit')->setName('products.edit');
        $this->get('/delete/{id}[/]', 'Products:delete')->setName('products.delete');
        $this->get('/duplicate/{id}[/]', 'Products:duplicate')->setName('products.duplicate');
        $this->get('/blukdelete[/]', 'Products:blukdelete')->setName('products.blukdelete');
    });


    // Pages System
    $this->group('/pages', function (){
        $this->get('[/]', pages::class .':index')->setName('pages');
        $this->any('{id}[/]', pages::class .':create')->setName('pages.view');
        $this->any('/create[/]', pages::class .':create')->setName('pages.create');
        $this->any('/edit/{id}[/]', pages::class .':edit')->setName('pages.edit');
        $this->get('/delete/{id}[/]', pages::class .':delete')->setName('pages.delete');
        $this->get('/duplicate/{id}[/]', pages::class .':duplicate')->setName('pages.duplicate');
        $this->get('/blukdelete[/]', pages::class .':blukdelete')->setName('pages.blukdelete');
        $this->any('/mutliAction[/]', pages::class .':mutliAction')->setName('pages.mutliAction');
    });
    
    
    
})->add( new App\Middleware\authMiddleware($container) );







$app->post('/storeApi[/]', function ($request, $response, $args) {  


    $data = [
        'name'  =>  $_POST['fullname'] ,
        'tel'  =>  $_POST['phone'] ,
        'adress'  =>  $_POST['address'] . ' ' . $_POST['districtDistrict'],
        'city'  =>  $_POST['districtSubDistrict'] ,
        'quantity' => $_POST['quantity'],
        'price' =>  $_POST['price'],
        'source' => '',
        'color' => $_POST['color'],
        'size' =>  $_POST['size'],
        'productID' => $_POST['idproduct'],
        'codepostal' => $_POST['codepostal'],
        'payment_mode' => $_POST['payment_mode'],
        'province' => $_POST['province'],
    ];
    
    $newstring = substr($_POST['phone'], -8);
    $foundAlreadyInLists     =   \App\Models\Lists::all('tel');
    $foundAlreadyInNeworders =  \App\Models\NewOrders::all('tel');
    
    $exist = false;
    foreach($foundAlreadyInLists as $order) {
        if( substr($order->tel, -8) == $newstring ) { $exist = true; }
    }
    foreach($foundAlreadyInNeworders as $order) {
        if( substr($order->tel, -8) == $newstring ) { $exist = true; }
    }
    
    if($exist == true ) {
         $data['duplicated_at'] = \Carbon\Carbon::Now();
    }
    
    $order = \App\Models\NewOrders::create($data);


    $_SESSION['order_id'] = $order->id;
    });


//   Middlewares
$app->add( new flash($container) );
$app->add( new old($container) );



