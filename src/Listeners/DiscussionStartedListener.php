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
        $firstPost = $discussion->firstPost; // 通常可以直接获取，Flarum 会处理加载

        if ($firstPost) {
            $mentionedUsers = $firstPost->mentionsUsers; // 获取被提及的用户集合
            $shouldTriggerAi = false;
            $targetUserId = 993;

            foreach ($mentionedUsers as $mentionedUser) {
                if ($mentionedUser->id == $targetUserId) {
                    $shouldTriggerAi = true;
                    break;
                }
            }

            if ($shouldTriggerAi) {
                $content = preg_replace('/<USERMENTION[^>]*id="' . $targetUserId . '"[^>]*>@[^<]+<\/USERMENTION>\s*/i', '', $firstPost->content);
                $content = trim($content);
                if (!empty($content)) {
                    $this->bus->dispatch(new GenerateAIReply($discussion->id, $content));
                }
            }
        }
    }
}
