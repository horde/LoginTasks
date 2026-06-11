<?php

declare(strict_types=1);

/**
 * Login tasks coordinator
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

use Horde\Exception\HordeException;
use Horde\Http\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Coordinates execution of login tasks
 */
class LoginTasks
{
    private TaskList|bool $tasklist;

    /**
     * @param LoginTaskBackend $backend Backend providing dependencies
     */
    public function __construct(
        private readonly LoginTaskBackend $backend,
    ) {
        // Retrieve cached tasklist or create new one
        $this->tasklist = $this->backend->getTasklistFromCache();

        if ($this->tasklist === false) {
            $this->tasklist = $this->createTaskList();
        }

        // Note: this class deliberately does NOT register a PHP shutdown
        // handler. The legacy Horde_LoginTasks class still does (gated on
        // its $registerShutdown constructor flag) for back-compat with
        // out-of-tree consumers, but persistence timing is a framework
        // concern and modern callers own it: invoke {@see persist()}
        // explicitly when the tasklist's final state should be written
        // back to the backend (or wrap the instance in a framework-side
        // shutdown adapter that does the same).
    }

    /**
     * Persist the current tasklist via the backend.
     *
     * Replaces the legacy `shutdown()` method. Callers decide when to
     * invoke this — typically once after {@see runTasks()} has finished
     * processing, or via a framework-owned adapter that fires it at
     * end of request.
     */
    public function persist(): void
    {
        if (!isset($this->tasklist)) {
            return;
        }

        try {
            $this->backend->storeTasklistInCache($this->tasklist);
        } catch (HordeException) {
            // Silently ignore storage failures
        }
    }

    /**
     * Create the list of login tasks for this session
     */
    private function createTaskList(): TaskList
    {
        $tasklist = new TaskList();

        // Get last run information
        $lastRun = $this->backend->getLastRun();
        if (!is_array($lastRun)) {
            $lastRun = [];
        }

        $currentDate = getdate();
        $app = $this->backend->getApp();

        // Process each registered task
        foreach ($this->backend->getTasks() as $task) {
            // Skip inactive tasks
            if (!$task->isActive()) {
                continue;
            }

            $shouldAdd = false;
            $interval = $task->getInterval();

            if ($interval === TaskInterval::FirstLogin) {
                $shouldAdd = !isset($lastRun[$app]);
            } else {
                // Initialize last run time if not set
                if (!isset($lastRun[$app])) {
                    $lastRun[$app] = time();
                    $this->backend->setLastRun($lastRun);
                }

                $lastRunDate = getdate($lastRun[$app]);

                $shouldAdd = match ($interval) {
                    TaskInterval::Yearly
                        => $currentDate['year'] > $lastRunDate['year'],

                    TaskInterval::Monthly
                        => ($currentDate['year'] > $lastRunDate['year'])
                        || ($currentDate['mon'] > $lastRunDate['mon']),

                    TaskInterval::Weekly => $this->shouldRunWeekly($currentDate, $lastRunDate),

                    TaskInterval::Daily
                        => ($currentDate['year'] > $lastRunDate['year'])
                        || ($currentDate['yday'] > $lastRunDate['yday']),

                    TaskInterval::Every => true,

                    TaskInterval::Once => $this->shouldRunOnce($task, $lastRun),

                    default => false,
                };
            }

            if ($shouldAdd) {
                $tasklist = $tasklist->withTask($task);
            }
        }

        // Return the tasklist (even if empty/done)
        return $tasklist;
    }

    /**
     * Determine if weekly task should run
     */
    private function shouldRunWeekly(array $currentDate, array $lastRunDate): bool
    {
        // Check if we crossed a Sunday boundary
        if ($currentDate['wday'] < $lastRunDate['wday']) {
            return true;
        }

        $daysInYear = date('L', $lastRunDate[0]) ? 366 : 365;

        if (($currentDate['year'] == $lastRunDate['year'])
            && ($currentDate['yday'] >= $lastRunDate['yday'] + 7)) {
            return true;
        }

        if (($currentDate['year'] > $lastRunDate['year'])
            && ($currentDate['yday'] >= $lastRunDate['yday'] + 7 - $daysInYear)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if ONCE task should run
     */
    private function shouldRunOnce(Task|SystemTask $task, array &$lastRun): bool
    {
        $className = $task::class;

        if (!isset($lastRun['_once']) || !is_array($lastRun['_once'])) {
            $lastRun['_once'] = [];
        }

        if (in_array($className, $lastRun['_once'], true)) {
            return false;
        }

        $lastRun['_once'][] = $className;
        $this->backend->setLastRun($lastRun);

        return true;
    }

    /**
     * Execute login tasks
     *
     * @param array<int, int> $confirmed List of confirmed task indices
     * @param UriInterface|string|null $target URL to redirect to when complete
     * @param bool $userConfirmed User has confirmed pending actions
     * @return mixed Null if no redirect, redirect result otherwise
     */
    public function runTasks(
        array $confirmed = [],
        UriInterface|string|null $target = null,
        bool $userConfirmed = false,
    ): mixed {
        // Nothing to do if no tasklist or already complete
        if (!isset($this->tasklist) || $this->tasklist === true) {
            return null;
        }

        // Set target URL if provided
        if ($target !== null && $this->tasklist->target === null) {
            $targetUri = $target instanceof UriInterface ? $target : new Uri((string) $target);
            $this->tasklist = $this->tasklist->withTarget($targetUri);
        }

        // Execute ready tasks
        $readyTasks = $this->tasklist->ready($userConfirmed);

        foreach ($readyTasks as $index => $task) {
            // Execute if:
            // - System task
            // - No display needed
            // - User confirmed and task doesn't require confirmation
            // - User confirmed this specific task (in confirmed array)
            $shouldExecute = $task instanceof SystemTask
                || !$task->needsDisplay()
                || ($userConfirmed && !$task->getDisplayStyle()->requiresConfirmation())
                || in_array($index, $confirmed, true);

            if ($shouldExecute) {
                $task->execute();
            }
        }

        // Remove completed tasks
        if (!empty($readyTasks)) {
            $systemTasks = array_filter($readyTasks, fn($t) => $t instanceof SystemTask);
            $regularTasks = array_filter($readyTasks, fn($t) => $t instanceof Task);

            if (!empty($systemTasks)) {
                $this->tasklist = $this->tasklist->withoutSystemTasks($systemTasks);
            }

            if (!empty($regularTasks)) {
                $this->tasklist = $this->tasklist->withTasksSliced(count($regularTasks));
            }
        }

        $wasProcessed = $this->tasklist->processed;
        $this->tasklist = $this->tasklist->withProcessed(true);

        // Check if all tasks complete
        if ($this->tasklist->isDone()) {
            $this->backend->markLastRun();
            $targetUrl = $this->tasklist->target;

            // Mark as complete
            $this->tasklist = true;

            // Redirect if user confirmed
            if ($userConfirmed && $targetUrl !== null) {
                return $this->backend->redirect($targetUrl);
            }

            return null;
        }

        // Redirect to display tasks if needed
        if ((!$wasProcessed || $userConfirmed)
            && !empty($this->tasklist->needDisplay())) {
            return $this->backend->redirect($this->getLoginTasksUrl());
        }

        return null;
    }

    /**
     * Get tasks that need to be displayed
     *
     * @return array<int, Task> Tasks requiring display
     */
    public function displayTasks(): array
    {
        if (!isset($this->tasklist) || $this->tasklist === true) {
            return [];
        }

        return $this->tasklist->needDisplay(true);
    }

    /**
     * Get the login tasks URL
     */
    public function getLoginTasksUrl(): UriInterface
    {
        return $this->backend->getLoginTasksUrl();
    }

    /**
     * Get interval labels
     *
     * @return array<int, string> Map of interval value to translated label
     */
    public static function getLabels(): array
    {
        return TaskInterval::labels();
    }
}
