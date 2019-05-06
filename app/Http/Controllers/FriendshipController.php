<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Friendship;

class FriendshipController extends Controller
{
    private $userLogin;

    function __construct(Request $request)
    {
        if ($request->header('access-token')) {
            $this->userLogin = User::whereAccessToken($request->header('access-token'))->first();
        }
    }

    /* API get List Friend */
    public function getListFriend(Request $request)
    {
        // chưa đăng nhập => return isLogin là false
        if (!$this->userLogin) {
            return response()->json([
                'isLogin'  => false,
            ], 200);
        }

        // đã đăng nhập => return listFriend
        $idsFriend = Friendship::getIdFriend($this->userLogin->id);
        $listFriend = User::findUserActive($idsFriend);

        return response()->json([
            'listFriend' => $listFriend,
        ], 200);
    }

    public function getListBlocked(Request $request)
    {
        // chưa đăng nhập => return isLogin là false
        if (!$this->userLogin) {
            return response()->json([
                'isLogin'  => false,
            ], 200);
        }

        // đã đăng nhập => return listBlocked
        $idsBlocked = Friendship::getIdUserBlock($this->userLogin->id);
        $listBlocked = User::findUserActive($idsBlocked);

        return response()->json([
            'listBlocked' => $listBlocked,
        ], 200);
    }

    public function addFriend(Request $request)
    {
        // chưa đăng nhập => return error: 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.messageNeedLogin'),
                'isLogin' => false,
            ], 200);
        }

        // nếu user chưa active hoặc ko tìm thấy user => return error: 'không tồn tại user'
        $isUserActive = User::findUserActive($request->id);
        if (!$isUserActive) {
            return response()->json([
                'message' => trans('messages.messageUserNameNotExit'),
            ], 200);
        }

        // nếu user đang đăng nhập có id trùng với id của user truyền vào => return error: 'không được thêm bạn với chính mình'
        if ($request->id === $this->userLogin->id) {
            return response()->json([
                'message' => trans('messages.checkFriendYourself'),
            ], 200);
        }
 
        // nếu đã tồn tại mối quan hệ trong bảng friendships(invite, friend, block) => return error: 'đã tồn tại mối quan hệ'
        $isRelationship = Friendship::checkExistRelationship($this->userLogin->id, $request->id)->count();
        if ($isRelationship) {
            return response()->json([
                'message' => trans('messages.messageExistRelationship'),
            ], 200);
        } 

        // thành công, tạo mối quan hệ mới
        $request->merge([
            'user_id'   => $this->userLogin->id,
            'friend_id' => $request->id,
            'status'    => 0,
        ]);
        FriendShip::create($request->all());

        return response()->json([
            'message' => trans('messages.messageAddFriendSuccess'),
            'flag'    => true,
        ], 200);
    }

    public function addBlocked(Request $request)
    {
        // chưa đăng nhập => return error: 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.messageNeedLogin'),
                'isLogin' => false,
            ], 200);
        }

        // nếu user chưa active hoặc ko tìm thấy user => return error: 'không tồn tại user'
        $isUserActive = User::findUserActive($request->id);
        if (!$isUserActive) {
            return response()->json([
                'message' => trans('messages.messageUserNameNotExit'),
            ], 200);
        }

        // nếu user đang đăng nhập có id trùng với id của user truyền vào => return error: 'không được block chính mình'
        if ($request->id === $this->userLogin->id) {
            return response()->json([
                'message' => 'không được block chính mình',
            ], 200);
        }
 
        // nếu đã tồn tại mối quan hệ blocked trong bảng friendships => return error: 'bạn không thể block user này'
        $isBlocked = Friendship::checkIsBlocked($this->userLogin->id, $request->id)->count();
        if ($isBlocked) {
            return response()->json([
                'message' => 'bạn không thể block user này',
            ], 200);
        }

        // thành công, tạo mối quan hệ (blocked) mới
        $request->merge([
            'user_id'   => $this->userLogin->id,
            'friend_id' => $request->id,
            'status'    => 2,
            'blocked'   => 1,
        ]);
        FriendShip::create($request->all());

        return response()->json([
            'message' => 'Block user thành công',
            'flag'    => true,
        ], 200);
    }

    public function accept(Request $request, $id)
    {
        // chưa đăng nhập => return error: 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.messageNeedLogin'),
                'isLogin' => false,
            ], 200);
        }

        // không tồn tại lời mời kết bạn
        $isInvite = FriendShip::where([['user_id', $id], ['friend_id', $this->userLogin->id], ['status', 0]])->first();
        if ($isInvite) {
            return response()->json([
                'message' => 'không tồn tại lời mời kết bạn',
            ], 200);
        }

        // thành công, cập nhật status = 1 (mối quan hệ bạn bè)
        $isInvite->update(['status' => 1]);
        return response()->json([
            'message' => 'Xác nhận kết bạn thành công',
            'flag'    => true,
        ], 200);
    }

    public function reject(Request $request, $id)
    {
        // chưa đăng nhập => return error: 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.messageNeedLogin'),
                'isLogin' => false,
            ], 200);
        }

        // không tồn tại lời mời kết bạn
        $isInvite = FriendShip::where([['user_id', $id], ['friend_id', $this->userLogin->id], ['status', 0]])->first();
        if ($isInvite) {
            return response()->json([
                'message' => 'không tồn tại lời mời kết bạn',
            ], 200);
        }

        // thành công, xóa bản ghi đó
        $isInvite->delete();
        return response()->json([
            'message' => 'Từ chối thành công',
            'flag'    => true,
        ], 200);
    }

    public function deleteFriend($id)
    {
        // chưa đăng nhập => return error: 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.messageNeedLogin'),
                'isLogin' => false,
            ], 200);
        }

        // chưa là bạn bè => return error: 'không có quyền xóa'
        $checkIsFriend = FriendShip::checkIsFriend($this->userLogin->id, $id)->first();
        if (!$checkIsFriend) {
            return response()->json([
                'message' => trans('messages.messageNotPermission'),
            ], 200);
        }

        // xóa thành công
        $checkIsDelete->delete();
        return response()->json([
            'message' => trans('messages.messageDeleteSuccess'),
            'flag'    => true,
        ], 200);
    }
}
