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

    public function getUrlBannerImage( string | null $image){
        return $image != null ? url('/') . '/storage/banner_image/' . $image : url('/') . '/storage/banner_image/padrao.png';
    }
}
