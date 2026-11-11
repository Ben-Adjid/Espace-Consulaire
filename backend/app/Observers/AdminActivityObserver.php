<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Consulate;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class AdminActivityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', array_keys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $fields = array_diff(array_keys($model->getChanges()), ['created_at', 'updated_at']);
        $this->record($model, 'updated', $fields);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', []);
    }

    /**
     * Store field names only; never persist field values such as contact details or appointment notes.
     *
     * @param  array<int, string>  $fields
     */
    private function record(Model $model, string $action, array $fields): void
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            return;
        }

        $changedFields = array_values(array_diff($fields, [
            'password',
            'remember_token',
            'access_token_hash',
        ]));
        $consulateId = $model instanceof Consulate
            ? $model->getKey()
            : $model->getAttribute('consulate_id');

        ActivityLog::query()->create([
            'consulate_id' => $consulateId,
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => (string) $model->getKey(),
            'changed_fields' => $changedFields,
        ]);
    }
}
