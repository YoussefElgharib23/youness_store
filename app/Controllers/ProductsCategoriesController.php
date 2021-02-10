<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \App\Classes\files;
use \App\Models\Product;
use \App\Models\ProductCategories;


defined('BASEPATH') OR exit('No direct script access allowed');

class ProductsCategoriesController extends Controller {
    
    public function index($request,$response){
 
        if($request->getMethod() == 'POST'){
        
            // get the form data
            $post   = $request->getParams();
            $route = $response->withRedirect($this->router->pathFor('products.categories'));
             
            // clean the form data & set the error route
            $name   = $post['name'];
            $slug   = strtolower($post['slug']);
            $active   = $post['active'];

            // upload the image

          
            $image  = ' ';
            if(!empty($_FILES['image'])){
                $dir        = $this->dir('categories');
                $uploader   = new \App\Helpers\Uploader('start');
                $image   = $uploader->file($_FILES['image'])->dir($dir)->save();
            }
            
            // create the category
            ProductCategories::create(['name' => $name,'slug' => $slug,'image' => $image ,'active' => $active]);
            
            // success
            $this->flashsuccess('created successfly');
            
            return $route;
            
        }
        
        if($request->getMethod() != 'POST'){

                $searchview     = false;
                $count          = ProductCategories::count();   
                $page           = ($request->getParam('page', 0) > 0) ? $request->getParam('page') : 1;
                $limit          = 10; 
                $lastpage       = (ceil($count / $limit) == 0 ? 1 : ceil($count / $limit));    // the number of the pages
                $skip           = ($page - 1) * $limit;
                $categories     = ProductCategories::skip($skip)->take($limit)->orderBy('created_at', 'desc')->get();

                return $this->view->render($response, 'admin/categories/index.twig', [
                    'pagination'    => [
                        'needed'        => $count > $limit,
                        'count'         => $count,
                        'page'          => $page,
                        'lastpage'      => $lastpage,
                        'limit'         => $limit,
                        'prev'          => $page-1,
                        'next'          => $page+1,
                        'start'          => max(1, $page - 4),
                        'end'          => min($page + 4, $lastpage),
                    ],
                  'categories'=> $categories ,
                ]);
        }
    }  
    
 
   public function delete($request,$response,$args) {
        
        // get the id
        $id = rtrim($args['id'], '/');
       
        // get the categorie & delete
        $categorie = ProductCategories::find($id);
        if($categorie) {
            $categorie->delete();
            $this->flashsuccess('deleted seccussfly');
        }
       
       return $response->withRedirect($this->router->pathFor('products.categories'));

    }
    
    
    public function edit($request,$response,$args) {
        
        // get the id
        $id = rtrim($args['id'], '/');
        
        //$uploader = $this->files;
        
        // get the categorie
        $categorie = ProductCategories::find($id);
        
        if($request->getMethod() == 'GET'){
            if($categorie){                
                return $this->view->render($response, 'admin/categories/edit.twig',['categorie'=>$categorie]);
            }
        }
    
        if($request->getMethod() == 'POST'){
            
            // get the form data
            $post   = $request->getParams();

            // clean the form data & set the error route
            $name   = $post['name'];
            $slug   = strtolower($post['slug']);
            $active   = $post['active'];


            // upload the new image
            //$file = $_FILES['image'];
                    
            if(!empty($_FILES['image']['name'])) {

                // Upload
                $dir        = $this->dir('categories');
                $uploader   = new \App\Helpers\Uploader('start');
                $hadiik   = $uploader->file($_FILES['image'])->dir($dir)->save();
                
                // delete old categorie
                $old = $this->dir('categories').$categorie->image;
                if(file_exists($old)) {unlink($old);}
                
                // update in database & save
                $categorie->image = $hadiik;
                $categorie->save();
            }

           
            $categorie->name = $name;
            $categorie->slug = $slug;
            $categorie->active = $active;
            $categorie->save();
            
            // success
            $this->flashsuccess('Categorie updated successfully');
            return $response->withRedirect($this->router->pathFor('products.categories'));
            
        }
        
    }
    
}