<?php

namespace Taxi\Models;

class Rating
{
    public int $id;
    public int $ride_id;
    public int $from_user_id;
    public int $to_user_id;
    public string $user_type; // driver or passenger (to_user)
    public int $score; // 1-5
    public ?string $comment;
    public string $created_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ride_id' => $this->ride_id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'user_type' => $this->user_type,
            'score' => $this->score,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
