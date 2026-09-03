const hoverClass = 'local-unittours-pick-hover';
let current = null;
const ignoredSelector = '.local-unittours-pickerbar, .local-unittours-pickerbar *';

const cssPath = function(element) {
    if (!element || element === document.body) {
        return 'body';
    }
    if (element.id) {
        return '#' + CSS.escape(element.id);
    }

    let path = [];
    let node = element;
    while (node && node.nodeType === Node.ELEMENT_NODE && node !== document.body) {
        let selector = node.nodeName.toLowerCase();
        if (node.classList.length) {
            selector += '.' + Array.from(node.classList).slice(0, 2).map(function(name) {
                return CSS.escape(name);
            }).join('.');
        }
        path.unshift(selector);
        node = node.parentElement;
    }

    return path.slice(-4).join(' > ');
};

const detectTarget = function(element) {
    let navitem = element.closest('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]');
    if (navitem) {
        let navtext = (navitem.textContent || '').trim().toLowerCase().replace(/[^a-z ]/g, '').replace(/\s+/g, '_');
        let navmap = {
            'course': 'course',
            'settings': 'settings',
            'participants': 'participants',
            'grades': 'grades',
            'activities': 'activities',
            'more': 'more',
            'unit_tours': 'unit_tours'
        };
        if (navmap[navtext]) {
            return {
                targettype: 'course_navigation',
                targetref: navmap[navtext],
                fallbackselector: ''
            };
        }
    }

    let cm = element.closest('[data-for="cm"][data-id], li.activity[data-id], #module-0, [id^="module-"]');
    if (cm) {
        let cmid = cm.getAttribute('data-id') || cm.id.replace('module-', '');
        if (cmid && cmid !== '0') {
            return {
                targettype: 'course_module',
                targetref: cmid,
                fallbackselector: '#module-' + cmid
            };
        }
    }

    let section = element.closest('[data-for="section"][data-id], li.section[data-sectionid], [id^="section-"]');
    if (section) {
        let sectionid = section.getAttribute('data-id') ||
            section.getAttribute('data-sectionid');
        if (!sectionid) {
            let sectionlink = section.querySelector('a[href*="/course/section.php?id="]');
            if (sectionlink) {
                sectionid = new URL(sectionlink.href, window.location.href).searchParams.get('id');
            }
        }
        if (sectionid) {
            return {
                targettype: 'section',
                targetref: sectionid,
                fallbackselector: '[data-sectionid="' + sectionid + '"], [data-for="section"][data-id="' + sectionid + '"]'
            };
        }
    }

    let block = element.closest('[data-block], [class*="block_"]');
    if (block) {
        let blockname = block.getAttribute('data-block');
        if (!blockname) {
            let blockclass = Array.from(block.classList).find(function(name) {
                return name.indexOf('block_') === 0;
            });
            blockname = blockclass ? blockclass.replace('block_', '') : '';
        }
        if (blockname) {
            return {
                targettype: 'block',
                targetref: blockname,
                fallbackselector: '[data-block="' + blockname + '"]'
            };
        }
    }

    let region = element.closest('[data-region]');
    if (region) {
        return {
            targettype: 'page_region',
            targetref: region.getAttribute('data-region'),
            fallbackselector: cssPath(region)
        };
    }

    return {
        targettype: 'selector',
        targetref: cssPath(element),
        fallbackselector: cssPath(element)
    };
};

const setHover = function(element) {
    if (current === element) {
        return;
    }
    if (current) {
        current.classList.remove(hoverClass);
    }
    current = element;
    if (current) {
        current.classList.add(hoverClass);
    }
};

const redirectWithTarget = function(config, target) {
    let url = new URL(config.returnurl, window.location.href);
    url.searchParams.set('targettype', target.targettype);
    url.searchParams.set('targetref', target.targetref || '');
    url.searchParams.set('fallbackselector', target.fallbackselector || '');
    window.location.href = url.toString();
};

export const init = function(config) {
    let bar = document.createElement('div');
    bar.className = 'local-unittours-pickerbar';
    bar.innerHTML = '<strong></strong><span></span><button type="button" class="btn btn-secondary btn-sm"></button>';
    bar.querySelector('strong').textContent = config.strings.picktarget;
    bar.querySelector('span').textContent = config.strings.picktargetinstructions;
    bar.querySelector('button').textContent = config.strings.cancel;
    document.body.appendChild(bar);

    bar.querySelector('button').addEventListener('click', function() {
        window.location.href = config.returnurl;
    });

    document.addEventListener('mouseover', function(event) {
        if (event.target.closest(ignoredSelector)) {
            setHover(null);
            return;
        }
        setHover(event.target);
    }, true);

    document.addEventListener('click', function(event) {
        if (event.target.closest(ignoredSelector)) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        redirectWithTarget(config, detectTarget(event.target));
    }, true);
};
