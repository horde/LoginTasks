<?php

class Horde_LoginTasks_Stub_Backend extends Horde_LoginTasks_Backend
{
    public static $lastRun;
    public static $lastTasklistCache;

    private $_tasklist;
    private $_tasklistCache = false;

    public function __construct(array $tasks, $last_run = false)
    {
        $this->_tasklist = $tasks;
        if ($last_run !== true) {
            self::$lastRun = $last_run;
        }
    }

    public function getTasklistFromCache()
    {
        return $this->_tasklistCache;
    }

    public function storeTasklistInCache($tasklist)
    {
        $this->_tasklistCache = $tasklist;
        self::$lastTasklistCache = $tasklist;
    }

    public function getTasks()
    {
        return $this->_tasklist;
    }

    public function getLastRun()
    {
        return self::$lastRun;
    }

    public function setLastRun(array $last)
    {
        self::$lastRun = $last;
    }

    public function markLastRun()
    {
        $lasttasks = $this->getLastRun();
        if ($lasttasks === false || !is_array($lasttasks)) {
            $lasttasks = [];
        }
        $lasttasks['test'] = time();
        self::$lastRun = $lasttasks;
    }

    public function redirect($url)
    {
        return $url;
    }

    public function getLoginTasksUrl()
    {
        return 'URL';
    }
}

class Horde_LoginTasks_Stub_Backend_ThrowsOnStore extends Horde_LoginTasks_Backend
{
    private $_tasklist;
    private $_lastRun;

    public function __construct(array $tasks, $last_run = false)
    {
        $this->_tasklist = $tasks;
        $this->_lastRun = $last_run;
    }

    public function getTasklistFromCache()
    {
        return false;
    }

    public function storeTasklistInCache($tasklist)
    {
        throw new Horde_Exception('Storage failed');
    }

    public function getTasks()
    {
        return $this->_tasklist;
    }

    public function getLastRun()
    {
        return $this->_lastRun;
    }

    public function setLastRun(array $last)
    {
        $this->_lastRun = $last;
    }

    public function markLastRun()
    {
        $this->_lastRun = ['test' => time()];
    }

    public function redirect($url)
    {
        return $url;
    }

    public function getLoginTasksUrl()
    {
        return 'URL';
    }
}

class Horde_LoginTasks_Stub_Task extends Horde_LoginTasks_Task
{
    public static $executed;

    public $interval = Horde_LoginTasks::EVERY;
    public $display = Horde_LoginTasks::DISPLAY_NONE;
    public $priority = Horde_LoginTasks::PRIORITY_NORMAL;

    public function execute()
    {
        Horde_LoginTasks_Stub_Task::$executed[] = get_class($this);
    }
}

class Horde_LoginTasks_Stub_TaskTwo extends Horde_LoginTasks_Stub_Task {}

class Horde_LoginTasks_Stub_Confirm extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmTwo extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmThree extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmNo extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_NO;
}

class Horde_LoginTasks_Stub_Day extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::DAILY;
}

class Horde_LoginTasks_Stub_First extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::FIRST_LOGIN;
}

class Horde_LoginTasks_Stub_High extends Horde_LoginTasks_Stub_Task
{
    public $priority = Horde_LoginTasks::PRIORITY_HIGH;
}

class Horde_LoginTasks_Stub_Month extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::MONTHLY;
}

class Horde_LoginTasks_Stub_Notice extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_NOTICE;
}

class Horde_LoginTasks_Stub_NoticeTwo extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_NOTICE;
}

class Horde_LoginTasks_Stub_Once extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::ONCE;
}

class Horde_LoginTasks_Stub_Week extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::WEEKLY;
}

class Horde_LoginTasks_Stub_Year extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::YEARLY;
}

class Horde_LoginTasks_Stub_SystemTask extends Horde_LoginTasks_SystemTask
{
    public function execute()
    {
        Horde_LoginTasks_Stub_Task::$executed[] = get_class($this);
    }
}

class Horde_LoginTasks_Stub_SystemTask_Skip extends Horde_LoginTasks_SystemTask
{
    public static $shouldSkip = false;

    public function execute()
    {
        Horde_LoginTasks_Stub_Task::$executed[] = get_class($this);
    }

    public function skip()
    {
        return self::$shouldSkip;
    }
}
