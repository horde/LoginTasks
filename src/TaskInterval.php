<?php

declare(strict_types=1);

/**
 * Task execution intervals
 *
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\LoginTasks;

use Horde_LoginTasks_Translation;

/**
 * Defines when a login task should be executed
 */
enum TaskInterval: int
{
    /** Execute yearly (first login on or after January 1) */
    case Yearly = 1;

    /** Execute monthly (first login on or after first of month) */
    case Monthly = 2;

    /** Execute weekly (first login on or after Sunday) */
    case Weekly = 3;

    /** Execute daily (first login of the day) */
    case Daily = 4;

    /** Execute on every login */
    case Every = 5;

    /** Execute on first login only */
    case FirstLogin = 6;

    /** Execute once only */
    case Once = 7;

    /**
     * Get human-readable label for this interval
     */
    public function label(): string
    {
        return match ($this) {
            self::Yearly => Horde_LoginTasks_Translation::t("Yearly"),
            self::Monthly => Horde_LoginTasks_Translation::t("Monthly"),
            self::Weekly => Horde_LoginTasks_Translation::t("Weekly"),
            self::Daily => Horde_LoginTasks_Translation::t("Daily"),
            self::Every => Horde_LoginTasks_Translation::t("Every Login"),
            self::FirstLogin => Horde_LoginTasks_Translation::t("First Login"),
            self::Once => Horde_LoginTasks_Translation::t("Once"),
        };
    }

    /**
     * Get all labels as associative array
     *
     * @return array<int, string> Map of interval value to label
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }
        return $labels;
    }
}
