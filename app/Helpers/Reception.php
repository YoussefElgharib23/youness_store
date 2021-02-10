<?php 

namespace App\Helpers;
use App\Models\MultiSale;
use PHPtricks\Orm\Database;
use Illuminate\Database\Capsule\Manager as Capsule;
use \App\Models\{ HistoryEntree , StockSortieList , Lists };

defined('BASEPATH') OR exit('No direct script access allowed');



                
class Reception { 
        
      protected $query;
      protected $products;
      protected $product;
      protected $livre;
      protected $recue;
      protected $physique;
      protected $encours;
      protected $cities;
      protected $city;
      protected $user_id;
      protected $city_id;
      protected $default;
    
      public function __construct( $city = false , $product = false){

          if($city && is_numeric($city)){
              $this->cities = \App\Models\Cities::all('id','city_name  as name','user_id','reference')->whereIn('id',[$city])->toArray();
          }else {
              $this->cities = \App\Models\Cities::all('id','city_name  as name','user_id','reference')->toArray();
          }

          if($product && is_numeric($product)){
              $this->products = \App\Models\Product::all('id','name','reference','prix_jmla','price')->whereIn('id',[$product])->toArray();
          }else {
              $this->products = \App\Models\Product::all('id','name','reference','prix_jmla','price')->toArray();
          }
      }
      
      public function load(){
          return $this->get();         
      }
      
      public function get(){
        $result = [];
        foreach ($this->cities as $city) {
          $this->city = $city['name'];
          $this->user_id = $city['user_id'];
          $this->city_id = $city['id'];
          $result[$this->city] = $this->getByProduct();
        }
        return $result;
      }
    
    
      public function getByProduct(){

              $data = [];

              foreach( $this->products as $product ){
                  
                  $this->product = $product['id'];
                  $this->recue   = 0;
                  $this->livre   = 0;
                  $this->physique   = 0;
                  $this->encours   = 0;

                  $item = [
                        'product_id'  =>  $product['id'],
                        'product_name'=>  $product['name'],
                        'product_ref' =>  $product['reference'],
                        'recue'       =>  $this->getStockRecue() ,
                        'livre'       =>  $this->getStockDelivred() ,
                        'physique'    =>  $this->getStockPhysique() ,
                        'theorique'   =>  $this->getStockPhysique() - $this->getStockEncours(),
                        'encours'     =>  $this->getStockEncours() ,
                        
                  ];
                  
                  array_push($data,$item);
              }
              return $data;
      }


      public function getStockRecue(){
            $validSortie =  StockSortieList::where('productID',$this->product)->where('cityID',$this->city_id)
                ->selectRaw('*, sum(quantity) as sum_quantity ,  sum(valid) as sum_valid ')
                ->get()->toArray();
            $this->recue = $validSortie[0]['sum_valid'] ?? 0;
            return  $this->recue;
      }
 
      public function getStockDelivred() {


            $list = \App\Models\Lists::with('deliver','employee','products','products.product','realcity')->where('cityID',$this->city_id)
            ->whereNotNull('delivred_at')->whereHas('products.product', function ($query) {
                    return $query->where('id', '=', $this->product);
                })->get()->toArray();
                
            $this->livre =  $this->getQuantityFromList($list);
    
            return $this->livre;
            
                
      }
    
    
      public function getStockPhysique(){
            $this->physique =   $this->recue - $this->livre ;
            return $this->physique ;
      }
      
      
      public function getStockTheorique() {
           return $this->physique  - $this->encours ;
      }
    
    
      public function getStockEncours(){

            // get stock en cours for the current  product only
            
            $list = 
            \App\Models\Lists::with('deliver','employee','products','products.product','realcity')
            ->where('cityID',$this->city_id)
            ->whereNull('deleted_at')
            ->whereNotNull('accepted_at')
            ->whereNotNull('verified_at')
            ->whereNull('duplicated_at')
            ->whereNull('canceled_at')
            ->whereNull('delivred_at')
            ->whereNull('recall_at')
            ->whereNull('delivred_at')
            ->where('statue','!=','NoAnswer')

            ->whereHas('products', function($q){
               $q->where('productID', $this->product);
            })

            ->get()->toArray();

            $this->encours =  $this->getQuantityFromList($list) ?? 0 ;

            return $this->encours;

      }
  

      public function getQuantityFromList($list){
          $ok = [];
          foreach($list as $item){
                foreach($item['products'] as $product){    
                    $ok[] = $product['quanity'];
                }
          }
          return array_sum($ok);
      }
  

  
}













