<?php

namespace App\Model\Entity;


final class AlbumEntity
{
    public static function createFromArray(array $data): self
    {
        $entity = new self();
        $entity->id = $data['id'];
        $entity->url = $data['url'];
        $entity->permalink = $data['permalink'];
        $entity->userUrl = $data['user_url'];
        $entity->name = $data['name'];
        $entity->username = $data['username'];
        $entity->public = $data['is_public'];
        $entity->codeProtected = $data['is_code_protected'];
        $entity->nsfw = $data['is_nsfw'];
        $entity->new = $data['is_new'];
        $entity->storage = $data['storage'];
        $entity->viewCount = $data['view_count'];
        $entity->mediaCommentCount = $data['media_comment_count'];
        $entity->commentCount = $data['comment_count'];
        $entity->mediaCount = $data['media_count'];
        $entity->videoCount = $data['video_count'];
        $entity->likeCount = $data['like_count'];
        $entity->photoCount = $data['photo_count'];
        $entity->description = $data['description'];
        return $entity;
    }

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

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
