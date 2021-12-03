<?php
declare(strict_types=1);

namespace App\Model\Request;

use Nette;

/**
 * @property-read string $userNormalized
 * @property-read string $albumNormalized
 */
final class AlbumParams
{
	use Nette\SmartObject;

	public string $user;

	public string $album;


	public static function create(string $user, string $album): self
	{
		$obj = new self;
		$obj->user = $user;
		$obj->album = $album;

		return $obj;
	}


	public function getUserNormalized(): string
	{
		return Nette\Utils\Strings::webalize($this->user);
	}


	public function getAlbumNormalized(): string
	{
		return Nette\Utils\Strings::lower(
			Nette\Utils\Strings::trim($this->album)
		);
	}
}
