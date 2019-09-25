<?php

namespace App\Observers;

use App\Jobs\SyncOnePostToES;
use App\Models\Post;
use App\Models\Tag;

class PostObserver
{
    /**
     * Post saving
     * @param Post $post
     */
    public function creating(Post $post)
    {
//        $post->content = clean($post->content, 'user_post_content'); //文章内容xss过滤
        if (empty($post->description)) {
            $post->description = make_description($post->content); //截取文章内容作为描述
        }

        //标签自动加超链接 存在bug
//        $tags = explode(',', $post->keyword);
//        foreach ($tags as $tag) {
//            $replace = '<a href="' . route('tag.show', $tag) . '" target="_blank">' . $tag . '</a>';
//            $post->content = str_replace($tag, $replace, $post->content);
//        }
    }

    /**
     * Post created
     * @param Post $post
     */
    public function created(Post $post)
    {
        clearCache();
    }

    /**
     * Post deleting
     * @param Post $post
     */
    public function deleted(Post $post)
    {
        if (!$post->trashed()) {
            $tag_ids = $post->tags->pluck('id')->all();
            //移除所有标签关联
            $post->tags()->detach();
            //todo 删除没有文章关联的标签
            foreach ($tag_ids as $k => $v) {
                $tag = Tag::find($v);
                if ($tag->posts->count() == 0) {
                    $tag->delete();
                }
            }
            //删除评论
            $post->comments()->delete();
        }
        clearCache();
    }
}
