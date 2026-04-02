<?php

declare(strict_types=1);

/**
 * Task display styles
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

/**
 * Defines how a task should be displayed to the user
 */
enum DisplayStyle: int
{
    /** Checkbox, unchecked by default */
    case ConfirmNo = 1;

    /** Checkbox, checked by default */
    case ConfirmYes = 2;

    /** Agree/disagree buttons */
    case Agree = 3;

    /** Notice with continue button */
    case Notice = 4;

    /** No display (execute silently) */
    case None = 5;

    /**
     * Does this style require user confirmation?
     */
    public function requiresConfirmation(): bool
    {
        return match ($this) {
            self::ConfirmNo, self::ConfirmYes => true,
            self::Agree, self::Notice, self::None => false,
        };
    }

    /**
     * Can this style be grouped with another?
     */
    public function canGroupWith(self $other): bool
    {
        // Same display style can be grouped
        if ($this === $other) {
            return true;
        }

        // ConfirmNo and ConfirmYes can be grouped together
        if ($this->isConfirmStyle() && $other->isConfirmStyle()) {
            return true;
        }

        return false;
    }

    /**
     * Is this a confirmation style?
     */
    public function isConfirmStyle(): bool
    {
        return $this === self::ConfirmNo || $this === self::ConfirmYes;
    }
}
