<?php
declare(strict_types=1);

namespace App\Service;

use App\Core;
use App\Model;
use Goutte;
use Nette;
use Nette\Schema;
use Predis;

final class AlbumService
{
	use Nette\SmartObject;

	private const ALBUM_EXISTS_KEY = 'album:exists';

	private const ALBUM_PHOTOS_KEY = 'album:photos';

	private Goutte\Client $goutte;


	public function __construct(
		private Predis\Client $predis,
		Core\BrowserKitFactory $browserKitFactory
	) {
		$this->goutte = $browserKitFactory->create();
	}


	public function check(array $data): Model\Request\AlbumParams
	{
		$processor= new Schema\Processor;
		$schema = Schema\Expect::structure([
			'user' => Schema\Expect::string()->required(),
			'album' => Schema\Expect::string()->required(),
		])->castTo(Model\Request\AlbumParams::class);

		return $processor->process($schema, $data);
	}


	public function createFromString(string $input): Model\Request\AlbumParams
	{
		$url = new Nette\Http\Url($input);
		$user = Nette\Utils\Strings::replace($url->getHost(), '/\.rajce\.idnes\.cz/');
		$album = Nette\Utils\Strings::trim($url->getPath(), '/');
		return Model\Request\AlbumParams::create($user, $album);
	}


	public function checkExists(Model\Request\AlbumParams $params): bool
	{
		$key = self::ALBUM_EXISTS_KEY . ':' . $params->userNormalized . ':' . $params->albumNormalized;
		if ($this->predis->exists($key) === 0) {
			$this->goutte->request('GET', $this->createUrl($params)->absoluteUrl);
			$result = $this->goutte->getInternalResponse()->getStatusCode() === 200;
			$this->predis->set($key, Nette\Utils\Json::encode(['result' => $result]), 'EX', 60 * 5);
		} else {
			$value = $this->predis->get($key);
			$result = Nette\Utils\Json::decode($value)->result;
		}

		return $result;
	}


	public function getPhotos(Model\Request\AlbumParams $params): array
	{
		if (!$this->checkExists($params)) {
			throw new \InvalidArgumentException('User or album not found');
		}

		$key = self::ALBUM_PHOTOS_KEY . ':' . $params->userNormalized . ':' . $params->albumNormalized;

		if ($this->predis->exists($key) === 0) {
			$response = $this->goutte->request('GET', $this->createUrl($params)->absoluteUrl);
			$images = $response
				->filter('#photos')
				->filter('.thumb-img-wrapper')
				->filter('noscript')
				->filter('img')
				->images();

			$results = [];

			foreach ($images as $image) {
				$thumb = $image->getUri();
				$baseName = pathinfo($thumb, PATHINFO_BASENAME);
				$full = Nette\Utils\Strings::replace($thumb, '~\/thumb\/~', '/images/');

				$results[] = [
					'thumb' => $thumb,
					'baseName' => $baseName,
					'full' => $full,
				];
			}

			$this->predis->set($key, Nette\Utils\Json::encode($results));
		} else {
			$value = $this->predis->get($key);
			$results = Nette\Utils\Json::decode($value, Nette\Utils\Json::FORCE_ARRAY);
		}

		return $results;
	}


	private function createUrl(Model\Request\AlbumParams $params): Nette\Http\Url
	{
		$url = new Nette\Http\Url;
		$url->setScheme('https')
			->setHost($params->user . '.rajce.idnes.cz')
			->setPath($params->album);
		return $url;
	}
}
