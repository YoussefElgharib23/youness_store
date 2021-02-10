<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Models
use \App\Models\{ Options , Lists , Product , DailyStock , Cities , StockGeneral , NewOrders  };
use \App\Models\{ User , Stock , MultiSale, Sources , StockEntree  , StockSortie  , StockSortieList , HistoryEntree };


// Classes And Libraries
use \App\Classes\{files , SystemLog};
use \App\Classes\Noanswser as ff;


defined('BASEPATH') OR exit('No direct script access allowed');

class DataController extends Controller {
    
   
    public function index($request,$response) {

        $type = $request->getParam('type') ?? 'waiting';
        
        $types = [
            'waiting' => NULL,
            'canceled' => 'ملغاة' ,
            'recall' => 'اعادة الإتصال' ,
            'livred'   => 'تم توزيعها' ,
            'unanswred'   => 'لا يجيب' ,
            'accepted'   => 'تم تأكيدها' ,
        ];

        $unanswred  = NewOrders::where('statue','تم توزيعها')->count();
        $canceled   = NewOrders::where('statue','ملغاة')->count();
        $recall  = NewOrders::where('statue','تم تأكيدها')->count();
        $new  = NewOrders::whereNull('statue')->count();
    
    
    
       $lists  = NewOrders::where('statue',$types[$type])->orderBy('id','DESC')->get();
       
        if(is_null($type)) {
            $type = 'waiting';
        }
        
       $twig = 'admin/admin/data.twig';
       return $this->view->render($response, $twig,  compact('view','lists','type','unanswred','canceled','recall','new') );  
    }
    
    
       

    public function bank($request,$response,$args) {
       $post = $request->getParams();
       $list  = NewOrders::find($post['order_id']);
       $list->bank_transfer = $post['bank_transfer'];
       $list->save();
       return $response->withRedirect("/");   
    }    
        
      
    
    

    public function load($request,$response,$args) {
       $id    = $_POST['id'];
       $list  = NewOrders::find($id)->toArray();
       $twig = 'admin/elements/table.twig';
       return $this->view->render($response, $twig,  compact('list') ); 
    }    
        
    
    public function create($request,$response,$args) {
        $post = $request->getParams();
        $list  = new NewOrders();
        $list->name = $post['name'];
        $list->tel = $post['tel'];	
        $list->adress = $post['adress'];
        $list->city = $post['city'];
        $list->quantity = $post['quantity'];
        $list->productID = $post['product'];
        $list->color = $post['color'];
        $list->size = $post['size'];
        $list->price = $post['price'];
        $list->save();
        
        
        return $response->withRedirect($this->router->pathFor('admin.index'));   
        
     }   
    
    

    public function update($request,$response,$args) {
       $post = $request->getParams();
       $list  = NewOrders::find($post['id']);
       $list->name = $post['name'];
       $list->tel = $post['tel'];	
       $list->adress = $post['adress'];
       $list->city = $post['city'];
       $list->quantity = $post['quantity'];
       $list->productID = $post['product'];
       $list->color = $post['color'];
       $list->size = $post['size'];
       $list->price = $post['price'];
       $list->save();
       
       
       return $response->withRedirect($this->router->pathFor('admin.index'));   
       
    }   
    

    public function edit($request,$response,$args) {
        $id     = rtrim($args['id'], '/');
        $list   = Lists::find($id);
        $double = Lists::where('tel', 'LIKE', '%' . $list->tel . '%')->where('id','!=',$list->id)->get();
        $double2 = SentLists::where('tel', 'LIKE', '%' . $list->tel . '%')->where('list_id','!=',$list->id)->get();
        return $this->view->render($response, 'admin/double/edit.twig', compact('double','double2'));  
    }    
          
    public function activate($request,$response,$args) {
        $id  = rtrim($args['id'], '/');
        $list  = Lists::find($id);
        $list->is_double = '';
        $list->save();
        $this->flashsuccess('تم التعديل بنجاح');
        return $response->withRedirect($this->router->pathFor('double'));   
    }    
    

    public function assignToCity($request,$response,$args){
        $ids = explode(',',$request->getParam('AssignToCityIDS')) ?? [];
        $cityID = $request->getParam('cityID');
        AssignNewOrdersToAcity($ids,$cityID);
        $this->flashsuccess('شكرا لك ، تم تعيين المدينة الى الطلبات بنجاح ');
        return $response->withRedirect($this->router->pathFor('data'));   
    }


    public function remove($request,$response,$args){
        $ids = explode(',',$request->getParam('AssignToRemove')) ?? [];
          $orders =  \App\Models\NewOrders::whereIn('id', $ids)->get();
          if($orders->count() > 0  ) {
                    foreach($orders as $order){
                        $order->delete();
                    }
          }
        $this->flashsuccess('شكرا لك ، تم حذف الطلبات بشكل كلي بنجاح ');
        return $response->withRedirect($this->router->pathFor('data').'?view=deleted');   
    }







    
    /**
     * جلب اسم الموظفة في البحث 
     * @author TakiDDine
     */
    public function getUernameByID($id){
        $user = \App\Models\User::find($id);       
        if($user){
          return  $user->username;
        } 
    }
    
    
  
    
    
    /**
     * الاحصائيات
     * @author TakiDDine
     */
    public function statiques(){
        return [
            'count_deleted' => NewOrders::whereNotNull('deleted_at')->count(),
            'count_sheet' =>  NewOrders::where('source','sheet')->whereNull('deleted_at')->whereNull('duplicated_at')->count(),
            'count_store' => NewOrders::where('source','!=','sheet')->whereNull('duplicated_at')->whereNull('deleted_at')->count(), 
            'count_duplicated' => NewOrders::whereNotNull('duplicated_at')->whereNull('deleted_at')->count() ,
        ];
    }
    
    
    
        
     /**
     * استرجاع الطلبات المحذوفة 
     * @author TakiDDine
     */
    public function restoreOrders($request,$response){
         $ids = explode(',',$request->getParam('AssignToRestore')) ?? [];
         RestoreNewOrdersFromDelete($ids);
         $this->flashsuccess('شكرا لك ، تم استرجاع الطلبات من المحذوفة');
         return $response->withRedirect($this->router->pathFor('data'));      
    }
    
    
     /**
     * حذف الطلبات
     * @author TakiDDine
     */
    public function delete($request,$response,$args){
         $ids = explode(',',$request->getParam('AssignToDelete')) ?? [];
         AssignNewOrdersToDeleted($ids);
         $this->flashsuccess('شكرا لك ، تم تعيين الطلبات كمحذوفة');
         return $response->withRedirect($this->router->pathFor('data'));        
    }
    

     /**
     * استرجاع من المكررة
     * @author TakiDDine
     */
    public function RestoreFromDuplicates($request,$response,$args){
         $ids = explode(',',$request->getParam('RestoreFromDuplicates')) ?? [];
         RestoreNewOrdersFromDuplicates($ids);
         $this->flashsuccess('تم ازالة الطلبات من المكررة');
         return $response->withRedirect($this->router->pathFor('data'));      
    }
    

        
    /**
     * تعيين الطلبات الى المنتوج
     * @author TakiDDine
     */
    public function assignToProduct($request,$response,$args){
            $ids = explode(',',$request->getParam('AssignToProductIDS')) ?? [];
            $productID = $request->getParam('productID');
            AssignNewOrdersToProduct($ids,$productID);
            $this->flashsuccess('شكرا لك ، تم تعيين الطلبات الى المنتوج بنجاح ');
            return $response->withRedirect($this->router->pathFor('data'));      
    }
    
    
    
    
    /*     
    *   
    */     
    public function set_the_orders_to_employee_with_count($employee,$count){
       $i = 0 ;
       while($i < $count):
           if(isset($_SESSION['idsToSend'][$i])){
                $_SESSION['sendToMe'][$employee][] = $_SESSION['idsToSend'][$i];   
                $i++;
           } else {
               break;
           }
       endwhile;  
       $_SESSION['idsToSend'] = array_splice($_SESSION['idsToSend'], $i);
    }
   

    /**
     * تعيين الطلبات الى الموظفة
     * @author TakiDDine
     */
    public function assignToEmployee($request,$response,$args){
        
        unset($_SESSION['idsToSend']);
        unset($_SESSION['sendToMe']);
        
        // get the ids & girls 
        $ids = explode(',',$request->getParam('AssignToEmployeeIDS')) ?? [];

        
        $list = $request->getParams();
        unset($list['AssignToEmployeeIDS']);
        $_SESSION['idsToSend'] = $ids;
       
        // Remove the empty fields
        foreach($list as $girl => $count ) {
            if(empty($count)) {
                unset($list[$girl]);
            }
        }
        
        // set the ids to girls
        foreach($list as $girl => $count ) {
            $this->set_the_orders_to_employee_with_count($girl,$count);
        }
                
        foreach($_SESSION['sendToMe'] as $girl => $ids ) {
            AssignNewOrdersToEmployee($girl,$ids);
        }
        
        $this->flashsuccess('شكرا لك ، تم تحويل الطلبات بنجاح ');
        return $response->withRedirect($this->router->pathFor('data'));
        
    }
    
    
    public function loadcount($request,$response,$args) {
        return json_encode(AutoGetEmployeesStats());
    }
    
         /**
      * To do : fix the probleme of checking if the file exist
      * رفع ملف من جوجل شيت الى قاعدة البيانات
      * @author TakiDDine
      */
     public function uploadTheSheet($request,$response,$args){
         
         if(!isset($_FILES['SheetFile'])){
              $this->flasherror('المرجوا اضافة الملف');
              return $response->withRedirect($this->router->pathFor('data'));
             exit;
         }else {
             
             $uploader = new \App\Helpers\Uploader("upload");
         
             $file = $_FILES['SheetFile'];
             $uploader->file = $file;
             $uploader->path = $this->dir('csv');
             $name = $uploader->save();
             $ext = strtolower(last(explode('.', $file['name'])));
             $csv = $this->dir('csv').$name;
             
             if(file_exists($csv)){
             
                 if($ext == 'csv') {
                    $orders = \App\Helpers\SimpleCSV::import($csv);
                 }

                 if($ext == 'xlsx') {
                    $xlsx = \App\Helpers\SimpleXLSX::parse($csv);
                    $orders = $xlsx->rows();
                 }
                    $termine = 0;
                    $i = 0;
                    foreach ($orders as $order) {
                        if($i != 0  and array_filter($order)):
                             $data = [
                                      'source'=>'sheet',
                                      'name'=>$order[1],
                                      'tel'=>$order[2],
                                      'city'=>$order[3],
                                      'adress'=>$order[4],
                                      'ProductReference'=>$order[5] ?? '',
                                      'price'=>$order[8] ?? '', 
                                      'quantity'=>$order[7], 
                             ];
                             
                             if($this->checkDuplicatedNumber($order[2])) {
                                $data['duplicated_at'] = \Carbon\Carbon::Now();
                             }
                             NewOrders::create($data);
                             $termine++;
                        endif;
                        $i++;
                    }   
                $this->flashsuccess(' لقد تم اضافة الطلبات الجديدة بعدد -  '. $termine);
             }
             
             return $response->withRedirect($this->router->pathFor('data'));
             }
    
    
    }
    
    
     /**
     * التحقق من أن الرقم غير مكرر في الطلبات القديمة والجديدة
     * @author TakiDDine
     */
    public function checkDuplicatedNumber($number){
        
        $newstring = substr($number, -8);
        $foundAlreadyInLists     =   \App\Models\Lists::all();
        $foundAlreadyInNeworders =  \App\Models\NewOrders::all();
        
        
        $exist = false;
        foreach($foundAlreadyInLists as $order) {
            if( substr($order->tel, -8) == $newstring ) { $exist = true; }
        }
        foreach($foundAlreadyInNeworders as $order) {
            if( substr($order->tel, -8) == $newstring ) { $exist = true; }
        }
        
        return $exist;
    }
    
    
    public function SearchForNumber($number){
       
       $results = [];
       
        $newstring = substr($number, -8);
        $foundAlreadyInLists     =   \App\Models\Lists::all();
        $foundAlreadyInNeworders =  \App\Models\NewOrders::all();
        
        
        $exist = false;
        foreach($foundAlreadyInLists as $order) {
            if( substr($order->tel, -8) == $newstring ) { 
                $order->cmdtype = 'oldOrder';
                $results[] = $order;
            }
        }
        foreach($foundAlreadyInNeworders as $order) {
            if( substr($order->tel, -8) == $newstring ) { 
                $order->cmdtype = 'NewOrder';
                $results[] = $order;
            }
        }
        
        
        return $results;
        
    }
    
    
    
    
    
    
   

    
    

}
