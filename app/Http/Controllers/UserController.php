<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;
use Mail;
use Auth;
use Validator;

class UserController extends Controller
{
    private $userLogin;

    function __construct(Request $request)
    {
        if ($request->header('access-token')) {
            $this->userLogin = User::whereAccessToken($request->header('access-token'))->first();
        }
    }

    public function getUser($id, Request $request)
    {
        $countFriendCommon = 0;
        $isFriend          = false;
        $listPost          = [];
        $isBestFriend      = false;
        $user              = null;

        // nếu id truyền vào là 1 số => xem user
        if ($id === (string)(int)$id) {
            $user = User::findUserActive($id);
        }
        // nếu id truyền vào là 1 chuỗi và bằng với access_token => xem profile
        if ($id !== (string)(int)$id && $id === $request->header('access-token')) {
            $user = $this->userLogin;
        }

        $IdsFriendUser = Friendship::getIdFriend($id);
        $listFriend = User::findUserActive($IdsFriendUser);

        // nếu user chưa active hoặc không tìm thấy user => return null
        if (!$user) {
            return response()->json([
                'info'    => null,
                'isLogin' => $this->userLogin && true, 
            ], 200);
        }

        // nếu tìm thấy user thì lấy các bài post của user đó
        $posts = Post::with('author', 'image')->whereTargetId($user->id)->orderBy('id', 'desc')->get();
        $listPost = $posts ? $posts->toArray() : [];

        // nếu chưa đăng nhập => chỉ cần return thông tin, danh sách bạn bè và danh sách bài post của user đó
        if (!$this->userLogin) {
            return response()->json([
                'info'       => $user,
                'listFriend' => $listFriend,
                'listPost'   => $listPost,
                'isLogin'    => false,
            ], 200);
        }

        // đã đăng nhập
        // nếu userLogin không phải là user cần xem => lấy thêm isFriend và số bạn chung
        if ($user->id !== $this->userLogin->id) {
            $checkIsFriend = Friendship::checkIsFriend($this->userLogin->id, $id)->first();
            $isFriend = $checkIsFriend && true;

            $IdsFriendUserLogin = Friendship::getIdFriend($this->userLogin->id);
            $countFriendCommon = count(array_intersect($IdsFriendUser, $IdsFriendUserLogin));

            // nếu là friend => kiểm tra mối quan hệ bạn thân
            if ($checkIsFriend) {
                if ($user->id === $checkIsFriend->user_id) { 
                    $isBestFriend = $checkIsFriend->best_friend_2 && true;
                } else {
                    $isBestFriend = $checkIsFriend->best_friend_1 && true;
                }
            }
        }

        return response()->json([
            'info'              => $user,
            'isFriend'          => $isFriend,
            'countFriendCommon' => $countFriendCommon,
            'listFriend'        => $listFriend,
            'listPost'          => $listPost,
            'isBestFriend'      => $isBestFriend,
            'isLogin'           => $this->userLogin && true,
            'emailLogin'        => $this->userLogin->email,
        ], 200);
    }

    //Change Avatart
    public function changeAvatar(Request $request) 
    {
        $ext = '';

        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => 'Cần đăng nhập',
                'isLogin' => false,
            ], 200);
        }

        if ($request->get('fileImage')) {
            $image = $request->get('fileImage');
            $ext = explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1]; // lấy phần mở rộng của file
        }

        // file gửi lên không là hình ảnh => return 'cần chọn file ảnh'
        if (!($ext === 'jpg' || $ext === 'png' || $ext === 'jpeg')) {
            return response()->json([
                'message' => 'Cần chọn file ảnh',
            ], 200);
        }

        // thành công, thêm ảnh vào folder /images và cập nhật user
        $name = time() . '.' . $ext;
        \Image::make($request->get('fileImage'))->save(public_path('images/') . $name);
        $this->userLogin->update(['avatar' => $name]);
        
        return response()->json([
            'message' => 'Change Avatar Success',
        ], 200);
    }

    //Change PassWord 
    public function changePassword(Request $request)
    {
        $rules = [
            'passwordCurrent'    => 'min:6|required',
            'newPassword'        => 'min:6|required',
            'newPasswordConfirm' => 'same:newPassword',
        ];
        $messages = [
            'passwordCurrent.min'      => trans('messages.passwordMin'),
            'passwordCurrent.required' => trans('messages.passwordRequired'),
            'newPassword.min'          => trans('messages.passwordMin'),
            'newPassword.required'     => trans('messages.passwordRequired'),
            'newPasswordConfirm.same'  => trans('messages.passwordConfirm'),
            
        ]; 
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error validate
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => 'Cần đăng nhập',
                'isLogin' => false,
            ], 200);
        }

        $checkCurrentPassword = Auth::attempt(['email' => $this->userLogin->email, 'password' => $request->passwordCurrent]);
        
        // mật khẩu hiện tại không đúng
        if (!$checkCurrentPassword) {
            return response()->json([
                'message' => 'password current is incorrect',
            ], 200);
        }

        //thành công, cập nhật lại mật khẩu
        $this->userLogin->update(['password' => bcrypt($request->newPassword)]);
        return response()->json([
            'message' => 'change password success',
        ], 200); 
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'newPassword'        => 'min:6|required',
            'newPasswordConfirm' => 'same:newPassword',
            'access_token'       => 'required',
        ];
        $messages = [
            'newPassword.min'         => trans('messages.passwordMin'),
            'newPassword.required'    => trans('messages.passwordRequired'),
            'newPasswordConfirm.same' => trans('messages.passwordConfirm'),
            'access_token.required'   => 'access_token may not be empty',
        ];
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error validate
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        $user = User::whereAccessToken($request->access_token)->first();

        // không tìm thấy access_token => thông báo 'mã access_token không đúng'
        if (!$user) {
            return response()->json('mã access_token không đúng', 200);
        }

        // thành công, cập nhật lại mật khẩu và reset access_token
        $user ->update([
            'password'     => bcrypt($request->newPassword),
            'access_token' => str_random(100),
        ]); 
        return response()->json('Update password success', 200);
    }

    //Lấy access_token - chức năng forgot password
    public function getAccessToken( Request $request)
    {   
        $user = User::whereEmail($request->email)->first();

        // không tìm được email => thông báo 'không tìm thấy email'
        if (!$user) {
            return response()->json('Email does not exist', 200);
        }

        // thành công, gửi access_token về email và thông báo
        Mail::send([], [], function($message) use ($user){
            $message->to($user->email)
                ->subject('Forgot password')
                ->setBody('Access token: ' . $user->access_token, 'text/html');
            });
        return response()->json('You need to enter the email to get the confirmation code', 200);
    }
}
