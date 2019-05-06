<?php

namespace App\Helpers;

use App\Models\Comment;
 
class CommentHelper
{
    public $comments = [];
    public $idsComment = [];

    public function getCommentPost($postId)
    {
    	$rootComments = Comment::wherePostId($postId)->with('user')->get();
        foreach ($rootComments as $rootComment) {
            if ($rootComment->parent_id === 0) {
                $this->getSubComment($rootComment, 0);
            }
        }

        return $this->comments;
    }

    public function getSubIdsComment($commentId)
    {
    	$this->getSubIdComment($commentId);
    }
    
    // đệ quy tìm các thông tin reply của comment
    public function getSubComment($comment, $index = 0)
    {
        $comment->index = $index;
        $this->comments[] = $comment;
        $replies = Comment::where('parent_id', $comment->id)->get();
        
        if (count($replies)) {
            foreach ($replies as $reply) {
                $this->getSubComment($reply, ++$index);
                $index--;
            }
        } else {
            return $this->comments;
        }
    }

    // đệ quy tìm các id reply của comment
    public function getSubIdComment($id)
    {
        $this->idsComment[] = $id;
        $replies = Comment::where('parent_id', $id)->get();
        
        if (count($replies)) {
            foreach ($replies as $reply) {
                $this->getSubIdsComment($reply->id);
            }
        } else {
            return $this->idsComment;
        }
    }
}
