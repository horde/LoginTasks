<?php

declare(strict_types=1);

/**
 * Task execution priority
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
 * Defines task execution priority
 */
enum TaskPriority: int
{
    /** High priority (executes before normal tasks) */
    case High = 1;

    /** Normal priority */
    case Normal = 2;

    /**
     * Compare priorities
     *
     * @return int Negative if $this < $other, 0 if equal, positive if $this > $other
     */
    public function compare(self $other): int
    {
        return $this->value <=> $other->value;
    }

    /**
     * Is this higher priority than another?
     */
    public function isHigherThan(self $other): bool
    {
        return $this->value < $other->value; // Lower value = higher priority
    }
}
