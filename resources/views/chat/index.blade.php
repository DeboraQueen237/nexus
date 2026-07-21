@extends('layouts.app')

@section('title', 'Messagerie - NEXUS')

@section('content')
<div
    x-data="chatApp({
        currentUserId: {{ auth()->id() }},
        conversations: @js($conversations),
        activeConversation: @js($activeConversation),
    })"
    x-init="init()"
    class="flex h-[calc(100vh-4rem)] overflow-hidden"
>
    {{-- ===== Colonne conversations ===== --}}
    <aside class="flex w-full max-w-xs shrink-0 flex-col border-r border-gray-100 bg-white dark:border-surface-800 dark:bg-surface-900" :class="active ? 'hidden sm:flex' : 'flex'">
        <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-surface-800">
            <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">Messagerie</h1>
            <button @click="openNewConversationModal()" class="btn-icon" aria-label="Nouvelle conversation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="conversations.length === 0">
                <p class="p-6 text-center text-sm text-gray-400">Aucune conversation. Lance-toi !</p>
            </template>

            <template x-for="conv in conversations" :key="conv.id">
                <button @click="openConversation(conv.id)" type="button"
                    class="flex w-full items-center gap-3 border-b border-gray-50 p-4 text-left transition hover:bg-gray-50 dark:border-surface-800/60 dark:hover:bg-surface-800/50"
                    :class="active && active.id === conv.id ? 'bg-primary-50 dark:bg-primary-900/20' : ''">
                    <div class="avatar" x-text="conv.avatar_initial"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="conv.display_name"></p>
                            <span class="shrink-0 text-xs text-gray-400" x-text="conv.last_message_at"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs text-gray-500" x-text="conv.last_message || 'Aucun message'"></p>
                            <span x-show="conv.unread_count > 0" x-text="conv.unread_count" class="flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-primary-600 px-1.5 text-[10px] font-bold text-white"></span>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </aside>

    {{-- ===== Fenêtre de conversation active ===== --}}
    <section class="flex flex-1 flex-col" :class="active ? 'flex' : 'hidden sm:flex'">
        <template x-if="!active">
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/40 dark:to-secondary-900/40">💬</div>
                <p class="text-gray-500">Sélectionne une conversation pour commencer à discuter</p>
            </div>
        </template>

        <template x-if="active">
            <div class="flex flex-1 flex-col overflow-hidden">
                {{-- En-tête --}}
                <div class="flex items-center gap-3 border-b border-gray-100 p-4 dark:border-surface-800">
                    <button @click="unsubscribeFromCurrent(); active = null" class="btn-icon sm:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <div class="avatar" x-text="(active.name || '?').charAt(0).toUpperCase()"></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-gray-800 dark:text-gray-100" x-text="active.name"></p>
                        <p class="h-4 text-xs text-primary-600" x-text="typingLabel"></p>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="flex-1 space-y-3 overflow-y-auto p-4" x-ref="messagesContainer">
                    <template x-for="message in messages" :key="message.id">
                        <div>
                            <div class="group flex items-end gap-1" :class="isMine(message) ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[75%] rounded-2xl px-4 py-2 text-sm"
                                     :class="isMine(message)
                                        ? 'bg-gradient-to-r from-primary-600 to-secondary-600 text-white rounded-br-sm'
                                        : 'bg-gray-100 text-gray-800 dark:bg-surface-800 dark:text-gray-100 rounded-bl-sm'">
                                    <p class="text-[11px] font-semibold opacity-70" x-show="!isMine(message)" x-text="message.user.name"></p>

                                    {{-- Pièce jointe image --}}
                                    <template x-if="message.attachment && message.attachment.is_image">
                                        <a :href="message.attachment.url" target="_blank">
                                            <img :src="message.attachment.url" class="mb-1 max-h-56 rounded-lg object-cover" />
                                        </a>
                                    </template>
                                    {{-- Pièce jointe fichier --}}
                                    <template x-if="message.attachment && !message.attachment.is_image">
                                        <a :href="message.attachment.url" target="_blank" class="mb-1 flex items-center gap-2 rounded-lg bg-black/10 px-3 py-2">
                                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="truncate text-xs" x-text="message.attachment.name"></span>
                                        </a>
                                    </template>

                                    <p class="whitespace-pre-wrap break-words" x-text="message.content" x-show="message.content"></p>
                                    <p class="mt-1 text-right text-[10px] opacity-60">
                                        <span x-text="message.created_at_human"></span>
                                        <span x-show="message.pending"> · envoi...</span>
                                        <span x-show="message.failed" class="text-red-300"> · échec</span>
                                    </p>
                                </div>

                                {{-- Bouton réaction (visible au survol) --}}
                                <div class="relative opacity-0 transition-opacity group-hover:opacity-100" x-show="!message.pending">
                                    <button type="button" @click="openReactionPickerFor = openReactionPickerFor === message.id ? null : message.id" class="btn-icon h-7 w-7">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <div x-show="openReactionPickerFor === message.id" @click.outside="openReactionPickerFor = null" x-cloak
                                         class="absolute bottom-8 z-10 flex gap-1 rounded-full border border-gray-100 bg-white p-1 shadow-soft dark:border-surface-800 dark:bg-surface-900"
                                         :class="isMine(message) ? 'right-0' : 'left-0'" style="display:none;">
                                        <template x-for="emoji in availableReactions" :key="emoji">
                                            <button type="button" @click="toggleReaction(message, emoji)" class="rounded-full p-1 text-lg hover:bg-gray-100 dark:hover:bg-surface-800" x-text="emoji"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Réactions posées --}}
                            <div class="mt-1 flex flex-wrap gap-1" :class="isMine(message) ? 'justify-end' : 'justify-start'" x-show="message.reactions && message.reactions.length">
                                <template x-for="r in (message.reactions || [])" :key="r.emoji">
                                    <button type="button" @click="toggleReaction(message, r.emoji)"
                                        class="flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-xs"
                                        :class="r.mine ? 'border-primary-300 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-surface-800'">
                                        <span x-text="r.emoji"></span>
                                        <span x-text="r.count"></span>
                                    </button>
                                </template>
                            </div>

                            {{-- Accusé de lecture --}}
                            <p class="mt-0.5 text-right text-[10px] text-gray-400" x-show="isMine(message) && isLastMineMessage(message) && wasReadByOthers(message)">
                                Vu ✓✓
                            </p>
                        </div>
                    </template>
                </div>

                {{-- Aperçu pièce jointe en attente --}}
                <div x-show="pendingFile" x-cloak class="flex items-center gap-2 border-t border-gray-100 px-4 py-2 text-sm dark:border-surface-800" style="display:none;">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="flex-1 truncate text-gray-600 dark:text-gray-300" x-text="pendingFile?.name"></span>
                    <button type="button" @click="clearPendingFile()" class="text-gray-400 hover:text-red-500">✕</button>
                </div>

                {{-- Saisie --}}
                <form @submit.prevent="sendMessage()" class="flex items-end gap-3 border-t border-gray-100 p-4 dark:border-surface-800">
                    <input type="file" x-ref="fileInput" @change="onFileSelected($event)" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
                    <button type="button" @click="$refs.fileInput.click()" class="btn-icon shrink-0" title="Joindre un fichier">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                    </button>
                    <textarea
                        x-model="newMessage"
                        @input="onTyping()"
                        @keydown.enter.prevent="sendMessage()"
                        rows="1"
                        placeholder="Écris un message..."
                        class="input-field max-h-32 flex-1 resize-none py-2.5"
                    ></textarea>
                    <button type="submit" class="btn-primary !px-3.5" :disabled="(!newMessage.trim() && !pendingFile) || sending">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                    </button>
                </form>
            </div>

        </template>
    </section>

    {{-- ===== Modale nouvelle conversation ===== --}}
    <div x-show="showNewConversationModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50" @click="showNewConversationModal = false"></div>
        <div class="card relative z-10 w-full max-w-md">
            <div class="card-header flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Nouvelle conversation</h2>
                <button @click="showNewConversationModal = false" class="btn-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-4 flex gap-2 rounded-xl bg-gray-100 p-1 dark:bg-surface-800">
                    <button type="button" @click="groupMode = false" class="flex-1 rounded-lg py-1.5 text-sm font-medium transition" :class="!groupMode ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500'">Message direct</button>
                    <button type="button" @click="groupMode = true" class="flex-1 rounded-lg py-1.5 text-sm font-medium transition" :class="groupMode ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500'">Groupe</button>
                </div>

                <template x-if="groupMode">
                    <div class="mb-4">
                        <x-input-label value="Nom du groupe" />
                        <input type="text" x-model="groupName" class="input-field" placeholder="Ex : Équipe Projet" />
                    </div>
                </template>

                <input type="search" x-model="userSearchQuery" placeholder="Rechercher un utilisateur..." class="input-field mb-3" />

                <template x-if="groupMode && selectedGroupUsers.length > 0">
                    <div class="mb-3 flex flex-wrap gap-2">
                        <template x-for="u in selectedGroupUsers" :key="u.id">
                            <span @click="toggleGroupUser(u)" x-text="u.name + ' ×'" class="badge-primary cursor-pointer"></span>
                        </template>
                    </div>
                </template>

                <div class="max-h-56 space-y-1 overflow-y-auto">
                    <template x-for="user in userSearchResults" :key="user.id">
                        <button type="button"
                            @click="groupMode ? toggleGroupUser(user) : startPrivateConversation(user)"
                            class="flex w-full items-center gap-3 rounded-xl p-2 text-left hover:bg-gray-50 dark:hover:bg-surface-800"
                            :class="groupMode && isSelected(user) ? 'bg-primary-50 dark:bg-primary-900/20' : ''">
                            <div class="avatar h-8 w-8 text-xs" x-text="user.name.charAt(0).toUpperCase()"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100" x-text="user.name"></p>
                                <p class="truncate text-xs text-gray-400" x-text="user.email"></p>
                            </div>
                        </button>
                    </template>
                </div>

                <template x-if="groupMode">
                    <button type="button" @click="createGroupConversation()" class="btn-primary mt-4 w-full" :disabled="!groupName.trim() || selectedGroupUsers.length === 0">
                        Créer le groupe
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
