<?php

namespace Modules\Glitter\Tasks\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;

class TaskCollection extends BaseApiCollection
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            ...$this->paginationMeta(),
        ];
    }
}
