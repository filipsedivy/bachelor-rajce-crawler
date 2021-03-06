<?php
declare(strict_types=1);

namespace App\Core;

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


	/**
	 * @return array{albums: int, views: int, followers: int}
	 */
	public function parseUserHeader(Crawler $header): array
	{
		$albums = Strings::trim($header->filter('.list-inline-item')->first()->text());
		$views = Strings::trim($header->filter('.list-inline-item')->eq(1)->text());

		$followersNode = $header->filter('.list-inline-item')->eq(2);
		$followers = $followersNode->count() > 0
			? Strings::trim($header->filter('.list-inline-item')->eq(2)->text())
			: 0;

		return [
			'albums' => $this->getDigitsFromString($albums),
			'views' => $this->getDigitsFromString($views),
			'followers' => is_int($followers) ? $followers : $this->getDigitsFromString($followers),
		];
	}


	private function getDigitsFromString(string $text): int
	{
		$matches = Strings::matchAll($text, '~([\d]+)~');
		$result = '';

		foreach ($matches as $match) {
			$result .= $match[0];
		}

		return (int) $result;
	}
}
