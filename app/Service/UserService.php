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

final class UserService
{
	use CrawlerHelper;

	private Client $client;


	public function __construct(BrowserKitFactory $browserKitFactory)
	{
		$this->client = $browserKitFactory->create();
	}


	public function isUserExists(UserEntity $entity): bool
	{
		$this->client->request('GET', $this->createBaseUrl($entity));
		return $this->client->getInternalResponse()->getStatusCode() === 200;
	}


	public function getUser(UserEntity $entity): array
	{
		if (!$this->isUserExists($entity)) {
			throw new \InvalidArgumentException('User not found');
		}

		$response = $this->client->request('GET', $this->createBaseUrl($entity));
		$script = Strings::trim($response->filter('script')->text());
		$accessCode = $this->getAccessCode($script);
		$userHeader = $this->parseUserHeader($response->filter('.user-header-content'));
		return [
			'header' => $userHeader,
			'accessCode' => $accessCode,
			'albums' => $this->getAlbums($entity, $accessCode, $userHeader['albums']),
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


	private function getAlbums(UserEntity $entity, string $accessCode, int $albums): array
	{
		$results = [];
		$totalPages = ceil($albums / 23);

		for ($i = 0; $i <= $totalPages; $i++) {
			$page = $this->getAlbumPage($entity, $accessCode, $i);
			if (!array_key_exists('status', $page) || $page['status'] !== 'success') {
				continue;
			}

			$result = Arrays::map($page['data']['albums'], fn($value) => AlbumEntity::createFromArray($value)->toArray());

			$results = array_merge($results, $result);
		}

		return $results;
	}


	private function getAlbumPage(UserEntity $entity, string $accessCode, int $page): array
	{
		$payload = [
			'username' => $entity->user,
			'sort' => 'createDateDesc',
			'offset' => $page * 23,
			'limit' => 23,
			'access_code' => $accessCode,
		];
		$this->client->request('POST', $this->createBaseUrl($entity) . '/services/web/get-albums', $payload);
		return Json::decode($this->client->getInternalResponse()->getContent(), Json::FORCE_ARRAY);
	}


	private function createBaseUrl(UserEntity $entity): string
	{
		return sprintf('https://%s.rajce.idnes.cz', $entity->user);
	}
}
