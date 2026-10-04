/*
 * Highlights the section you are reading in the contents list. Purely a
 * reading aid: the links work without it.
 */
(function () {
    const links = new Map(
        [...document.querySelectorAll('.contents a[href^="#"]')].map((link) => [
            link.getAttribute('href').slice(1),
            link,
        ]),
    );

    if (links.size === 0 || !('IntersectionObserver' in window)) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries
                .filter((entry) => entry.isIntersecting)
                .forEach((entry) => {
                    links.forEach((link) =>
                        link.classList.remove('is-current'),
                    );
                    links.get(entry.target.id)?.classList.add('is-current');
                });
        },
        { rootMargin: '-20% 0px -70% 0px' },
    );

    links.forEach((_, id) => {
        const section = document.getElementById(id);

        if (section) {
            observer.observe(section);
        }
    });
})();
