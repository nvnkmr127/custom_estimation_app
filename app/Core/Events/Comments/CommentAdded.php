<?php

namespace App\Core\Events\Comments;

use App\Core\Events\BaseEvent;

class CommentAdded extends BaseEvent
{
    public function __construct(
        public int $commentId,
        public int $estimateId,
        public ?int $authorUserId, // null if client
        public string $contentSnippet
    ) {
        parent::__construct();
        $this->priority = self::PRIORITY_HIGH;
    }

    public function getEventName(): string
    {
        return 'comment.added';
    }

    public function getPayload(): array
    {
        return [
            'comment_id' => $this->commentId,
            'estimate_id' => $this->estimateId,
            'author_user_id' => $this->authorUserId,
            'content_snippet' => $this->contentSnippet,
        ];
    }
    public function getEntityType(): string
    {
        return 'comment';
    }

    public function getEntityId(): int|string|null
    {
        return $this->commentId;
    }
}
