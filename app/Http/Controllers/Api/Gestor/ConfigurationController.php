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

    public function store(Request $request): JsonResponse{

        $validator = Validator::make($request->all(),[
            'name_company' => 'required',
            'url' => 'required|regex:/^[A-Za-z0-9-]+$/',
            'background_color' => ['required', Rule::in(['#ffffff', '#18181b'])],
            'theme_color' => 'hex_color',
            'logo_image' => ['nullable', File::image()->max(5 * 1024)]
        ]);


        if($validator->fails()){
            return response()->json($validator->errors(), 400);    
        }


        //Verifca se já tem cadastro
        $configurationIsSaved = DB::table('configurations')
                ->where('company_id', session()->get('id') )
                ->first();

        if($configurationIsSaved){
            return response()->json(['message' => 'Company has already configuration saved'], 406);
        }

        if($this->urlIsSaved($request->url)){
            return response()->json(['message' => 'url field already exists'], 406);
        }

        $configuration = new Configuration();

        $configuration->company_id = session()->get('id');
        $configuration->name_company = $request->name_company;
        $configuration->url = $request->url;
        $configuration->background_color = $request->background_color;
        $configuration->theme_color = $request->theme_color;

        $configuration->save();

        if($request->hasFile('logo_image')){

            $logoImageName = session()->get('id') . '.png';
            $request->file('logo_image')->storeAs('public/logo_image', $logoImageName);
            
            $configuration->logo_image = $logoImageName;

            $configuration->save();
        }
        
        return response()->json([$configuration->id], 200);
    }

    public function index(): JsonResponse{
        $configuration = DB::table('configurations')
                ->where('company_id', session()->get('id'))
                ->first();

        if($configuration == ''){
            $configurationEmpty = [
                'id' => 0,
                'company_id' => session()->get('id'),
                'name_company' => '',
                'url' => '',
                'theme_color' => '',
                'background_color' => '',
                'logo_image' => '',
                'created_at' => '',
                'updated_at' => '',
            ];
            
            return response()->json($configurationEmpty);
        }

        $configuration->url_logo = $this->getUrlLogoImage($configuration->logo_image);

        return response()->json($configuration, 200, [],JSON_UNESCAPED_SLASHES);
    }

    public function update($id,Request $request){
        
        $validator = Validator::make($request->all(),[
            'name_company' => 'required',
            'url' => 'required|regex:/^[A-Za-z0-9-]+$/',
            'background_color' => ['required', Rule::in(['#ffffff', '#18181b'])],
            'theme_color' => 'hex_color',
            'logo_image' => ['nullable', File::image()->max(5 * 1024)]
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);    
        }

        //Verifica se url já foi cadastrada
        if($this->urlIsSaved($request->url))
            return response()->json(['message' => 'url field already exists'], 406);

        $configuration = DB::table('configurations')
                ->where('company_id', session()->get('id') );

        $configuration->update([
            'name_company' => $request->name_company,
            'url' => $request->url,
            'theme_color' => $request->theme_color,
            'background_color' => $request->background_color,
        ]);


        if($request->hasFile('logo_image')){
            
            $logoImageName = session()->get('id') . '.png';
            $request->file('logo_image')->storeAs('public/logo_image', $logoImageName);

            $configuration->update(['logo_image' => $logoImageName]);
        }

        return response()->json(['message' => 'Configuration successfully updated', 'data' => $configuration->first()->id], 200);
    }

}
