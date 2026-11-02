<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

final class FeedWriter
{
    public const ITUNES_NAMESPACE = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    public const ATOM_NAMESPACE = 'http://www.w3.org/2005/Atom';

    public function rss(array $channel, iterable $items): string
    {
        $xml = $this->open();
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:itunes', self::ITUNES_NAMESPACE);
        $xml->writeAttribute('xmlns:atom', self::ATOM_NAMESPACE);

        $xml->startElement('channel');
        $this->emptyElement($xml, 'atom:link', ['href' => $channel['self'], 'rel' => 'self', 'type' => 'application/rss+xml']);
        $this->element($xml, 'title', $channel['title'], true);
        $this->element($xml, 'link', $channel['link'], true);
        $this->element($xml, 'description', $channel['description'], true);
        $this->element($xml, 'generator', 'PuMuKIT');
        $this->element($xml, 'lastBuildDate', $channel['lastBuildDate']);
        $this->element($xml, 'language', $channel['language']);
        $this->element($xml, 'copyright', $channel['copyright']);
        if ('' !== $channel['image']) {
            $this->emptyElement($xml, 'itunes:image', ['href' => $channel['image']]);
            $xml->startElement('image');
            $this->element($xml, 'url', $channel['image']);
            $this->element($xml, 'title', $channel['title'], true);
            $this->element($xml, 'link', $channel['link'], true);
            $xml->endElement();
        }
        $this->emptyElement($xml, 'itunes:category', ['text' => $channel['category']]);
        $this->element($xml, 'itunes:summary', $channel['summary']);
        $this->element($xml, 'itunes:subtitle', $channel['subtitle']);
        $this->element($xml, 'itunes:author', $channel['author']);
        if ('' !== $channel['email']) {
            $xml->startElement('itunes:owner');
            $this->element($xml, 'itunes:name', $channel['author']);
            $this->element($xml, 'itunes:email', $channel['email']);
            $xml->endElement();
        }
        $this->element($xml, 'itunes:explicit', $channel['explicit']);

        foreach ($items as $item) {
            $this->item($xml, $item);
        }

        $xml->endElement();
        $xml->endElement();

        return $this->close($xml);
    }

    public function list(iterable $urls): string
    {
        $xml = $this->open();
        $xml->startElement('list');
        foreach ($urls as $url) {
            $this->element($xml, 'podcast', $url);
        }
        $xml->endElement();

        return $this->close($xml);
    }

    public static function clean(?string $value): string
    {
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value) ?? '';
    }

    private function item(\XMLWriter $xml, array $item): void
    {
        $xml->startElement('item');
        $this->element($xml, 'title', $item['title'], true);
        $this->element($xml, 'itunes:subtitle', $item['subtitle']);
        $this->element($xml, 'itunes:summary', $item['description']);
        $this->element($xml, 'description', $item['description'], true);
        $this->element($xml, 'link', $item['link']);
        $this->emptyElement($xml, 'enclosure', ['url' => $item['url'], 'length' => (string) $item['length'], 'type' => $item['type']]);
        $xml->startElement('guid');
        $xml->writeAttribute('isPermaLink', 'false');
        $xml->text(self::clean($item['guid']));
        $xml->endElement();
        $this->element($xml, 'itunes:duration', (string) $item['duration']);
        $this->element($xml, 'author', $item['author']);
        $this->element($xml, 'itunes:author', $item['itunesAuthor']);
        $this->element($xml, 'itunes:keywords', $item['keywords']);
        $this->element($xml, 'itunes:explicit', $item['explicit']);
        if ('' !== $item['image']) {
            $this->emptyElement($xml, 'itunes:image', ['href' => $item['image']]);
        }
        $this->element($xml, 'pubDate', $item['pubDate']);
        $xml->endElement();
    }

    private function element(\XMLWriter $xml, string $name, ?string $value, bool $required = false): void
    {
        $value = self::clean($value);
        if ('' === $value && !$required) {
            return;
        }

        $xml->writeElement($name, $value);
    }

    private function emptyElement(\XMLWriter $xml, string $name, array $attributes): void
    {
        $xml->startElement($name);
        foreach ($attributes as $attribute => $value) {
            $xml->writeAttribute($attribute, self::clean($value));
        }
        $xml->endElement();
    }

    private function open(): \XMLWriter
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');

        return $xml;
    }

    private function close(\XMLWriter $xml): string
    {
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
