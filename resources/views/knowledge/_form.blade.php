@php($isEdit = isset($article))

<div class="card">
    <div class="card-body space-y-5">
        <div>
            <x-input-label for="title" value="Titre" />
            <x-text-input id="title" name="title" value="{{ old('title', $isEdit ? $article->title : '') }}" class="mt-1" required autofocus />
            <x-input-error :messages="$errors->get('title')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="category_id" value="Catégorie" />
            <select id="category_id" name="category_id" class="input-field mt-1" required>
                <option value="">— Choisir une catégorie —</option>
                @foreach($categoryOptions as $id => $label)
                    <option value="{{ $id }}" @selected(old('category_id', $isEdit ? $article->category_id : null) == $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label value="Contenu (Markdown)" />

            {{-- Barre d'outils --}}
            <div class="mt-1 flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-gray-200 bg-gray-50 p-2 dark:border-surface-800 dark:bg-surface-800/50">
                <button type="button" @click="insertBold()" class="btn-icon h-8 w-8" title="Gras"><strong>G</strong></button>
                <button type="button" @click="insertItalic()" class="btn-icon h-8 w-8 italic" title="Italique">I</button>
                <button type="button" @click="insertHeading()" class="btn-icon h-8 w-8" title="Titre">H</button>
                <button type="button" @click="insertLink()" class="btn-icon h-8 w-8" title="Lien">🔗</button>
                <button type="button" @click="insertList()" class="btn-icon h-8 w-8" title="Liste">≡</button>
                <button type="button" @click="insertQuote()" class="btn-icon h-8 w-8" title="Citation">"</button>
                <button type="button" @click="insertCode()" class="btn-icon h-8 w-8 font-mono text-xs" title="Code">&lt;/&gt;</button>
                <button type="button" @click="insertCodeBlock()" class="btn-icon h-8 w-8 font-mono text-xs" title="Bloc de code">{ }</button>
                <button type="button" @click="insertTable()" class="btn-icon h-8 w-8" title="Tableau">⊞</button>

                <div class="ml-auto flex gap-1 rounded-lg bg-white p-0.5 dark:bg-surface-900">
                    <button type="button" @click="activeTab = 'write'" class="rounded-md px-3 py-1 text-xs font-medium" :class="activeTab === 'write' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40' : 'text-gray-500'">Écrire</button>
                    <button type="button" @click="activeTab = 'preview'" class="rounded-md px-3 py-1 text-xs font-medium" :class="activeTab === 'preview' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40' : 'text-gray-500'">Aperçu</button>
                </div>
            </div>

            <textarea x-ref="textarea" x-show="activeTab === 'write'" x-model="content" name="content" rows="16"
                class="input-field rounded-t-none font-mono text-sm" placeholder="# Titre de section&#10;&#10;Écris ton contenu en Markdown ici..."></textarea>

            <div x-show="activeTab === 'preview'" x-html="previewHtml" class="prose prose-sm dark:prose-invert max-w-none rounded-b-xl border border-t-0 border-gray-200 p-4 dark:border-surface-800" style="display:none;"></div>

            <x-input-error :messages="$errors->get('content')" class="mt-1" />
        </div>

        @if(auth()->user()->can('publish articles'))
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $isEdit && $article->is_featured) ? 'checked' : '' }} class="rounded text-primary-600 focus:ring-primary-500">
                Mettre en avant cet article
            </label>
        @endif
    </div>

    <div class="card-footer flex flex-wrap justify-end gap-3">
        <a href="{{ route('knowledge.index') }}" class="btn-secondary">Annuler</a>
        <button type="submit" name="action" value="draft" class="btn-secondary">Enregistrer en brouillon</button>
        @if(auth()->user()->can('publish articles'))
            <button type="submit" name="action" value="publish" class="btn-primary">
                {{ $isEdit && $article->status === 'published' ? 'Mettre à jour' : 'Publier' }}
            </button>
        @else
            <button type="submit" name="action" value="submit" class="btn-primary">Soumettre pour validation</button>
        @endif
    </div>
</div>
