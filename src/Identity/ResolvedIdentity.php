<?php

namespace Trail\Identity;

class ResolvedIdentity
{
    public function __construct(
        public string $ownerType,
        public ?string $ownerId,
        public ?string $ownerLabel,
        public string $source,
        public string $confidence,
    ) {
    }

    public function toArray(): array
    {
        return [
            'owner_type' => $this->ownerType,
            'owner_id' => $this->ownerId,
            'owner_label' => $this->ownerLabel,
            'source' => $this->source,
            'confidence' => $this->confidence,
        ];
    }
}
