<?php

namespace App\Http\Controllers;

use App\Models\Superadmin;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Hash;

class SuperadminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $superadmin = Superadmin::where('email', $request->email)->first();

        if (!$superadmin || !Hash::check($request->password, $superadmin->password)) {

            return ResponseHelper::withError('The provided credentials are incorrect.');
        }



        return ResponseHelper::withSuccess('Super admin loggen in', [
            'token' => $superadmin->createToken('Superadmin Token')->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return ResponseHelper::withSuccess('Logged out');
    }
}
