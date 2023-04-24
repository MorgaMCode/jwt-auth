<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;





class AuthController extends Controller
{

    public  function register(Request $request){

        try {
            return DB::transaction(function () use($request){
                $validation = Validator::make($request->all(),[
                    'name' => 'required|string',
                    'email'=>'required|email|unique:users',
                    'password' => 'required|confirmed',
                    "admin_code" => "numeric"
                ],[
                    "name.required" =>"el campo name es obligatorio",
                    "email.required" =>"el campo email es obligatorio",
                    "email.unique" =>"el campo email esta duplicado",
                    "password.required" =>"el campo password es obligatorio"
                ]);
                if($validation->fails()){
                    return response($validation->errors(),400);
                }else {
                    $user = new User();
                    $user->name = $request->name;
                    $user->email = $request->email;
                    $user->password = bcrypt($request->password);
                        //asignando el role al usuario
                        if ($request->admin_code && $request->admin_code == 123456) {
                            $user->assignRole('admin');
                        }else{
                            $user->assignRole('customer');
                        }
                    $user->save();
                    return response()->json(['mesaage'=>"usuario {$user->name} creado correctamente "],201);
                }
            },2);
        } catch (\Exception $e) {
            return response([$e],400);

        };
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }

    public function me()
    {
        $user = User::where('id', Auth::user()->id)->with(['roles','roles.permissions'])->first();
        return response()->json($user);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }


}
