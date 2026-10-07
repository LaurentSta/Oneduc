<?php

namespace App\Domains\ModulesFormateur\Support;

use App\Domains\CatalogueFormations\Support\AccesScormVersionne;
use App\Models\Module;
use App\Models\ScormPackageVersion;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NettoyeurBlocsModule
{
    public function __construct(
        private readonly AccesScormVersionne $accesScormVersionne,
    ) {}

    private const ALLOWED_HTML_TAGS = [
        'p' => true,
        'br' => true,
        'strong' => true,
        'b' => true,
        'em' => true,
        'i' => true,
        'u' => true,
        's' => true,
        'ul' => true,
        'ol' => true,
        'li' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'blockquote' => true,
        'a' => true,
        'code' => true,
        'pre' => true,
    ];

    private const DROP_HTML_TAGS_WITH_CONTENT = [
        'script' => true,
        'style' => true,
        'iframe' => true,
        'object' => true,
        'embed' => true,
        'svg' => true,
        'math' => true,
        'template' => true,
    ];

    private const SAFE_LINK_SCHEMES = [
        'http' => true,
        'https' => true,
        'mailto' => true,
        'tel' => true,
    ];

    public function sanitizeBlocks(?string $rawBlocksJson, int $moduleId): array
    {
        if (! $rawBlocksJson) {
            return [];
        }

        $decoded = json_decode($rawBlocksJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $module = Module::find($moduleId);

        $validMediaIds = Media::query()
            ->where('model_type', Module::class)
            ->where('model_id', $moduleId)
            ->where('collection_name', 'lesson-images')
            ->pluck('id')
            ->flip();

        $validAudioIds = Media::query()
            ->where('model_type', Module::class)
            ->where('model_id', $moduleId)
            ->where('collection_name', 'lesson-audios')
            ->pluck('id')
            ->flip();

        $sanitized = [];

        foreach (array_slice($decoded, 0, 100) as $block) {
            if (! is_array($block) || ! isset($block['type'])) {
                continue;
            }

            switch ($block['type']) {
                case 'text':
                    $html = $this->sanitizeHtmlFragment($block['html'] ?? null);
                    if ($html !== null) {
                        $sanitized[] = ['type' => 'text', 'html' => $html];
                    }
                    break;

                case 'image':
                    $mediaId = (int) ($block['media_id'] ?? 0);
                    if ($mediaId <= 0 || ! isset($validMediaIds[$mediaId])) {
                        break;
                    }
                    $sanitized[] = [
                        'type' => 'image',
                        'media_id' => $mediaId,
                        'caption' => Str::limit(strip_tags((string) ($block['caption'] ?? '')), 255, ''),
                    ] + (array_key_exists('alt', $block) ? [
                        'alt' => Str::limit(strip_tags(is_string($block['alt']) ? $block['alt'] : ''), 512, ''),
                    ] : []) + (array_key_exists('decorative', $block) ? [
                        'decorative' => filter_var($block['decorative'], FILTER_VALIDATE_BOOLEAN),
                    ] : []);
                    break;

                case 'quote':
                    $text = trim(strip_tags((string) ($block['text'] ?? '')));
                    if ($text === '') {
                        break;
                    }
                    $sanitized[] = [
                        'type' => 'quote',
                        'text' => Str::limit($text, 1000, ''),
                        'source' => Str::limit(strip_tags((string) ($block['source'] ?? '')), 255, ''),
                    ];
                    break;

                case 'video':
                    $url = trim((string) ($block['url'] ?? ''));
                    if ($url === '' || ClassifieurUrlVideo::classify($url) === null) {
                        break;
                    }
                    $sanitized[] = [
                        'type' => 'video',
                        'url' => $url,
                        'caption' => Str::limit(strip_tags((string) ($block['caption'] ?? '')), 255, ''),
                    ] + (array_key_exists('transcript', $block) ? [
                        'transcript' => Str::limit(strip_tags(is_string($block['transcript']) ? $block['transcript'] : ''), 20000, ''),
                    ] : []);
                    break;

                case 'audio':
                    $mediaId = (int) ($block['media_id'] ?? 0);
                    if ($mediaId <= 0 || ! isset($validAudioIds[$mediaId])) {
                        break;
                    }
                    $sanitized[] = [
                        'type' => 'audio',
                        'media_id' => $mediaId,
                        'caption' => Str::limit(strip_tags((string) ($block['caption'] ?? '')), 255, ''),
                    ] + (array_key_exists('transcript', $block) ? [
                        'transcript' => Str::limit(strip_tags(is_string($block['transcript']) ? $block['transcript'] : ''), 20000, ''),
                    ] : []);
                    break;

                case 'divider':
                    $mode = (string) ($block['mode'] ?? 'simple');
                    if (! in_array($mode, ['simple', 'reveal'], true)) {
                        $mode = 'simple';
                    }
                    $sanitized[] = ['type' => 'divider', 'mode' => $mode];
                    break;

                case 'scorm':
                    $key = (string) ($block['content_block_key'] ?? '');
                    $versionId = (int) ($block['scorm_package_version_id'] ?? 0);
                    if ($key === '' || ! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $key) || $versionId <= 0) {
                        break;
                    }

                    $version = ScormPackageVersion::find($versionId);
                    if (! $version || ! $module || ! $this->accesScormVersionne->autorise($module, $key, $version)) {
                        break;
                    }

                    $sanitized[] = [
                        'type' => 'scorm',
                        'content_block_key' => $key,
                        'scorm_package_version_id' => $versionId,
                    ];
                    break;
            }
        }

        return $sanitized;
    }

    public function sanitizeHtmlFragment(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $input = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $input->loadHTML(
            '<?xml encoding="UTF-8"><div id="oneduc-sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $input->getElementsByTagName('div')->item(0);
        if (! $root instanceof DOMElement) {
            return null;
        }

        $output = new DOMDocument('1.0', 'UTF-8');
        $container = $output->createElement('div');
        $output->appendChild($container);

        foreach (iterator_to_array($root->childNodes) as $child) {
            $cleanNode = $this->sanitizeNode($child, $output);
            if ($cleanNode) {
                $container->appendChild($cleanNode);
            }
        }

        $clean = trim($this->innerHtml($container));

        return $clean !== '' ? $clean : null;
    }

    private function sanitizeNode(DOMNode $node, DOMDocument $output): ?DOMNode
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $output->createTextNode($node->nodeValue ?? '');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        $tag = Str::lower($node->nodeName);
        if (isset(self::DROP_HTML_TAGS_WITH_CONTENT[$tag])) {
            return null;
        }

        if (! isset(self::ALLOWED_HTML_TAGS[$tag])) {
            $fragment = $output->createDocumentFragment();
            foreach (iterator_to_array($node->childNodes) as $child) {
                $cleanChild = $this->sanitizeNode($child, $output);
                if ($cleanChild) {
                    $fragment->appendChild($cleanChild);
                }
            }

            return $fragment;
        }

        $clean = $output->createElement($tag);

        if ($tag === 'a' && $node instanceof DOMElement) {
            $href = $this->sanitizeHref($node->getAttribute('href'));
            if ($href !== null) {
                $clean->setAttribute('href', $href);

                $target = $this->sanitizeTarget($node->getAttribute('target'));
                if ($target !== null) {
                    $clean->setAttribute('target', $target);
                }

                if ($target === '_blank') {
                    $clean->setAttribute('rel', 'noopener noreferrer');
                }
            }

            $title = trim($node->getAttribute('title'));
            if ($title !== '') {
                $clean->setAttribute('title', Str::limit($title, 255, ''));
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $cleanChild = $this->sanitizeNode($child, $output);
            if ($cleanChild) {
                $clean->appendChild($cleanChild);
            }
        }

        return $clean;
    }

    private function sanitizeHref(?string $href): ?string
    {
        $href = trim(html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($href === '') {
            return null;
        }

        $normalized = preg_replace('/[\x00-\x20\x7F]+/u', '', $href) ?? '';
        $lower = Str::lower($normalized);

        if (str_starts_with($lower, '//')) {
            return null;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $lower, $matches)) {
            return isset(self::SAFE_LINK_SCHEMES[Str::lower($matches[1])]) ? $href : null;
        }

        if (str_starts_with($href, '#') || str_starts_with($href, '?') || str_starts_with($href, '/')) {
            return $href;
        }

        return str_contains($href, ':') ? null : $href;
    }

    private function sanitizeTarget(?string $target): ?string
    {
        $target = trim((string) $target);

        return in_array($target, ['_blank', '_self'], true) ? $target : null;
    }

    private function innerHtml(DOMElement $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
