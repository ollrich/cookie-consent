/**
 * schongeil.de Cookie Consent – Frontend JavaScript
 *
 * Vanilla JS, no jQuery dependency.
 * Handles: consent cookie, banner, settings popup, embed loading, floating icon.
 * Consent stores per-service granularity.
 */

(function () {
    'use strict';

    var config = window.sgccConfig || {};

    /* ======================================================================
       Cookie Helpers
       ====================================================================== */

    var Cookie = {
        get: function () {
            var name = config.cookieName + '=';
            var parts = document.cookie.split(';');
            for (var i = 0; i < parts.length; i++) {
                var c = parts[i].trim();
                if (c.indexOf(name) === 0) {
                    try {
                        return JSON.parse(decodeURIComponent(c.substring(name.length)));
                    } catch (e) {
                        return null;
                    }
                }
            }
            return null;
        },

        set: function (data) {
            var expires = new Date();
            expires.setDate(expires.getDate() + (config.cookieLifetime || 365));
            var cookieValue = encodeURIComponent(JSON.stringify(data));
            var cookie = config.cookieName + '=' + cookieValue;
            cookie += '; expires=' + expires.toUTCString();
            cookie += '; path=' + (config.cookiePath || '/');
            cookie += '; SameSite=Lax';
            if (config.cookieSecure) {
                cookie += '; Secure';
            }
            document.cookie = cookie;
        },

        exists: function () {
            return this.get() !== null;
        }
    };

    /* ======================================================================
       Consent Manager
       Per-service consent: { services: { youtube: true, soundcloud: false, ... } }
       ====================================================================== */

    var Consent = {
        data: null,

        init: function () {
            this.data = Cookie.get();
        },

        isServiceGranted: function (serviceKey) {
            if (!this.data || !this.data.services) {
                return false;
            }
            return !!this.data.services[serviceKey];
        },

        isCategoryGranted: function (category) {
            if (!this.data || !this.data.services) {
                return false;
            }
            var services = config.services || {};
            for (var key in services) {
                if (services[key].category === category && !this.data.services[key]) {
                    return false;
                }
            }
            return true;
        },

        save: function (serviceConsents) {
            var data = {
                timestamp: new Date().toISOString(),
                version: config.consentVersion || '1.0',
                services: serviceConsents
            };
            Cookie.set(data);
            this.data = data;

            // Update Google Consent Mode v2 if available.
            this.updateGCM(serviceConsents);

            // Always attempt to log – the server checks whether logging is enabled.
            // This avoids stale logEnabled values on cached pages.
            if (config.ajaxUrl) {
                this.logConsent(data);
            }
        },

        acceptAll: function () {
            var consents = {};
            var services = config.services || {};
            for (var key in services) {
                consents[key] = true;
            }
            this.save(consents);
        },

        rejectAll: function () {
            var consents = {};
            var services = config.services || {};
            for (var key in services) {
                consents[key] = false;
            }
            this.save(consents);
        },

        /**
         * Get current consent state for all services.
         */
        getServiceConsents: function () {
            var consents = {};
            var services = config.services || {};
            for (var key in services) {
                consents[key] = this.isServiceGranted(key);
            }
            return consents;
        },

        /**
         * Log consent via navigator.sendBeacon (survives page reload/unload).
         * Falls back to synchronous XHR if sendBeacon is unavailable.
         */
        logConsent: function (data) {
            var payload = 'action=sgcc_log_consent' +
                '&nonce=' + encodeURIComponent(config.logNonce) +
                '&consent=' + encodeURIComponent(JSON.stringify(data));

            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/x-www-form-urlencoded' });
                navigator.sendBeacon(config.ajaxUrl, blob);
            } else {
                // Fallback: synchronous XHR (blocks briefly but guarantees delivery).
                var xhr = new XMLHttpRequest();
                xhr.open('POST', config.ajaxUrl, false);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(payload);
            }
        },

        /**
         * Update Google Consent Mode v2 based on current consent state.
         */
        updateGCM: function (serviceConsents) {
            if (typeof gtag !== 'function') return;

            // Check if any service has consent granted.
            var hasAnyConsent = false;
            for (var key in serviceConsents) {
                if (serviceConsents[key]) {
                    hasAnyConsent = true;
                    break;
                }
            }

            gtag('consent', 'update', {
                'analytics_storage': hasAnyConsent ? 'granted' : 'denied',
                'ad_storage': hasAnyConsent ? 'granted' : 'denied',
                'ad_user_data': hasAnyConsent ? 'granted' : 'denied',
                'ad_personalization': hasAnyConsent ? 'granted' : 'denied'
            });
        }
    };

    /* ======================================================================
       Banner Controller
       ====================================================================== */

    var Banner = {
        el: null,

        init: function () {
            this.el = document.getElementById('sgcc-banner');
            if (!this.el) return;
            if (config.isPrivacyPage) return;
            if (Cookie.exists()) return;

            this.bindEvents();
            this.show();
        },

        show: function () {
            if (!this.el) return;
            this.el.setAttribute('aria-hidden', 'false');
            void this.el.offsetHeight;
            this.el.classList.add('sgcc-banner--visible');
            this.el.focus();
        },

        hide: function () {
            if (!this.el) return;
            this.el.classList.remove('sgcc-banner--visible');
            this.el.setAttribute('aria-hidden', 'true');
        },

        bindEvents: function () {
            var self = this;

            var acceptBtn = this.el.querySelector('[data-sgcc-action="accept-all"]');
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function () {
                    Consent.acceptAll();
                    self.hide();
                    FloatingIcon.show();
                    window.location.reload();
                });
            }

            var rejectBtn = this.el.querySelector('[data-sgcc-action="reject-all"]');
            if (rejectBtn) {
                rejectBtn.addEventListener('click', function () {
                    Consent.rejectAll();
                    self.hide();
                    FloatingIcon.show();
                    // No reload needed – embeds are already blocked.
                });
            }

            var settingsBtn = this.el.querySelector('[data-sgcc-action="open-settings"]');
            if (settingsBtn) {
                settingsBtn.addEventListener('click', function () {
                    Popup.show();
                });
            }
        }
    };

    /* ======================================================================
       Settings Popup Controller
       ====================================================================== */

    var Popup = {
        overlay: null,
        popup: null,
        lastFocusedEl: null,

        init: function () {
            this.overlay = document.getElementById('sgcc-popup-overlay');
            this.popup = this.overlay ? this.overlay.querySelector('.sgcc-popup') : null;
            if (!this.overlay) return;
            this.bindEvents();
        },

        show: function () {
            if (!this.overlay) return;
            this.lastFocusedEl = document.activeElement;

            // Set toggle states from current consent.
            this.syncToggles();

            this.overlay.classList.add('sgcc-popup-overlay--visible');
            this.overlay.setAttribute('aria-hidden', 'false');

            if (this.popup) {
                var firstFocusable = this.popup.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) firstFocusable.focus();
            }
        },

        hide: function () {
            if (!this.overlay) return;
            this.overlay.classList.remove('sgcc-popup-overlay--visible');
            this.overlay.setAttribute('aria-hidden', 'true');
            if (this.lastFocusedEl) this.lastFocusedEl.focus();
        },

        /**
         * Sync toggle states to current consent.
         */
        syncToggles: function () {
            var serviceCheckboxes = this.overlay.querySelectorAll('.sgcc-service-toggle');
            for (var i = 0; i < serviceCheckboxes.length; i++) {
                var cb = serviceCheckboxes[i];
                var svcKey = cb.getAttribute('data-sgcc-service');
                cb.checked = Consent.isServiceGranted(svcKey);
            }

            // Sync category toggles.
            var categoryToggles = this.overlay.querySelectorAll('.sgcc-category-toggle');
            for (var j = 0; j < categoryToggles.length; j++) {
                var ct = categoryToggles[j];
                var catKey = ct.getAttribute('data-sgcc-category');
                ct.checked = Consent.isCategoryGranted(catKey);
            }
        },

        /**
         * Read toggle states and build service consents object.
         */
        readToggles: function () {
            var consents = {};
            var serviceCheckboxes = this.overlay.querySelectorAll('.sgcc-service-toggle');
            for (var i = 0; i < serviceCheckboxes.length; i++) {
                var cb = serviceCheckboxes[i];
                consents[cb.getAttribute('data-sgcc-service')] = cb.checked;
            }
            return consents;
        },

        bindEvents: function () {
            var self = this;

            // Close button.
            var closeBtn = this.overlay.querySelector('[data-sgcc-action="close-popup"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { self.hide(); });
            }

            // Overlay click.
            this.overlay.addEventListener('click', function (e) {
                if (e.target === self.overlay) self.hide();
            });

            // Category toggle: toggles all services in that category.
            var categoryToggles = this.overlay.querySelectorAll('.sgcc-category-toggle');
            for (var i = 0; i < categoryToggles.length; i++) {
                categoryToggles[i].addEventListener('change', function () {
                    var catKey = this.getAttribute('data-sgcc-category');
                    var checked = this.checked;
                    var serviceCheckboxes = self.overlay.querySelectorAll('.sgcc-service-toggle[data-sgcc-category="' + catKey + '"]');
                    for (var j = 0; j < serviceCheckboxes.length; j++) {
                        serviceCheckboxes[j].checked = checked;
                    }
                });
            }

            // Service toggle: update category toggle state.
            var serviceToggles = this.overlay.querySelectorAll('.sgcc-service-toggle');
            for (var k = 0; k < serviceToggles.length; k++) {
                serviceToggles[k].addEventListener('change', function () {
                    var catKey = this.getAttribute('data-sgcc-category');
                    var allInCat = self.overlay.querySelectorAll('.sgcc-service-toggle[data-sgcc-category="' + catKey + '"]');
                    var allChecked = true;
                    for (var m = 0; m < allInCat.length; m++) {
                        if (!allInCat[m].checked) { allChecked = false; break; }
                    }
                    var catToggle = self.overlay.querySelector('.sgcc-category-toggle[data-sgcc-category="' + catKey + '"]');
                    if (catToggle) catToggle.checked = allChecked;
                });
            }

            // Save selection.
            var saveBtn = this.overlay.querySelector('[data-sgcc-action="save-settings"]');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    var consents = self.readToggles();
                    Consent.save(consents);
                    self.hide();
                    Banner.hide();
                    FloatingIcon.show();
                    window.location.reload();
                });
            }

            // Accept all in popup.
            var acceptBtn = this.overlay.querySelector('[data-sgcc-action="popup-accept-all"]');
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function () {
                    Consent.acceptAll();
                    self.hide();
                    Banner.hide();
                    FloatingIcon.show();
                    window.location.reload();
                });
            }

            // Escape key.
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && self.overlay.classList.contains('sgcc-popup-overlay--visible')) {
                    self.hide();
                }
            });

            // Focus trap.
            if (this.popup) {
                this.popup.addEventListener('keydown', function (e) {
                    if (e.key !== 'Tab') return;
                    var focusable = self.popup.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    if (!focusable.length) return;
                    var first = focusable[0];
                    var last = focusable[focusable.length - 1];
                    if (e.shiftKey) {
                        if (document.activeElement === first) { last.focus(); e.preventDefault(); }
                    } else {
                        if (document.activeElement === last) { first.focus(); e.preventDefault(); }
                    }
                });
            }
        }
    };

    /* ======================================================================
       Floating Icon Controller
       Only shown on desktop operating systems (Windows, macOS, Linux).
       ====================================================================== */

    var FloatingIcon = {
        el: null,

        /**
         * Detect desktop OS via User-Agent.
         * Returns true for Windows, macOS, and Linux (excluding Android).
         */
        isDesktop: function () {
            var ua = navigator.userAgent || '';
            if (/Android/i.test(ua)) return false;
            if (/iPhone|iPad|iPod/i.test(ua)) return false;
            return /Windows|Macintosh|Mac OS X|Linux/i.test(ua);
        },

        init: function () {
            this.el = document.querySelector('.sgcc-floating-icon');
            if (!this.el) return;
            if (!this.isDesktop()) return;
            if (Cookie.exists()) this.show();
            this.bindEvents();
        },

        show: function () {
            if (!this.el || !this.isDesktop()) return;
            this.el.style.display = 'flex';
        },

        hide: function () {
            if (this.el) this.el.style.display = 'none';
        },

        bindEvents: function () {
            if (!this.el) return;
            this.el.addEventListener('click', function () { Popup.show(); });
        }
    };

    /* ======================================================================
       Embed Loader
       ====================================================================== */

    var Embeds = {
        init: function () {
            // Load embeds for consented services.
            this.loadConsented();
            // Bind placeholder buttons.
            this.bindPlaceholders();
        },

        /**
         * Load all embeds for services that have consent.
         */
        loadConsented: function () {
            var placeholders = document.querySelectorAll('.sgcc-placeholder');
            for (var i = 0; i < placeholders.length; i++) {
                var svcKey = placeholders[i].getAttribute('data-sgcc-service');
                if (svcKey && Consent.isServiceGranted(svcKey)) {
                    this.loadEmbed(placeholders[i]);
                }
            }
        },

        /**
         * Load a single embed.
         */
        loadEmbed: function (placeholder) {
            var embedContainer = placeholder.querySelector('.sgcc-placeholder__embed');
            if (!embedContainer) return;

            var iframe = embedContainer.querySelector('.sgcc-blocked-iframe');
            if (iframe) {
                var src = iframe.getAttribute('data-sgcc-src');
                if (src) {
                    iframe.setAttribute('src', src);
                    iframe.removeAttribute('data-sgcc-src');
                    iframe.classList.remove('sgcc-blocked-iframe');
                    iframe.style.width = '100%';
                    iframe.style.display = 'block';
                }
                // Explicitly show embed container (overrides inline display:none).
                embedContainer.style.display = 'block';
                placeholder.classList.add('sgcc-placeholder--loaded');
                var overlay = placeholder.querySelector('.sgcc-placeholder__overlay');
                if (overlay) overlay.style.display = 'none';
                var thumb = placeholder.querySelector('.sgcc-placeholder__thumbnail');
                if (thumb) thumb.style.display = 'none';

                // For plugins that use absolute iframe positioning (e.g. ARVE),
                // the wrapper divs must fill their parent's height.
                var iframePos = window.getComputedStyle(iframe).position;
                if (iframePos === 'absolute') {
                    placeholder.style.height = '100%';
                    placeholder.style.minHeight = '0';
                    embedContainer.style.height = '100%';
                }
                return;
            }

            // Handle non-iframe oEmbed content (Instagram blockquotes, etc.).
            var blockedEmbed = embedContainer.querySelector('.sgcc-blocked-embed');
            if (blockedEmbed) {
                // Decode base64-encoded original HTML (avoids double-encoding issues).
                // Use UTF-8-safe decoding: atob returns Latin1, so we must
                // convert multi-byte sequences back to proper UTF-8 characters.
                var b64 = blockedEmbed.getAttribute('data-sgcc-html-b64');
                var originalHtml;
                if (b64) {
                    try {
                        originalHtml = decodeURIComponent(atob(b64).split('').map(function (c) {
                            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                        }).join(''));
                    } catch (e) {
                        // Fallback: plain atob (works if content is ASCII-only).
                        originalHtml = atob(b64);
                    }
                } else {
                    originalHtml = blockedEmbed.getAttribute('data-sgcc-html');
                }
                if (originalHtml) {
                    // IMPORTANT: Strip <script> tags from HTML before innerHTML insertion.
                    // Scripts added via innerHTML do NOT execute (browser security).
                    // We load the required scripts separately via DOM API below.
                    var cleanHtml = originalHtml.replace(/<script[^>]*instagram\.com\/embed\.js[^>]*><\/script>/gi, '');

                    var temp = document.createElement('div');
                    temp.innerHTML = cleanHtml;
                    placeholder.parentNode.replaceChild(temp, placeholder);

                    // Handle Instagram embeds: load embed.js via DOM API and process.
                    if (originalHtml.indexOf('instagram-media') !== -1) {
                        var processIG = function () {
                            if (window.instgrm && window.instgrm.Embeds) {
                                window.instgrm.Embeds.process();
                            }
                        };

                        // Retry mechanism: Instagram embed.js may not be immediately
                        // available (Firefox tracking protection, slow load, etc.).
                        var retryProcess = function (attemptsLeft) {
                            if (window.instgrm && window.instgrm.Embeds) {
                                window.instgrm.Embeds.process();
                                return;
                            }
                            if (attemptsLeft > 0) {
                                setTimeout(function () { retryProcess(attemptsLeft - 1); }, 500);
                            }
                        };

                        if (window.instgrm) {
                            // embed.js already loaded from another embed – just re-process.
                            processIG();
                        } else {
                            // Remove any dead script tags (inserted via innerHTML, never executed).
                            var deadScripts = document.querySelectorAll('script[src*="instagram.com/embed.js"]');
                            for (var ds = 0; ds < deadScripts.length; ds++) {
                                deadScripts[ds].parentNode.removeChild(deadScripts[ds]);
                            }
                            // Create a fresh script element via DOM API (this WILL execute).
                            var igScript = document.createElement('script');
                            igScript.src = 'https://www.instagram.com/embed.js';
                            igScript.async = true;
                            igScript.onload = function () {
                                // Delay process() to ensure DOM is settled (Firefox needs this).
                                setTimeout(processIG, 100);
                            };
                            igScript.onerror = function () {
                                // embed.js blocked (e.g. Firefox tracking protection):
                                // show the raw blockquote as visible fallback.
                                var blockquotes = temp.querySelectorAll('blockquote.instagram-media');
                                for (var bq = 0; bq < blockquotes.length; bq++) {
                                    blockquotes[bq].style.display = 'block';
                                    blockquotes[bq].style.maxWidth = '540px';
                                    blockquotes[bq].style.margin = '0 auto';
                                    blockquotes[bq].style.border = '1px solid #dbdbdb';
                                    blockquotes[bq].style.borderRadius = '4px';
                                    blockquotes[bq].style.padding = '16px';
                                    blockquotes[bq].style.background = '#fff';
                                }
                            };
                            document.body.appendChild(igScript);
                            // Fallback retry in case onload fires before instgrm is set.
                            retryProcess(6);
                        }
                    }
                }
            }
        },

        /**
         * Bind click events on placeholder buttons.
         */
        bindPlaceholders: function () {
            var self = this;

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-sgcc-action="load-single"]');
                if (btn) {
                    var placeholder = btn.closest('.sgcc-placeholder');
                    if (!placeholder) return;

                    var svcKey = placeholder.getAttribute('data-sgcc-service');
                    var alwaysCheckbox = placeholder.querySelector('[data-sgcc-action="load-always"]');

                    if (alwaysCheckbox && alwaysCheckbox.checked && svcKey) {
                        // Grant consent for this service and reload.
                        var consents = Consent.getServiceConsents();
                        consents[svcKey] = true;
                        Consent.save(consents);
                        Banner.hide();
                        FloatingIcon.show();
                        window.location.reload();
                    } else {
                        // Load only this one embed (no consent saved).
                        self.loadEmbed(placeholder);
                    }
                }
            });
        }
    };

    /* ======================================================================
       Initialization
       ====================================================================== */

    function init() {
        Consent.init();
        Banner.init();
        Popup.init();
        FloatingIcon.init();
        Embeds.init();

        // Add thumbnail class to placeholders.
        var placeholders = document.querySelectorAll('.sgcc-placeholder');
        for (var i = 0; i < placeholders.length; i++) {
            if (placeholders[i].querySelector('.sgcc-placeholder__thumbnail')) {
                placeholders[i].classList.add('sgcc-placeholder--has-thumbnail');
            }
        }

        // Global listener: open settings popup from any element with
        // data-sgcc-action="open-settings" OR href="#sgcc-settings".
        // This handles [sgcc_settings] shortcode links, custom menu links, footer links, etc.
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-sgcc-action="open-settings"], a[href="#sgcc-settings"]');
            if (!trigger) return;
            // Skip if inside banner or floating icon (already handled by their own listeners).
            if (trigger.closest('#sgcc-banner') || trigger.classList.contains('sgcc-floating-icon')) return;
            e.preventDefault();
            Popup.show();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
