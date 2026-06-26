<?php
namespace EsotericCurrent\Core\Ingestion;

class Feed_Parser {
    public function parse(string $raw_xml): array {
        $xml = simplexml_load_string($raw_xml);
        if ($xml === false) {
            return ['success' => false, 'error' => 'Invalid XML'];
        }

        $format = $this->detect_format($xml);

        return match ($format) {
            'rss' => $this->parse_rss($xml),
            'atom' => $this->parse_atom($xml),
            default => ['success' => false, 'error' => "Unsupported format: {$format}"],
        };
    }

    private function detect_format(\SimpleXMLElement $xml): string {
        if (isset($xml->channel)) {
            return 'rss';
        }
        if ($xml->getName() === 'feed') {
            return 'atom';
        }
        return 'unknown';
    }

    private function parse_rss(\SimpleXMLElement $xml): array {
        $channel = $xml->channel;
        $items = [];

        foreach ($channel->item as $item) {
            $items[] = [
                'title' => (string)$item->title,
                'url' => (string)$item->link,
                'content' => (string)($item->children('content', true)->encoded ?? $item->description ?? ''),
                'excerpt' => (string)($item->description ?? ''),
                'author' => (string)($item->author ?? $item->children('dc', true)->creator ?? ''),
                'published_at' => (string)($item->pubDate ?? $item->children('dc', true)->date ?? ''),
            ];
        }

        return [
            'success' => true,
            'title' => (string)$channel->title,
            'description' => (string)$channel->description,
            'link' => (string)$channel->link,
            'items' => $items,
        ];
    }

    private function parse_atom(\SimpleXMLElement $xml): array {
        $items = [];

        foreach ($xml->entry as $entry) {
            $link = '';
            foreach ($entry->link as $l) {
                $attrs = $l->attributes();
                if ((string)($attrs['rel'] ?? '') === 'alternate' || empty($link)) {
                    $link = (string)($attrs['href'] ?? '');
                }
            }

            $items[] = [
                'title' => (string)$entry->title,
                'url' => $link,
                'content' => (string)($entry->content ?? $entry->summary ?? ''),
                'excerpt' => (string)($entry->summary ?? ''),
                'author' => (string)($entry->author->name ?? ''),
                'published_at' => (string)($entry->published ?? $entry->updated ?? ''),
            ];
        }

        return [
            'success' => true,
            'title' => (string)$xml->title,
            'description' => (string)($xml->subtitle ?? ''),
            'link' => (string)$xml->link->attributes()->href ?? '',
            'items' => $items,
        ];
    }
}
