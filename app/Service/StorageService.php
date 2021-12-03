<?php
declare(strict_types=1);

namespace App\Service;

use App\Model;
use Nette;
use Predis;

final class StorageService
{
	use Nette\SmartObject;

	private const STORAGE_KEY = 'storage:list';


	public function __construct(
		private Predis\Client $predis
	) {
	}


	public function get(Model\Request\AlbumParams $params): ?string
	{
		$key = $this->createKey($params);
		return $this->predis->exists($key) === 0 ? null : $this->predis->get($key);
	}


	public function set(Model\Request\AlbumParams $params, string $storage): void
	{
		$key = $this->createKey($params);
		if ($this->predis->exists($key) === 0) {
			$this->predis->set($key, $storage);
		}
	}


	private function createKey(Model\Request\AlbumParams $params): string
	{
		return self::STORAGE_KEY . ':' . $params->userNormalized . ':' . $params->albumNormalized;
	}
}
