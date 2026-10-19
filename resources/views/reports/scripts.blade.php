    {{-- Unified month/year/NOP picker with funnel logo trigger for all paired period and NOP filters --}}
    <script>
        $(function () {
            const filterIconSrc = "{{ asset('images/filter-icon.png') }}";
            const fallbackFunnelSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 512 512" fill="#3b82f6" class="simaster-filter-icon flex-shrink-0"><path d="M472 48H40c-22.1 0-40 17.9-40 40 0 11.8 5.2 23.1 14.3 30.7L192 268.8V448c0 17.7 14.3 32 32 32h64c17.7 0 32-14.3 32-32V268.8l177.7-150.1c9.1-7.7 14.3-18.9 14.3-30.7 0-22.1-17.9-40-40-40z"/></svg>';

            [
                ['#filter-bulan', '#filter-tahun', '#filter-nop'],
                ['#filter-month', '#filter-year', '#filter-nop'],
                ['#dashboard-month', '#dashboard-year', '#dashboard-nop'],
            ].forEach(function ([monthSelector, yearSelector, nopSelector]) {
                const $month = $(monthSelector);
                const $year = $(yearSelector);
                const $nop = nopSelector ? $(nopSelector) : $();

                if (!$month.length || !$year.length) return;
                if ($month.data('simaster-picker-initialized')) return;
                $month.data('simaster-picker-initialized', true);

                const $monthGroup = $month.closest('.d-flex');
                const $yearGroup = $year.closest('.d-flex');
                const $nopGroup = $nop.length ? $nop.closest('.d-flex') : $();
                const hasNop = $nop.length > 0;

                const pickerId = 'simaster-filter-' + Math.random().toString(36).slice(2, 9);

                // Dropdown Wrapper
                const $pickerGroup = $('<div>', {
                    class: 'dropdown simaster-filter-wrapper',
                });

                // Trigger Button dengan Icon Funnel / Corong Filter Biru
                const $pickerButton = $('<button>', {
                    id: pickerId + '-button',
                    type: 'button',
                    class: 'btn simaster-filter-btn d-inline-flex align-items-center gap-2',
                    'aria-expanded': 'false',
                });

                const $btnIcon = $('<img>', {
                    src: filterIconSrc,
                    width: 18,
                    height: 18,
                    alt: 'Filter',
                    class: 'simaster-filter-icon flex-shrink-0',
                }).on('error', function () {
                    $(this).replaceWith(fallbackFunnelSvg);
                });

                const $btnLabel = $('<span>', {
                    class: 'fw-semibold small',
                    text: 'Filter',
                });

                const $badge = $('<span>', {
                    class: 'badge simaster-filter-badge text-truncate',
                    style: 'max-width: 220px;',
                    text: 'Memuat...',
                });

                const $caret = $('<span class="small opacity-75 ms-auto">▾</span>');

                $pickerButton.append($btnIcon, $btnLabel, $badge, $caret);

                // Popover Dropdown Menu
                const $pickerMenu = $('<div>', {
                    class: 'dropdown-menu p-3 simaster-filter-menu',
                    style: hasNop ? 'min-width: 320px; max-width: 360px;' : 'min-width: 280px; max-width: 320px;',
                }).on('click', function (e) {
                    e.stopPropagation();
                });

                // Header Popover
                const $menuHeader = $('<div>', {
                    class: 'd-flex align-items-center justify-content-between pb-2 mb-2 border-bottom simaster-period-header',
                });
                const $headerTitle = $('<div>', {
                    class: 'd-flex align-items-center gap-2',
                }).append(
                    $('<img>', {
                        src: filterIconSrc,
                        width: 16,
                        height: 16,
                        alt: 'Filter',
                        class: 'simaster-filter-icon flex-shrink-0',
                    }).on('error', function () {
                        $(this).replaceWith(fallbackFunnelSvg);
                    }),
                    $('<strong>', { class: 'small', text: hasNop ? 'Filter Periode & NOP' : 'Filter Periode' })
                );
                const $closeBtn = $('<button>', {
                    type: 'button',
                    class: 'btn-close btn-close-sm simaster-period-close',
                    'aria-label': 'Tutup',
                }).on('click', function () {
                    $pickerMenu.removeClass('show');
                    $pickerButton.attr('aria-expanded', 'false');
                });
                $menuHeader.append($headerTitle, $closeBtn);

                // Section: Tahun
                const $yearSection = $('<div>', { class: 'mb-2' });
                const $yearRow = $('<div>', { class: 'd-flex align-items-center justify-content-between' });
                $yearRow.append($('<span>', { class: 'small fw-semibold text-secondary', text: 'Tahun:' }));

                const $yearStepper = $('<div>', { class: 'd-flex align-items-center gap-1' });
                const $previousYear = $('<button>', {
                    type: 'button',
                    class: 'btn btn-outline-secondary btn-sm simaster-year-btn',
                    text: '‹',
                    title: 'Tahun sebelumnya',
                    'aria-label': 'Tahun sebelumnya',
                });
                const $yearText = $('<input>', {
                    class: 'form-control form-control-sm text-center fw-bold simaster-period-year',
                    type: 'number',
                    min: 2000,
                    max: 2100,
                    title: 'Ketik tahun',
                    'aria-label': 'Tahun',
                    style: 'width: 80px; padding: 2px 4px;',
                });
                const $nextYear = $('<button>', {
                    type: 'button',
                    class: 'btn btn-outline-secondary btn-sm simaster-year-btn',
                    text: '›',
                    title: 'Tahun berikutnya',
                    'aria-label': 'Tahun berikutnya',
                });
                $yearStepper.append($previousYear, $yearText, $nextYear);
                $yearRow.append($yearStepper);
                $yearSection.append($yearRow);

                // Section: Bulan
                const $monthSection = $('<div>', { class: 'mb-2' });
                const $monthHeader = $('<div>', { class: 'd-flex align-items-center justify-content-between mb-1' });
                $monthHeader.append($('<span>', { class: 'small fw-semibold text-secondary', text: 'Bulan:' }));

                const $allMonths = $('<label>', {
                    class: 'form-check-label small d-flex align-items-center gap-1 text-primary mb-0',
                    style: 'cursor: pointer;',
                });
                const $allMonthsInput = $('<input>', {
                    class: 'form-check-input mt-0 simaster-all-months',
                    type: 'checkbox',
                });
                $allMonths.append($allMonthsInput, $('<span>', { class: 'fw-semibold', text: 'Semua bulan' }));
                $monthHeader.append($allMonths);

                const $monthGrid = $('<div>', {
                    class: 'd-grid gap-1',
                    style: 'grid-template-columns: repeat(4, 1fr);',
                });
                const months = [
                    [1, 'Jan'], [2, 'Feb'], [3, 'Mar'], [4, 'Apr'],
                    [5, 'Mei'], [6, 'Jun'], [7, 'Jul'], [8, 'Ags'],
                    [9, 'Sep'], [10, 'Okt'], [11, 'Nov'], [12, 'Des'],
                ];
                months.forEach(function ([monthNum, monthName]) {
                    $monthGrid.append($('<button>', {
                        type: 'button',
                        class: 'btn btn-sm btn-outline-secondary simaster-month-btn',
                        text: monthName,
                        'data-month': monthNum,
                    }));
                });
                $monthSection.append($monthHeader, $monthGrid);

                // Section: NOP (jika modul memiliki NOP)
                let $nopSection = null;
                let $nopSelectInside = null;
                let $nopSearchInput = null;
                if (hasNop) {
                    $nopSection = $('<div>', { class: 'simaster-nop-section pt-2 border-top mb-2' });
                    $nopSection.append($('<div>', { class: 'small fw-semibold text-secondary mb-1', text: 'Nomor Objek Pajak (NOP):' }));
                    $nopSearchInput = $('<input>', {
                        type: 'text',
                        class: 'form-control form-control-sm mb-1 simaster-nop-search',
                        placeholder: 'Cari NOP...',
                    }).on('input', function () {
                        const q = this.value.toLowerCase().trim();
                        if ($nopSelectInside) {
                            $nopSelectInside.find('option').each(function () {
                                const txt = $(this).text().toLowerCase();
                                const val = String($(this).val()).toLowerCase();
                                const match = !q || val === '' || txt.includes(q) || val.includes(q);
                                $(this).toggle(match);
                            });
                        }
                    });

                    $nopSelectInside = $('<select>', {
                        class: 'form-select form-select-sm simaster-picker-nop',
                        style: 'width: 100%;',
                    });

                    $nopSection.append($nopSearchInput, $nopSelectInside);
                }

                // Section: Footer / Apply Button
                const $footerSection = $('<div>', { class: 'pt-2 border-top mt-2' });
                const $applyBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-primary w-100 d-flex align-items-center justify-content-center gap-1 text-white shadow-sm simaster-period-apply',
                    html: '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/></svg> <span>Tutup &amp; Terapkan</span>',
                }).on('click', function () {
                    $pickerMenu.removeClass('show');
                    $pickerButton.attr('aria-expanded', 'false');
                });
                $footerSection.append($applyBtn);

                $pickerMenu.append($menuHeader, $yearSection, $monthSection);
                if (hasNop && $nopSection) {
                    $pickerMenu.append($nopSection);
                }
                $pickerMenu.append($footerSection);

                $pickerGroup.append($pickerButton, $pickerMenu);

                // Letakkan picker tepat di tempat filter awal berada
                const $targetAnchor = ($yearGroup.length && $yearGroup.index() < $monthGroup.index()) ? $yearGroup : $monthGroup;
                $targetAnchor.before($pickerGroup);

                // Sembunyikan elemen filter terpisah lama
                $monthGroup.add($yearGroup).add($nopGroup).addClass('d-none');

                function selectedYear() {
                    return Number($year.val()) || new Date().getFullYear();
                }

                function isAllMonths() {
                    const value = $month.val();
                    return value === '' || value === 'all' || value === null;
                }

                function ensureYearOption(year) {
                    if (!$year.find('option').filter(function () {
                        return String($(this).val()) === String(year);
                    }).length) {
                        $year.append($('<option>').val(year).text(year));
                    }
                    $year.val(String(year));
                }

                function syncNopOptions() {
                    if (!hasNop || !$nopSelectInside) return;
                    const currentVal = $nop.val();
                    $nopSelectInside.empty();
                    let count = 0;
                    $nop.find('option').each(function () {
                        $nopSelectInside.append($('<option>').val(this.value).text(this.text));
                        count++;
                    });
                    $nopSelectInside.val(currentVal);
                    if ($nopSearchInput) {
                        $nopSearchInput.toggle(count > 8);
                    }
                }

                function updatePicker() {
                    const year = selectedYear();
                    const all = isAllMonths();
                    const month = Number($month.val());
                    $yearText.val(String(year));

                    let periodText = all
                        ? 'Semua Bulan ' + year
                        : (months[month - 1]?.[1] || 'Bulan ' + month) + ' ' + year;

                    let badgeText = periodText;
                    let fullTitle = periodText;

                    if (hasNop) {
                        syncNopOptions();
                        const nopVal = $nop.val();
                        const selectedOption = $nop.find('option:selected');
                        const nopText = selectedOption.length ? selectedOption.text().trim() : (nopVal || 'Semua NOP');
                        const isAllNop = !nopVal || nopVal === '' || nopText.toLowerCase().includes('semua');
                        const displayNop = isAllNop ? 'Semua NOP' : (nopText.startsWith('NOP') ? nopText : 'NOP ' + nopText);

                        badgeText = periodText + ' · ' + displayNop;
                        fullTitle = 'Filter: ' + periodText + ' | ' + displayNop;

                        if ($nopSelectInside) {
                            $nopSelectInside.val(nopVal);
                        }
                    } else {
                        fullTitle = 'Filter: ' + periodText;
                    }

                    $badge.text(badgeText);
                    $pickerButton.attr('title', fullTitle);

                    $allMonthsInput.prop('checked', all);

                    const availableMonthsInSelect = $month.find('option').map(function () {
                        const val = $(this).val();
                        return (val !== '' && val !== 'all') ? Number(val) : null;
                    }).get().filter(Boolean);

                    $monthGrid.find('.simaster-month-btn')
                        .removeClass('active')
                        .toggleClass('btn-primary text-white', false)
                        .each(function () {
                            const m = Number($(this).data('month'));
                            const isAvailable = !availableMonthsInSelect.length || availableMonthsInSelect.includes(m);
                            $(this).prop('disabled', !isAvailable);
                            $(this).css('opacity', isAvailable ? '1' : '0.4');
                            if (!all && m === month) {
                                $(this).addClass('active btn-primary text-white');
                            }
                        });

                    const isDisabled = $year.prop('disabled') || $month.prop('disabled') || (hasNop && $nop.prop('disabled'));
                    $pickerButton.prop('disabled', isDisabled);
                    if ($nopSelectInside) {
                        $nopSelectInside.prop('disabled', isDisabled);
                    }
                }

                $pickerButton.on('click', function () {
                    $pickerMenu.toggleClass('show');
                    $(this).attr('aria-expanded', $pickerMenu.hasClass('show'));
                    updatePicker();
                });
                $previousYear.on('click', function () {
                    ensureYearOption(selectedYear() - 1);
                    $year.trigger('change');
                    updatePicker();
                });
                $nextYear.on('click', function () {
                    ensureYearOption(selectedYear() + 1);
                    $year.trigger('change');
                    updatePicker();
                });
                $yearText.on('change', function () {
                    const year = Number(this.value);
                    if (!Number.isInteger(year) || year < 2000 || year > 2100) {
                        updatePicker();
                        return;
                    }
                    ensureYearOption(year);
                    $year.trigger('change');
                    updatePicker();
                }).on('keydown', function (event) {
                    if (event.key === 'Enter') {
                        $(this).trigger('change');
                    }
                });
                $allMonthsInput.on('change', function () {
                    $month.val($month.find('option[value="all"]').length ? 'all' : '');
                    $month.trigger('change');
                    updatePicker();
                });
                $monthGrid.on('click', '.simaster-month-btn', function () {
                    if ($(this).prop('disabled')) return;
                    $month.val(String($(this).data('month'))).trigger('change');
                    updatePicker();
                });

                if (hasNop && $nopSelectInside) {
                    $nopSelectInside.on('change', function () {
                        $nop.val(this.value).trigger('change');
                        updatePicker();
                    });
                }

                $month.add($year).add($nop).on('change simaster:period-sync', updatePicker);
                new MutationObserver(updatePicker).observe($month[0], { childList: true });
                new MutationObserver(updatePicker).observe($year[0], { childList: true });
                if (hasNop && $nop[0]) {
                    new MutationObserver(function () {
                        syncNopOptions();
                        updatePicker();
                    }).observe($nop[0], { childList: true, subtree: true, attributes: true });
                }

                $(document).on('click', function (event) {
                    if (!$(event.target).closest($pickerGroup).length) {
                        $pickerMenu.removeClass('show');
                        $pickerButton.attr('aria-expanded', 'false');
                    }
                });

                updatePicker();
            });

            const $dashboardPeriod = $('#period-select');
            if ($dashboardPeriod.length) {
                const months = [
                    [1, 'Jan'], [2, 'Feb'], [3, 'Mar'], [4, 'Apr'],
                    [5, 'Mei'], [6, 'Jun'], [7, 'Jul'], [8, 'Ags'],
                    [9, 'Sep'], [10, 'Okt'], [11, 'Nov'], [12, 'Des'],
                ];
                const $button = $('<button>', {
                    type: 'button',
                    class: 'btn btn-outline-secondary btn-sm text-start',
                    style: 'min-width: 200px;',
                    'aria-expanded': 'false',
                });
                const $menu = $('<div>', {
                    class: 'dropdown-menu p-3 shadow',
                    style: 'min-width: 260px;',
                });
                const $yearInput = $('<input>', {
                    type: 'number', min: 2000, max: 2100,
                    class: 'form-control form-control-sm text-center',
                    style: 'width: 90px;',
                    title: 'Ketik tahun',
                    'aria-label': 'Tahun dashboard',
                });
                const $months = $('<div>', {
                    class: 'd-grid gap-1 mt-2',
                    style: 'grid-template-columns: repeat(3, 1fr);',
                });
                const $all = $('<label>', { class: 'form-check small mt-2' }).append(
                    $('<input>', { type: 'checkbox', class: 'form-check-input' }),
                    $('<span>', { class: 'form-check-label', text: ' Semua bulan' })
                );
                const $yearRow = $('<div>', {
                    class: 'd-flex align-items-center justify-content-between',
                });
                const $prev = $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-secondary', text: '‹' });
                const $next = $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-secondary', text: '›' });
                $yearRow.append($prev, $yearInput, $next);
                months.forEach(function ([month, name]) {
                    $months.append($('<button>', {
                        type: 'button',
                        class: 'btn btn-sm btn-outline-secondary',
                        text: name,
                        'data-month': month,
                    }));
                });
                $menu.append($yearRow, $all, $months);
                $dashboardPeriod.after($('<div>', { class: 'dropdown d-inline-block' }).append($button, $menu));
                $dashboardPeriod.addClass('d-none');

                function dashboardYear() {
                    const value = String($dashboardPeriod.val() || '').split('-')[0];
                    return Number(value) || new Date().getFullYear();
                }
                function dashboardMonth() {
                    return Number(String($dashboardPeriod.val() || '').split('-')[1]) || 0;
                }
                function syncDashboardPicker() {
                    const year = dashboardYear();
                    const month = dashboardMonth();
                    $yearInput.val(year);
                    $button.text(month === 0 ? 'Semua bulan ' + year : (months[month - 1]?.[1] || 'Pilih bulan') + ' ' + year);
                    $all.find('input').prop('checked', month === 0);
                    $months.children().toggleClass('btn-primary text-white', false)
                        .filter('[data-month="' + month + '"]').addClass('btn-primary text-white');
                }
                function setDashboardPeriod(year, month) {
                    const value = year + '-' + month;
                    if (!$dashboardPeriod.find('option[value="' + value + '"]').length) {
                        $dashboardPeriod.append($('<option>').val(value).text(month ? months[month - 1][1] + ' ' + year : 'Semua bulan ' + year));
                    }
                    $dashboardPeriod.val(value).trigger('change');
                    syncDashboardPicker();
                }
                $button.on('click', function () {
                    $menu.toggleClass('show');
                    $button.attr('aria-expanded', $menu.hasClass('show'));
                    syncDashboardPicker();
                });
                $prev.on('click', function () { setDashboardPeriod(dashboardYear() - 1, dashboardMonth()); });
                $next.on('click', function () { setDashboardPeriod(dashboardYear() + 1, dashboardMonth()); });
                $yearInput.on('change', function () {
                    const year = Number(this.value);
                    if (year >= 2000 && year <= 2100) setDashboardPeriod(year, dashboardMonth());
                });
                $all.on('change', 'input', function () { setDashboardPeriod(dashboardYear(), 0); });
                $months.on('click', 'button', function () {
                    setDashboardPeriod(dashboardYear(), Number($(this).data('month')));
                });
                $dashboardPeriod.on('change', syncDashboardPicker);
                new MutationObserver(syncDashboardPicker).observe($dashboardPeriod[0], { childList: true });
                $(document).on('click', function (event) {
                    if (!$(event.target).closest($button.parent()).length) {
                        $menu.removeClass('show');
                        $button.attr('aria-expanded', 'false');
                    }
                });
                syncDashboardPicker();
            }
        });
    </script>
    @once
    <script src="{{ asset('js/report-data-refresh.js') }}?v={{ filemtime(public_path('js/report-data-refresh.js')) }}" data-version-url="{{ route('reports.data-version') }}" data-initial-version="{{ \App\Support\ReportDataVersion::current() }}" defer></script>
    @endonce

    {{-- Shared compact contextual filter. Existing controls are moved, not
         cloned, so every page keeps its current IDs and event handlers. --}}
    <script>
        $(function () {
            let closeActiveFilter = null;
            document.querySelectorAll('[data-simaster-filter-panel]').forEach(function (source, index) {
                if (source.dataset.simasterFilterReady === 'true') return;
                source.dataset.simasterFilterReady = 'true';
                const reportRoot = source.closest('.simaster-report') || document.body;

                source.classList.add('simaster-filter-source');

                const placeInHeader = function (control) {
                    const heading = document.querySelector('.app-main h1');
                    const headingRow = heading ? heading.closest('.d-flex') : null;
                    if (headingRow) {
                        headingRow.classList.add('flex-wrap', 'gap-2');
                        const headingOwner = Array.from(headingRow.children).find(child => child === heading || child.contains(heading));
                        const actionNodes = Array.from(headingRow.children).filter(child => child !== headingOwner && child !== source);
                        let actionArea = actionNodes.length === 1 && actionNodes[0].classList.contains('d-flex')
                            ? actionNodes[0]
                            : null;

                        if (!actionArea) {
                            actionArea = document.createElement('div');
                            actionArea.className = 'd-flex align-items-center gap-2 flex-wrap';
                            actionNodes.forEach(node => actionArea.appendChild(node));
                            headingRow.appendChild(actionArea);
                        }
                        actionArea.appendChild(control);
                        return actionArea;
                    }

                    const launcher = document.createElement('div');
                    launcher.className = 'd-flex justify-content-end mb-3';
                    source.parentNode.insertBefore(launcher, source);
                    launcher.appendChild(control);
                    return launcher;
                };

                const moveExportActions = function (actionArea) {
                    source.querySelectorAll('a#btn-export, a#download-excel').forEach(link => actionArea.prepend(link));
                };

                const periodPicker = source.querySelector('.simaster-filter-wrapper');
                const periodMenu = periodPicker?.querySelector('.simaster-filter-menu');
                const periodApply = periodMenu?.querySelector('.simaster-period-apply');
                const filterForm = source.matches('form') ? source : source.querySelector('form');
                const formSubmit = filterForm?.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');

                // Header setiap modul selalu memakai satu tombol Filter biru.
                // Kontrol periode/NOP yang sudah ada hanya ditampilkan setelah
                // tombol tersebut diklik, sehingga event dan struktur lama tetap utuh.
                const popupId = `simaster-filter-popup-${index + 1}`;
                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'simaster-context-filter-trigger';
                trigger.setAttribute('aria-controls', popupId);
                trigger.setAttribute('aria-haspopup', 'dialog');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.textContent = 'Filter';
                const actionArea = placeInHeader(trigger);
                moveExportActions(actionArea);

                const overlay = document.createElement('div');
                overlay.className = 'simaster-filter-overlay';
                overlay.setAttribute('aria-hidden', 'true');
                overlay.innerHTML = `
                    <aside class="simaster-filter-drawer" id="${popupId}" role="dialog" aria-modal="true" aria-labelledby="${popupId}-title">
                        <div class="simaster-filter-drawer-header">
                            <h2 class="simaster-filter-drawer-title" id="${popupId}-title"></h2>
                            <button type="button" class="btn-close simaster-filter-close ms-auto" aria-label="Tutup filter"></button>
                        </div>
                        <div class="simaster-filter-drawer-body"></div>
                        ${periodMenu || formSubmit ? '' : `<div class="simaster-filter-drawer-footer">
                            <button type="button" class="btn btn-primary w-100 simaster-filter-apply">Selesai</button>
                        </div>`}
                    </aside>`;
                const popupBody = overlay.querySelector('.simaster-filter-drawer-body');
                const popup = overlay.querySelector('.simaster-filter-drawer');
                overlay.querySelector('.simaster-filter-drawer-title').textContent = source.dataset.simasterFilterPanel || 'Filter';

                if (periodPicker && periodMenu) {
                    source.querySelectorAll('.badge.text-bg-light').forEach(badge => {
                        const group = badge.closest('.d-flex');
                        if (group) group.classList.add('d-none');
                    });
                    source.querySelectorAll('.text-body-secondary.small').forEach(text => {
                        if (!text.closest('label')) text.classList.add('simaster-filter-muted-copy');
                    });

                    popupBody.appendChild(periodPicker);
                    const footer = periodMenu.querySelector('.pt-2.border-top.mt-2');
                    periodMenu.classList.add('simaster-filter-context-menu');
                    if (footer) {
                        periodMenu.insertBefore(source, footer);
                        // Reuse the original action and its listeners in one footer.
                        footer.className = 'simaster-filter-drawer-footer';
                        popup.appendChild(footer);
                        const label = periodApply?.querySelector('span');
                        if (label) label.textContent = 'Selesai';
                    } else {
                        periodMenu.appendChild(source);
                    }
                } else {
                    // Modul yang tidak memakai periode/NOP tetap menyimpan
                    // kontrol aslinya di pop-up kecil yang sama.
                    popupBody.appendChild(source);
                }
                if (formSubmit && !periodMenu) {
                    // Keep submit/reset inside their original form so Enter,
                    // validation, GET parameters and delegated handlers survive.
                    const resets = Array.from(filterForm.querySelectorAll('button[type="reset"], input[type="reset"], a.btn'))
                        .filter(control => control.type === 'reset' || /reset|atur ulang|↺/i.test(control.textContent + ' ' + (control.getAttribute('aria-label') || '')));
                    const actions = document.createElement('div');
                    actions.className = 'simaster-filter-form-actions';
                    [...resets, formSubmit].forEach(control => {
                        const previousParent = control.parentElement;
                        if (control.textContent.trim() === '↺') control.textContent = 'Reset';
                        actions.appendChild(control);
                        if (previousParent !== filterForm && previousParent.children.length === 0 && previousParent.textContent.trim() === '') {
                            previousParent.remove();
                        }
                    });
                    filterForm.appendChild(actions);
                }
                reportRoot.appendChild(overlay);

                const closeButton = overlay.querySelector('.simaster-filter-close');
                const positionPopup = function () {
                    const rect = trigger.getBoundingClientRect();
                    const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
                    const width = Math.min(390, viewportWidth - 24);
                    popup.style.width = `${width}px`;
                    popup.style.maxHeight = `${Math.max(0, window.innerHeight - 24)}px`;
                    popup.style.left = `${Math.max(12, Math.min(rect.right - width, viewportWidth - width - 12))}px`;
                    const height = popup.offsetHeight;
                    const below = rect.bottom + 8;
                    const above = rect.top - height - 8;
                    const preferred = below + height <= window.innerHeight - 12 ? below : (above >= 12 ? above : below);
                    popup.style.top = `${Math.max(12, Math.min(preferred, window.innerHeight - height - 12))}px`;
                };
                const openPopup = function () {
                    if (closeActiveFilter) closeActiveFilter();
                    closeActiveFilter = closePopup;
                    if (periodMenu) periodMenu.classList.add('show');
                    positionPopup();
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                    trigger.setAttribute('aria-expanded', 'true');
                    window.setTimeout(() => closeButton.focus(), 0);
                };
                const closePopup = function () {
                    if (closeActiveFilter === closePopup) closeActiveFilter = null;
                    if (periodMenu) periodMenu.classList.remove('show');
                    overlay.classList.remove('is-open');
                    overlay.setAttribute('aria-hidden', 'true');
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.focus();
                };

                trigger.addEventListener('click', openPopup);
                closeButton.addEventListener('click', closePopup);
                overlay.querySelector('.simaster-filter-apply')?.addEventListener('click', closePopup);
                periodMenu?.querySelector('.simaster-period-close')?.addEventListener('click', closePopup);
                periodApply?.addEventListener('click', closePopup);
                overlay.addEventListener('click', event => {
                    if (event.target === overlay) closePopup();
                });
                const repositionOpenPopup = function () {
                    if (overlay.classList.contains('is-open')) positionPopup();
                };
                window.addEventListener('resize', repositionOpenPopup);
                window.addEventListener('scroll', repositionOpenPopup, true);
                document.addEventListener('keydown', event => {
                    if (!overlay.classList.contains('is-open')) return;
                    if (event.key === 'Escape') closePopup();
                    if (event.key === 'Tab') {
                        const controls = Array.from(popup.querySelectorAll('button, a[href], input, select, textarea, [tabindex="0"]'))
                            .filter(control => !control.disabled && control.getClientRects().length > 0);
                        const first = controls[0];
                        const last = controls[controls.length - 1];
                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last?.focus();
                        } else if (!event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first?.focus();
                        }
                    }
                });
            });
        });
    </script>

    <script>
        // Palet bersama untuk seluruh diagram. Kombinasi warna sengaja dibuat
        // cukup lembut untuk pemakaian lama, tetapi tetap mudah dibedakan.
        window.SimasterChartPalette = Object.freeze({
            series: Object.freeze([
                '#3B82F6', // blue
                '#14B8A6', // teal
                '#8B5CF6', // violet
                '#F59E0B', // amber
                '#F97316', // coral
                '#D977A8', // rose
                '#06B6D4', // cyan
                '#6366F1', // indigo
                '#16A34A', // green
                '#DC2626', // red
                '#A855F7', // purple
                '#DB2777', // pink
                '#65A30D', // lime
                '#1E40AF', // navy
                '#A16207', // ochre
                '#475569', // slate
            ]),
            colorForIndex(index) {
                return this.series[index]
                    || `hsl(${Math.round((index * 137.508) % 360)}, 68%, ${index % 2 ? 45 : 38}%)`;
            },
            distinctColors(count) {
                return Array.from({ length: count }, (_, index) => this.colorForIndex(index));
            },
            financial: Object.freeze({
                revenue: '#3B82F6',
                cost: '#F97316',
                profit: '#14B8A6',
                loss: '#E76F51',
                inactive: '#94A3B8',
            }),
            status: Object.freeze({
                active: '#14B8A6',
                warning: '#F59E0B',
                pending: '#8B5CF6',
                neutral: '#94A3B8',
            }),
        });

        // Semua tooltip diagram menggunakan kontribusi terhadap total nilai
        // pada seri yang sama. Nilai absolut dipakai sebagai penyebut agar
        // diagram yang memuat laba/rugi tetap menghasilkan persentase jelas.
        window.SimasterChartMetrics = Object.freeze({
            share(value, values) {
                const numericValue = Number(value);
                const total = (values || []).reduce((sum, item) => {
                    const candidate = item && typeof item === 'object' ? item.y : item;
                    const numeric = Number(candidate);
                    return Number.isFinite(numeric) ? sum + Math.abs(numeric) : sum;
                }, 0);

                if (!Number.isFinite(numericValue) || total === 0) return null;

                return (Math.abs(numericValue) / total) * 100;
            },
            format(percentage, digits = 1) {
                if (percentage === null || percentage === undefined) return '—';

                return `${Number(percentage).toLocaleString('id-ID', {
                    maximumFractionDigits: digits,
                })}%`;
            },
            highcharts(point) {
                return this.share(point?.y, point?.series?.data || []);
            },
            chartJs(context) {
                return this.share(context?.raw, context?.dataset?.data || []);
            },
            apex(value, options) {
                const series = options?.w?.globals?.series || [];
                const currentSeries = series?.[options?.seriesIndex];

                // Bar/line menyimpan data per-seri (array bersarang), sedangkan
                // pie/donut ApexCharts memberi daftar nilai datar.
                return this.share(value, Array.isArray(currentSeries) ? currentSeries : series);
            },
        });

    </script>
