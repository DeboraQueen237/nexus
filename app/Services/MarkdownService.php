<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    protected MarkdownConverter $converter;

    public function __construct()
    {
        // Sécurité : le HTML brut saisi dans un article (balises <script>,
        // etc.) est totalement neutralisé — jamais interprété par le
        // navigateur. Seule la syntaxe Markdown est rendue.
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => 'section',
                'insert' => 'before',
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        return (string) $this->converter->convert($markdown);
    }

    /**
     * Extrait un sommaire (table des matières) à partir des titres ##/###
     * du markdown — pratique pour une navigation "sur cette page".
     */
    public function extractHeadings(?string $markdown): array
    {
        if (blank($markdown)) {
            return [];
        }

        preg_match_all('/^(#{2,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);

        return collect($matches)->map(fn ($m) => [
            'level' => strlen($m[1]),
            'text' => trim($m[2]),
            'slug' => \Illuminate\Support\Str::slug(trim($m[2])),
        ])->all();
    }
}
