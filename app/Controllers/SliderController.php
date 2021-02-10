<?php

namespace App\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \App\Classes\files;
use \App\Models\Slider;
defined('BASEPATH') OR exit('No direct script access allowed');
class SliderController extends Controller{
   
  
    public function index($request,$response) {
        
            $count          = Slider::count();   
            $page           = ($request->getParam('page', 0) > 0) ? $request->getParam('page') : 1;
            $limit          = 10; 
            $lastpage       = (ceil($count / $limit) == 0 ? 1 : ceil($count / $limit));    
            $skip           = ($page - 1) * $limit;
            $slides         =  Slider::skip($skip)->take($limit)->orderBy('created_at', 'desc')->get();

            return $this->view->render($response, 'admin/slider/index.twig', [
                    'pagination'    => [
                    'needed'        => $count > $limit,
                    'count'         => $count,
                    'page'          => $page,
                    'lastpage'      => $lastpage,
                    'limit'         => $limit,
                    'prev'          => $page-1,
                    'next'          => $page+1,
                    'start'         => max(1, $page - 4),
                    'end'           => min($page + 4, $lastpage),
                ],
              'slides'=>$slides
            ]);  
       
    }
    
  
    public function edit($request,$response,$args) { 
        
    //  Get the id & slide
    $id = rtrim($args['id'], '/');
    $slider = Slider::find($id);
        
    // initlize the helper & the form
   // $helper = $this->helper;
    //$uploader = $this->files;
        
      if($request->getMethod() == 'GET'){ 
          if($slider) {
              return $this->container->view->render($response,'admin/slider/edit.twig',['slider'=>$slider]);
          }
          return $response->withRedirect($this->router->pathFor('slider'));
      }
        
        
        
      if($request->getMethod() == 'POST'){ 
          
          
        // get the form data
        $post   = $request->getParams();

                    
        if(!empty($_FILES['image']['name'])) {

            // Upload
            $dir        = $this->dir('sliders');
            $uploader   = new \App\Helpers\Uploader('start');
            $hadiik   = $uploader->file($_FILES['image'])->dir($dir)->save();
            
            // delete old slide
            $old = $this->dir('slider').$slider->image;
            if(file_exists($old)) {unlink($old);}
            
            // update in database & save
            $slider->image = $hadiik;
            $slider->save();
            
        }
          
        $slider->link = $post['link'];
        $slider->text = $post['text'];
        $slider->save();
            
            
            
          $this->flashsuccess('slider updated successfully');
          return $response->withRedirect($this->router->pathFor('slider.edit', ['id'=> $slider->id , 'slider'=>$slider]));
          
          
      }
        
       
    }
    
    
    
    
    public function create($request,$response) {
        
          if($request->getMethod() == 'GET'){ 
              return $this->container->view->render($response,'admin/slider/create.twig');
          }
        
          if($request->getMethod() == 'POST'){ 
            
            $form     = $request->getParams();

            // upload the image
          
            $image  = ' ';
            if(!empty($_FILES['image'])){
                $dir        = $this->dir('sliders');
                $uploader   = new \App\Helpers\Uploader('start');
                $image   = $uploader->file($_FILES['image'])->dir($dir)->save();
            }
            
            // create the slider
            Slider::create(['image' => $image, 'link'=>$form['link'] , 'text'=>$form['text' ]] );
            
            // flash & redirect
            $this->flashsuccess('slider created successfully');
            return $response->withRedirect($this->router->pathFor('slider'));
        }

    }
    
    
    
    public function delete($request,$response,$args) {
        
        //  Get the id & slide
        $id = rtrim($args['id'], '/');
        $slider = Slider::find($id);
        
        // Delete Slide image if exist
        $image = $this->dir('slider').$slider->image;
        if(file_exists($image)) {unlink($image);}
     
        // Delete the slider if exist & flash success
        if($slider){$slider->delete();$this->flashsuccess('slider removed successfully');}
        
        // redirect to slides route
        return $response->withHeader('Location', $this->router->urlFor('slider'));
    }
    
    
     
}