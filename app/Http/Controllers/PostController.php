<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\Image;
use App\Models\User;
use App\Models\Comment;

class PostController extends Controller
{
    private $userLogin;

    function __construct(Request $request)
    {
        if ($request->header('access-token')) {
            $this->userLogin = User::whereAccessToken($request->header('access-token'))->first();
        }
    }

    public function store(Request $request)
    {
        $flag = false;

        // chưa đăng nhập => return biến isLogin = false và thông báo 'cần login'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        // kiểm tra điều kiện được post => post cho bản thân hoặc post cho bạn bè
        if ($request->target_id === $this->userLogin->id) {
            $flag = true;
        } else {
            $flag = Friendship::checkIsFriend($this->userLogin->id, $request->target_id)->count() && true;
        }

        // mục tiêu post không phải là bản thân hoặc là bạn => thông báo 'không có quyền post'
        if ($flag === false) {
            return response()->json([
                'message' => trans('messages.noPermissionPost'),
            ], 200);
        }

        // không gửi file lên => thành công, tạo bài post mới => return bài post và full_name của user login
        if (!$request->get('fileImage')) {
            $request->merge([
                'author_id' => $this->userLogin->id,
                'status'    => 0,
            ]);
            $post = Post::create($request->all());
            $post = Post::with('author')->find($post->id); // lấy ra full_name author

            return response()->json([
                'message'       => 'thêm bài post thành công',
                'post'          => $post,
                'fullnameLogin' => $this->userLogin->full_name,
            ], 200);
        }

        // gửi file lên => kiểm tra file có phải là hình ảnh?
        $image = $request->get('fileImage');
        $ext = explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1];

        // file không phải là ảnh => return error: 'cần gửi file ảnh'
        if (!($ext === 'jpeg' || $ext === 'jpg' || $ext === 'png')) {
            return $response()->json([
                'message' => 'Vui lòng chọn lại hình ảnh',
            ], 200);
        }

        // file là ảnh => thêm bài post mới, tạo ảnh trong thư mục /images và thêm vào database
        $name = time() . '.' . $ext;
        \Image::make($request->get('fileImage'))->save(public_path('images/') . $name);
        $request->merge([
            'author_id' => $this->userLogin->id,
            'status'    => 0,
        ]);
        $post = Post::create($request->all());
        $image = Image::create(['post_id' => $post->id, 'path' => $name]);
        
        $post = Post::with('author', 'image')->find($post->id); // lấy ra full_name author và image của bài post
        return response()->json([
            'message'       => 'thêm bài post thành công',
            'post'          => $post,
            'fullnameLogin' => $this->userLogin->full_name,
        ], 200);
    }

    public function share(Request $request, $id)
    {
        $flag = false;

        // chưa đăng nhập => return biến isLogin = false và thông báo 'cần login'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        $post = Post::find($id);
        // không tồn tại bài post => return 'không tồn tại bài post'
        if (!$post) {
            return response()->json([
                'message' => 'không tồn tại bài post',
            ], 200);
        }

        // bài post không ở chế độ xem 'tất cả mọi người' => return 'không được share bài post này'
        if ($post->status !== 0) {
            return response()->json([
                'message' => 'không được share bài post này',
            ], 200);
        }

        // thành công => tạo bài post (chia sẻ) mới
        // nếu bài post là bài chia sẻ => tìm bài post gốc
        if ($post->parent_id !== 0) {
            $post = Post::find($post->parent_id);
        }

        $request->merge([
            'author_id' => $this->userLogin->id,
            'target_id' => $this->userLogin->id,
            'content'   => $post,
            'parent_id' => $post->id,
            'status'    => 0,
        ]);
        $share = Post::create($request->all());

        return response()->json([
            'message'       => 'share bài post thành công',
            'share'         => $share,
            'fullnameLogin' => $this->userLogin->full_name,
        ], 200);
    }

    public function show($id)
    {
        $infoPost = [];
        $post = Post::findById($id);
    
        // không tồn tại bài post => return mảng rỗng
        if (!$post) {
            return response()->json($infoPost, 200);
        }

        // tìm thấy bài post => return thông tin bài post và các comment của bài post đó
        $infoPost = $post->toArray();
        foreach ($post->comment as $comment) {
            $cmt = $comment->toArray();
            $cmt['email_comment'] = $comment->user->email; 
            $infoPost['comment'][] = $cmt;
        }

        return response()->json($infoPost, 200);
    }

    public function destroy(Request $request, $id)
    {
        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        $post = Post::find($id);
        // không tồn tại bài post cần xóa => thông báo 'không tồn tại bài post'
        if (!$post) {
            return response()->json([
                'message' => 'không tồn tại bài post cần xóa',
            ], 200);
        }

        // id của user đăng nhập khác với target_id trong bảng posts => thông báo 'không có quyền xóa'
        if ($this->userLogin->id !== $post->target_id) {
            return response()->json([
                'message' => 'bạn không có quyền xóa bài post này',
            ], 200);
        }

        // Xóa thành công
        $post->delete();
        return response()->json([
            'message' => 'Xóa thành công',
        ], 200);
    }
}
