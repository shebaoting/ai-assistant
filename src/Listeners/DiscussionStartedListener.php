<?php

namespace Shebaoting\AIAssistant\Listeners;

use Flarum\Discussion\Event\Started;
use Flarum\Post\Command\PostReply;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Flarum\User\UserRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Post\Post;

class DiscussionStartedListener
{
    protected $bus;
    protected $users;
    protected $settings;

    public function __construct(BusDispatcher $bus, UserRepository $users, SettingsRepositoryInterface $settings)
    {
        $this->bus = $bus;
        $this->users = $users;
        $this->settings = $settings;
    }

    public function handle(Started $event)
    {
        $discussion = $event->discussion;

        // 显式加载首个帖子
        $firstPost = Post::where('discussion_id', $discussion->id)
            ->where('number', 1)
            ->first();

        if ($firstPost && strpos($firstPost->content, '@小乌鸦') !== false) {
            $content = str_replace('@小乌鸦', '', $firstPost->content);

            $apiKey = $this->settings->get('shebaoting-ai-assistant.api_key');
            $model = $this->settings->get('shebaoting-ai-assistant.model');
            $systemPrompt = $this->settings->get('shebaoting-ai-assistant.system_prompt');
            $style = $this->settings->get('shebaoting-ai-assistant.style', 'formal');

            $aiResponse = $this->getAIResponse($content, $apiKey, $model, $systemPrompt, $style);

            if ($aiResponse) {
                $this->createAIReply($discussion->id, $aiResponse);
            }
        }
    }

    protected function getAIResponse($content, $apiKey, $model, $systemPrompt, $style)
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $content],
            ],
        ];

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post('https://ark.cn-beijing.volces.com/api/v3/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => $payload,
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                return $responseBody['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            // 使用正确的日志记录方式
            app('log')->error('AI Assistant error: ' . $e->getMessage());
        }

        return null;
    }

    protected function createAIReply($discussionId, $content)
    {
        $aiUser = $this->users->findOrFail(1); // 使用您指定的 AI 用户 ID

        $content .= "\n\n*此回复由AI生成*";

        $command = new PostReply($discussionId, $aiUser, [
            'attributes' => [
                'content' => $content,
            ],
        ]);

        $this->bus->dispatch($command);
    }
}
