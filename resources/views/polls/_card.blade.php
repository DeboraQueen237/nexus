<div x-data="pollCard(@js($poll))" class="card animate-fade-in">
    <div class="card-body">
        {{-- En-tête auteur --}}
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="avatar" x-text="poll.author.initial"></div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="poll.author.name"></p>
                    <p class="text-xs text-gray-400">
                        <span x-text="poll.created_at_human"></span>
                        <template x-if="poll.expires_at_human && !poll.is_expired">
                            <span> · Se termine <span x-text="poll.expires_at_human"></span></span>
                        </template>
                        <template x-if="poll.is_expired">
                            <span class="text-red-400"> · Terminé</span>
                        </template>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="badge-info" x-show="poll.type === 'multiple'">Choix multiple</span>
                <button @click="copyLink()" class="btn-icon h-8 w-8" title="Copier le lien">
                    <svg x-show="!linkCopied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m5.656 0a4 4 0 000-5.656l-1.5-1.5a4 4 0 00-5.656 5.656l3 3" /></svg>
                    <svg x-show="linkCopied" class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
                <template x-if="poll.is_owner">
                    <a :href="`/polls/${poll.id}/export`" class="btn-icon h-8 w-8" title="Exporter en CSV">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </template>
                <template x-if="poll.is_owner">
                    <form :action="`/polls/${poll.id}`" method="POST" onsubmit="return confirm('Supprimer ce sondage ?')">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-icon h-8 w-8 text-red-400 hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </form>
                </template>
            </div>
        </div>

        {{-- Question --}}
        <a :href="`/polls/${poll.id}`" class="block">
            <h3 class="text-base font-semibold text-gray-900 hover:text-primary-600 dark:text-gray-100" x-text="poll.title"></h3>
            <p x-show="poll.description" class="mt-1 text-sm text-gray-500" x-text="poll.description"></p>
        </a>

        {{-- Options --}}
        <div class="mt-4 space-y-2">
            <template x-for="option in poll.options" :key="option.id">
                <button
                    type="button"
                    @click="toggleOption(option.id)"
                    :disabled="poll.is_expired || voting"
                    class="relative w-full overflow-hidden rounded-xl border text-left transition disabled:cursor-default"
                    :class="isSelected(option.id) ? 'border-primary-400 ring-1 ring-primary-300' : 'border-gray-200 dark:border-surface-800 hover:border-primary-300'"
                >
                    {{-- Barre de progression --}}
                    <div class="absolute inset-y-0 left-0 bg-primary-50 transition-all duration-500 dark:bg-primary-900/30"
                         :style="`width: ${(poll.has_voted || poll.is_expired) ? option.percentage : 0}%`"></div>

                    <div class="relative flex items-center justify-between gap-3 px-4 py-2.5">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <svg x-show="isSelected(option.id)" class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span x-text="option.text"></span>
                        </span>
                        <span x-show="poll.has_voted || poll.is_expired" class="text-xs font-semibold text-gray-500" x-text="option.percentage + '%'"></span>
                    </div>
                </button>
            </template>
        </div>

        <template x-if="poll.type === 'multiple' && !poll.has_voted && !poll.is_expired">
            <button @click="submitMultiple()" :disabled="selectedOptions.length === 0 || voting" class="btn-primary mt-3 w-full">
                Voter
            </button>
        </template>

        <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
            <span x-text="poll.total_votes + ' vote' + (poll.total_votes > 1 ? 's' : '')"></span>
            <span x-show="justVoted" class="font-medium text-emerald-500">✓ Vote enregistré</span>
        </div>
    </div>
</div>
