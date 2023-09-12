<?php

namespace App\Http\Controllers\Cardapio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardapioController extends Controller
{
    //
    public function getCardapio($cardapioUrl){

        $configuration = DB::table('configurations')
            ->where('url', $cardapioUrl)
            ->first();

        if($configuration == null)
            return response()->json(['message' => 'menu not find'], 404);

        $companyId = $configuration->company_id;

        $urlLogo = $this->getUrlLogoImage($configuration->logo_image);
        $urlBanner = $this->getUrlBannerImage($configuration->banner_image);

        /**
         * Pega os produtos em destaque
         */
        $productsHighlight = DB::table('products')
                ->where('company_id', $companyId)
                ->where('highlight', 1)
                ->where('visible_online', 1)
                ->get();

        /**
         * Adiciona os links das imagens dos produtos na variável $productsHighlight
         */
        foreach($productsHighlight as $key => $product){
            unset($product->updated_at);
            unset($product->created_at);

            $productsHighlight[$key]->url_image = $this->getUrlProductImage($product->image);

        }
        
        /**
         * Pega as categorias de acordo com a ordem
         */
        $categories = DB::table('categories')
                ->where('company_id', $companyId)
                ->orderBy('order')
                ->get();

        if($categories != null){

            foreach($categories as $key => $category){
                
                unset($category->updated_at);
                unset($category->created_at);

                /**
                 * Pega os produtos de acordo com id da categoria
                 */
                $products = DB::table('products')
                        ->where('company_id', $companyId)
                        ->where('visible_online', 1)
                        ->where('category_id', $category->id)
                        ->get();

                /**
                 * Verifica se encontrou produto.
                 * Se não encontrou, passa para o próximo índice
                 */
                if(count($products) == 0){
                    unset($categories[$key]);
                    continue;
                }
                        
                foreach($products as $keyProduct => $product){
                    unset($product->updated_at);
                    unset($product->created_at);
                    
                    $products[$keyProduct]->url_image = $this->getUrlProductImage($product->image);
                }
                
                $categories[$key]->products = $products;
            }

        }

        return response()->json([
            'configuration' => [
                'name_company' => $configuration->name_company,
                'banner_image' => $urlBanner,
                'url_logo' => $urlLogo
            ],
            'highlight' => $productsHighlight, 
            'company' => $companyId,
            'categories' => $categories
        ], 200);
    }

    public function detailProduct($id){

        $product = DB::table('products')
                ->where('id', $id)
                ->first();

        if($product == null){
            return response()->json(['message' => 'Product not find'], 404);
        }

        $product->url_image = $this->getUrlProductImage($product->image);

        unset($product->updated_at);
        unset($product->created_at);
        unset($product->highlight);
        unset($product->id);
        unset($product->visible_online);
        unset($product->company_id);
        unset($product->category_id);

        return response()->json($product);
    }
}
