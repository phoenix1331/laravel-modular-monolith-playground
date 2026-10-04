/*
 * The team sheet on the home page: installed versus enabled, by hand.
 *
 * Mirrors the real seed data (database/seeders/ClubSeeder.php) and module
 * definitions. Choosing a club shows which installed modules that club has
 * enabled. Removing a module takes it out of config/modules.php for every
 * club, and is refused while another installed module hard-depends on it,
 * the same rule ModuleRegistry enforces at boot.
 */
(function () {
    const modules = [
        { key: 'fixtures', name: 'Fixtures', hard: [], soft: [] },
        {
            key: 'availability',
            name: 'Availability',
            hard: ['fixtures'],
            soft: [],
        },
        {
            key: 'match-reports',
            name: 'Match Reports',
            hard: ['fixtures'],
            soft: [],
        },
        { key: 'subscriptions', name: 'Subscriptions', hard: [], soft: [] },
        {
            key: 'kit-shop',
            name: 'Kit Shop',
            hard: [],
            soft: ['subscriptions'],
        },
        { key: 'compliance', name: 'Compliance', hard: [], soft: [] },
        {
            key: 'messaging',
            name: 'Messaging',
            hard: [],
            soft: ['fixtures', 'subscriptions'],
        },
    ];

    const clubs = [
        {
            name: 'Riverside Juniors FC',
            short: 'Riverside',
            enabled: modules.map((m) => m.key),
        },
        {
            name: 'Dockside Rovers',
            short: 'Dockside',
            enabled: ['fixtures', 'availability', 'match-reports'],
        },
        {
            name: 'Hillcrest Ladies',
            short: 'Hillcrest',
            enabled: ['fixtures', 'subscriptions'],
        },
        {
            name: 'Valley Vets Walking Football',
            short: 'Valley Vets',
            enabled: [],
        },
    ];

    const root = document.querySelector('[data-team-sheet]');

    if (!root) {
        return;
    }

    const clubList = root.querySelector('.clubs');
    const tileList = root.querySelector('.tiles');
    const status = root.querySelector('[data-status]');
    const reset = root.querySelector('[data-reset]');

    const nameOf = (key) => modules.find((m) => m.key === key).name;
    const listOf = (names) =>
        names.length < 2
            ? names.join('')
            : `${names.slice(0, -1).join(', ')} and ${names[names.length - 1]}`;

    // Dockside first: a mix of on and off says more than Riverside's full house
    let club = clubs[1];
    const uninstalled = new Set();

    function describeDeps(module) {
        if (module.hard.length) {
            return `Needs ${listOf(module.hard.map(nameOf))}`;
        }

        if (module.soft.length) {
            return `Uses ${listOf(module.soft.map(nameOf))} when present`;
        }

        return 'No dependencies';
    }

    function stateOf(module) {
        if (uninstalled.has(module.key)) {
            return 'uninstalled';
        }

        return club.enabled.includes(module.key) ? 'enabled' : 'off';
    }

    function remove(module) {
        const dependents = modules.filter(
            (m) => !uninstalled.has(m.key) && m.hard.includes(module.key),
        );

        if (dependents.length) {
            say(
                `${module.name} stays installed: ${listOf(dependents.map((m) => m.name))} hard-depend on it, so the app would refuse to boot. Remove those first.`,
            );
            return;
        }

        uninstalled.add(module.key);
        say(
            `${module.name} removed from config/modules.php. No club can use it, and nothing else changes.`,
        );
        render();
    }

    function restore(module) {
        uninstalled.delete(module.key);
        say(
            `${module.name} is back in config/modules.php. Each club's flag for it was never touched.`,
        );
        render();
    }

    function say(message) {
        status.textContent = message;
    }

    function render() {
        clubList.querySelectorAll('button').forEach((button) => {
            button.setAttribute(
                'aria-pressed',
                String(button.dataset.club === club.short),
            );
        });

        tileList.replaceChildren(
            ...modules.map((module) => {
                const state = stateOf(module);
                const tile = document.createElement('li');
                tile.className = `tile is-${state}`;

                const name = document.createElement('span');
                name.className = 'tile-name';
                name.textContent = module.name;

                const deps = document.createElement('span');
                deps.className = 'tile-deps';
                deps.textContent = describeDeps(module);

                const label = document.createElement('span');
                label.className = 'tile-state';
                label.textContent = {
                    enabled: `Enabled for ${club.short}`,
                    off: `Installed, off for ${club.short}`,
                    uninstalled: 'Not installed',
                }[state];

                const action = document.createElement('button');
                action.type = 'button';
                action.className = 'tile-remove';
                action.textContent =
                    state === 'uninstalled' ? 'Install again' : 'Uninstall';
                action.setAttribute(
                    'aria-label',
                    `${state === 'uninstalled' ? 'Install' : 'Uninstall'} ${module.name}`,
                );
                action.addEventListener('click', () =>
                    state === 'uninstalled' ? restore(module) : remove(module),
                );

                tile.append(name, deps, label, action);

                return tile;
            }),
        );

        reset.hidden = uninstalled.size === 0;
    }

    clubs.forEach((c) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.club = c.short;
        button.textContent = c.short;
        button.title = c.name;
        button.addEventListener('click', () => {
            club = c;
            say(
                c.enabled.length
                    ? `${c.name} has ${c.enabled.length} of the installed modules enabled.`
                    : `${c.name} runs on Core alone: teams, players and the audit log.`,
            );
            render();
        });
        clubList.append(button);
    });

    reset.addEventListener('click', () => {
        uninstalled.clear();
        say('Every module is installed again.');
        render();
    });

    render();
})();
