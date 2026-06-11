# Upgrading Horde_LoginTasks

## Decoupling shutdown timing from the library

The library's `register_shutdown_function` self-installation has been
made framework-controllable. Persistence timing is a framework concern
and the library should not commit to a specific moment by itself —
the historical default ran *after* a host framework's own shutdown
coordinator and any session writes the persist call made were dropped.

### Legacy class — `lib/Horde/LoginTasks.php`

Backwards-compatible. Existing callers and out-of-tree code do not need
to change.

A second optional constructor argument has been added:

```php
public function __construct(
    Horde_LoginTasks_Backend $backend,
    bool $registerShutdown = true
)
```

Default `true` preserves the historical auto-registration of a PHP
shutdown handler that calls `shutdown()` at request end.

Frameworks that own their own end-of-request coordination (e.g.
`horde/core` via `Horde_Shutdown_Task`) should pass `false` and
arrange the persist call themselves. The `shutdown()` method name
is unchanged.

### Modern class — `src/LoginTasks.php` (PSR-4 `Horde\LoginTasks\LoginTasks`)

**Breaking change.** The modern class no longer self-installs any
shutdown handler.

- Removed: `register_shutdown_function([$this, 'shutdown'])` in the
  constructor.
- Renamed: `shutdown()` → `persist()`. No back-compat wrapper.
- Constructor signature unchanged otherwise; no flag parameter.

#### Migration

If you currently rely on `shutdown()` running automatically at request
end, invoke `persist()` explicitly at the appropriate moment, or wrap
the instance in a framework-side shutdown adapter:

```php
// Before
$tasks = new \Horde\LoginTasks\LoginTasks($backend);
// (shutdown() fired automatically at request end)

// After — explicit persist
$tasks = new \Horde\LoginTasks\LoginTasks($backend);
$tasks->runTasks();
$tasks->persist();
```

If you manually call `shutdown()`, rename to `persist()`:

```php
// Before
$tasks->shutdown();

// After
$tasks->persist();
```

### Why this changed

The library's auto-registered PHP shutdown handler ran via
`register_shutdown_function`. When used inside `horde/core`, that
callback fired *after* `Horde_Shutdown::runTasks()` had already
executed — including the session shim's "mirror modern HordeSession
into `$_SESSION`" task. As a result, any write the persist call
made (including the `tasklist = true` "tasks done for this session"
marker) was dropped from `$_SESSION` and the next request re-ran
the login tasks. The user-visible symptom was login-task
notifications (e.g. "Last login: ...") repeating on every page
load.

Moving the timing decision out of the library lets the host
framework persist before its own session-flush step runs. In
`horde/core` this is done with a `Horde\Core\ShutdownTask\LoginTasks`
adapter that wraps the LoginTasks instance and runs as a
`Horde_Shutdown_Task`.
