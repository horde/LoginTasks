<?php

declare(strict_types=1);

/**
 * Backend interface for LoginTasks
 *
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\LoginTasks;

use Psr\Http\Message\UriInterface;

/**
 * Backend interface providing dependencies for LoginTasks system
 *
 * Handles preferences, session storage, redirection, and task registry.
 */
interface LoginTaskBackend
{
    /**
     * Retrieve cached tasklist if it exists
     *
     * @return TaskList|false The cached task list or false if none cached
     */
    public function getTasklistFromCache(): TaskList|false;

    /**
     * Store a login tasklist in the cache
     *
     * @param TaskList|bool $tasklist The tasklist to store, or true if complete
     */
    public function storeTasklistInCache(TaskList|bool $tasklist): void;

    /**
     * Get the task instances that need to be performed
     *
     * @return iterable<Task|SystemTask> Tasks to execute
     */
    public function getTasks(): iterable;

    /**
     * Get the application name this backend serves
     */
    public function getApp(): string;

    /**
     * Get information about the last time tasks were run
     *
     * Array keys are app names, values are last run timestamps.
     * Special key '_once' contains list of ONCE tasks previously run.
     *
     * @return array<string, mixed> Last run information
     */
    public function getLastRun(): array;

    /**
     * Store information about the last time tasks were run
     *
     * @param array<string, mixed> $last Last run information
     */
    public function setLastRun(array $last): void;

    /**
     * Mark current time as when login tasks were last run
     */
    public function markLastRun(): void;

    /**
     * Redirect to the given URL
     *
     * @param UriInterface|string $url URL to redirect to
     * @return mixed Return value from redirect (often void or never)
     */
    public function redirect(UriInterface|string $url): mixed;

    /**
     * Return the URL of the login tasks view
     */
    public function getLoginTasksUrl(): UriInterface;
}
