<?php

declare(strict_types=1);

/**
 * Test the modern LoginTasks PSR-4 implementation
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

namespace Horde\LoginTasks\Test;

use Horde_Date;
use Horde\Http\Uri;
use Horde\LoginTasks\LoginTasks;
use Horde\LoginTasks\TaskInterval;
use Horde\LoginTasks\Test\Stub\Backend;
use Horde\LoginTasks\Test\Stub\BackendThrowsOnStore;
use Horde\LoginTasks\Test\Stub\ConfirmNoTask;
use Horde\LoginTasks\Test\Stub\ConfirmTask;
use Horde\LoginTasks\Test\Stub\ConfirmTaskThree;
use Horde\LoginTasks\Test\Stub\ConfirmTaskTwo;
use Horde\LoginTasks\Test\Stub\DayTask;
use Horde\LoginTasks\Test\Stub\FirstTask;
use Horde\LoginTasks\Test\Stub\HighPriorityTask;
use Horde\LoginTasks\Test\Stub\MonthTask;
use Horde\LoginTasks\Test\Stub\NoticeTask;
use Horde\LoginTasks\Test\Stub\NoticeTaskTwo;
use Horde\LoginTasks\Test\Stub\OnceTask;
use Horde\LoginTasks\Test\Stub\SkippableSystemTask;
use Horde\LoginTasks\Test\Stub\TestSystemTask;
use Horde\LoginTasks\Test\Stub\TestTask;
use Horde\LoginTasks\Test\Stub\TestTaskTwo;
use Horde\LoginTasks\Test\Stub\WeekTask;
use Horde\LoginTasks\Test\Stub\YearTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test the modern LoginTasks implementation
 */
#[CoversClass(LoginTasks::class)]
class LoginTasksTest extends TestCase
{
    public function testTheTasksAreRun(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([new TestTask()]);
        $tasks->runTasks();

        $this->assertEquals(
            TestTask::class,
            TestTask::$executed[0]
        );
    }

    public function testNoTasksAreRunIfTheTasklistIsEmpty(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([]);
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);
    }

    public function testNoTasksAreRunIfTheTasklistIsCompleted(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([new TestTask()]);
        $tasks->runTasks();
        TestTask::$executed = [];
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);
    }

    public function testTasksWithHighPriorityAreExecutedBeforeTasksWithLowPriority(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
        ]);
        $tasks->runTasks();

        $this->assertEquals(
            [HighPriorityTask::class, TestTask::class],
            TestTask::$executed
        );
    }

    public function testTasksThatRepeatYearlyAreExecutedAtTheBeginningOfEachYear(): void
    {
        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new YearTask()],
            $date
        );

        $this->assertEquals([], TestTask::$executed);

        $date->year--;
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks(
            [new YearTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals(YearTask::class, TestTask::$executed[0]);
    }

    public function testTasksThatRepeatMonthlyAreExecutedAtTheBeginningOfEachMonth(): void
    {
        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new MonthTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);

        $date->month--;
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks(
            [new MonthTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals(MonthTask::class, TestTask::$executed[0]);
    }

    public function testTasksThatRepeatWeeklyAreExecutedAtTheBeginningOfEachWeek(): void
    {
        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new WeekTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);

        $date->mday -= 7;
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks(
            [new WeekTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals(WeekTask::class, TestTask::$executed[0]);
    }

    public function testTasksThatRepeatDailyAreExecutedAtTheBeginningOfEachDay(): void
    {
        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new DayTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);

        $date->mday--;
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks(
            [new DayTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals(DayTask::class, TestTask::$executed[0]);
    }

    public function testTasksThatRepeatEachLoginAreExecutedOnEachLogin(): void
    {
        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new TestTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals([TestTask::class], TestTask::$executed);
    }

    public function testTasksThatAreExecutedOnFirstLoginAreExecutedOnlyThen(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([new FirstTask()]);
        $tasks->runTasks();

        $this->assertEquals([FirstTask::class], TestTask::$executed);

        TestTask::$executed = [];
        $date = new Horde_Date(time());
        $tasks = $this->getLoginTasks(
            [new FirstTask()],
            $date
        );
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);
    }

    public function testTasksThatRunOnceAreNotExecutedMoreThanOnce(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([new OnceTask()]);
        $tasks->runTasks();

        $this->assertEquals([OnceTask::class], TestTask::$executed);

        TestTask::$executed = [];
        $tasks = $this->getLoginTasks(
            [new OnceTask()],
            true
        );
        $tasks->runTasks();

        $this->assertEquals([], TestTask::$executed);
    }

    public function testAllTasksGetRunIfNoTasksRequiresDisplay(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
        ]);
        $tasks->runTasks();

        $this->assertEquals(
            [HighPriorityTask::class, TestTask::class],
            TestTask::$executed
        );
    }

    public function testTheLastTimeOfCompletingTheLoginTasksWillBeStoredOnceAllTasksWereExecuted(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
        ]);
        $tasks->runTasks();

        $this->assertTrue(Backend::$lastRun['test'] > time() - 10);
    }

    public function testAllTasksToBeRunBeforeTheFirstTaskRequiringDisplayGetExecutedInABatch(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
            new NoticeTask(),
        ]);
        $tasks->runTasks();

        $this->assertEquals(
            [HighPriorityTask::class, TestTask::class],
            TestTask::$executed
        );
    }

    public function testTheFirstTaskRequiringDisplayRedirectsToTheLoginTasksUrl(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
            new NoticeTask(),
        ]);

        $result = $tasks->runTasks();
        $this->assertStringContainsString('/login/tasks', (string) $result);
    }

    public function testADisplayTaskWillBeExecutedOnceDisplayed(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
            new NoticeTask(),
        ]);
        $tasks->runTasks();
        $tasklist = $tasks->displayTasks();

        $this->assertEquals(NoticeTask::class, $tasklist[0]::class);
    }

    public function testSeveralSubsequentTasksWithTheSameDisplayOptionGetDisplayedTogether(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
            new NoticeTask(),
            new NoticeTaskTwo(),
        ]);
        $tasks->runTasks();
        $tasklist = $tasks->displayTasks();

        $classes = array_map(fn($t) => $t::class, $tasklist);
        sort($classes);

        $this->assertEquals(
            [NoticeTask::class, NoticeTaskTwo::class],
            $classes
        );
    }

    public function testSeveralSubsequentTasksWithTheSameDisplayOptionGetExecutedTogether(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new HighPriorityTask(),
            new NoticeTask(),
            new NoticeTaskTwo(),
        ]);
        $tasks->runTasks();
        TestTask::$executed = [];
        $tasks->displayTasks();
        $tasks->runTasks(userConfirmed: true);

        $this->assertEquals(
            [NoticeTask::class, NoticeTaskTwo::class],
            TestTask::$executed
        );
    }

    public function testAfterConfirmationOfADisplayedTaskTheUserIsRedirectedToTheUrlStoredBeforeDisplaying(): void
    {
        $tasks = $this->getLoginTasks([
            new ConfirmTask(),
            new NoticeTask(),
        ]);
        $tasks->runTasks(target: '/redirect');
        $tasks->displayTasks();

        $result = $tasks->runTasks(userConfirmed: true);
        $this->assertStringContainsString('/login/tasks', (string) $result);

        $this->assertNull($tasks->runTasks());
        $tasks->displayTasks();

        $result = $tasks->runTasks(userConfirmed: true);
        $this->assertEquals('/redirect', $result);
    }

    public function testConfirmSeriesDisplay(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new ConfirmNoTask(),
            new ConfirmTask(),
            new TestTask(),
            new NoticeTask(),
            new ConfirmTaskTwo(),
            new TestTaskTwo(),
            new ConfirmTaskThree(),
            new NoticeTaskTwo(),
        ]);

        $result = $tasks->runTasks(target: '/redirect');
        $this->assertStringContainsString('/login/tasks', (string) $result);
        $this->assertEquals([], TestTask::$executed);

        $tasklist = $tasks->displayTasks();
        $classes = array_map(fn($t) => $t::class, $tasklist);
        sort($classes);
        $this->assertEquals([ConfirmNoTask::class, ConfirmTask::class], $classes);

        $result = $tasks->runTasks(confirmed: [0, 1], userConfirmed: true);
        $this->assertStringContainsString('/login/tasks', (string) $result);
        $this->assertEquals(
            [ConfirmNoTask::class, ConfirmTask::class, TestTask::class],
            TestTask::$executed
        );

        $this->assertNull($tasks->runTasks());

        $tasklist = $tasks->displayTasks();
        $classes = array_map(fn($t) => $t::class, $tasklist);
        $this->assertEquals([NoticeTask::class], $classes);

        $result = $tasks->runTasks(userConfirmed: true);
        $this->assertStringContainsString('/login/tasks', (string) $result);
        $this->assertEquals(
            [ConfirmNoTask::class, ConfirmTask::class, TestTask::class, NoticeTask::class],
            TestTask::$executed
        );

        $tasklist = $tasks->displayTasks();
        $classes = array_map(fn($t) => $t::class, $tasklist);
        $this->assertEquals([ConfirmTaskTwo::class], $classes);

        $result = $tasks->runTasks(confirmed: [0], userConfirmed: true);
        $this->assertStringContainsString('/login/tasks', (string) $result);
        $this->assertEquals(
            [
                ConfirmNoTask::class,
                ConfirmTask::class,
                TestTask::class,
                NoticeTask::class,
                ConfirmTaskTwo::class,
                TestTaskTwo::class,
            ],
            TestTask::$executed
        );

        $tasklist = $tasks->displayTasks();
        $classes = array_map(fn($t) => $t::class, $tasklist);
        $this->assertEquals([ConfirmTaskThree::class], $classes);

        $result = $tasks->runTasks(confirmed: [0], userConfirmed: true);
        $this->assertStringContainsString('/login/tasks', (string) $result);
        $this->assertEquals(
            [
                ConfirmNoTask::class,
                ConfirmTask::class,
                TestTask::class,
                NoticeTask::class,
                ConfirmTaskTwo::class,
                TestTaskTwo::class,
                ConfirmTaskThree::class,
            ],
            TestTask::$executed
        );

        $tasklist = $tasks->displayTasks();
        $classes = array_map(fn($t) => $t::class, $tasklist);
        $this->assertEquals([NoticeTaskTwo::class], $classes);

        $result = $tasks->runTasks(userConfirmed: true);
        $this->assertEquals('/redirect', $result);
        $this->assertEquals(
            [
                ConfirmNoTask::class,
                ConfirmTask::class,
                TestTask::class,
                NoticeTask::class,
                ConfirmTaskTwo::class,
                TestTaskTwo::class,
                ConfirmTaskThree::class,
                NoticeTaskTwo::class,
            ],
            TestTask::$executed
        );
    }

    public function testGetLabelsReturnsTranslatedIntervalNames(): void
    {
        $labels = LoginTasks::getLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey(TaskInterval::Yearly->value, $labels);
        $this->assertArrayHasKey(TaskInterval::Monthly->value, $labels);
        $this->assertArrayHasKey(TaskInterval::Weekly->value, $labels);
        $this->assertArrayHasKey(TaskInterval::Daily->value, $labels);
        $this->assertArrayHasKey(TaskInterval::Every->value, $labels);
        $this->assertCount(7, $labels);
    }

    public function testGetLoginTasksUrlDelegatesToBackend(): void
    {
        $tasks = $this->getLoginTasks([]);
        $url = $tasks->getLoginTasksUrl();

        $this->assertEquals('/login/tasks', (string) $url);
    }

    public function testShutdownStoresTasklistInCache(): void
    {
        $tasks = $this->getLoginTasks([new TestTask()]);
        $tasks->shutdown();

        $this->assertNotNull(Backend::$lastTasklistCache);
    }

    public function testShutdownHandlesExceptionSilently(): void
    {
        $backend = new BackendThrowsOnStore(
            [new TestTask()],
            'test'
        );
        $tasks = new LoginTasks($backend);

        // Should not throw
        $tasks->shutdown();
        $this->assertTrue(true);
    }

    public function testSystemTasksAreAlwaysExecutedFirst(): void
    {
        TestTask::$executed = [];
        $tasks = $this->getLoginTasks([
            new TestTask(),
            new TestSystemTask(),
            new HighPriorityTask(),
        ]);
        $tasks->runTasks();

        $this->assertEquals(
            TestSystemTask::class,
            TestTask::$executed[0]
        );
    }

    public function testSystemTaskWithSkipIsNotExecutedButIsRetried(): void
    {
        TestTask::$executed = [];
        SkippableSystemTask::$shouldSkip = true;

        $tasks = $this->getLoginTasks([
            new SkippableSystemTask(),
            new TestTask(),
        ]);
        $tasks->runTasks();

        $this->assertEquals([TestTask::class], TestTask::$executed);

        // Try again with skip disabled
        TestTask::$executed = [];
        SkippableSystemTask::$shouldSkip = false;

        $tasks2 = $this->getLoginTasks([
            new SkippableSystemTask(),
            new TestTask(),
        ]);
        $tasks2->runTasks();

        $this->assertEquals(
            SkippableSystemTask::class,
            TestTask::$executed[0]
        );
    }

    public function testPsr7UriSupportInRunTasks(): void
    {
        $tasks = $this->getLoginTasks([new TestTask()]);
        $uri = new Uri('/app/dashboard');

        $result = $tasks->runTasks(target: $uri);

        // Should complete and redirect with PSR-7 Uri
        $this->assertNull($result); // No redirect since no display needed
    }

    public function testStringUrlConvertedToUri(): void
    {
        $tasks = $this->getLoginTasks([new NoticeTask()]);

        // String URL should be accepted
        $result = $tasks->runTasks(target: '/app/home');

        $this->assertStringContainsString('/login/tasks', (string) $result);
    }

    private function getLoginTasks(array $tasks, Horde_Date|bool $lastRun = false): LoginTasks
    {
        if ($lastRun instanceof Horde_Date) {
            $lastRun = ['test' => $lastRun->timestamp()];
        }

        $backend = new Backend($tasks, 'test', $lastRun);
        return new LoginTasks($backend);
    }
}
