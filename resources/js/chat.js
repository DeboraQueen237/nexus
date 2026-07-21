/**
 * Composant Alpine.js pilotant toute l'interface de messagerie NEXUS :
 * liste des conversations, fenêtre active, envoi/réception en temps réel
 * via Reverb (Laravel Echo), indicateur de frappe, réactions, pièces
 * jointes, accusés de lecture, création de conversation.
 */
export default function chatApp(config) {
    return {
        currentUserId: config.currentUserId,
        conversations: config.conversations ?? [],
        active: config.activeConversation ?? null,
        messages: config.activeConversation?.messages ?? [],
        newMessage: '',
        pendingFile: null,
        sending: false,
        typingUsers: {}, // { userId: name }
        typingTimeout: null,
        echoChannel: null,
        showNewConversationModal: false,
        userSearchQuery: '',
        userSearchResults: [],
        groupMode: false,
        selectedGroupUsers: [],
        groupName: '',
        loadingConversation: false,
        readReceipts: {}, // { userId: isoDateString }
        openReactionPickerFor: null,
        availableReactions: ['👍', '❤️', '😂', '😮', '😢', '🙏'],

        init() {
            if (this.active) {
                this.subscribeToConversation(this.active.id);
                this.hydrateReadReceipts();
            }
            this.$watch('userSearchQuery', () => this.searchUsers());
        },

        hydrateReadReceipts() {
            this.readReceipts = {};
            (this.active?.participants ?? []).forEach((p) => {
                if (p.id !== this.currentUserId && p.last_read_at) {
                    this.readReceipts[p.id] = p.last_read_at;
                }
            });
        },

        // ==================== Sélection de conversation ====================

        async openConversation(id) {
            if (this.active?.id === id) return;

            this.loadingConversation = true;
            this.unsubscribeFromCurrent();

            try {
                const res = await fetch(`/chat/conversation/${id}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('Impossible de charger la conversation');
                const data = await res.json();

                this.active = data;
                this.messages = data.messages;
                this.subscribeToConversation(id);
                this.hydrateReadReceipts();

                const conv = this.conversations.find((c) => c.id === id);
                if (conv) conv.unread_count = 0;

                const url = new URL(window.location);
                url.searchParams.set('c', id);
                window.history.pushState({}, '', url);
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingConversation = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        subscribeToConversation(id) {
            if (!window.Echo) return;

            this.echoChannel = window.Echo.private(`conversation.${id}`)
                .listen('.message.sent', (payload) => {
                    if (this.active?.id !== id) return;
                    this.messages.push(payload);
                    delete this.typingUsers[payload.user.id];
                    this.$nextTick(() => this.scrollToBottom());
                    this.bumpConversationPreview(id, payload);
                })
                .listen('.user.typing', (payload) => {
                    if (payload.user_id === this.currentUserId) return;
                    if (payload.is_typing) {
                        this.typingUsers[payload.user_id] = payload.name;
                    } else {
                        delete this.typingUsers[payload.user_id];
                    }
                })
                .listen('.message.reacted', (payload) => {
                    const msg = this.messages.find((m) => m.id === payload.message_id);
                    if (msg) msg.reactions = payload.reactions;
                })
                .listen('.conversation.read', (payload) => {
                    if (payload.user_id === this.currentUserId) return;
                    this.readReceipts[payload.user_id] = payload.read_at;
                });
        },

        unsubscribeFromCurrent() {
            if (this.active && window.Echo) {
                window.Echo.leave(`conversation.${this.active.id}`);
            }
            this.typingUsers = {};
        },

        bumpConversationPreview(id, message) {
            const conv = this.conversations.find((c) => c.id === id);
            if (conv) {
                conv.last_message = message.content || (message.attachment ? '📎 Pièce jointe' : '');
                conv.last_message_at = "à l'instant";
                if (this.active?.id !== id) {
                    conv.unread_count = (conv.unread_count ?? 0) + 1;
                }
                this.conversations = [conv, ...this.conversations.filter((c) => c.id !== id)];
            }
        },

        // ==================== Pièce jointe ====================

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                alert('Fichier trop volumineux (10 Mo max).');
                event.target.value = '';
                return;
            }
            this.pendingFile = file;
        },

        clearPendingFile() {
            this.pendingFile = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        // ==================== Envoi de message ====================

        async sendMessage() {
            const content = this.newMessage.trim();
            if ((!content && !this.pendingFile) || this.sending || !this.active) return;

            this.sending = true;
            this.notifyTyping(false);

            const tempId = `temp-${Date.now()}`;
            const optimisticMessage = {
                id: tempId,
                content,
                type: this.pendingFile ? (this.pendingFile.type.startsWith('image/') ? 'image' : 'file') : 'text',
                attachment: this.pendingFile ? { name: this.pendingFile.name, is_image: this.pendingFile.type.startsWith('image/'), url: this.pendingFile.type.startsWith('image/') ? URL.createObjectURL(this.pendingFile) : null } : null,
                reactions: [],
                created_at_human: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
                user: { id: this.currentUserId, name: 'Moi', initial: '•' },
                pending: true,
            };
            this.messages.push(optimisticMessage);
            this.newMessage = '';
            const fileToSend = this.pendingFile;
            this.clearPendingFile();
            this.$nextTick(() => this.scrollToBottom());

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                let res;

                if (fileToSend) {
                    const form = new FormData();
                    form.append('conversation_id', this.active.id);
                    form.append('content', content);
                    form.append('attachment', fileToSend);
                    res = await fetch('/chat/message', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: form,
                    });
                } else {
                    res = await fetch('/chat/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ conversation_id: this.active.id, content }),
                    });
                }

                if (!res.ok) throw new Error('Envoi impossible');
                const data = await res.json();

                const idx = this.messages.findIndex((m) => m.id === tempId);
                if (idx !== -1) this.messages[idx] = data.message;

                this.bumpConversationPreview(this.active.id, data.message);
            } catch (e) {
                const idx = this.messages.findIndex((m) => m.id === tempId);
                if (idx !== -1) this.messages[idx].failed = true;
                console.error(e);
            } finally {
                this.sending = false;
            }
        },

        // ==================== Réactions ====================

        async toggleReaction(message, emoji) {
            this.openReactionPickerFor = null;
            try {
                const res = await fetch(`/chat/message/${message.id}/react`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ emoji }),
                });
                if (!res.ok) return;
                const data = await res.json();
                const msg = this.messages.find((m) => m.id === message.id);
                if (msg) msg.reactions = data.reactions;
            } catch (e) {
                console.error(e);
            }
        },

        wasReadByOthers(message) {
            if (!this.isMine(message)) return false;
            const messageTime = new Date(message.created_at).getTime();
            return Object.values(this.readReceipts).some((readAt) => new Date(readAt).getTime() >= messageTime);
        },

        isLastMineMessage(message) {
            const mine = this.messages.filter((m) => this.isMine(m));
            return mine.length > 0 && mine[mine.length - 1].id === message.id;
        },

        // ==================== Indicateur de frappe ====================

        onTyping() {
            this.notifyTyping(true);
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => this.notifyTyping(false), 2000);
        },

        notifyTyping(isTyping) {
            if (!this.active) return;
            fetch('/chat/typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ conversation_id: this.active.id, is_typing: isTyping }),
                keepalive: true,
            }).catch(() => {});
        },

        get typingLabel() {
            const names = Object.values(this.typingUsers);
            if (names.length === 0) return '';
            if (names.length === 1) return `${names[0]} est en train d'écrire...`;
            return `${names.join(', ')} sont en train d'écrire...`;
        },

        // ==================== Nouvelle conversation ====================

        openNewConversationModal() {
            this.showNewConversationModal = true;
            this.groupMode = false;
            this.userSearchQuery = '';
            this.userSearchResults = [];
            this.selectedGroupUsers = [];
            this.groupName = '';
        },

        async searchUsers() {
            if (this.userSearchQuery.trim().length === 0) {
                this.userSearchResults = [];
                return;
            }
            const res = await fetch(`/chat/users/search?q=${encodeURIComponent(this.userSearchQuery)}`, {
                headers: { Accept: 'application/json' },
            });
            this.userSearchResults = res.ok ? await res.json() : [];
        },

        toggleGroupUser(user) {
            const idx = this.selectedGroupUsers.findIndex((u) => u.id === user.id);
            if (idx === -1) {
                this.selectedGroupUsers.push(user);
            } else {
                this.selectedGroupUsers.splice(idx, 1);
            }
        },

        isSelected(user) {
            return this.selectedGroupUsers.some((u) => u.id === user.id);
        },

        async startPrivateConversation(user) {
            await this.createConversation({ type: 'private', participant_id: user.id });
        },

        async createGroupConversation() {
            if (!this.groupName.trim() || this.selectedGroupUsers.length === 0) return;
            await this.createConversation({
                type: 'group',
                name: this.groupName,
                participants: this.selectedGroupUsers.map((u) => u.id),
            });
        },

        async createConversation(payload) {
            try {
                const res = await fetch('/chat/conversation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error('Création impossible');
                const data = await res.json();

                this.showNewConversationModal = false;

                const listRes = await fetch('/chat?json=1', { headers: { Accept: 'application/json' } });
                if (listRes.ok) {
                    const listData = await listRes.json();
                    if (listData.conversations) this.conversations = listData.conversations;
                }

                await this.openConversation(data.conversation_id);
            } catch (e) {
                console.error(e);
            }
        },

        // ==================== Utilitaires ====================

        scrollToBottom() {
            const el = this.$refs.messagesContainer;
            if (el) el.scrollTop = el.scrollHeight;
        },

        isMine(message) {
            return message.user.id === this.currentUserId;
        },

        formatFileSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return `${bytes} o`;
            if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} Ko`;
            return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
        },
    };
}
