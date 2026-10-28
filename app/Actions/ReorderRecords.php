<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderRecords
{
    /**
     * Persist the given order, rejecting ids that are missing from or foreign to the scope.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $scope
     * @param  list<int>  $orderedIds
     * @param  array<string, mixed>  $extraAttributes  Attributes written to every record, e.g. a new parent id.
     */
    public function handle(Builder $scope, array $orderedIds, string $field = 'ids', array $extraAttributes = []): void
    {
        $this->ensureMatchesScope($scope, $orderedIds, $field);

        DB::transaction(function () use ($scope, $orderedIds, $extraAttributes) {
            foreach ($orderedIds as $position => $id) {
                (clone $scope)->getModel()->newQuery()
                    ->whereKey($id)
                    ->update([...$extraAttributes, 'position' => $position]);
            }
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $scope
     * @param  list<int>  $orderedIds
     */
    private function ensureMatchesScope(Builder $scope, array $orderedIds, string $field): void
    {
        $scopeIds = (clone $scope)->pluck((clone $scope)->getModel()->getKeyName())->map(fn ($id) => (int) $id)->sort()->values()->all();
        $givenIds = collect($orderedIds)->sort()->values()->all();

        if ($scopeIds !== $givenIds) {
            throw ValidationException::withMessages([
                $field => 'A lista enviada não corresponde aos itens atuais. Recarregue a página e tente novamente.',
            ]);
        }
    }
}
