<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\BrowserKitFactory;
use App\Core\CrawlerHelper;
use App\Model;
use App\Model\Entity\AlbumEntity;
use Goutte\Client;
use Nette;
use Nette\Schema;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Predis;

final class UserService
{
	use CrawlerHelper;

	private const SUCCESS_STATUS = 'success';

	private const USER_EXISTS_KEY = 'user:exists';

	private const USER_DATA = 'user:data';

	private const ALBUM_KEY = 'album:list';

	private Client $client;

	private int $offset;


	public function __construct(
		private StorageService $storageService,
		private AlbumService $albumService,
		private Predis\Client $predis,
		BrowserKitFactory $browserKitFactory,
		int $offset
	) {
		$this->client = $browserKitFactory->create();
		$this->offset = $offset;
	}


	public function isUserExists(Model\Request\UserParams $entity): bool
	{
		$key = self::USER_EXISTS_KEY . ':' . $entity->user;
		$value = $this->predis->get($key);

		if ($value === null) {
			$this->client->request('GET', $this->createUrl($entity)->absoluteUrl);
			$value = $this->client->getInternalResponse()->getStatusCode() === 200;
			$this->predis->set($key, Json::encode(['result' => $value]), 'EX', 60 * 5);
		} else {
			$value = Json::decode($value)->result;
		}

		return $value;
	}


	public function getOnePage(Model\Request\UserParams $params, int $page = 0): array
	{
		if (!$this->isUserExists($params)) {
			throw new \InvalidArgumentException('User not found');
		}

		$response = $this->client->request('GET', $this->createUrl($params)->absoluteUrl);
		$script = Strings::trim($response->filter('script')->text());
		$accessCode = $this->getAccessCode($script);
		$userHeader = $this->parseUserHeader($response->filter('.user-header-content'));

		$totalPages = ceil($userHeader['albums'] / $this->offset);

		return [
			'page' => [
				'actual' => $page,
				'itemsPerPage' => $this->offset,
				'total' => $totalPages,
			],
			'security' => [
				'accessCode' => $accessCode,
			],
			'information' => $userHeader,
			'albums' => Arrays::map($this->getPage($params, $accessCode, $page), fn(AlbumEntity $value) => $value->toArray()),
		];
	}


	public function getAllPage(Model\Request\UserParams $params): array
	{
		if (!$this->isUserExists($params)) {
			throw new \InvalidArgumentException('User not found');
		}

		$response = $this->client->request('GET', $this->createUrl($params)->absoluteUrl);
		$script = Strings::trim($response->filter('script')->text());
		$accessCode = $this->getAccessCode($script);
		$userHeader = $this->parseUserHeader($response->filter('.user-header-content'));

		$totalPages = ceil($userHeader['albums'] / $this->offset);
		$albums = [];

		for ($p = 0; $p <= $totalPages; $p++) {
			$page = Arrays::map(
				$this->getPage($params, $accessCode, $p),
				fn(AlbumEntity $value) => $value->toArray()
			);

			$albums = array_merge($albums, $page);
		}

		return [
			'page' => [
				'itemPerPage' => $this->offset,
				'total' => $totalPages,
			],
			'security' => [
				'accessCode' => $accessCode,
			],
			'information' => $userHeader,
			'albums' => $albums,
		];
	}


	public function check(array $data): Model\Request\UserParams
	{
		$processor = new Schema\Processor;
		$schema = Schema\Expect::structure([
			'user' => Schema\Expect::string()->required(),
		])->castTo(Model\Request\UserParams::class);
		return $processor->process($schema, $data);
	}


	/** @return AlbumEntity[] */
	private function getPage(Model\Request\UserParams $params, string $accessCode, int $page): array
	{
		$key = self::USER_DATA . ':' . $params->user . ':page:' . $page;

		if ($this->predis->exists($key) === 0) {
			$payload = [
				'username' => $params->user,
				'sort' => 'createDateDesc',
				'offset' => $page * $this->offset,
				'limit' => $this->offset,
				'access_code' => $accessCode,
			];

			$this->client->request('POST', $this->createUrl($params)->absoluteUrl . 'services/web/get-albums', $payload);
			$response = Json::decode($this->client->getInternalResponse()->getContent(), Json::FORCE_ARRAY);

			if (Arrays::get($response, 'status') !== self::SUCCESS_STATUS) {
				return [];
			}

			$data = Arrays::map($response['data']['albums'], function ($value): AlbumEntity {
				$albumParams = $this->albumService->createFromString($value['url']);
				$value['album'] = $albumParams->album;

				$entity = AlbumEntity::createFromArray($value);
				$key = self::ALBUM_KEY . ':' . $entity->getId();
				if ($this->predis->exists($key) === 0) {
					$this->predis->set($key, Json::encode($entity->toArray()));
				}

				// Save album storage
				if ($entity->getStorage() !== null) {
					$this->storageService->set($albumParams, $entity->getStorage());
				}

				return $entity;
			});

			$ids = Arrays::map($data, fn(AlbumEntity $entity) => $entity->getId());
			$this->predis->set($key, Json::encode($ids), 'EX', 60 * 60 /* Expire 1 hour */);
		} else {
			$ids = Json::decode($this->predis->get($key));
			$data = Arrays::map($ids, function (int $id): AlbumEntity {
				$album = Json::decode($this->predis->get(self::ALBUM_KEY . ':' . $id), Json::FORCE_ARRAY);
				return AlbumEntity::createFromArray($album);
			});
		}

		return $data;
	}


	private function createUrl(Model\Request\UserParams $params): Nette\Http\Url
	{
		$url = new Nette\Http\Url;
		$url->setScheme('https')
			->setHost($params->user . '.rajce.idnes.cz');

		return $url;
	}
}
