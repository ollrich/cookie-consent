/**
 * schongeil.de Cookie Consent – Admin JavaScript
 *
 * Handles: Scanner AJAX, Log Export/Delete, Cookie Row Management.
 */

(function () {
    'use strict';

    var admin = window.sgccAdmin || {};

    /**
     * Embed Scanner.
     */
    function initScanner() {
        var btn = document.getElementById('sgcc-run-scanner');
        var resultsContainer = document.getElementById('sgcc-scanner-results');

        if (!btn || !resultsContainer) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.textContent = admin.scanningText || 'Scanning...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', admin.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                btn.disabled = false;
                btn.textContent = admin.scanBtnText || 'Scan Website';

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            renderScanResults(resultsContainer, response.data);
                        } else {
                            resultsContainer.innerHTML = '<p>Error: ' + (response.data || 'Unknown error') + '</p>';
                        }
                    } catch (e) {
                        resultsContainer.innerHTML = '<p>Error parsing response.</p>';
                    }
                } else {
                    resultsContainer.innerHTML = '<p>Request failed.</p>';
                }

                resultsContainer.style.display = 'block';
            };

            xhr.send('action=sgcc_run_scanner&nonce=' + encodeURIComponent(admin.scannerNonce));
        });
    }

    /**
     * Render scan results.
     */
    function renderScanResults(container, data) {
        var summary = data.summary || {};
        var totalPosts = data.totalPosts || 0;
        var keys = Object.keys(summary);

        if (keys.length === 0) {
            container.innerHTML = '<h3>Scan Results</h3><p>No third-party embeds found in published content.</p>';
            return;
        }

        var html = '<h3>Scan Results</h3>';
        html += '<p>Found embeds in <strong>' + totalPosts + '</strong> post(s).</p>';

        keys.forEach(function (key) {
            var service = summary[key];
            var badgeClass = service.blocking ? 'sgcc-admin__scanner-badge--active' : 'sgcc-admin__scanner-badge--inactive';
            var badgeText = service.blocking ? 'Blocking active' : 'Not blocked';

            html += '<div class="sgcc-admin__scanner-service">';
            html += '<h4>' + escapeHtml(service.name) + ' <span class="sgcc-admin__scanner-badge ' + badgeClass + '">' + badgeText + '</span>';
            html += ' &mdash; ' + service.count + ' embed(s)</h4>';

            if (service.posts && service.posts.length) {
                html += '<ul class="sgcc-admin__scanner-posts">';
                service.posts.forEach(function (post) {
                    html += '<li>';
                    html += '<a href="' + escapeHtml(post.edit_url) + '">' + escapeHtml(post.post_title) + '</a>';
                    html += ' (<a href="' + escapeHtml(post.view_url) + '" target="_blank">View</a>)';
                    html += '</li>';
                });
                html += '</ul>';
            }

            html += '</div>';
        });

        container.innerHTML = html;
    }

    /**
     * Consent Log Export.
     */
    function initLogExport() {
        var btn = document.getElementById('sgcc-export-log');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var url = admin.ajaxUrl + '?action=sgcc_export_log&nonce=' + encodeURIComponent(admin.exportNonce);
            // Respect the currently visible date filter.
            var from = document.querySelector('input[name="date_from"]');
            var to = document.querySelector('input[name="date_to"]');
            if (from && from.value) url += '&date_from=' + encodeURIComponent(from.value);
            if (to && to.value) url += '&date_to=' + encodeURIComponent(to.value);
            window.location.href = url;
        });
    }

    /**
     * Consent Log Delete.
     */
    function initLogDelete() {
        var btn = document.getElementById('sgcc-delete-log');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm(admin.confirmDelete || 'Delete all entries?')) return;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', admin.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status === 200) {
                    window.location.reload();
                }
            };

            xhr.send('action=sgcc_delete_log&nonce=' + encodeURIComponent(admin.deleteNonce));
        });
    }

    /**
     * Cookie Row Management.
     */
    function initCookieRows() {
        var addBtn = document.getElementById('sgcc-add-cookie-row');
        var table = document.getElementById('sgcc-cookies-table');

        if (!addBtn || !table) return;

        // Add new row.
        addBtn.addEventListener('click', function () {
            var tbody = table.querySelector('tbody');
            var row = document.createElement('tr');
            row.className = 'sgcc-cookie-row';
            row.innerHTML = '<td><input type="text" name="cookie_name[]" value="" class="regular-text" /></td>' +
                '<td><input type="text" name="cookie_provider[]" value="" style="width:120px;" /></td>' +
                '<td><select name="cookie_category[]">' +
                    '<option value="necessary">Notwendig</option>' +
                    '<option value="audio">Audio</option>' +
                    '<option value="video">Video</option>' +
                '</select></td>' +
                '<td><input type="text" name="cookie_desc_de[]" value="" style="width:200px;" /></td>' +
                '<td><input type="text" name="cookie_desc_en[]" value="" style="width:200px;" /></td>' +
                '<td><input type="text" name="cookie_duration[]" value="" style="width:80px;" /></td>' +
                '<td><input type="text" name="cookie_type[]" value="" style="width:100px;" /></td>' +
                '<td><button type="button" class="button sgcc-remove-cookie-row">&times;</button></td>';
            tbody.appendChild(row);
        });

        // Remove row (event delegation).
        table.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.sgcc-remove-cookie-row');
            if (removeBtn) {
                var row = removeBtn.closest('tr');
                if (row) row.remove();
            }
        });
    }

    /**
     * Escape HTML.
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    /**
     * Init.
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initScanner();
            initLogExport();
            initLogDelete();
            initCookieRows();
        });
    } else {
        initScanner();
        initLogExport();
        initLogDelete();
        initCookieRows();
    }
})();
