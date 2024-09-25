<?php

namespace App\Http\Controllers\Api\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;

class ProductController extends Controller
{
    private array $rules; 

    public function __construct()
    {
        $this->rules = [
                'category_id' => 'required|numeric',
                'name' => 'required',
                'value' => 'required|decimal:0,2',
                'resume' => 'nullable',
                'details' => 'nullable',
                'highlight' => 'required|boolean',
                // 'image' => ['nullable', File::image()->max(5 * 1024)],
                'visible_online' => 'required|boolean',
            ];
    }

    public function store(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), $this->rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);    
        }
        $category = DB::table('categories')
                ->where('id', $request->category_id)
                ->where('company_id', session()->get('id'))
                ->first();
        
        if($category == null){
            return response()->json('Categoria não encontrada', 404);
        }

        $product = new Product();

        $product->category_id = $request->category_id;
        $product->company_id  = session()->get('id');
        $product->name = $request->name;
        $product->value = $request->value;
        $product->resume = $request->resume;
        $product->details = $request->details;
        $product->highlight = $request->highlight;
        $product->visible_online = $request->visible_online;

        $product->save();

        return response()->json($product->id, 200);
    }

    public function index(Request $request): JsonResponse{
        $queryId = $request->id;
        $queryName = $request->name;
        $queryCategory = $request->category;
        $queryHighlight = $request->highlight;
        $queryVisibleOnline = $request->visible_online;

        $products = DB::table('products')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->select('products.*', 'categories.id as category_id', 'categories.name as category_name')
                ->where('products.company_id', session()->get('id'))

                ->when($queryId, function ($query, $queryId){
                    $query->where('products.id', $queryId);
                })

                ->when($queryName, function ($query, $queryName){
                    $query->where('products.name', 'LIKE', '%' . $queryName . '%');
                })

                ->when($queryHighlight, function ($query, $queryHighlight){
                    $query->where('products.highlight', $queryHighlight == 'true' ? 1 : 0);
                })

                ->when($queryVisibleOnline, function ($query, $queryVisibleOnline){
                    $query->where('products.visible_online', $queryVisibleOnline == 'true' ? 1 : 0);
                })

                ->when($queryCategory, function ($query, $queryCategory){
                    $query->where('categories.name', 'LIKE', '%' . $queryCategory . '%');
                })

                ->latest()
                ->paginate(15);

        return response()->json($products, 200);
    }

    public function show($id){
        $product = DB::table('products')
            ->where('company_id', session()->get('id'))
            ->where('id', $id)
            ->first();
        
        if($product == null)
            return response()->json("Produto não encontrado", 404);

        $product->url_image = $this->getUrlProductImage($product->image);

        return response()->json($product);
    }

    public function destroy($id){

        $product = DB::table('products')
            ->where('company_id', session()->get('id'))
            ->where('id', $id)
            ->delete();

        if(Storage::exists('public/products/' . $id . '.png')){
            Storage::delete('public/products/' . $id . '.png');
        }
        
        if($product == null)
            return response()->json("Produto não encontrado", 404);

        return response()->json("", 204);
    }

    public function update($id, Request $request):JsonResponse{
        $validator = Validator::make($request->all(), $this->rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);    
        }

        $category = DB::table('categories')
                ->where('id', $request->category_id)
                ->where('company_id', session()->get('id'))
                ->first();
        
        if($category == null){
            return response()->json("Categoria não encontrada", 404);
        }

        $product = DB::table('products')
            ->where('id', $id)
            ->where('company_id', session()->get('id'));

        if($product->first() == null){
            return response()->json("Produto não encontrado", 404);
        }

        $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'value' => $request->value,
                'resume' => $request->resume,
                'details' => $request->details,
                'highlight' => $request->highlight,
                'visible_online' => $request->visible_online,
            ]);

        return response()->json('Produto atualizado', 200);
    }

    function storeImage(Request $request){

        $validator = Validator::make($request->all(),[
            'id' => 'required|numeric',
            'logo_image' => ['nullable',File::image()->max(5 * 1024)]
        ]);

        if($validator->fails())
            return response()->json($validator->errors()->first(), 400);

        if($request->hasFile('image')){

            $product = DB::table('products')
            ->where('id', $request->id)
            ->where('company_id', session()->get('id'));

            if($product->first() == null){
                return response()->json('Produto não encontrado', 404);
            }
            
            $produtoImageName = $request->id . '.png';

            $request->file('image')->storeAs('public/products/', $produtoImageName);

            $product->update([
                'image' => $produtoImageName
            ]);

            return response()->json('',201);
        }
        
        return response()->json('',200);

    }

}
