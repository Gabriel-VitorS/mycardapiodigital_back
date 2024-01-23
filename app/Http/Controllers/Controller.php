<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    public function getUrlLogoImage( string | null $image){
        return $image != null ? url('/') . '/storage/logo_image/' . $image : url('/') . '/storage/logo_image/padrao.png';
    }

    public function getUrlProductImage( string | null $image){
        return $image != null ? url('/') . '/storage/products/' . $image : url('/') . '/storage/products/padrao.png';
    }
}
