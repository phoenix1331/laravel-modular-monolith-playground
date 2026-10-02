<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Core architecture rules
|--------------------------------------------------------------------------
|
| Pest arch tests read the code without running it and fail the suite when a
| rule is broken. Deptrac answers "which layer may depend on which", while
| these tests cover rules that live inside a layer: coding standards and,
| later, conventions every module has to follow (see tests/Arch/ModulesTest.php
| once modules exist).
|
| A new module gets these rules for free because the checks below target the
| whole App namespace, and module-specific rules target Modules.
|
| Arch tests inspect autoloadable classes only. A loose file of namespaced
| functions is invisible to them, which is one more reason to keep logic in
| classes.
|
*/

arch('every PHP file declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('debugging helpers never reach the codebase')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('php preset')->preset()->php();

arch('security preset')->preset()->security();
