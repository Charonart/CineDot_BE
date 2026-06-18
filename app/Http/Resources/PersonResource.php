<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->person_id,
            'name'   => $this->full_name,
            'avatar' => $this->avatar_url,
            'bio'    => $this->biography,
        ];
    }
}
