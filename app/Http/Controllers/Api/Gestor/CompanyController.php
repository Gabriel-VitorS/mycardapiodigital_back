<?php

namespace App\Http\Controllers\Api\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;

class CompanyController extends Controller
{

    public function verifyIfEmailExist(Request $request): JsonResponse{
        $emailExist = DB::table('companies')
            ->where('email', $request->email)
            ->first();

        if($emailExist){
            return response()->json(['isValid' => false], 200);
        }else{
            return response()->json(['isValid' => true], 200);
        }

    }

    public function show(){
        $company = DB::table('companies')
                ->where('id', session()->get('id'))
                ->first();
        
        if($company == null){
            return response()->json(['message' => 'Company not find'], 402);    
        }

        unset($company->password);

        return response()->json($company);
    }

    public function store(Request $request): JsonResponse{

        try {
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3',
                'cpf_cnpj' => 'required|numeric|min_digits:11|max_digits:14',
                'email' => 'required|email:rfc',
                'password' => 'required|confirmed'
            ]);
    
            if($validator->fails()){
                return response()->json($validator->errors()->first(), 400);    
            }
    
            $findEmail = DB::table('companies')->where('email', '=', $request->email)->first();
    
            if($findEmail){
                return response()->json("E-mail já cadastrado", 406);
            }
            
            $company = new Company;
    
            $company->name = $request->name;
            $company->cpf_cnpj = $request->cpf_cnpj;
            $company->email = $request->email;
            $company->password = Hash::make($request->password);
    
            $company->save();
    
            $key = env('JWT_KEY');
            $payload = [
                'id' => $company->id,
                'name' => $company->name,
                'cpf_cnpj' => $company->cpf_cnpj,
                'email' => $company->email,
                'exp' => time() + (int)env('JWT_EXP')
            ];
    
            $jwt = JWT::encode($payload, $key, 'HS256');
    
            return response()->json($jwt, 201);
        } catch (\Throwable $th) {
            return response()->json('Erro no servidor. Tente novamente', 500);
        }
        
    }

    public function login(Request $request): JsonResponse{

        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
                'email' => 'required|email:rfc'
            ]);
    
            if($validator->fails()){
                return response()->json($validator->errors()->first(), 400);    
            }
    
            $company = DB::table('companies')->where('email', '=', $request->email)->first();
    
            if(!$company){
                return response()->json('E-mail ou senha inválido', 401);
            }
    
            if(Hash::check($request->password, $company->password) == false){
                return response()->json('E-mail ou senha inválido', 401);
            }
    
            $key = env('JWT_KEY');
            $payload = [
                'id' => $company->id,
                'name' => $company->name,
                'cpf_cnpj' => $company->cpf_cnpj,
                'email' => $company->email,
                'exp' => time() * env('JWT_EXP')
            ];
    
            $jwt = JWT::encode($payload, $key, 'HS256');
    
            return response()->json($jwt, 200);

        } catch (\Throwable $th) {
            return response()->json('Erro no servidor. Tente novamente', 500);
        }



    }
}
