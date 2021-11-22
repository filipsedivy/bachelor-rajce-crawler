<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Core;
use Nette;

final class AlbumEntity
{
	use Nette\SmartObject;
	use Core\Arrays;

	private int $id;

	private string $url;

	private string $permalink;

	private string $userUrl;

	private string $name;

	private string $username;

	private bool $public;

	private bool $codeProtected;

	private bool $nsfw;

	private bool $new;

	private ?string $storage;

	private int $commentCount;

	private int $viewCount;

	private int $mediaCount;

	private int $mediaCommentCount;

	private int $photoCount;

	private int $videoCount;

	private int $likeCount;

	private ?string $description;


	public static function createFromArray(array $data): self
	{
		$entity = new self;
		$entity->id = $data['id'];
		$entity->url = $data['url'];
		$entity->permalink = $data['permalink'];
		$entity->userUrl = self::findValueFromKeys($data, ['user_url', 'userUrl']);
		$entity->name = $data['name'];
		$entity->username = $data['username'];
		$entity->public = self::findValueFromKeys($data, ['is_public', 'public']);
		$entity->codeProtected = self::findValueFromKeys($data, ['is_code_protected', 'codeProtected']);
		$entity->nsfw = self::findValueFromKeys($data, ['is_nsfw', 'nsfw']);
		$entity->new = self::findValueFromKeys($data, ['is_new', 'new']);
		$entity->storage = $data['storage'];
		$entity->viewCount = self::findValueFromKeys($data, ['view_count', 'viewCount']);
		$entity->mediaCommentCount = self::findValueFromKeys($data, ['media_comment_count', 'mediaCommentCount']);
		$entity->commentCount = self::findValueFromKeys($data, ['comment_count', 'commentCount']);
		$entity->mediaCount = self::findValueFromKeys($data, ['media_count', 'mediaCount']);
		$entity->videoCount = self::findValueFromKeys($data, ['video_count', 'videoCount']);
		$entity->likeCount = self::findValueFromKeys($data, ['like_count', 'likeCount']);
		$entity->photoCount = self::findValueFromKeys($data, ['photo_count', 'photoCount']);
		$entity->description = $data['description'];
		return $entity;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getPermalink(): string
	{
		return $this->permalink;
	}


	public function getUserUrl(): string
	{
		return $this->userUrl;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getUsername(): string
	{
		return $this->username;
	}


	public function isPublic(): bool
	{
		return $this->public;
	}


	public function isCodeProtected(): bool
	{
		return $this->codeProtected;
	}


	public function isNsfw(): bool
	{
		return $this->nsfw;
	}


	public function isNew(): bool
	{
		return $this->new;
	}


	public function getStorage(): ?string
	{
		return $this->storage;
	}


	public function getViewCount(): int
	{
		return $this->viewCount;
	}


	public function getMediaCount(): int
	{
		return $this->mediaCount;
	}


	public function getMediaCommentCount(): int
	{
		return $this->mediaCommentCount;
	}


	public function getPhotoCount(): int
	{
		return $this->photoCount;
	}


	public function getVideoCount(): int
	{
		return $this->videoCount;
	}


	public function getLikeCount(): int
	{
		return $this->likeCount;
	}


	public function getDescription(): ?string
	{
		return $this->description;
	}


	public function getCommentCount(): int
	{
		return $this->commentCount;
	}


	/** @return mixed[] */
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
