import app from 'flarum/admin/app';

app.initializers.add('shebaoting/ai-assistant', () => {
  app.extensionData
        .for('shebaoting-ai-assistant')
        .registerSetting({
            setting: 'shebaoting-ai-assistant.api_key',
            label: 'API Key',
            type: 'text',
        })
        .registerSetting({
            setting: 'shebaoting-ai-assistant.model',
            label: '模型',
            type: 'text',
            placeholder: '请输入模型ID',
        })
        .registerSetting({
            setting: 'shebaoting-ai-assistant.system_prompt',
            label: '系统提示词',
            type: 'textarea',
            placeholder: '你是豆包，是由字节跳动开发的 AI 人工智能助手.',
        })
        .registerSetting({
            setting: 'shebaoting-ai-assistant.style',
            label: '回复风格',
            type: 'select',
            options: {
                formal: '正式',
                humorous: '幽默',
            },
            default: 'formal',
        });
});
