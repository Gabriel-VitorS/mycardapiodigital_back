<?php

namespace App\Http\Controllers\Api\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ConfigurationController extends Controller
{

    public function urlIsSaved(string $url): bool{

        //pesquisa url no banco
        $urlExist = DB::table('configurations')->where('url', $url)->first();
        
        //verifica se está atualizando
        if($urlExist){

            if($urlExist->company_id != session()->get('id') )
                return true;
            else
                return false;

        }else{
            return false;
        }
    }

    public function verifyIfUrlExist(Request $request): JsonResponse{
        $validator = Validator::make($request->all(),[
            'url' => 'required|regex:/^[A-Za-z0-9-]+$/',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);    
        }

        //Retorna a negação do método
        return response()->json(['isValid' => !$this->urlIsSaved($request->url)]);
        
    }

    public function storeImage(Request $request){

        $validator = Validator::make($request->all(),[
            'logo_image' => ['nullable',File::image()->max(5 * 1024)]
        ]);

        if($validator->fails())
            return response()->json($validator->errors()->first(), 400);    

        if($request->hasFile('logo_image')){
            $configuration = DB::table('configurations')
                ->where('company_id', session()->get('id') );        
    
            $logoImageName = session()->get('id') . '.png';
            $request->file('logo_image')->storeAs('public/logo_image', $logoImageName);
    
            $configuration->update(['logo_image' => $logoImageName]);

            return response()->json('',201);
        }
        
        return response()->json('',200);

    }

    public function store(Request $request): JsonResponse{

        $validator = Validator::make($request->all(),[
            'name_company' => 'required',
            'url' => 'required|regex:/^[A-Za-z0-9-]+$/',
            'background_color' => ['required', Rule::in(['#F8F9FA', '#18181b'])],
            'theme_color' => 'hex_color',
        ]);


        if($validator->fails()){
            return response()->json($validator->errors()->first(), 400);    
        }


        //Verifca se já tem cadastro
        $configurationIsSaved = DB::table('configurations')
                ->where('company_id', session()->get('id') )
                ->first();

        if($configurationIsSaved){
            return response()->json(['message' => 'Company has already configuration saved'], 406);
        }

        if($this->urlIsSaved($request->url)){
            return response()->json("URL já existe. Utilize outra", 406);
        }

        $configuration = new Configuration();

        $configuration->company_id = session()->get('id');
        $configuration->name_company = $request->name_company;
        $configuration->url = $request->url;
        $configuration->background_color = $request->background_color;
        $configuration->theme_color = $request->theme_color;

        $configuration->save();
        
        return response()->json([$configuration->id], 200);
    }

    public function index(): JsonResponse{
        $configuration = DB::table('configurations')
                ->where('company_id', session()->get('id'))
                ->first();


        if(!$configuration){
            return response()->json('Configuração não encontrada', 404);
        }

        $configuration->url_logo = $this->getUrlLogoImage($configuration->logo_image);

        return response()->json($configuration, 200, [],JSON_UNESCAPED_SLASHES);
    }

    public function update($id,Request $request){
        
        $validator = Validator::make($request->all(),[
            'name_company' => 'required',
            'url' => 'required|regex:/^[A-Za-z0-9-]+$/',
            'background_color' => ['required', Rule::in(['#F8F9FA', '#252525'])],
            'theme_color' => 'hex_color',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->first(), 400);    
        }

        //Verifica se url já foi cadastrada
        if($this->urlIsSaved($request->url))
            return response()->json("URL já existe. Utilize outra", 406);

        $configuration = DB::table('configurations')
                ->where('company_id', session()->get('id') );

        $configuration->update([
            'name_company' => $request->name_company,
            'url' => $request->url,
            'theme_color' => $request->theme_color,
            'background_color' => $request->background_color,
        ]);

        return response()->json($configuration->first()->id, 200);
    }

}
