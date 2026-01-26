<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Seshac\Otp\Otp;
use Laravel\Socialite\Facades\Socialite;
use App\Models\UserInfo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class UserController extends Controller
{
    public function signup()
    {
        return view('users.userAuthentication');
    }
    public function sendOTPEmail($name, $email, $otp)
    {
        $data = ['name' => $name, 'otp' => $otp];
        $user['to'] = $email;
        Mail::send('email.otpMail', $data, function ($messages) use ($user) {
            $messages->to($user['to']);
            $messages->subject('Your OTP for verification');
        });
    }

    public function sendVerifyMail($name, $email, $otp)
    {
        $data = ['name' => $name, 'otp' => $otp];
        $user['to'] = $email;
        Mail::send('email.verifyMail', $data, function ($messages) use ($user) {
            $messages->to($user['to']);
            $messages->subject('Your verification link');
        });
    }
    public function createUser(Request $request)
    {
        $inputData = $request->validate(
            [
                'fullName' => 'required',
                'email' => ['required', 'email', Rule::unique('users', 'email')],
                'password' => ['required', 'confirmed', 'min:6'],
            ],
            [
                'fullName.required' => 'Empty fields',
                'email.required' => 'Empty fields',
                'password.required' => 'Empty fields',
                'password.confirmed' => 'Password didn\'t match',
                'email.unique' => 'Email already in use',
            ],
        );
        $inputData['account_type'] = 'directSignup';
        $inputData['password'] = bcrypt($inputData['password']);
        $otp = rand(10000, 99999);
        $inputData['otp'] = $otp;
        $user = User::create($inputData);
        $user_info = UserInfo::create([
            'fullName' => $inputData['fullName'],
            'email' => $inputData['email'],
        ]);        $this->sendOTPEmail($inputData['fullName'], $inputData['email'], $otp);
    }
    public function logout(Request $request)
    {
        auth()->logout();
        request()
            ->session()
            ->invalidate();
        request()
            ->session()
            ->regenerateToken();
        session()->flash('createMessage', 'Logged out Successfully!');
        session()->flash('messageColor', 'crimson'); // Replace with the desired background color

        // Redirect the user to a specific route
        return redirect()->route('index');
    }
    public function loginUser(Request $request)
    {
        $inputData = $request->validate(
            [
                'email' => 'required',
                'password' => 'required',
            ],
            [
                'email.required' => 'Empty fields',
                'password.required' => 'Empty fields',
            ],
        );

        $user = User::where('email', $inputData['email'])->first();
        $otp = User::where('email', $inputData['email'])->value('otp');
        $fullName = User::where('email', $inputData['email'])->value('fullName');

        if ($user && !$user->is_verified) {
            $this->sendVerifyMail($fullName, $inputData['email'], $otp);
            return back()
                ->withErrors(['email' => 'Email is not verified. Verification link has been sent to the mail'])
                ->onlyInput();
        }

        if (auth()->attempt($inputData)) {
            $request->session()->regenerate();
            session()->flash('createMessage', 'Logged in Successfully!');
            session()->flash('messageColor', '#00b300'); // Replace with the desired background color

            // Redirect the user to a specific route
            return redirect()->route('index'); // Replace 'your.route.name' with the actual route name
        }

        return back()
            ->withErrors(['email' => 'Invalid Credentials'])
            ->onlyInput();
    }

    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }
    public function googleHandle(Request $request)
    {
        try {
            $user = Socialite::driver('google')->user();
            $findUser = User::where('email', $user['email'])->first();

            if (!$findUser) {
                $findUser = new User();
                $findUser->fullName = $user->name;
                $findUser->email = $user->email;
                $findUser->otp = intval('XXXXXX');
                $findUser->password = $findUser->fullName . rand(100000, 999999);
                $findUser->is_verified = 1;
                $findUser->email_verified_at = \Carbon\Carbon::now();
                $findUser->account_type = $inputData['account_type'] = 'googleSignup';
                $findUser->save();
                $user_info = UserInfo::create([
                    'fullName' => $findUser->fullName,
                    'email' => $findUser->email,
                ]);
                auth()->login($findUser);
                $request->session()->regenerate();
                session()->flash('createMessage', 'Logged in Successfully!');
                session()->flash('messageColor', '#00b300');
                return redirect()->route('index');
            } else {
                $signupMethod = $findUser->account_type;
                if ($signupMethod == 'googleSignup') {
                    auth()->login($findUser);
                    $request->session()->regenerate();
                    session()->flash('createMessage', 'Logged in Successfully!');
                    session()->flash('messageColor', '#00b300');
                    return redirect()->route('index');
                } elseif ($signupMethod == 'directSignup') {
                    return redirect('/user')
                        ->withErrors(['email' => 'The email is already associated with an account. Try logging in with email and password.'])
                        ->onlyInput();
                }
            }
            return redirect('/user')
                ->withErrors(['email' => 'The email is already associated with an account. Try logging in with email and password.'])
                ->onlyInput();
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    public function resendOTP(Request $request)
    {
    }

    public function verifyOTP(Request $request)
    {
        $otp = $request->input('otp');
        $user = User::where('otp', $otp)->first();
        if ($user) {
            $user->is_verified = true;
            $user->email_verified_at = \Carbon\Carbon::now();

            $user->save();
            auth()->login($user);
            session()->flash('createMessage', 'User Created Successfully!');
            session()->flash('messageColor', '#00b300'); // Replace with the desired background color
            return response()->json(['message' => 'User Created Successfully!'], 200);
        } else {
            return response()->json(['errors' => ['otp' => ['Invalid OTP']]], 422);
        }
    }
    public function VerifyLink($otp)
    {
        $user = User::where('otp', $otp)->first();
        if ($user) {
            $user->is_verified = true;
            $user->email_verified_at = \Carbon\Carbon::now();

            $user->save();
            auth()->login($user);
            session()->flash('createMessage', 'Email Verified Successfully!');
            session()->flash('messageColor', '#00b300'); // Replace with the desired background color

            // Redirect the user to a specific route
            return redirect()->route('index'); // Replace with the desired background color
        }
    }

    public function userProfile()
    {
        $email = Auth::user()->email;
        $userInfo = UserInfo::where('email', $email)->first();
        return view('users.userProfile', ['user' => $userInfo]);
    }
    public function userProfileUpdate(Request $request)
    {
        $email = Auth::user()->email;
        $userInfo = UserInfo::where('email', $email)->first();
    
        if ($request->input('phone') == '' || $request->input('address') == '' || $request->input('city') == '' || $request->input('country') == '') {
            // Return a plain text response with the desired data
            return response("empty", 404);
        }
    
        UserInfo::where('email', $email)->update([
            'fullName'=> $request->input('fullName'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'country' => $request->input('country'),
        ]);
    
        // Return a plain text response with the desired data
        return response("success", 200);
    }
    // public function userPasswordUpdate(Request $request){
    //     $email = Auth::user()->email;
    //     $userInfo = UserInfo::where('email', $email)->first();
    //     $inputData = $request->validate(
    //         [
    //             'password' => ['required', 'confirmed', 'min:8'],
    //         ],
    //         [
    //             'password.required' => 'Empty fields',
    //             'password.confirmed' => 'Password didn\'t match',
    //         ],
    //     );
    //     $inputData['password'] = bcrypt($inputData['password']);
    //     User::where('email', $email)->update([
    //         'password' => $inputData['password'],
    //     ]);
    //     return response("success", 200);
    // }
    public function userPasswordUpdate(Request $request)
    {
        $rules = [
            'current_password' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'min:8',
        ];
    
        // Custom validation error messages
        $messages = [
            'current_password.required' => 'One or more fields are empty',
            'password.required' => 'One or more fields are empty',
            'password_confirmation.required' => 'One or more fields are empty',
            'password.confirmed' => 'New passwords do not match.',
        ];
    
        // Validate the request data
        $validator = \Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            // Return validation error response
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Get the authenticated user
        $user = Auth::user();
    
        // Check if the current password matches the one stored in the database
        if($user->account_type == 'googleSignup'){
            if($request->current_password==$user->password){
                $user->password = Hash::make($request->password);
                $user->account_type=='directSignup';
                $user->save();
                return response()->json(['message' => 'Password updated successfully'], 200);
            }
            else{
                return response()->json(['error' => 'Incorrect current password'], 401);
            }
        }
        if (Hash::check($request->current_password, $user->password)) {
            // Update the password with the new one
            $user->password = Hash::make($request->password);
            $user->save();
    
            // Password updated successfully
            return response()->json(['message' => 'Password updated successfully'], 200);
        } else {
            // Wrong current password
            return response()->json(['error' => 'Incorrect current password'], 401);
        }
    }
    

}
