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
        $comment = \App\Models\EstimateComment::find($this->commentId);
        $estimate = \App\Models\Estimate::find($this->estimateId);
        $sender = $comment?->user;
        $client = $estimate?->client;

        return [
            'comment_id' => $this->commentId,
            'estimate_id' => $this->estimateId,
            'author_user_id' => $this->authorUserId,
            'content_snippet' => $this->contentSnippet,

            // Author/Sender Details
            'sender_id' => $this->authorUserId,
            'sender_name' => $sender?->name ?? ($this->authorUserId ? 'N/A' : 'Client'),
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',
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
