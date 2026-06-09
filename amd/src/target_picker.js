define([], function() {
    var hoverClass = 'local-unittours-pick-hover';
    var current = null;
    var ignoredSelector = '.local-unittours-pickerbar, .local-unittours-pickerbar *';

    var cssPath = function(element) {
        if (!element || element === document.body) {
            return 'body';
        }
        if (element.id) {
            return '#' + CSS.escape(element.id);
        }

        var path = [];
        var node = element;
        while (node && node.nodeType === Node.ELEMENT_NODE && node !== document.body) {
            var selector = node.nodeName.toLowerCase();
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

    var detectTarget = function(element) {
        var navitem = element.closest('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]');
        if (navitem) {
            var navtext = (navitem.textContent || '').trim().toLowerCase().replace(/[^a-z ]/g, '').replace(/\s+/g, '_');
            var navmap = {
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

        var cm = element.closest('[data-for="cm"][data-id], li.activity[data-id], #module-0, [id^="module-"]');
        if (cm) {
            var cmid = cm.getAttribute('data-id') || cm.id.replace('module-', '');
            if (cmid && cmid !== '0') {
                return {
                    targettype: 'course_module',
                    targetref: cmid,
                    fallbackselector: '#module-' + cmid
                };
            }
        }

        var section = element.closest('[data-for="section"][data-id], li.section[data-sectionid], [id^="section-"]');
        if (section) {
            var sectionid = section.getAttribute('data-id') ||
                section.getAttribute('data-sectionid') ||
                section.getAttribute('data-number') ||
                section.id.replace('section-', '');
            if (sectionid) {
                return {
                    targettype: 'section',
                    targetref: sectionid,
                    fallbackselector: section.id ? '#' + CSS.escape(section.id) : cssPath(section)
                };
            }
        }

        var block = element.closest('[data-block], [class*="block_"]');
        if (block) {
            var blockname = block.getAttribute('data-block');
            if (!blockname) {
                var blockclass = Array.from(block.classList).find(function(name) {
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

        var region = element.closest('[data-region]');
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

    var setHover = function(element) {
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

    var redirectWithTarget = function(config, target) {
        var url = new URL(config.returnurl, window.location.href);
        url.searchParams.set('targettype', target.targettype);
        url.searchParams.set('targetref', target.targetref || '');
        url.searchParams.set('fallbackselector', target.fallbackselector || '');
        window.location.href = url.toString();
    };

    var init = function(config) {
        var bar = document.createElement('div');
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

    return {
        init: init
    };
});
