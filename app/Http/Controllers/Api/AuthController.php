<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Wallet;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'pin' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $user = User::where('email', $request->email)->exists();

        if ($user) {
            return response()->json(['massage' => 'Email already taken'], 409);
        }
        try {
            $profilePicture = null;
            $ktp =null;

            if($request->profile_picture) {
                $profilePicture = $this->uploadBase64Image($request->profile_picture);
            }

            if($request->ktp) {
                $ktp = $this->uploadBase64Image($request->ktp);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->email,
                'password' => bcrypt($request->password),
                'profile_picture' => $profilePicture,
                'ktp' => $ktp,
                'verified' => ($ktp) ? true : false
            ]);

            wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $this->generationCardNumber(16)
            ]);

        } catch (\Throwable $th) {
            echo $th;

        }
    }

    private function generationCardNumber($length)
    {
        $result = '';
        for ($i=0; $i < $length; $i++){
            $result .= mt_rand(0, 9);
        }

        $wallet = Wallet::where('card_number', $result)->exists();

        if ($wallet) {
            return $this->generationCardNumber($length);
        }

        return $result;
    }

    private function uploadBase64Image($base64Image)
    {
        $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'jpg']);

        $decodedContent = $decoder->getDecodedContent();
        $format = $decoder->getFormat();
        $image = Str::random(10).'.'.$format;
        Storage::disk('public')->put($image, $decodedContent);

        return $image;
    }
}
