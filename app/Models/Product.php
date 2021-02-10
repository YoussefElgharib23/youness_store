<?php

namespace App\Models;
use illuminate\database\eloquent\model;

class Product extends model{

    protected $table = 'products';    
    protected $guarded = ['id', 'created_at', 'updated_at'];   
    
        protected $appends = ['Galleryimages'];


    public function getGalleryimagesAttribute()
    {
        if(!empty($this->gallery)){
            return json_decode($this->gallery);
        }   
        return '';

    }
    
   
   public function category(){
        return $this->belongsTo('\App\Models\ProductCategories','categoryID')->withDefault(['name'=> 'none']);
   }
   
    
    
}