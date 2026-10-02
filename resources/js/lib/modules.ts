import type { FrontendModule } from '@/types';

/**
 * Declare a module's public frontend entry. Used as the default export of
 * `resources/js/modules/{key}/index.ts`:
 *
 *     export default defineModule({
 *         key: 'fixtures',
 *         navigation: [
 *             { title: 'Fixtures', href: fixtures.index(), icon: CalendarDays },
 *         ],
 *     });
 *
 * A typed identity function: it only exists so the object is checked against
 * FrontendModule where it is written.
 */
export function defineModule(module: FrontendModule): FrontendModule {
    return module;
}
