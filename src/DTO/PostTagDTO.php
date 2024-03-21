<?php

namespace App\DTO;

use DateTimeInterface;

class PostTagDTO {
    public int $id;
    public string $name;
    public DateTimeInterface $createdAt;

    public function __construct(int $id, string $name, DateTimeInterface $createdAt) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }
}
