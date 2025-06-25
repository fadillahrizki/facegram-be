<?php

namespace App\Http\Controllers\Api\V1;

use Reefki\DeviceDetector\Device as DeviceDetector;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string:min:1',
            'bio' => 'required|string|min:1|max:100',
            'username' => 'required|min:3|unique:users|regex:/^[a-zA-Z0-9._]+$/',
            'password' => 'required|min:6',
            'is_private' => 'boolean'
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'bio' => $validated['bio'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'is_private' => $validated['is_private'] ?? false
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $device = $request->device();
        $device->parse();
        $deviceInfo = [];

        if ($device->isBot()) {
            $deviceInfo = [
                'botInfo' => $device->getBot()
            ];
        } else {
            $deviceInfo = [
                'client' => $device->getClient(),
                'os' => $device->getOs(),
                'device_name' => $device->getDeviceName(),
                'brand_name' => $device->getBrandName(),
                'model' => $device->getModel(),
                'ip' => file_get_contents('https://api.ipify.org')
            ];
        }

        $user->devices()->create([
            'device_info' => json_encode($deviceInfo)
        ]);

        return response()->json([
            'message' => 'Register success',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Wrong username or password'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $device = $request->device();
        $device->parse();
        $deviceInfo = [];

        if ($device->isBot()) {
            $deviceInfo = [
                'botInfo' => $device->getBot()
            ];
        } else {
            $deviceInfo = [
                'client' => $device->getClient(),
                'os' => $device->getOs(),
                'device_name' => $device->getDeviceName(),
                'brand_name' => $device->getBrandName(),
                'model' => $device->getModel(),
                'ip' => file_get_contents('https://api.ipify.org')
            ];
        }

        $user->devices()->create([
            'device_info' => json_encode($deviceInfo)
        ]);

        return response()->json([
            'message' => 'Login success',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout success'
        ]);
    }
}
