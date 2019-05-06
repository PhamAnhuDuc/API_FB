<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Helpers\CommentHelper;

class CommentController extends Controller
{
    private $userLogin;

    function __construct(Request $request)
    {
        if ($request->header('access-token')) {
            $this->userLogin = User::whereAccessToken($request->header('access-token'))->first();
        }
    }
    
    public function getListComment(Request $request, $postId)
    {
        $commentHelper = new CommentHelper();
        $commentHelper->getCommentPost($postId);

        dd(json_encode($commentHelper->comments));

        foreach ($commentHelper->comments as $comment) {
            if ($comment->index === 0) {
                echo '<b>' . $comment->user->full_name . '</b>: ' . $comment->content.'<br>';
            }
            if ($comment->index === 1) {
                echo '___<b>' . $comment->user->full_name . '</b>: ' . $comment->content.'<br>';
            }
            if ($comment->index === 2) {
                echo '______<b>' . $comment->user->full_name . '</b>: ' . $comment->content.'<br>';
            }
            if ($comment->index === 3) {
                echo '_________<b>' . $comment->user->full_name . '</b>: ' . $comment->content.'<br>';
            }
            if ($comment->index === 4) {
                echo '____________<b>' . $comment->user->full_name . '</b>: ' . $comment->content.'<br>';
            }
            
        }
        // dd(microtime(true) - LARAVEL_START);
    }

    //API comment and reply
    public function store(Request $request)
    {
        $rules = [
            'content' => 'required'
        ];
        $messages = [
            'content.required' => trans('messages.checkContentRequired')
        ]; 
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        $post = Post::find($request->post_id);
        // không tồn tại bài post => thông báo 'không có bài post'
        if (!$post) {
            return response()->json([
                'message' => trans('messages.noPostsFound'),
            ], 200);
        }

        // nếu có parent_id
        if ($request->parent_id) {
            $checkIsComment = Comment::find($request->parent_id);

            // không tồn tại comment có id = parent_id truyền vào => thông báo lỗi
            if (!$checkIsComment) {
                return response()->json([
                    'message' => 'Không tồn tại comment',
                ], 200);
            }
        }

        // thành công => tạo comment mới
        $request->merge(['user_id' => $this->userLogin->id]);
        $comment = Comment::create($request->all());

        $comment['email_comment'] = $comment->user->full_name;
        return response()->json([
            'message' => trans('messages.commentSuccess'),
            'comment' => $comment,
        ], 200);
    }

    // API edit comment or reply
    public function update(Request $request, $id)
    {
        $rules = [
            'content' => 'required'
        ];
        $messages = [
            'content.required' => trans('messages.checkContentRequired')
        ]; 
        $validator = \Validator::make($request->all(), $rules, $messages);

        // lỗi validate form => return error
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 200);
        }

        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        $comment = Comment::find($id);
        // không tồn tại comment cần sửa => thông báo 'không tồn tại comment'
        if (!$comment) {
            return response()->json([
                'message' => 'không tồn tại comment cần sửa',
            ], 200);
        }

        // id của user đăng nhập khác với user_id trong bảng comments => thông báo 'không có quyền sửa'
        if ($this->userLogin->id !== $comment->user_id) {
            return response()->json([
                'message' => 'bạn không có quyền sửa comment này',
            ], 200);
        }

        // thành công => cập nhật lại content của comment
        $comment->update($request->only('content'));

        $comment['email_comment'] = $comment->user->full_name;
        return response()->json([
            'message' => trans('messages.commentSuccess'),
            'comment' => $comment,
        ], 200);
    }

    // API delete comment or reply
    public function destroy(Request $request, $id)
    {
        // chưa đăng nhập => thông báo 'cần đăng nhập'
        if (!$this->userLogin) {
            return response()->json([
                'message' => trans('messages.needLogin'),
                'isLogin' => false,
            ], 200);
        }

        $comment = Comment::find($id);
        // không tồn tại comment cần xóa => thông báo 'không tồn tại comment'
        if (!$comment) {
            return response()->json([
                'message' => 'không tồn tại comment cần xóa',
            ], 200);
        }

        // id của user đăng nhập khác với user_id trong bảng comments => thông báo 'không có quyền xóa'
        if ($this->userLogin->id !== $comment->user_id) {
            return response()->json([
                'message' => 'bạn không có quyền xóa comment này',
            ], 200);
        }

        // Xóa thành công
        $commentHelper = new CommentHelper();
        $commentHelper->getSubIdsComment($comment->id);
        Comment::whereIn('id', $commentHelper->idsComment)->delete();

        return response()->json([
            'message' => 'Xóa thành công',
            'flag'    => true,
        ], 200);
    }
}
