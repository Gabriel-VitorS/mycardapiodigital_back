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

    public function store(Request $request): JsonResponse{
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'cpf_cnpj' => 'required|numeric|min_digits:11|max_digits:14',
            'email' => 'required|email:rfc',
            'password' => 'required|confirmed'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 500);    
        }

        $findEmail = DB::table('companies')->where('email', '=', $request->email)->first();

        if($findEmail){
            return response()->json(['message' => 'email field already exists'], 406);
        }
        
        $company = new Company;

        $company->name = $request->name;
        $company->cpf_cnpj = $request->cpf_cnpj;
        $company->email = $request->email;
        $company->password = Hash::make($request->password);

        $company->save();

        return response()->json(['id'=>$company->id], 200);
    }

    public function login(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'email' => 'required|email:rfc'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 500);    
        }

        $company = DB::table('companies')->where('email', '=', $request->email)->first();

        if(!$company){
            return response()->json(['message' => 'Invalid user'], 422);
        }

        if(Hash::check($request->password, $company->password) == false){
            return response()->json(['message' => 'Invalid user'], 422);
        }

        $key = env('JWT_KEY');
        $payload = [
            'id' => $company->id,
            'name' => $company->name,
            'cpf_cnpj' => $company->cpf_cnpj,
            'email' => $company->email,
            'exp' => time() + 60 * 60
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return response()->json(['token' => $jwt], 200);

    }
}
