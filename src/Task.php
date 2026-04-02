<?php

declare(strict_types=1);

/**
 * Task interface
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
 * Interface for login tasks
 */
interface Task
{
    /**
     * Execute the task
     */
    public function execute(): void;

    /**
     * Get task description for display
     */
    public function describe(): string;

    /**
     * Is this task active?
     */
    public function isActive(): bool;

    /**
     * Get display style
     */
    public function getDisplayStyle(): DisplayStyle;

    /**
     * Get execution interval
     */
    public function getInterval(): TaskInterval;

    /**
     * Get execution priority
     */
    public function getPriority(): TaskPriority;

    /**
     * Does this task need to be displayed to the user?
     */
    public function needsDisplay(): bool;

    /**
     * Can this task be displayed together with another?
     */
    public function canJoinDisplayWith(Task $previous): bool;
}
