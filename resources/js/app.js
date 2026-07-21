import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import chatApp from './chat';
import pollCard from './polls';
import markdownEditor from './markdown-editor';
import webrtcRoom from './webrtc-room';
import './echo';

window.Alpine = Alpine;
window.chatApp = chatApp;
window.pollCard = pollCard;
window.markdownEditor = markdownEditor;
window.webrtcRoom = webrtcRoom;

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();
