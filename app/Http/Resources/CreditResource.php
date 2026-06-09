<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = [
            'id'         => $this->person->person_id,
            'name'       => $this->person->name,
            'profileUrl' => $this->person->profile_path,
        ];

        if ($this->credit_type === 'cast') {
            $base['character'] = $this->character_name;
            $base['order']     = $this->order;
        } else {
            $base['job']        = $this->job;
            $base['department'] = $this->department;
        }

        return $base;
    }
}
