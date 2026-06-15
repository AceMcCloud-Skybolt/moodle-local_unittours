define([], function() {
    var active = null;
    var launcher = null;
    var speechState = null;
    var highlightClass = 'local-unittours-highlight';
    var previouslyFocused = null;

    var storageKey = function(payload, tour) {
        return 'local_unittours_completed_' + payload.userid + '_' + payload.courseid + '_' + tour.id;
    };

    var isCompleted = function(payload, tour) {
        return tour.showmode !== 'always' && window.localStorage.getItem(storageKey(payload, tour)) === '1';
    };

    var markCompleted = function(payload, tour, status) {
        window.localStorage.setItem(storageKey(payload, tour), '1');

        if (!payload.completeurl || !window.fetch) {
            return;
        }

        var form = new FormData();
        form.append('courseid', payload.courseid);
        form.append('tourid', tour.id);
        form.append('status', status);
        form.append('sesskey', payload.sesskey);

        window.fetch(payload.completeurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).catch(function() {
            // Local storage still prevents repeat display if the network request fails.
        });
    };

    var markStarted = function(payload, tour) {
        if (!payload.starturl || !window.fetch) {
            return;
        }

        var form = new FormData();
        form.append('courseid', payload.courseid);
        form.append('tourid', tour.id);
        form.append('sesskey', payload.sesskey);

        window.fetch(payload.starturl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).catch(function() {
            // Analytics should never block tour playback.
        });
    };

    var removeActive = function() {
        if (speechState && speechState.isPlaying && window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        speechState = null;

        if (!active) {
            return;
        }

        if (active.target) {
            active.target.classList.remove(highlightClass);
        }
        if (active.scrim) {
            active.scrim.remove();
        }
        if (active.popover) {
            active.popover.remove();
        }
        if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
            previouslyFocused.focus();
        }
        previouslyFocused = null;
        active = null;
    };

    var supportsSpeech = function() {
        return typeof window !== 'undefined' && !!window.speechSynthesis && typeof window.SpeechSynthesisUtterance === 'function';
    };

    var stopSpeech = function(button, payload) {
        if (!supportsSpeech()) {
            return;
        }
        window.speechSynthesis.cancel();
        if (button) {
            button.textContent = payload.strings.playaudio;
        }
        if (speechState) {
            speechState.isPlaying = false;
        }
    };

    var playSpeech = function(step, button, payload) {
        if (!supportsSpeech()) {
            if (button) {
                button.disabled = true;
                button.title = payload.strings.audiounavailable;
            }
            return;
        }

        if (!step.audiotext) {
            return;
        }

        if (speechState && speechState.isPlaying) {
            stopSpeech(button, payload);
            return;
        }

        var utterance = new SpeechSynthesisUtterance(step.audiotext);
        if (step.audiolang) {
            utterance.lang = step.audiolang;
        }
        utterance.onstart = function() {
            speechState = {isPlaying: true};
            button.textContent = payload.strings.stopaudio;
        };
        utterance.onend = function() {
            speechState = {isPlaying: false};
            button.textContent = payload.strings.playaudio;
        };
        utterance.onerror = function() {
            speechState = {isPlaying: false};
            button.textContent = payload.strings.playaudio;
        };

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
    };

    var clearLocalCompletion = function(payload, tourid) {
        window.localStorage.removeItem('local_unittours_completed_' + payload.userid + '_' + payload.courseid + '_' + tourid);
    };

    var getTourById = function(payload, tourid) {
        for (var i = 0; i < payload.tours.length; i++) {
            if (payload.tours[i].id === tourid) {
                return payload.tours[i];
            }
        }
        return null;
    };

    var htmlToElement = function(html) {
        var template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstChild;
    };

    var normaliseText = function(text) {
        return (text || '').trim().toLowerCase().replace(/[^a-z ]/g, '').replace(/\s+/g, '_');
    };

    var clickMoreMenu = function() {
        var candidates = document.querySelectorAll('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]');
        for (var i = 0; i < candidates.length; i++) {
            if (normaliseText(candidates[i].textContent) === 'more') {
                candidates[i].click();
                return true;
            }
        }
        return false;
    };

    var findNavigationTarget = function(targetref) {
        var candidates = document.querySelectorAll('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]');
        for (var i = 0; i < candidates.length; i++) {
            if (normaliseText(candidates[i].textContent) === targetref) {
                return candidates[i];
            }
        }

        if (targetref === 'unit_tours' && clickMoreMenu()) {
            candidates = document.querySelectorAll('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]');
            for (var j = 0; j < candidates.length; j++) {
                if (normaliseText(candidates[j].textContent) === targetref) {
                    return candidates[j];
                }
            }
        }

        return null;
    };

    var focusableElements = function(container) {
        return Array.from(container.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        )).filter(function(element) {
            return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
        });
    };

    var trapFocus = function(event, popover) {
        if (event.key !== 'Tab') {
            return;
        }

        var focusable = focusableElements(popover);
        if (!focusable.length) {
            event.preventDefault();
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    var findTarget = function(step) {
        var selectors = [];

        if (step.targettype === 'course_module' && step.targetref) {
            selectors.push('#module-' + step.targetref);
            selectors.push('[data-id="' + step.targetref + '"]');
        } else if (step.targettype === 'section' && step.targetref) {
            selectors.push('[data-sectionid="' + step.targetref + '"]');
            selectors.push('[data-id="' + step.targetref + '"]');
        } else if (step.targettype === 'block' && step.targetref) {
            selectors.push('[data-block="' + step.targetref + '"]');
            selectors.push('.block_' + step.targetref);
        } else if (step.targettype === 'course_index' && step.targetref) {
            selectors.push('[data-for="section"][data-id="' + step.targetref + '"]');
            selectors.push('[data-for="cm"][data-id="' + step.targetref + '"]');
        } else if (step.targettype === 'course_navigation' && step.targetref) {
            return findNavigationTarget(step.targetref);
        } else if (step.targettype === 'page_region' && step.targetref) {
            selectors.push('[data-region="' + step.targetref + '"]');
            selectors.push('#' + step.targetref);
        } else if (step.targettype === 'selector' && step.targetref) {
            selectors.push(step.targetref);
        }

        if (step.fallbackselector) {
            selectors.push(step.fallbackselector);
        }

        for (var i = 0; i < selectors.length; i++) {
            try {
                var match = document.querySelector(selectors[i]);
                if (match) {
                    return match;
                }
            } catch (error) {
                // Ignore invalid selectors entered during early prototyping.
            }
        }

        return step.targettype === 'unattached' || step.showiftargetmissing ? document.body : null;
    };

    var positionPopover = function(popover, target, placement) {
        var margin = 14;
        var rect = target === document.body ? {
            top: window.innerHeight / 2,
            bottom: window.innerHeight / 2,
            left: window.innerWidth / 2,
            right: window.innerWidth / 2,
            width: 0,
            height: 0
        } : target.getBoundingClientRect();

        var poprect = popover.getBoundingClientRect();
        var top = rect.bottom + margin;
        var left = rect.left + Math.max(0, (rect.width - poprect.width) / 2);

        if (target === document.body) {
            top = Math.max(margin, (window.innerHeight - poprect.height) / 2);
            left = Math.max(margin, (window.innerWidth - poprect.width) / 2);
        } else if (placement === 'top') {
            top = rect.top - poprect.height - margin;
        } else if (placement === 'left') {
            top = rect.top;
            left = rect.left - poprect.width - margin;
        } else if (placement === 'right') {
            top = rect.top;
            left = rect.right + margin;
        }

        top = Math.max(margin, Math.min(top, window.innerHeight - poprect.height - margin));
        left = Math.max(margin, Math.min(left, window.innerWidth - poprect.width - margin));

        popover.style.top = top + 'px';
        popover.style.left = left + 'px';
    };

    var showStep = function(payload, tour, index) {
        removeActive();

        var step = tour.steps[index];
        var target = findTarget(step);
        if (!target) {
            if (index + 1 < tour.steps.length) {
                showStep(payload, tour, index + 1);
            }
            return;
        }

        if (target !== document.body) {
            target.scrollIntoView({block: 'center', inline: 'nearest'});
            target.classList.add(highlightClass);
        }

        var scrim = step.backdrop ? htmlToElement('<div class="local-unittours-scrim"></div>') : null;
        if (scrim) {
            document.body.appendChild(scrim);
        }

        var current = index + 1;
        var total = tour.steps.length;
        var counter = payload.strings.stepcounter
            .replace('{$a->current}', current)
            .replace('{$a->total}', total);
        var nextlabel = current === total ? payload.strings.done : payload.strings.next;
        var backdisabled = index === 0 ? ' disabled' : '';

        var popover = htmlToElement(
            '<section class="local-unittours-popover" role="dialog" aria-modal="true" tabindex="-1">' +
                '<h3 id="local-unittours-title"></h3>' +
                '<div class="local-unittours-content"></div>' +
                '<div class="local-unittours-progress"></div>' +
                '<div class="local-unittours-actions">' +
                    '<button type="button" class="btn btn-link local-unittours-skip"></button>' +
                    '<button type="button" class="btn btn-secondary local-unittours-audio"></button>' +
                    '<button type="button" class="btn btn-secondary local-unittours-back"' + backdisabled + '></button>' +
                    '<button type="button" class="btn btn-primary local-unittours-next"></button>' +
                '</div>' +
            '</section>'
        );

        popover.querySelector('h3').textContent = step.title;
        popover.setAttribute('aria-labelledby', 'local-unittours-title');
        popover.querySelector('.local-unittours-content').innerHTML = step.content;
        popover.querySelector('.local-unittours-progress').textContent = counter;
        popover.querySelector('.local-unittours-skip').textContent = payload.strings.skip;
        popover.querySelector('.local-unittours-audio').textContent = payload.strings.playaudio;
        popover.querySelector('.local-unittours-back').textContent = payload.strings.back;
        popover.querySelector('.local-unittours-next').textContent = nextlabel;
        document.body.appendChild(popover);

        positionPopover(popover, target, step.placement);
        previouslyFocused = previouslyFocused || document.activeElement;
        popover.focus();
        popover.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                markCompleted(payload, tour, 'skipped');
                removeActive();
                return;
            }
            trapFocus(event, popover);
        });

        popover.querySelector('.local-unittours-skip').addEventListener('click', function() {
            markCompleted(payload, tour, 'skipped');
            removeActive();
        });
        var audioButton = popover.querySelector('.local-unittours-audio');
        if (!step.audioenabled || !step.audiotext) {
            audioButton.remove();
        } else {
            audioButton.addEventListener('click', function() {
                playSpeech(step, audioButton, payload);
            });
            if (step.audioautoplay) {
                setTimeout(function() {
                    playSpeech(step, audioButton, payload);
                }, 120);
            }
        }
        popover.querySelector('.local-unittours-back').addEventListener('click', function() {
            if (index > 0) {
                showStep(payload, tour, index - 1);
            }
        });
        popover.querySelector('.local-unittours-next').addEventListener('click', function() {
            if (index + 1 < tour.steps.length) {
                showStep(payload, tour, index + 1);
                return;
            }

            markCompleted(payload, tour, 'complete');
            removeActive();
        });

        active = {
            target: target === document.body ? null : target,
            scrim: scrim,
            popover: popover
        };
    };

    var showTour = function(payload, tourid) {
        var tour = getTourById(payload, tourid);
        if (!tour || !tour.steps || !tour.steps.length) {
            return;
        }
        markStarted(payload, tour);
        showStep(payload, tour, 0);
    };

    var initLauncher = function(payload) {
        if (!payload.tours || !payload.tours.length) {
            return;
        }

        var primarytourid = payload.tours[0].id;
        launcher = htmlToElement(
            '<div class="local-unittours-launcher">' +
                '<button type="button" class="btn btn-primary local-unittours-launch"></button>' +
                '<button type="button" class="btn btn-secondary local-unittours-reset"></button>' +
            '</div>'
        );
        launcher.querySelector('.local-unittours-launch').textContent = payload.strings.showtour;
        launcher.querySelector('.local-unittours-reset').textContent = payload.strings.resettourcompletion;
        if (!payload.canmanage) {
            launcher.querySelector('.local-unittours-reset').remove();
        }
        document.body.appendChild(launcher);

        launcher.querySelector('.local-unittours-launch').addEventListener('click', function() {
            showTour(payload, primarytourid);
        });

        var resetbutton = launcher.querySelector('.local-unittours-reset');
        if (resetbutton) {
            resetbutton.addEventListener('click', function() {
                resetbutton.disabled = true;
                resetbutton.textContent = payload.strings.resettingshort;
                for (var i = 0; i < payload.tours.length; i++) {
                    clearLocalCompletion(payload, payload.tours[i].id);
                }
                if (payload.reseturl && window.fetch) {
                    var form = new FormData();
                    form.append('courseid', payload.courseid);
                    form.append('sesskey', payload.sesskey);
                    window.fetch(payload.reseturl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).finally(function() {
                        resetbutton.disabled = false;
                        resetbutton.textContent = payload.strings.resettourcompletion;
                    });
                } else {
                    resetbutton.disabled = false;
                    resetbutton.textContent = payload.strings.resettourcompletion;
                }
            });
        }
    };

    var init = function(payload) {
        if (!payload || !payload.tours || !payload.tours.length) {
            return;
        }

        initLauncher(payload);

        if (!payload.autorunids || !payload.autorunids.length) {
            return;
        }

        for (var i = 0; i < payload.autorunids.length; i++) {
            var tour = getTourById(payload, payload.autorunids[i]);
            if (tour && !isCompleted(payload, tour)) {
                showTour(payload, tour.id);
                return;
            }
        }
    };

    return {
        init: init
    };
});
