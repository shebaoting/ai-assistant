import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Post from 'flarum/forum/components/Post';

app.initializers.add('shebaoting/ai-assistant', () => {

  extend(Post.prototype, 'view', function (vdom) {
    const aiUserId = 2; // 替换为您的 AI 用户 ID

    // 正确访问用户 ID，使用 user() 方法并调用 id()
    const author = this.attrs.post.user();
    const authorId = author ? author.id() : null;
    if (authorId == aiUserId) {
      console.log('你好')
      // 检查是否已经插入提示，避免重复插入
      if (!vdom.children.some(child => child.attrs && child.attrs.className == 'ai-reply-label')) {
        // 在帖子内容下方添加一个带有样式的标签
        vdom.children.push(
          m('div.TagLabel.colored.text-contrast--light.TagLabel--child', '此回复由AI生成')
        );
      }
    }
  });


});
