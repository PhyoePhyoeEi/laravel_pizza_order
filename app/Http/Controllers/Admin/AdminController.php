<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // direct admin profile
    public function profile()
    {
        $id = auth()->user()->id;
        $userData = User::where('id', $id)->first();
        return view('admin.profile.index')->with(['user' => $userData]);
    }

    // update profile
    public function updateProfile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'address' => 'required',

        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $updateData = $this->requestUserData($request);
        User::where('id', $id)->update($updateData);
        return back()->with(['updateSuccess' => 'User Info Updated']);
    }

    // direct change password page
    public function changePasswordPage()
    {
        return view('admin.profile.changePassword');

    }

    public function changePassword($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required',
            'confirmPassword' => 'required',

        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = User::where('id', $id)->first();
        $hashPassword = $data['password'];

        $oldPassword = $request->oldPassword;
        $newPassword = $request->newPassword;
        $confirmPassword = $request->confirmPassword;

        if (Hash::check($oldPassword, $hashPassword)) {
            if ($newPassword == $confirmPassword) {
                if (strlen($newPassword) >= 6 || strlen($confirmPassword) >= 6) {

                    $hash = Hash::make($newPassword);
                    User::where('id', $id)->update([
                        'password' => $hash,
                    ]);
                    return back()->with(['success' => 'Password Chagne...']);

                } else {
                    return back()->with(['lengthError' => 'Password Must Be More Than 6 characters...']);
                }

            } else {
                return back()->with(['notMatch' => "Password Do Not Match! Try Again..."]);

            }

        } else {
            return back()->with(['wrongPassword' => "Wrong Password! Try Again..."]);
        }

    }

    private function requestUserData($request)
    {
        return [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,

        ];

    }

}
