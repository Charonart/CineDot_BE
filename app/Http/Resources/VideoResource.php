<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = '';
        if ($this->site === 'YouTube') {
            $url = 'https://www.youtube.com/watch?v=' . $this->key_value;
        } elseif ($this->site === 'Vimeo') {
            $url = 'https://vimeo.com/' . $this->key_value;
        }

        return [
            'id'   => $this->id,
            'name' => $this->name,
            'url'  => $url,
            'type' => $this->type,
        ];
    }
}
