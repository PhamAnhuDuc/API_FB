<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;
use Auth;
use Mail;
use Validator;

class PageController extends Controller
{

	private $userLogin;

    function __construct(Request $request)
    {
        if ($request->header('access-token')) {
            $this->userLogin = User::whereAccessToken($request->header('access-token'))->first();
        }
    }

    public function changeLanguage(Request $request)
    {
        return response()->json(trans('messages.checkContentRequired'), 200);
    }

    //API register
    public function register(Request $request)
    {
        $rules = [
            'full_name'        => 'required',
            'email'            => 'required|email|unique:users',
            'password'         => 'min:6|required',
            'password_confirm' => 'required|same:password',
            'phone'            => 'required|regex:/^\+?\d{1,3}?[- .]?\(?(?:\d{2,3})\)?[- .]?\d\d\d[- .]?\d\d\d\d$/i|max:11|min:10',
            'address'          => 'required',
        ];
        $messages = [
            'full_name.required'    => trans('messages.nameRequired'),
            'email.required'        => trans('messages.emailRequired'),
            'email.email'           => trans('messages.emailCheck'),
            'email.unique'          => trans('messages.emailCheckUnique'),
            'password.min'          => trans('messages.passwordMin'),
            'password.required'     => trans('messages.passwordRequired'),
            'password_confirm.same' => trans('messages.passwordConfirm'),
            'phone.regex'           => trans('messages.phoneCheck'),
            'phone.min'             => trans('messages.phoneMin'),
            'phone.max'             => trans('messages.phoneMax'),
            'address.required'      => trans('messages.addressRequired'),
        ]; 
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error validate
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        // thành công, tạo user và gửi email để active tài khoản
        $request->merge([
            'password'     => bcrypt($request->password),
            'access_token' => str_random(100),
        ]);
        $user = User::create($request->only('password', 'access_token', 'full_name', 'email', 'phone', 'address'));

        $verifyUrl = route('verify_email', $user->access_token);
        Mail::send([], [], function($message) use ($user, $verifyUrl){
            $message->to($user->email)
                ->subject('Verify your email address')
                ->setBody('<a href="' . $verifyUrl . '">Click verify confirm email</a>', 'text/html');
            });
        return response()->json($user, 200);
    }

    // API verify email
    public function verify($access_token)
    {
        $checkUser = User::whereAccessToken($access_token)->first();

        // nếu không tồn tại access_token trong bảng user => return error: 'không tồn tại user'
        if (!$checkUser) {
            return response()->json('không tồn tại user', 200);
        }

        // nếu active trong bảng user = 1 => return error: 'tài khoản đã active'
        if ($checkUser->active === 1) {
            return response()->json('tài khoản này đã được active', 200);
        }

        // thành công, cập nhật active = 1 và reset access_token
        $checkUser->update([
            'active'       => 1,
            'access_token' => str_random(100),
        ]);
        return response()->json(trans('messages.messageActiveSuccess'), 200);
    }

	// API login     
    public function login(Request $request)
    {
        $rules = [
            'email'    => 'required|email',
            'password' => 'min:6|required',
        ];
        $messages = [
            'email.required'    => trans('messages.emailRequired'),
            'email.email'       => trans('messages.emailCheck'),
            'password.min'      => trans('messages.passwordMin'),
            'password.required' => trans('messages.passwordRequired'),
        ]; 
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error validate
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        $isLogin = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        
        // đăng nhập thất bại => thông báo lỗi
        if (!$isLogin) {
            return response()->json([
                'message' => 'Login Fail',
            ], 200);
        }

        $userLogin = Auth::user();
        Auth::logout();

        // tài khoản chưa được active => thông báo 'cần verify email'
        if ($userLogin->active === 0) {
            return response()->json([
                'message' => trans('messages.messageConfirmEmail'),
            ], 200);
        }
        
        // đăng nhập thành công => thông báo 'thành công' và return thông tin của user
        if ($userLogin->active === 1) {
            return response()->json([
	            'userLogin' => $userLogin,
	            'message'   => trans('messages.messageLoginSuccess'),
	        ], 200);
        }
    }   

    //API search
    public function search(Request $request)
    {
        $listUserSearch = [];

        if ($request->textSearch) {
            $listUserSearch = User::search($request->textSearch);
        }

        return response()->json([
            'listUserSearch' => $listUserSearch,
            'key'            => $request->textSearch,
            'isLogin'        => $this->userLogin && true,
        ], 200);
    }
}
