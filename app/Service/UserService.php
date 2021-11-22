<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\BrowserKitFactory;
use App\Core\CrawlerHelper;
use App\Model\Entity\AlbumEntity;
use App\Model\Entity\UserEntity;
use Goutte\Client;
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

	private const ALBUM_KEY = 'album';

	private Client $client;

	private Predis\Client $predis;

	private int $offset;


	public function __construct(
		BrowserKitFactory $browserKitFactory,
		Predis\Client $predis,
		int $offset
	)
	{
		$this->client = $browserKitFactory->create();
		$this->predis = $predis;
		$this->offset = $offset;
	}


	public function isUserExists(UserEntity $entity): bool
	{
		$key = self::USER_EXISTS_KEY . ':' . $entity->user;
		$value = $this->predis->get($key);

		if ($value === null) {
			$this->client->request('GET', $this->createBaseUrl($entity));
			$value = $this->client->getInternalResponse()->getStatusCode() === 200;
			$this->predis->set($key, Json::encode(['result' => $value]), 'EX', 60 * 5);
		} else {
			$value = Json::decode($value)->result;
		}

		return $value;
	}


	public function getFullUser(UserEntity $entity): array
	{
		if (!$this->isUserExists($entity)) {
			throw new \InvalidArgumentException('User not found');
		}

		// Load first page
		$result = $this->getUser($entity);
		$accessCode = $result['security']['accessCode'];

		// Get total pages
		$totalPages = $result['page']['total'];

		if ($totalPages > 1) {
			for ($page = 1; $page <= $totalPages; $page++) {
				$albumPage = Arrays::map($this->getPage($entity, $accessCode, $page), fn(AlbumEntity $value) => $value->toArray());
				$result['albums'] = array_merge($result['albums'], $albumPage);
				$result['page']['actual'] = $page;
			}
		}

		return $result;
	}


	/** @return mixed[] */
	public function getUser(UserEntity $entity, int $page = 0): array
	{
		if (!$this->isUserExists($entity)) {
			throw new \InvalidArgumentException('User not found');
		}

		$response = $this->client->request('GET', $this->createBaseUrl($entity));
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
			'albums' => Arrays::map($this->getPage($entity, $accessCode, $page), fn(AlbumEntity $value) => $value->toArray()),
		];
	}


	public function check(array $data): UserEntity
	{
		$processor = new Schema\Processor;
		$schema = Schema\Expect::structure([
			'user' => Schema\Expect::string()->required(),
		])->castTo(UserEntity::class);
		return $processor->process($schema, $data);
	}


	/** @return AlbumEntity[] */
	private function getPage(UserEntity $entity, string $accessCode, int $page): array
	{
		$key = self::USER_DATA . ':' . $entity->user . ':page:' . $page;

		if ($this->predis->exists($key) === 0) {
			$payload = [
				'username' => $entity->user,
				'sort' => 'createDateDesc',
				'offset' => $page * 23,
				'limit' => 23,
				'access_code' => $accessCode,
			];

			$this->client->request('POST', $this->createBaseUrl($entity) . '/services/web/get-albums', $payload);
			$response = Json::decode($this->client->getInternalResponse()->getContent(), Json::FORCE_ARRAY);

			if (Arrays::get($response, 'status') !== self::SUCCESS_STATUS) {
				return [];
			}

			$data = Arrays::map($response['data']['albums'], function ($value): AlbumEntity {
				$entity = AlbumEntity::createFromArray($value);
				$key = self::ALBUM_KEY . ':' . $entity->getId();
				if ($this->predis->exists($key) === 0) {
					$this->predis->set($key, Json::encode($entity->toArray()));
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


	private function createBaseUrl(UserEntity $entity): string
	{
		return sprintf('https://%s.rajce.idnes.cz', $entity->user);
	}
}
