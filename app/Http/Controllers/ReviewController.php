<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReview;
use CacheUser;
use App\Models\Thread;
use App\Models\Post;
use Auth;
use Carbon;
use App\Models\Review;
use App\Events\NewPost;
use App\Sosadfun\Traits\ThreadObjectTraits;
use App\Sosadfun\Traits\PostObjectTraits;

class ReviewController extends Controller
{
    use ThreadObjectTraits;
    use PostObjectTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create($id)
    {
        $thread = Thread::find($id);
        $channel = $thread->channel();
        if(!$channel||!$thread||$channel->type!='list'||$thread->is_locked){
            abort(403);
        }
        return view('reviews.create', compact('thread'));
    }

    public function store($id, StoreReview $form)
    {
        $thread = Thread::find($id);
        if ($thread->is_locked||$thread->user_id!=Auth::id()){
            abort(403);
        }

        $post = $form->generateReview($thread);

        event(new NewPost($post));

        $this->clearAllThread($thread->id);

        if($post->checklongcomment()){
            $this->user->reward('long_post');
            return redirect()->route('post.show', $post->id)->with('success', '您得到了长评奖励');
        }
        $post->user->reward("regular_post");
        return redirect()->route('post.show', $post->id)->with('success', '您已成功发布书评');
    }

    public function update($id, StoreReview $form)
    {
        $post = Post::find($id);
        $review = $post->review;
        $thread = $post->thread;

        if(!$post||!$review||!$thread||($thread->is_locked&&!Auth::user()->isAdmin())){
            abort(403);
        }

        $post = $form->updateReview($post, $thread);
        $thread->recalculate_characters();
        $this->clearPostProfile($id);
        $this->clearAllThread($thread->id);

        return redirect()->route('post.show', $id)->with('success','已经成功更新书评');
    }

    public function turn_to_review($id)
    {
        $post = Post::find($id);
        $thread=$post->thread;
        $channel=$thread->channel();
        if($post->user_id!=Auth::id()){abort(403);}
        if(($thread->is_locked||!$thread->channel()->allow_edit)&&(!Auth::user()->isAdmin())){abort(403);}
        if($post->review){abort(409);}

        $review = Review::create(['post_id'=>$post->id]);
        $post->type = 'review';
        $post->reply_to_id = 0;
        $post->reply_to_brief = '';
        $post->reply_to_position = 0;
        $post->edited_at = Carbon::now();
        $post->save();

        return view('reviews.edit', compact('review','post','thread'));
    }
}
