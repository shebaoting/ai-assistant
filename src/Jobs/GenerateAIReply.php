<?php

namespace Shebaoting\AIAssistant\Jobs;

use Flarum\Post\Command\PostReply;
use Flarum\User\UserRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GenerateAIReply implements ShouldQueue
{
    use InteractsWithQueue;

    public $discussionId;
    public $content;
    public $queue; // 添加 queue 属性

    public function __construct(int $discussionId, string $content)
    {
        $this->discussionId = $discussionId;
        $this->content = $content;
    }

    public function handle(
        Dispatcher $bus,
        UserRepository $users,
        SettingsRepositoryInterface $settings
    ) {
        // 获取设置
        $apiKey = $settings->get('shebaoting-ai-assistant.api_key');
        $model = $settings->get('shebaoting-ai-assistant.model');
        $systemPrompt = $settings->get('shebaoting-ai-assistant.system_prompt');
        $style = $settings->get('shebaoting-ai-assistant.style', 'formal');

        // 调用 AI 接口获取回复
        $aiResponse = $this->getAIResponse($this->content, $apiKey, $model, $systemPrompt, $style);

        if ($aiResponse) {
            // 创建 AI 回复帖子
            $this->createAIReply($bus, $users, $aiResponse);
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

        $client = new Client();

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

    protected function createAIReply(Dispatcher $bus, UserRepository $users, $content)
    {
        // 获取或创建 AI 用户
        $aiUser = $users->findOrFail(993); // 确保用户 ID 正确，或更改为专用 AI 用户 ID

        // 为回复内容添加 AI 标识（可选，现已通过前端添加标签）
        // $content .= "\n\n*此回复由AI生成*";

        // 创建 PostReply 命令
        $command = new PostReply($this->discussionId, $aiUser, [
            'attributes' => [
                'content' => $content,
            ],
        ]);

        // 派发命令，创建回复
        $bus->dispatch($command);
    }
}
