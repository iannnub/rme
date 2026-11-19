<?php

namespace App\Support;

use App\Models\User;

class AuditContext
{
    private static ?string $reason = null;

    private static ?int $caseId = null;

    private static ?User $causer = null;

    public static function setReason(?string $reason): void
    {
        self::$reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
    }

    public static function reason(): ?string
    {
        return self::$reason;
    }

    public static function setCaseId(?int $caseId): void
    {
        self::$caseId = $caseId;
    }

    public static function caseId(): ?int
    {
        return self::$caseId;
    }

    public static function setCauser(?User $causer): void
    {
        self::$causer = $causer;
    }

    public static function causer(): ?User
    {
        return self::$causer;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function run(callable $callback, ?string $reason = null, ?int $caseId = null, ?User $causer = null): mixed
    {
        $previousReason = self::$reason;
        $previousCaseId = self::$caseId;
        $previousCauser = self::$causer;

        self::setReason($reason);
        if ($caseId !== null) {
            self::setCaseId($caseId);
        }
        if ($causer !== null) {
            self::setCauser($causer);
        }

        try {
            return $callback();
        } finally {
            self::$reason = $previousReason;
            self::$caseId = $previousCaseId;
            self::$causer = $previousCauser;
        }
    }
}
