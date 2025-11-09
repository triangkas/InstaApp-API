<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;

class PostController extends Controller
{   
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:5000',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ],[],[]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = $request->file('image')->store('posts', 'public');
        $post = new Post();
        $post->user_id = $user->id;
        $post->description = $request->description;
        $post->image = $imagePath;
        $post->save();

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post,
        ], 201);
    }

    public function list(Request $request)
    {
        $user = $request->user();

        $userTable        = (new User)->getTable();
        $postTable        = (new Post)->getTable();
        $postLikeTable    = (new PostLike)->getTable();
        $postCommentTable = (new PostComment)->getTable();

        $dataPost = Post::select("$postTable.*",
                    "$userTable.username as username",
                    DB::raw("COUNT(DISTINCT $postLikeTable.id) as likes_count"),
                    DB::raw("COUNT(DISTINCT $postCommentTable.id) as comments_count"),
                    DB::raw("MAX(CASE WHEN $postLikeTable.user_id = $user->id THEN 1 ELSE 0 END) as liked")
                )
                ->leftJoin($userTable, "$userTable.id", '=', "$postTable.user_id")
                ->leftJoin($postLikeTable, function($join) use ($postTable, $postLikeTable) {
                    $join->on("$postLikeTable.post_id", '=', "$postTable.id")
                         ->whereNull("$postLikeTable.deleted_at");
                })
                ->leftJoin($postCommentTable, function($join) use ($postTable, $postCommentTable) {
                    $join->on("$postCommentTable.post_id", '=', "$postTable.id")
                         ->whereNull("$postCommentTable.deleted_at");
                })
                ->when((request()->has('user') && request()->user), function ($query) use ($request, $postTable) {
                    $query->where("$postTable.user_id", $request->user);
                })
                ->groupBy("$postTable.id")
                ->get()
                ->map(function($post) {
                    $post->image_url = $post->image ? asset('storage/' . $post->image) : null;
                    return $post;
                });

        return response()->json([
            'message' => 'Get list successfully',
            'data' => $dataPost,
        ], 200);
    }

    public function commentList(Request $request)
    {
        $user = $request->user();

        $userTable        = (new User)->getTable();
        $postCommentTable = (new PostComment)->getTable();

        $dataComment = PostComment::select("$postCommentTable.*",
                            "$userTable.username as username"
                        )
                        ->leftJoin($userTable, "$userTable.id", '=', "$postCommentTable.user_id")
                        ->where("$postCommentTable.post_id", $request->post)
                        ->get();

        return response()->json([
            'message' => 'Get data successfully',
            'data' => $dataComment,
        ], 200);
    }

    public function storeComment(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:5000',
        ],[],[]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = new PostComment();
        $comment->user_id = $user->id;
        $comment->post_id = $request->idx;
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            'message' => 'Post comment successfully',
            'post' => $comment,
        ], 201);
    }

    public function likes(Request $request)
    {
        $user = $request->user();
        $postId = $request->post;

        // cek sudah pernah like atau belum
        $cekLikes = PostLike::where('post_id', $postId)->where('user_id', $user->id)->first();

        if(!empty($cekLikes)){
            $likes = $cekLikes->delete();
        } else {
            $likes = new PostLike();
            $likes->post_id = $postId;
            $likes->user_id = $user->id;
            $likes->save();
        }

        return response()->json([
            'message' => 'Like successfully',
            'post' => $likes,
        ], 201);
    }

    public function cekStatus(Request $request)
    {
        $user = $request->user();
        $postId = $request->post;
        
        // cek liked
        $liked = false;
        $like = PostLike::where('post_id', $postId)->where('user_id', $user->id)->first();
        if(!empty($like)){
            $liked = true;
        }

        // cek count like
        $countLike = PostLike::where('post_id', $postId)->count();

        // cek count comment
        $countComment = PostComment::where('post_id', $postId)->count();

        $data = [
            'liked' => $liked,
            'count_like' => $countLike,
            'count_comment' => $countComment,
        ];

        return response()->json([
            'message' => 'Like successfully',
            'data' => $data,
        ], 200);
    }
}
