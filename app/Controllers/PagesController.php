<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \App\Helpers\{Revenue,Cash,Search,Double,Statistiques};
use \App\Models\{User,DailyStock};

defined('BASEPATH') OR exit('No direct script access allowed');

class PagesController extends Controller {
    
    
    
    public function statistiques($request,$response){
        $params     = $request->getParams();
        $view       = 'admin/admin/statistiques.twig';
        $employees = (new \App\Helpers\Stats\Employee())->list();
        $delivers = (new \App\Helpers\Stats\Deliver())->list();
        return $this->view->render($response, $view , compact('employees','delivers'));
    }         
        
    public function statistiques_employees($request,$response){
        $params     = $request->getParams();
        $view       = 'stats/dailyEmployees.twig';
        $employees  = ((new \App\Helpers\Stats\DailyEmployees())->load());
        return $this->view->render($response, $view , compact('employees'));
    }         
        
    
    
    public function statistiques_delivers($request,$response){
        $params     = $request->getParams();
        $view       = 'stats/dailyDelivers.twig';
        $delivers = (new \App\Helpers\Stats\DailyDelivers())->load();
        return $this->view->render($response, $view , compact('delivers'));
    }         
    
    
        
    function reception($request,$response){ 
        $city = $_GET['city'] ?? NULL;
        $product = $_GET['product'] ?? NULL;
        $reception = (new \App\Helpers\Reception($city,$product))->load();
        $file = '/admin/admin/reception.twig';    
        return $this->view->render($response, $file , compact('reception','delivers'));    
    }    
    
    
    
     public function downloadExcelDeliverJour($request,$response){
         
            $route = $request->getAttribute('route');
            
            $link = $route->getArguments();
            
            $revenue    =  (new Revenue('loadHistory'))->HistoryDetails($link['jour'],$link['deliver']);
            
            $city = \App\Models\User::find($link['deliver'])->username;

            $data = [];
            foreach($revenue as $day => $products) {
                
                foreach ($products['products'] as $product ){
                     $row = [
                        $city,
                        $day,
                        $product['product'],
                        $product['clients'],
                        $product['quantity'],
                        $product['total'],
                        $product['laivraison'],
                        $product['rest'],
                    ];
                    $data[] = $row;
                }
                
            }
            
           // Add header
            $columns = [
                'ville',
                'date',
                'product',
                'clients',
                'quantity',
                'total',
                'laivraison',
                'Rest',
            ];
            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

                  
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );
            
            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            echo "\xEF\xBB\xBF";
            fputcsv( $fh, $columns ,';');

            foreach ($data as  $row ) {
                 
                fputcsv($fh, $row, ';');
            }

            fclose( $fh );
            
    } 
    
    
    
    
    
    
    
    
    
    
 	public function cities($request,$response){
        if($request->getMethod() == 'POST'){ 
            \App\Models\Cities::create($request->getParams());
            $this->flashsuccess('تم اضافة المدينة بنجاح');
            return $response->withRedirect($this->router->pathFor('pages.cities'));
        }
		$cities   =  Cities();
		$delivers =  GetDelivers();
		$view     = 'admin/admin/cities.twig';
        return $this->view->render($response, $view ,compact('cities','delivers'));    
    }   

    public function Delivercash($request,$response){
        $params     = $request->getParams();
        $view       = 'admin/deliver/cash.twig';
        $deliver    = Deliver();
        $cash       = (new Cash(@$deliver))->listing();
        return $this->view->render($response, $view , compact('cash'));
    } 
 	    
    public function cash($request,$response){
        $params     = $request->getParams();
        $view       = 'admin/admin/cash.twig';
        //$cash       = (new Cash(@$params))->listing();
        $cash       = [];
        return $this->view->render($response, $view , compact('cash'));
    }         
        

    public function ads($request,$response){
        $data          = $request->getAttribute('route')->getArguments();
        Revenue::SpentsAds($data);
        return $response->withRedirect($this->router->pathFor('pages.revenue'));
    } 

    public function revenue($request,$response){
        $view       = 'admin/admin/revenue.twig';
        $post       = clean($request->getParams());
        $revenue    = (new Revenue($post))->get();
        return $this->view->render($response, $view , compact('revenue'));
    }         

          
    public function double($request,$response){
        $lists = (new Double())->get();
        $view     = 'admin/admin/double.twig';
        return $this->view->render($response, $view, compact('lists'));
    }     


    public function search($request,$response){
        $post     = clean($request->getParams());
        $route    = $request->getAttribute('route')->getName();
        $search   = new Search($post,$route);
        $view     = $search->view();
        $lists    = $search->search();
        $number   = $search->number();
        return $this->view->render($response, $view, compact('lists','number'));
    }     
      


    public function ExportRevenue($request,$response){
            $dats    = (new Revenue())->get();
            
                // Add header
            $columns = [
                'date',
                'product',
                'clients',
                'quantity',
                'total',
                'laivraison',
                'Rest',
            ];
            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

                  
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );
            
          
            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            echo "\xEF\xBB\xBF";
            fputcsv( $fh, $columns ,';');

            foreach ($dats as $day => $value ) {
                    foreach ($value['products'] as  $product ) {
                         $data = [
                            $day,
                            $product['product'],
                            $product['clients'],
                            $product['quantity'],
                            $product['total'],
                            $product['laivraison'],
                            $product['rest'],
                        ];
                        fputcsv($fh, $data, ';');
                    }
            }

          

            
            fclose( $fh );
    }     
      





}