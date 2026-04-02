<?php

declare(strict_types=1);

/**
 * Task list for login session
 *
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
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

use Psr\Http\Message\UriInterface;

/**
 * Stores the list of login tasks for this session
 *
 * Immutable - modifications return new instances.
 */
readonly class TaskList
{
    /**
     * @param array<int, Task> $tasks Regular tasks to execute
     * @param array<int, SystemTask> $systemTasks System tasks to execute
     * @param int $pointer Current task position
     * @param UriInterface|null $target URL to load after tasks complete
     * @param bool $processed Has this tasklist been processed?
     */
    public function __construct(
        private array $tasks = [],
        private array $systemTasks = [],
        private int $pointer = 0,
        public ?UriInterface $target = null,
        public bool $processed = false,
    ) {}

    /**
     * Add a task to the list
     *
     * @return self New instance with task added
     */
    public function withTask(Task|SystemTask $task): self
    {
        if ($task instanceof SystemTask) {
            return new self(
                $this->tasks,
                [...$this->systemTasks, $task],
                $this->pointer,
                $this->target,
                $this->processed,
            );
        }

        // Insert based on priority
        $tasks = $this->tasks;
        if ($task->getPriority() === TaskPriority::High) {
            array_unshift($tasks, $task);
        } else {
            $tasks[] = $task;
        }

        return new self(
            $tasks,
            $this->systemTasks,
            $this->pointer,
            $this->target,
            $this->processed,
        );
    }

    /**
     * Get tasks that are ready to execute
     *
     * @param bool $includeDisplay Whether to include tasks needing display
     * @return array<int, Task|SystemTask> Tasks ready to execute
     */
    public function ready(bool $includeDisplay = false): array
    {
        $ready = [];

        // System tasks always first
        foreach ($this->systemTasks as $task) {
            if (!$task->skip()) {
                $ready[] = $task;
            }
        }

        // Regular tasks
        if ($includeDisplay) {
            // When including display tasks, return:
            // 1. All tasks that need display and can group together
            // 2. Any non-display tasks that follow them before the next display group
            $displayTasks = $this->needDisplay(false);
            foreach ($displayTasks as $task) {
                $ready[] = $task;
            }

            // Continue with non-display tasks after the display group
            $displayCount = count($displayTasks);
            foreach (array_slice($this->tasks, $displayCount) as $task) {
                if ($task->needsDisplay()) {
                    break; // Stop at next display task
                }
                $ready[] = $task;
            }
        } else {
            // Otherwise, return tasks up to first that needs display
            foreach ($this->tasks as $index => $task) {
                if ($task->needsDisplay() && $index >= $this->pointer) {
                    break;
                }
                $ready[] = $task;
            }
        }

        return $ready;
    }

    /**
     * Get tasks that need to be displayed
     *
     * @param bool $advance Mark displayed tasks as seen
     * @return array<int, Task> Tasks needing display
     */
    public function needDisplay(bool $advance = false): array
    {
        $display = [];
        $previous = null;

        foreach ($this->tasks as $task) {
            if (!$task->needsDisplay()) {
                break;
            }

            if ($previous !== null && !$task->canJoinDisplayWith($previous)) {
                break;
            }

            $display[] = $task;
            $previous = $task;
        }

        if ($advance && !empty($display)) {
            // Caller should use withPointer() to advance
        }

        return $display;
    }

    /**
     * Are all tasks complete?
     */
    public function isDone(): bool
    {
        return empty($this->systemTasks) && $this->pointer >= count($this->tasks);
    }

    /**
     * Return new instance with processed flag set
     */
    public function withProcessed(bool $processed): self
    {
        return new self(
            $this->tasks,
            $this->systemTasks,
            $this->pointer,
            $this->target,
            $processed,
        );
    }

    /**
     * Return new instance with target URL set
     */
    public function withTarget(?UriInterface $target): self
    {
        return new self(
            $this->tasks,
            $this->systemTasks,
            $this->pointer,
            $target,
            $this->processed,
        );
    }

    /**
     * Return new instance with pointer advanced
     */
    public function withPointer(int $pointer): self
    {
        return new self(
            $this->tasks,
            $this->systemTasks,
            $pointer,
            $this->target,
            $this->processed,
        );
    }

    /**
     * Return new instance with system tasks removed
     */
    public function withoutSystemTasks(array $completed): self
    {
        $remaining = array_filter(
            $this->systemTasks,
            fn($t) => !in_array($t, $completed, true)
        );

        return new self(
            $this->tasks,
            array_values($remaining),
            $this->pointer,
            $this->target,
            $this->processed,
        );
    }

    /**
     * Return new instance with regular tasks sliced
     */
    public function withTasksSliced(int $count): self
    {
        return new self(
            array_slice($this->tasks, $count),
            $this->systemTasks,
            0, // Reset pointer
            $this->target,
            $this->processed,
        );
    }
}
