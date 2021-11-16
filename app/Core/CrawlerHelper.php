<?php

namespace App\Core;

use JetBrains\PhpStorm\ArrayShape;
use Nette\Utils\Strings;
use Symfony\Component\DomCrawler\Crawler;

trait CrawlerHelper
{
    public function getAccessCode(string $script): string
    {
        $match = Strings::match($script, '~\"web_service_access_code\"\:\"([\w]+)\"~');
        if ($match === null) {
            throw new \InvalidArgumentException('Access code not found');
        }

        return $match[1];
    }

    #[ArrayShape(['albums' => "int", 'views' => "int", 'followers' => "int"])]
    public function parseUserHeader(Crawler $header): array
    {
        $albums = Strings::trim($header->filter('.list-inline-item')->first()->text());
        $views = Strings::trim($header->filter('.list-inline-item')->eq(1)->text());
        $followers = Strings::trim($header->filter('.list-inline-item')->eq(2)->text());

        return [
            'albums' => $this->getDigitsFromString($albums),
            'views' => $this->getDigitsFromString($views),
            'followers' => $this->getDigitsFromString($followers),
        ];
    }

    private function getDigitsFromString(string $text): int
    {
        $matches = Strings::matchAll($text, '~([\d]+)~');
        $result = '';

        foreach ($matches as $match) {
            $result .= $match[0];
        }

        return (int)$result;
    }
}
