/**
 * Éditeur Markdown : zone de texte + aperçu en direct (rendu côté client
 * avec marked.js, uniquement pour la prévisualisation — le rendu
 * définitif et sécurisé se fait côté serveur avec CommonMark).
 */
export default function markdownEditor(initialContent = '') {
    return {
        content: initialContent,
        previewHtml: '',
        activeTab: 'write',

        init() {
            this.renderPreview();
            this.$watch('content', () => this.renderPreview());
        },

        renderPreview() {
            if (window.marked) {
                this.previewHtml = window.marked.parse(this.content || '*Rien à prévisualiser pour le moment...*');
            }
        },

        insert(before, after = '') {
            const textarea = this.$refs.textarea;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = this.content.substring(start, end) || 'texte';

            this.content = this.content.substring(0, start) + before + selected + after + this.content.substring(end);

            this.$nextTick(() => {
                textarea.focus();
                textarea.selectionStart = start + before.length;
                textarea.selectionEnd = start + before.length + selected.length;
            });
        },

        insertBold() { this.insert('**', '**'); },
        insertItalic() { this.insert('*', '*'); },
        insertHeading() { this.insert('## '); },
        insertLink() { this.insert('[', '](https://)'); },
        insertList() { this.insert('- '); },
        insertCode() { this.insert('`', '`'); },
        insertCodeBlock() { this.insert('\n```\n', '\n```\n'); },
        insertQuote() { this.insert('> '); },
        insertTable() {
            this.insert('\n| Colonne 1 | Colonne 2 |\n| --- | --- |\n| Valeur | Valeur |\n');
        },
    };
}
