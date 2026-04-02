<?php

declare(strict_types=1);

/**
 * Test stubs for modern PSR-4 implementation
 *
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\LoginTasks\Test\Stub;

use Horde\Exception\HordeException;
use Horde\Http\Uri;
use Horde\LoginTasks\DisplayStyle;
use Horde\LoginTasks\LoginTaskBackend;
use Horde\LoginTasks\SystemTask;
use Horde\LoginTasks\Task;
use Horde\LoginTasks\TaskInterval;
use Horde\LoginTasks\TaskList;
use Horde\LoginTasks\TaskPriority;
use Psr\Http\Message\UriInterface;

/**
 * Test backend implementation
 */
class Backend implements LoginTaskBackend
{
    public static array $lastRun = [];
    public static TaskList|bool|null $lastTasklistCache = null;

    private TaskList|bool $tasklistCache = false;

    public function __construct(
        private array $tasks,
        private string $app = 'test',
        array|bool $lastRun = false,
    ) {
        if ($lastRun !== true) {
            self::$lastRun = is_array($lastRun) ? $lastRun : [];
        }
    }

    public function getTasklistFromCache(): TaskList|false
    {
        return $this->tasklistCache;
    }

    public function storeTasklistInCache(TaskList|bool $tasklist): void
    {
        $this->tasklistCache = $tasklist;
        self::$lastTasklistCache = $tasklist;
    }

    public function getTasks(): iterable
    {
        return $this->tasks;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getLastRun(): array
    {
        return self::$lastRun;
    }

    public function setLastRun(array $last): void
    {
        self::$lastRun = $last;
    }

    public function markLastRun(): void
    {
        $lasttasks = $this->getLastRun();
        $lasttasks[$this->app] = time();
        self::$lastRun = $lasttasks;
    }

    public function redirect(UriInterface|string $url): mixed
    {
        return $url instanceof UriInterface ? (string) $url : $url;
    }

    public function getLoginTasksUrl(): UriInterface
    {
        return new Uri('/login/tasks');
    }
}

/**
 * Backend that throws on storage
 */
class BackendThrowsOnStore implements LoginTaskBackend
{
    public function __construct(
        private array $tasks,
        private string $app = 'test',
        private array $lastRun = [],
    ) {}

    public function getTasklistFromCache(): TaskList|false
    {
        return false;
    }

    public function storeTasklistInCache(TaskList|bool $tasklist): void
    {
        throw new HordeException('Storage failed');
    }

    public function getTasks(): iterable
    {
        return $this->tasks;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getLastRun(): array
    {
        return $this->lastRun;
    }

    public function setLastRun(array $last): void
    {
        $this->lastRun = $last;
    }

    public function markLastRun(): void
    {
        $this->lastRun[$this->app] = time();
    }

    public function redirect(UriInterface|string $url): mixed
    {
        return $url;
    }

    public function getLoginTasksUrl(): UriInterface
    {
        return new Uri('/login/tasks');
    }
}

/**
 * Base test task
 */
class TestTask implements Task
{
    public static array $executed = [];

    public function __construct(
        private bool $active = true,
        private DisplayStyle $display = DisplayStyle::None,
        private TaskInterval $interval = TaskInterval::Every,
        private TaskPriority $priority = TaskPriority::Normal,
    ) {}

    public function execute(): void
    {
        self::$executed[] = static::class;
    }

    public function describe(): string
    {
        return '';
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getDisplayStyle(): DisplayStyle
    {
        return $this->display;
    }

    public function getInterval(): TaskInterval
    {
        return $this->interval;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function needsDisplay(): bool
    {
        return $this->display !== DisplayStyle::None;
    }

    public function canJoinDisplayWith(Task $previous): bool
    {
        return $this->display->canGroupWith($previous->getDisplayStyle());
    }
}

class TestTaskTwo extends TestTask {}

class ConfirmTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::ConfirmYes);
    }
}

class ConfirmTaskTwo extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::ConfirmYes);
    }
}

class ConfirmTaskThree extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::ConfirmYes);
    }
}

class ConfirmNoTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::ConfirmNo);
    }
}

class DayTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::Daily);
    }
}

class FirstTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::FirstLogin);
    }
}

class HighPriorityTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(priority: TaskPriority::High);
    }
}

class MonthTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::Monthly);
    }
}

class NoticeTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::Notice);
    }
}

class NoticeTaskTwo extends TestTask
{
    public function __construct()
    {
        parent::__construct(display: DisplayStyle::Notice);
    }
}

class OnceTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::Once);
    }
}

class WeekTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::Weekly);
    }
}

class YearTask extends TestTask
{
    public function __construct()
    {
        parent::__construct(interval: TaskInterval::Yearly);
    }
}

/**
 * System task stub
 */
class TestSystemTask implements SystemTask
{
    public function __construct(
        private bool $active = true,
        private TaskInterval $interval = TaskInterval::Every,
    ) {}

    public function execute(): void
    {
        TestTask::$executed[] = static::class;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getInterval(): TaskInterval
    {
        return $this->interval;
    }

    public function skip(): bool
    {
        return false;
    }
}

/**
 * System task with configurable skip
 */
class SkippableSystemTask implements SystemTask
{
    public static bool $shouldSkip = false;

    public function execute(): void
    {
        TestTask::$executed[] = static::class;
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getInterval(): TaskInterval
    {
        return TaskInterval::Every;
    }

    public function skip(): bool
    {
        return self::$shouldSkip;
    }
}
