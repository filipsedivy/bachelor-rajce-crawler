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

	private const SUCCESS_STATUS = 'success';

	private Client $client;

	private int $offset;


	public function __construct(BrowserKitFactory $browserKitFactory, int $offset)
	{
		$this->client = $browserKitFactory->create();
		$this->offset = $offset;
	}


	public function isUserExists(UserEntity $entity): bool
	{
		$this->client->request('GET', $this->createBaseUrl($entity));
		return $this->client->getInternalResponse()->getStatusCode() === 200;
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

		return Arrays::map($response['data']['albums'], fn($value) => AlbumEntity::createFromArray($value));
	}


	private function createBaseUrl(UserEntity $entity): string
	{
		return sprintf('https://%s.rajce.idnes.cz', $entity->user);
	}
}
