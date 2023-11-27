<?php

namespace App\Http\Controllers\Api\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CategoryController extends Controller
{   

    public function store(Request $request): JsonResponse{
        $validator = Validator::make($request->all(),[
            'order' => 'required|numeric',
            'name' => 'required'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 500);    
        }

        $findOrder = DB::table('categories')
                ->where('company_id', '=', session()->get('id'))
                ->where('order', '=', $request->order)
                ->first();

        if($findOrder)
            return response()->json(['message' => 'order field already exists'], 406);

        $category = new Category();
        
        $category->company_id = session()->get('id');
        $category->name = $request->name;
        $category->order = $request->order;

        $category->save();

        return response()->json($category->id, 200) ;
        
    }

    public function show(string $id) : JsonResponse {
        
        $category = DB::table('categories')
                ->where('id', $id)
                ->where('company_id', session()->get('id'))
                ->first();
        
        if($category == null){
            return response()->json(['message' => 'Category not find'], 404);
        }

        return response()->json($category);
    }

    public function destroy(string $id) : JsonResponse{

        $category = DB::table('categories')
                ->where('id', $id)
                ->where('company_id', session()->get('id'))
                ->delete();
        
        if($category == null){
            return response()->json(['message' => 'Category not find'], 404);
        }

        return response()->json(['message' => 'Category deleted successfully' , 'data' => $id]);
    }

    public function index(Request $request): JsonResponse{

        $queryName = $request->name;
        $queryId = $request->id;

        $categories = DB::table('categories')
                ->where('company_id', '=', session()->get('id'))
                ->when($queryName, function ($query, $queryName){
                    $query->where('name', 'LIKE', '%' . $queryName . '%');
                })
                ->when($queryId, function ($query, $queryId){
                    $query->where('id', $queryId);
                })
                ->latest()
                ->paginate($request->limit ?? 15);

        return response()->json($categories);
    }

    public function verifyOrder(Request $request): JsonResponse{

        $validator = Validator::make($request->all(),[
            'order' => 'required|numeric',
            'id' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 500);    
        }

        $findOrder = DB::table('categories')
                    ->where('company_id', '=', session()->get('id'))
                    ->where('order', '=', $request->order)
                    ->first();

        if($findOrder){
            if($findOrder->id == $request->id)
                return response()->json(['isValid' => true], 200);
            else
                return response()->json(['isValid' => false], 200);
        }

        return response()->json(['isValid' => true], 200);
    }


    public function update($id, Request $request): JsonResponse{
        
        $validator = Validator::make($request->all(),[
            'order' => 'required|numeric',
            'name' => 'required'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 500);    
        }

        //Verifica ordem se já existe
        $findOrder = DB::table('categories')
            ->where('company_id', '=', session()->get('id'))
            ->where('order', '=', $request->order)
            ->first();

        if($findOrder){
            if($findOrder->id != $id)
                return response()->json(['messsage' => 'order field already exists'], 406);
        }

        $category = DB::table('categories')
                ->where('id', $id)
                ->where('company_id', '=', session()->get('id'))
                ->update([
                    'order' => $request->order,
                    'name' => $request->name
                ]);

        if(!$category){
            return response()->json(['messsage' => 'Category not find'], 404);
        }

        return response()->json(['messsage' => 'Category successfully updated', 'data' => $id], 200);

    }


}
