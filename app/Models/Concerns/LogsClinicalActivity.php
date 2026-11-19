<?php

namespace App\Models\Concerns;

use App\Models\AuditActivity;
use App\Support\AuditContext;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsClinicalActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('clinical')
            ->logOnly($this->clinicalLogAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return list<string>
     */
    protected function clinicalLogAttributes(): array
    {
        return $this->getFillable();
    }

    public function resolveAuditCaseId(): ?int
    {
        return null;
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($activity instanceof AuditActivity) {
            $activity->case_id = $this->resolveAuditCaseId() ?? AuditContext::caseId();
            $activity->ip_address = request()->ip();
            $activity->reason = AuditContext::reason();

            if (AuditContext::causer() !== null) {
                $activity->causer()->associate(AuditContext::causer());
            }
        }
    }
}
