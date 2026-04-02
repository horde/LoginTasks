<?php

declare(strict_types=1);

/**
 * System task interface
 *
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
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
 * Interface for system login tasks
 *
 * System tasks are always executed before regular tasks and run silently.
 * They cannot be skipped by users.
 */
interface SystemTask
{
    /**
     * Execute the system task
     */
    public function execute(): void;

    /**
     * Is this task active?
     */
    public function isActive(): bool;

    /**
     * Get execution interval
     */
    public function getInterval(): TaskInterval;

    /**
     * Should this task be skipped for this execution?
     *
     * If true, the task will not run on this access but will be
     * retried on the next access.
     */
    public function skip(): bool;
}
