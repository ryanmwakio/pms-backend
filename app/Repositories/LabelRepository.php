<?php

namespace App\Repositories;

use App\Models\Label;
use App\Repositories\Contracts\LabelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LabelRepository implements LabelRepositoryInterface
{
    public function allForWorkspace(int $workspaceId): Collection
    {
        return Label::where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): Label
    {
        return Label::findOrFail($id);
    }

    public function create(array $data): Label
    {
        return Label::create($data);
    }

    public function update(Label $label, array $data): Label
    {
        $label->update($data);

        return $label->fresh();
    }

    public function delete(Label $label): void
    {
        $label->issues()->detach();
        $label->delete();
    }
}
