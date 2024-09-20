<?php

namespace Shebaoting\AIAssistant\Listeners;

use Flarum\Discussion\Event\Started;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Shebaoting\AIAssistant\Jobs\GenerateAIReply;
use Flarum\Post\Post;

class DiscussionStartedListener
{
    protected $bus;

    public function __construct(BusDispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function handle(Started $event)
    {
        $discussion = $event->discussion;

        // 显式加载首个帖子
        $firstPost = Post::where('discussion_id', $discussion->id)
            ->where('number', 1)
            ->first();

        if ($firstPost && strpos($firstPost->content, '@乌鸦') !== false) {
            $content = str_replace('@乌鸦', '', $firstPost->content);

            // 分发队列任务
            $this->bus->dispatch(new GenerateAIReply($discussion->id, $content));
        }
    }
}
