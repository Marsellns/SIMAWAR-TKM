document.addEventListener("DOMContentLoaded", async function () {
    "use strict";

    const loadState = document.getElementById("erLoadState");

    /* =========================================================
       EQUIPMENT RELOCATION
       ========================================================= */

    const chartAvailable = typeof Chart !== "undefined";
    const chartTextColor = () => document.documentElement.getAttribute("data-bs-theme") === "dark" ? "#d4dde8" : "#475569";
    const chartGridColor = () => document.documentElement.getAttribute("data-bs-theme") === "dark" ? "#425062" : "#e2e8f0";
    const chartPalette = window.SimasterChartPalette;
    const chartColor = index => chartPalette.colorForIndex(index);

    if (!chartAvailable) {
        console.warn(
            "EQUIPMENT RELOCATION: Chart.js tidak ditemukan. KPI/table/filter tetap dijalankan tanpa chart."
        );
    }

    try {
        await window.__equipmentRelocationReady;
        if (loadState) loadState.textContent = "";
    } catch (error) {
        if (loadState) {
            loadState.textContent = "Gagal memuat inventaris Equipment Relocation: " + error.message;
            loadState.classList.add("error");
        }
        return;
    }

    if (!chartAvailable && loadState) {
        loadState.textContent += " Diagram belum tersedia karena pustaka grafik gagal dimuat.";
    }

    /* =========================================================
       CONSTANTS
       ========================================================= */

    const ALL = "All";

    const PIC_OPTIONS = [
        "NOP BOGOR",
        "NOP BEKASI",
        "NOP KARAWANG",
        "NBAE"
    ];

    const PROGRESS_OPTIONS = [
        "NOT YET",
        "ON GOING",
        "DONE"
    ];

    /* =========================================================
       STATE
       ========================================================= */

    let allData = Array.isArray(window.__equipmentInventoryData)
        ? window.__equipmentInventoryData
        : [];

    let drilldownCategory = "All Data";
    let searchKeyword = "";
    let currentPage = 1;
    let pageSize = 10;

    const charts = {};

    const appliedFilters = {
        nop: ALL,
        group: ALL,
        type: ALL,
        status: ALL,
        pic: ALL,
        safe: ALL
    };

    /* =========================================================
       ELEMENTS
       ========================================================= */

    const $ = id => document.getElementById(id);

    const els = {
        tableBody: $("erTableBody"),
        tableTitle: $("erTableTitle"),
        tableDescription: $("erTableDescription"),
        tableResultInfo: $("erTableResultInfo"),
        activeFilterBadge: $("erActiveFilterBadge"),

        search: $("erSearch"),
        pageSizeSelect: $("erPageSizeSelect"),
        exportData: $("erExportData"),
        resetFilter: $("erResetFilter"),

        prevPage: $("erPrevPage"),
        nextPage: $("erNextPage"),
        paginationInfo: $("erPaginationInfo"),

        /* -----------------------------------------------------
           KPI
           ----------------------------------------------------- */

        kpi: {
            bbpTotal: $("erKpiBbpTotal"),
            bbpSafe: $("erKpiBbpSafe"),
            ruTotal: $("erKpiRuTotal"),
            ruSafe: $("erKpiRuSafe"),
            onGoing: $("erKpiOnGoing")
        },

        /* -----------------------------------------------------
           FILTER
           ----------------------------------------------------- */

        openFilterBtn: $("erOpenFilterBtn"),
        closeFilterBtn: $("erCloseFilterBtn"),
        filterOverlay: $("erFilterOverlay"),

        filterNop: $("erFilterNop"),
        filterGroup: $("erFilterGroup"),
        filterType: $("erFilterType"),
        filterStatus: $("erFilterStatus"),
        filterPic: $("erFilterPic"),
        filterSafe: $("erFilterSafe"),

        clearGlobalFilterBtn: $("erClearGlobalFilterBtn"),
        applyGlobalFilterBtn: $("erApplyGlobalFilterBtn")
    };

    /* =========================================================
       HELPERS
       ========================================================= */

    function normalize(value) {
        return String(value ?? "")
            .trim()
            .toLowerCase();
    }

    function fmt(value) {
        return new Intl.NumberFormat("id-ID").format(
            Number(value) || 0
        );
    }

    function pct(value) {
        const number = Number(value) || 0;

        return `${number.toFixed(1)}%`;
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function typeLabel(item) {
        return `${item.equipment_group || "-"} - ${item.equipment_type || "-"}`;
    }

    /* =========================================================
       SAFE / ELIGIBILITY
       ========================================================= */

    function isSafe(item) {
        return (
            item.is_safe_to_reloc === true ||
            normalize(item.is_safe_to_reloc) === "true" ||
            normalize(item.is_safe_to_reloc) === "1"
        );
    }

    function safeLabel(item) {
        return isSafe(item)
            ? "SAFE"
            : "NOT SAFE";
    }

    function safeBadgeClass(item) {
        return isSafe(item)
            ? "status-success"
            : "status-danger";
    }

    /* =========================================================
       RELOCATION STATUS
       ========================================================= */

    function statusOf(item) {
        const progress = String(
            item.progress ?? ""
        ).trim();

        return progress || "Belum Diisi";
    }

    function statusBadgeClass(status) {
        if (status === "Belum Diisi") {
            return "status-warning";
        }

        if (status === "NOT YET") {
            return "status-warning";
        }

        if (status === "ON GOING") {
            return "status-info";
        }

        if (status === "DONE") {
            return "status-success";
        }

        return "";
    }

    /* =========================================================
       FILTERING
       ========================================================= */

    function applyGlobalFilters(data) {
        return data.filter(item => {

            if (
                appliedFilters.nop !== ALL &&
                normalize(item.nop) !== normalize(appliedFilters.nop)
            ) {
                return false;
            }

            if (
                appliedFilters.group !== ALL &&
                normalize(item.equipment_group) !== normalize(appliedFilters.group)
            ) {
                return false;
            }

            if (
                appliedFilters.type !== ALL &&
                normalize(item.equipment_type) !== normalize(appliedFilters.type)
            ) {
                return false;
            }

            if (
                appliedFilters.pic !== ALL &&
                normalize(item.pic || "") !== normalize(appliedFilters.pic)
            ) {
                return false;
            }

            if (
                appliedFilters.status !== ALL &&
                statusOf(item) !== appliedFilters.status
            ) {
                return false;
            }

            if (
                appliedFilters.safe !== ALL &&
                safeLabel(item) !== appliedFilters.safe
            ) {
                return false;
            }

            return true;
        });
    }

    function applyDrilldown(data) {

        if (
            drilldownCategory === "All Data" ||
            drilldownCategory === "All"
        ) {
            return data;
        }

        if (drilldownCategory === "SafeToReloc") {
            return data.filter(item => isSafe(item));
        }

        if (drilldownCategory.startsWith("NOP:")) {

            const nop = drilldownCategory.slice(4);

            return data.filter(
                item =>
                    normalize(item.nop) === normalize(nop)
            );
        }

        const typeMatch =
            drilldownCategory.match(/^(RU|BBP)Type:(.*)$/);

        if (typeMatch) {

            const [, group, type] = typeMatch;

            return data.filter(item =>
                normalize(item.equipment_group) ===
                    normalize(group) &&
                normalize(item.equipment_type) ===
                    normalize(type)
            );
        }

        return data.filter(
            item => statusOf(item) === drilldownCategory
        );
    }

    function applySearch(data) {

        if (!searchKeyword) {
            return data;
        }

        const kw = normalize(searchKeyword);

        return data.filter(item =>
            normalize(item.site_id).includes(kw) ||
            normalize(item.equipment_type).includes(kw) ||
            normalize(item.serial_number).includes(kw) ||
            normalize(item.site_target_source || "").includes(kw) ||
            normalize(item.donor_acceptor || "").includes(kw)
        );
    }

    function getFilteredData() {
        return applyGlobalFilters(allData);
    }

    function getDisplayData() {

        let data = applyGlobalFilters(allData);

        data = applyDrilldown(data);

        data = applySearch(data);

        return data;
    }

    /* =========================================================
       KPI
       RU & BBP COMPLETELY SEPARATED
       ========================================================= */

    function updateKPI() {

        const data = getFilteredData();

        /*
         * -----------------------------------------------
         * GROUP SPLIT
         * -----------------------------------------------
         */

        const bbpData = data.filter(
            item =>
                normalize(item.equipment_group) === "bbp"
        );

        const ruData = data.filter(
            item =>
                normalize(item.equipment_group) === "ru"
        );


        /*
         * -----------------------------------------------
         * TOTALS
         * -----------------------------------------------
         */

        const bbpTotal = bbpData.length;

        const bbpSafe = bbpData.filter(
            item => isSafe(item)
        ).length;

        const ruTotal = ruData.length;

        const ruSafe = ruData.filter(
            item => isSafe(item)
        ).length;


        /*
         * -----------------------------------------------
         * ON GOING (RU + BBP COMBINED)
         * -----------------------------------------------
         */

        const onGoing = data.filter(
            item =>
                statusOf(item) === "ON GOING"
        ).length;


        /*
         * UPDATE DOM
         */

        if (els.kpi.bbpTotal) {
            els.kpi.bbpTotal.textContent =
                fmt(bbpTotal);
        }

        if (els.kpi.bbpSafe) {
            els.kpi.bbpSafe.textContent =
                fmt(bbpSafe);
        }

        if (els.kpi.ruTotal) {
            els.kpi.ruTotal.textContent =
                fmt(ruTotal);
        }

        if (els.kpi.ruSafe) {
            els.kpi.ruSafe.textContent =
                fmt(ruSafe);
        }

        if (els.kpi.onGoing) {
            els.kpi.onGoing.textContent =
                fmt(onGoing);
        }
    }

    /* =========================================================
       KPI CLICK
       ========================================================= */

    function setupKPIClick() {

        document
            .querySelectorAll(".kpi-card[data-er-category]")
            .forEach(card => {

                const raw =
                    card.dataset.erCategory || "";

                const handleClick = () => {

                    const parts =
                        raw.split(":");

                    const group =
                        parts[0];

                    const category =
                        parts.slice(1).join(":");

                    /*
                     * KPI click automatically filters
                     * the selected equipment group.
                     * "All" means no group restriction
                     * (used by the combined On Going card).
                     */

                    appliedFilters.group =
                        group === "All"
                            ? ALL
                            : group;


                    if (category === "SafeToReloc") {

                        appliedFilters.safe = "SAFE";

                        appliedFilters.status = ALL;

                    }

                    else {

                        appliedFilters.safe = ALL;

                        appliedFilters.status =
                            category || ALL;
                    }


                    drilldownCategory =
                        "All Data";

                    currentPage = 1;


                    syncFilterInputs();

                    renderDashboard();


                    document
                        .getElementById("erTableSection")
                        ?.scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                };


                card.addEventListener(
                    "click",
                    handleClick
                );


                card.addEventListener(
                    "keydown",
                    e => {

                        if (
                            e.key === "Enter" ||
                            e.key === " "
                        ) {

                            e.preventDefault();

                            handleClick();
                        }
                    }
                );
            });
    }

    /* =========================================================
       CHART MANAGEMENT
       ========================================================= */

    function destroyChart(key) {

        if (!charts[key]) {
            return;
        }

        try {
            charts[key].destroy();
        }

        catch (error) {

            console.warn(
                `Failed to destroy chart ${key}:`,
                error
            );
        }

        delete charts[key];
    }

    /* =========================================================
       COMMON CHART OPTIONS
       ========================================================= */

    function commonOptions(extra = {}) {

        return {

            responsive: true,

            maintainAspectRatio: false,

            animation: {
                duration: 250
            },

            layout: {

                padding: {
                    top: 28,
                    right: 24,
                    bottom: 18,
                    left: 8
                }
            },

            plugins: {

                legend: {
                    display: false
                },

                tooltip: {
                    enabled: true
                },

                ...(extra.plugins || {})
            },

            ...extra,

            plugins: {

                legend: {
                    display: false
                },

                tooltip: {
                    enabled: true
                },

                ...(extra.plugins || {})
            }
        };
    }

    /* =========================================================
       GENERIC BAR VALUE LABEL PLUGIN
       ========================================================= */

    function createBarValueLabelPlugin(pluginId) {

        return {

            id: pluginId,

            afterDatasetsDraw(chart) {

                const ctx = chart.ctx;

                ctx.save();

                chart.data.datasets.forEach(
                    (dataset, datasetIndex) => {

                        const meta =
                            chart.getDatasetMeta(datasetIndex);

                        if (
                            !meta ||
                            meta.hidden
                        ) {
                            return;
                        }

                        meta.data.forEach(
                            (bar, index) => {

                                const value =
                                    Number(
                                        dataset.data[index] || 0
                                    );

                                if (value <= 0) {
                                    return;
                                }

                                ctx.font =
                                    "700 15px Inter, Arial, sans-serif";

                                ctx.fillStyle = chartTextColor();

                                ctx.textAlign =
                                    "center";

                                ctx.textBaseline =
                                    "bottom";

                                ctx.fillText(
                                    fmt(value),
                                    bar.x,
                                    bar.y - 7
                                );
                            }
                        );
                    }
                );

                ctx.restore();
            }
        };
    }

    /* =========================================================
       EQUIPMENT PER NOP
       RU + BBP GROUPED BAR
       ========================================================= */

    function renderNopChart() {

        if (!chartAvailable) {
            return;
        }

        const canvas =
            $("erNopChart");

        if (!canvas) {
            return;
        }

        destroyChart("erNopChart");


        /*
         * ALL EQUIPMENT (SAFE & NOT SAFE)
         *
         * Safe/Not Safe can still be narrowed down
         * separately via the Safe to Reloc filter.
         */

        const data =
            getFilteredData();


        /*
         * NOP LIST
         */

        const nops = [
            ...new Set(
                data
                    .map(
                        item =>
                            String(
                                item.nop || ""
                            ).trim()
                    )
                    .filter(Boolean)
            )
        ].sort(
            (a, b) =>
                a.localeCompare(b, "id")
        );


        /*
         * RU COUNT PER NOP
         */

        const ruCounts =
            nops.map(nop =>
                data.filter(item =>
                    normalize(item.nop) ===
                        normalize(nop) &&
                    normalize(item.equipment_group) ===
                        "ru"
                ).length
            );


        /*
         * BBP COUNT PER NOP
         */

        const bbpCounts =
            nops.map(nop =>
                data.filter(item =>
                    normalize(item.nop) ===
                        normalize(nop) &&
                    normalize(item.equipment_group) ===
                        "bbp"
                ).length
            );


        /*
         * TOTAL BY GROUP
         *
         * Percentage tooltip NOP menggunakan
         * total equipment dari group masing-masing
         * (seluruh status, Safe & Not Safe).
         */

        const totalRuAll =
            ruCounts.reduce(
                (sum, value) =>
                    sum + value,
                0
            );

        const totalBbpAll =
            bbpCounts.reduce(
                (sum, value) =>
                    sum + value,
                0
            );


        /*
         * VALUE LABEL
         */

        const valueLabelPlugin =
            createBarValueLabelPlugin(
                "erNopValueLabels"
            );


        /* -----------------------------------------------------
           CREATE CHART
           ----------------------------------------------------- */

        charts.erNopChart =
            new Chart(canvas, {

                type: "bar",

                data: {

                    labels: nops,

                    datasets: [

                        {
                            label: "RU",

                            data: ruCounts,

                            backgroundColor:
                                chartColor(0),

                            borderWidth: 0,

                            borderRadius: 6,

                            borderSkipped: false,

                            barPercentage: 0.72,

                            categoryPercentage: 0.72
                        },

                        {
                            label: "BBP",

                            data: bbpCounts,

                            backgroundColor:
                                chartColor(2),

                            borderWidth: 0,

                            borderRadius: 6,

                            borderSkipped: false,

                            barPercentage: 0.72,

                            categoryPercentage: 0.72
                        }
                    ]
                },


                plugins: [
                    valueLabelPlugin
                ],


                options: commonOptions({

                    interaction: {
                        mode: "nearest",
                        intersect: true
                    },

                    layout: {

                        padding: {
                            top: 28,
                            right: 24,
                            bottom: 34,
                            left: 8
                        }
                    },

                    scales: {

                        x: {

                            grid: {
                                display: false
                            },

                            ticks: {

                                font: {
                                    size: 12
                                }
                            }
                        },

                        y: {

                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: chartGridColor()
                            }
                        }
                    },


                    plugins: {

                        legend: {

                            display: true,

                            position: "bottom",

                            labels: {

                                boxWidth: 24,

                                padding: 18,

                                usePointStyle: false
                            },


                            /*
                             * Click legend:
                             * isolate RU / BBP.
                             */

                            onClick:
                                function (
                                    event,
                                    legendItem,
                                    legend
                                ) {

                                    const chart =
                                        legend.chart;

                                    const clicked =
                                        legendItem.datasetIndex;

                                    const other =
                                        clicked === 0
                                            ? 1
                                            : 0;

                                    const clickedVisible =
                                        chart.isDatasetVisible(
                                            clicked
                                        );

                                    const otherVisible =
                                        chart.isDatasetVisible(
                                            other
                                        );


                                    if (
                                        clickedVisible &&
                                        otherVisible
                                    ) {

                                        chart.setDatasetVisibility(
                                            clicked,
                                            true
                                        );

                                        chart.setDatasetVisibility(
                                            other,
                                            false
                                        );
                                    }

                                    else if (
                                        clickedVisible &&
                                        !otherVisible
                                    ) {

                                        chart.setDatasetVisibility(
                                            0,
                                            true
                                        );

                                        chart.setDatasetVisibility(
                                            1,
                                            true
                                        );
                                    }

                                    else {

                                        chart.setDatasetVisibility(
                                            clicked,
                                            true
                                        );

                                        chart.setDatasetVisibility(
                                            other,
                                            false
                                        );
                                    }

                                    chart.update();
                                }
                        },


                        tooltip: {

                            callbacks: {

                                title:
                                    function (context) {

                                        return (
                                            context[0]?.label ||
                                            ""
                                        );
                                    },


                                label:
                                    function (context) {

                                        const value =
                                            Number(
                                                context.raw || 0
                                            );

                                        const group =
                                            context.dataset.label;

                                        const total =
                                            group === "RU"
                                                ? totalRuAll
                                                : totalBbpAll;

                                        const percentage =
                                            total > 0
                                                ? (
                                                    value /
                                                    total
                                                ) * 100
                                                : 0;


                                        return [
                                            `${group}: ${fmt(value)} equipment`,
                                            `${pct(percentage)} of total ${group}`
                                        ];
                                    }
                            }
                        }
                    },


                    onClick:
                        function (event, elements, chartInstance) {

                            const points =
                                (
                                    chartInstance &&
                                    typeof chartInstance.getElementsAtEventForMode ===
                                        "function"
                                )
                                    ? chartInstance.getElementsAtEventForMode(
                                        event,
                                        "nearest",
                                        { intersect: true },
                                        true
                                    )
                                    : elements;

                            if (
                                !points ||
                                !points.length
                            ) {
                                return;
                            }

                            const point =
                                points[0];

                            const index =
                                point.index;

                            const datasetIndex =
                                point.datasetIndex;

                            const nop =
                                nops[index];

                            /*
                            * datasetIndex:
                            * 0 = RU
                            * 1 = BBP
                            */

                            const group =
                                datasetIndex === 0
                                    ? "RU"
                                    : "BBP";

                            if (
                                nop &&
                                group
                            ) {

                                /*
                                * Apply NOP + Equipment Group
                                */

                                appliedFilters.nop =
                                    nop;

                                appliedFilters.group =
                                    group;

                                /*
                                * Reset unrelated filters
                                */

                                appliedFilters.type =
                                    ALL;

                                appliedFilters.status =
                                    ALL;

                                appliedFilters.pic =
                                    ALL;

                                appliedFilters.safe =
                                    ALL;

                                drilldownCategory =
                                    "All Data";

                                currentPage =
                                    1;

                                syncFilterInputs();

                                renderDashboard();

                                document
                                    .getElementById("erTableSection")
                                    ?.scrollIntoView({
                                        behavior: "smooth",
                                        block: "start"
                                    });
                            }
                        }
                })
            });
    }

    /* =========================================================
       STATUS DOUGHNUT
       ========================================================= */

    function renderStatusChart() {

        if (!chartAvailable) {
            return;
        }

        const canvas =
            $("erStatusChart");

        if (!canvas) {
            return;
        }

        destroyChart("erStatusChart");


        const data =
            getFilteredData();


        const labels = [
            "Belum Diisi",
            "Not Yet",
            "On Going",
            "Done"
        ];


        const keys = [
            "Belum Diisi",
            "NOT YET",
            "ON GOING",
            "DONE"
        ];


        const values =
            keys.map(key =>
                data.filter(
                    item =>
                        statusOf(item) === key
                ).length
            );


        const total =
            values.reduce(
                (sum, value) =>
                    sum + value,
                0
            );


        /*
         * -----------------------------------------------
         * DOUGHNUT VALUE LABELS
         * -----------------------------------------------
         */

        const doughnutLabelPlugin = {

            id: "erStatusValueLabels",

            afterDatasetsDraw(chart) {

                const ctx =
                    chart.ctx;

                const meta =
                    chart.getDatasetMeta(0);

                if (
                    !meta ||
                    meta.hidden
                ) {
                    return;
                }

                ctx.save();

                meta.data.forEach(
                    (arc, index) => {

                        const value =
                            Number(
                                chart.data.datasets[0]
                                    .data[index] || 0
                            );

                        if (
                            value <= 0 ||
                            total <= 0
                        ) {
                            return;
                        }


                        const percentage =
                            (
                                value /
                                total
                            ) * 100;


                        /*
                         * Only display label when
                         * segment is large enough.
                         */

                        const circumference =
                            arc.circumference || 0;

                        const minimumAngle =
                            0.12;


                        if (
                            circumference <
                            minimumAngle
                        ) {
                            return;
                        }


                        const angle =
                            (
                                arc.startAngle +
                                arc.endAngle
                            ) / 2;


                        const radius =
                            (
                                arc.innerRadius +
                                arc.outerRadius
                            ) / 2;


                        const x =
                            arc.x +
                            Math.cos(angle) *
                            radius;


                        const y =
                            arc.y +
                            Math.sin(angle) *
                            radius;


                        ctx.textAlign =
                            "center";

                        ctx.textBaseline =
                            "middle";


                        /*
                         * VALUE
                         */

                        ctx.font =
                            "700 15px Inter, Arial, sans-serif";

                        ctx.fillStyle =
                            "#ffffff";

                        ctx.fillText(
                            fmt(value),
                            x,
                            y - 7
                        );


                        /*
                         * PERCENTAGE
                         */

                        ctx.font =
                            "700 14px Inter, Arial, sans-serif";

                        ctx.fillText(
                            pct(percentage),
                            x,
                            y + 9
                        );
                    }
                );

                ctx.restore();
            }
        };


        /*
         * CENTER TOTAL
         */

        const centerTextPlugin = {

            id: "erStatusCenterText",

            afterDraw(chart) {

                const {
                    ctx,
                    chartArea
                } = chart;

                if (!chartArea) {
                    return;
                }


                const centerX =
                    (
                        chartArea.left +
                        chartArea.right
                    ) / 2;


                const centerY =
                    (
                        chartArea.top +
                        chartArea.bottom
                    ) / 2;


                ctx.save();


                ctx.textAlign =
                    "center";

                ctx.textBaseline =
                    "middle";


                /*
                 * TOTAL
                 */

                ctx.font =
                    "700 22px Inter, Arial, sans-serif";

                ctx.fillStyle = chartTextColor();

                ctx.fillText(
                    fmt(total),
                    centerX,
                    centerY - 7
                );


                /*
                 * LABEL
                 */

                ctx.font =
                    "700 15px Inter, Arial, sans-serif";

                ctx.fillStyle = chartTextColor();

                ctx.fillText(
                    "Equipment",
                    centerX,
                    centerY + 16
                );


                ctx.restore();
            }
        };


        /* -----------------------------------------------------
           CREATE CHART
           ----------------------------------------------------- */

        charts.erStatusChart =
            new Chart(canvas, {

                type: "doughnut",

                data: {

                    labels,

                    datasets: [

                        {
                            data: values,

                            backgroundColor: [
                                chartPalette.financial.inactive,
                                chartPalette.status.warning,
                                chartPalette.status.pending,
                                chartPalette.status.active
                            ],

                            borderColor:
                                "#ffffff",

                            borderWidth: 4,

                            hoverOffset: 6
                        }
                    ]
                },


                plugins: [
                    doughnutLabelPlugin,
                    centerTextPlugin
                ],


                options: commonOptions({

                    cutout: "62%",


                    plugins: {

                        legend: {

                            display: true,

                            position: "bottom",

                            labels: {

                                padding: 14,

                                boxWidth: 24
                            }
                        },


                        tooltip: {

                            callbacks: {

                                title:
                                    function (context) {

                                        return (
                                            context[0]?.label ||
                                            ""
                                        );
                                    },


                                label:
                                    function (context) {

                                        const value =
                                            Number(
                                                context.raw || 0
                                            );

                                        const percentage =
                                            total > 0
                                                ? (
                                                    value /
                                                    total
                                                ) * 100
                                                : 0;


                                        return [
                                            `${fmt(value)} equipment`,
                                            `${pct(percentage)} of total`
                                        ];
                                    }
                            }
                        }
                    },


                    onClick:
                        function (_, elements) {

                            if (
                                !elements.length
                            ) {
                                return;
                            }

                            const index =
                                elements[0].index;

                            const key =
                                keys[index];

                            if (key) {
                                setDrilldown(key);
                            }
                        }
                })
            });
    }

    /* =========================================================
       SAFE TYPE BAR CHART
       RU / BBP TOP 10
       ========================================================= */

    function createSafeTypeChart({
        canvasId,
        group,
        colorOffset
    }) {

        if (!chartAvailable) {
            return null;
        }


        const canvas =
            $(canvasId);

        if (!canvas) {

            console.warn(
                `Equipment Relocation: canvas #${canvasId} tidak ditemukan.`
            );

            return null;
        }


        destroyChart(canvasId);


        /*
         * ALL EQUIPMENT IN THIS GROUP (SAFE & NOT SAFE)
         *
         * Safe/Not Safe can still be narrowed down
         * separately via the Safe to Reloc filter.
         */

        const data =
            getFilteredData().filter(item =>
                normalize(item.equipment_group) ===
                    normalize(group)
            );


        /*
         * COUNT EQUIPMENT TYPE
         */

        const counts = {};

        data.forEach(item => {

            const type =
                String(
                    item.equipment_type ||
                    "Unknown"
                ).trim();

            if (!type) {
                return;
            }

            counts[type] =
                (counts[type] || 0) + 1;
        });


        /*
         * TOP 10
         */

        const ranked =
            Object.entries(counts)
                .sort((a, b) => {

                    if (
                        b[1] !== a[1]
                    ) {
                        return (
                            b[1] -
                            a[1]
                        );
                    }

                    return a[0].localeCompare(
                        b[0],
                        "id"
                    );
                })
                .slice(0, 10);


        const labels =
            ranked.map(
                item => item[0]
            );


        const values =
            ranked.map(
                item => item[1]
            );


        /*
         * TOTAL IN GROUP
         *
         * Important:
         * percentage is against ALL equipment
         * in the selected group, not only Top 10.
         */

        const totalGroup =
            data.length;


        /*
         * VALUE LABEL
         */

        const valueLabelPlugin =
            createBarValueLabelPlugin(
                `er${group}SafeTypeValueLabels`
            );


        /* -----------------------------------------------------
           CREATE CHART
           ----------------------------------------------------- */

        const chart =
            new Chart(canvas, {

                type: "bar",

                data: {

                    labels,

                    datasets: [

                        {
                            label: "Equipment",

                            data: values,

                            backgroundColor:
                                labels.map((_, index) => chartColor(index + colorOffset)),

                            borderWidth: 0,

                            borderRadius: 6,

                            borderSkipped: false,

                            barPercentage: 0.62,

                            categoryPercentage: 0.72
                        }
                    ]
                },


                plugins: [
                    valueLabelPlugin
                ],


                options: commonOptions({

                    layout: {

                        padding: {

                            top: 34,

                            right: 22,

                            bottom: 18,

                            left: 8
                        }
                    },


                    scales: {

                        x: {

                            grid: {
                                display: false
                            },

                            ticks: {

                                autoSkip: false,

                                maxRotation: 35,

                                minRotation: 0,

                                font: {
                                    size: 11
                                }
                            }
                        },


                        y: {

                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: chartGridColor()
                            }
                        }
                    },


                    plugins: {

                        legend: {
                            display: false
                        },


                        tooltip: {

                            callbacks: {

                                title:
                                    function (context) {

                                        return (
                                            context[0]?.label ||
                                            ""
                                        );
                                    },


                                label:
                                    function (context) {

                                        const value =
                                            Number(
                                                context.raw || 0
                                            );


                                        const percentage =
                                            totalGroup > 0
                                                ? (
                                                    value /
                                                    totalGroup
                                                ) * 100
                                                : 0;


                                        return [
                                            `${fmt(value)} equipment`,
                                            `${pct(percentage)} of total ${group}`
                                        ];
                                    }
                            }
                        }
                    },


                    onClick:
                        function (_, elements) {

                            if (
                                !elements.length
                            ) {
                                return;
                            }


                            const index =
                                elements[0].index;


                            const type =
                                labels[index];


                            if (!type) {
                                return;
                            }


                            setDrilldown(
                                `${group}Type:${type}`
                            );
                        }
                })
            });


        return chart;
    }

    /* =========================================================
       BBP TOP 10
       ========================================================= */

    function renderBbpTypeChart() {

        if (!chartAvailable) {
            return;
        }

        charts.erBbpTypeChart =
            createSafeTypeChart({

                canvasId:
                    "erBbpTypeChart",

                group:
                    "BBP",

                colorOffset: 2
            });
    }

    /* =========================================================
       RU TOP 10
       ========================================================= */

    function renderRuTypeChart() {

        if (!chartAvailable) {
            return;
        }

        charts.erRuTypeChart =
            createSafeTypeChart({

                canvasId:
                    "erRuTypeChart",

                group:
                    "RU",

                colorOffset: 0
            });
    }

    /* =========================================================
       RENDER ALL CHARTS
       ========================================================= */

    function renderCharts() {
        if (chartAvailable) {
            Chart.defaults.color = chartTextColor();
            Chart.defaults.borderColor = chartGridColor();
        }

        try {
            renderNopChart();
        }

        catch (error) {

            console.error(
                "Equipment Relocation - NOP chart error:",
                error
            );
        }


        try {
            renderStatusChart();
        }

        catch (error) {

            console.error(
                "Equipment Relocation - Status chart error:",
                error
            );
        }


        try {
            renderBbpTypeChart();
        }

        catch (error) {

            console.error(
                "Equipment Relocation - BBP type chart error:",
                error
            );
        }


        try {
            renderRuTypeChart();
        }

        catch (error) {

            console.error(
                "Equipment Relocation - RU type chart error:",
                error
            );
        }
    }

    /* =========================================================
       TABLE
       ========================================================= */

    function actionCellHtml(item) {

        if (window.__equipmentRelocationCanEdit === false) {
            return '<span class="text-muted">Read only</span>';
        }

        return `
            <div class="er-action-group">

                <button
                    type="button"
                    class="er-action-btn er-edit-btn"
                    data-uniq="${escapeHtml(item.uniq_key)}"
                    title="Isi / Edit Data"
                >
                    <svg class="er-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-1 5 5-1L19 9l-4-4L4 16Z"/><path d="m13 7 4 4"/></svg>
                </button>

                ${
                    item.has_data
                        ? `
                            <button
                                type="button"
                                class="er-action-btn er-delete-btn"
                                data-uniq="${escapeHtml(item.uniq_key)}"
                                title="Hapus Data"
                            >
                                <svg class="er-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 3h6l1 4H8l1-4ZM7 7l1 14h8l1-14M10 11v6M14 11v6"/></svg>
                            </button>
                        `
                        : ""
                }

            </div>
        `;
    }

    function renderTable() {

        if (!els.tableBody) {
            return;
        }

        const data =
            getDisplayData();


        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    data.length /
                    pageSize
                )
            );


        currentPage =
            Math.min(
                currentPage,
                totalPages
            );


        const start =
            (currentPage - 1) *
            pageSize;


        const rows =
            data.slice(
                start,
                start + pageSize
            );


        els.tableBody.innerHTML =
            "";


        if (!rows.length) {

            els.tableBody.innerHTML = `
                <tr>
                    <td
                        colspan="12"
                        class="empty-state"
                    >
                        Tidak ada data yang sesuai.
                    </td>
                </tr>
            `;

            renderPagination(data);

            return;
        }


        rows.forEach(item => {

            const tr =
                document.createElement("tr");

            tr.className =
                "site-table-row";
            tr.dataset.uniq = item.uniq_key || "";


            const status =
                statusOf(item);


            tr.innerHTML = `

                <td>
                    ${actionCellHtml(item)}
                </td>

                <td>
                    <strong>
                        ${escapeHtml(item.site_id)}
                    </strong>
                </td>

                <td>
                    ${escapeHtml(item.nop || "-")}
                </td>

                <td>
                    ${escapeHtml(typeLabel(item))}
                </td>

                <td>${escapeHtml(item.serial_number || "-")}</td>

                <td>${escapeHtml(item.utilization_status || "-")}</td>

                <td>
                    <span
                        class="table-status ${safeBadgeClass(item)}"
                    >
                        ${escapeHtml(safeLabel(item))}
                    </span>
                </td>

                <td>
                    <span
                        class="table-status ${statusBadgeClass(status)}"
                    >
                        ${escapeHtml(status)}
                    </span>
                </td>

                <td>
                    ${escapeHtml(
                        item.donor_acceptor || "-"
                    )}
                </td>

                <td>
                    ${escapeHtml(
                        item.site_target_source || "-"
                    )}
                </td>

                <td>
                    ${escapeHtml(
                        item.pic || "-"
                    )}
                </td>

                <td>
                    ${
                        item.progress
                            ? `
                                <span
                                    class="table-status ${statusBadgeClass(item.progress)}"
                                >
                                    ${escapeHtml(item.progress)}
                                </span>
                            `
                            : "-"
                    }
                </td>
            `;


            els.tableBody.appendChild(tr);
        });

        // Every data cell opens the same centered detail popup. The first
        // action cell remains reserved for edit/delete controls.
        els.tableBody.querySelectorAll("tr.site-table-row td:not(:first-child)").forEach(cell => {
            const openDetail = () => {
                const item = allData.find(i => i.uniq_key === cell.closest("tr")?.dataset.uniq);
                if (item) openEquipmentRowDetail(item);
            };
            cell.tabIndex = 0;
            cell.setAttribute("role", "button");
            cell.setAttribute("aria-label", "Lihat detail equipment");
            cell.addEventListener("click", openDetail);
            cell.addEventListener("keydown", event => {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openDetail();
                }
            });
        });


        /*
         * EDIT
         */

        els.tableBody
            .querySelectorAll(".er-edit-btn")
            .forEach(btn => {

                btn.addEventListener(
                    "click",
                    () => {

                        const item =
                            allData.find(
                                i =>
                                    i.uniq_key ===
                                    btn.dataset.uniq
                            );

                        if (item) {
                            openEditModal(item);
                        }
                    }
                );
            });


        /*
         * DELETE
         */

        els.tableBody
            .querySelectorAll(".er-delete-btn")
            .forEach(btn => {

                btn.addEventListener(
                    "click",
                    () => {

                        const item =
                            allData.find(
                                i =>
                                    i.uniq_key ===
                                    btn.dataset.uniq
                            );

                        if (item) {
                            openDeleteConfirm(item);
                        }
                    }
                );
            });


        renderPagination(data);
    }

    /* =========================================================
       PAGINATION
       ========================================================= */

    function renderPagination(data) {

        const total =
            Math.max(
                1,
                Math.ceil(
                    data.length /
                    pageSize
                )
            );


        currentPage =
            Math.min(
                currentPage,
                total
            );


        if (els.paginationInfo) {

            els.paginationInfo.textContent =
                `Page ${currentPage} of ${total}`;
        }


        if (els.prevPage) {

            els.prevPage.disabled =
                currentPage <= 1;
        }


        if (els.nextPage) {

            els.nextPage.disabled =
                currentPage >= total;
        }


        if (els.tableResultInfo) {

            if (!data.length) {

                els.tableResultInfo.textContent =
                    "Tidak ada data yang sesuai.";
            }

            else {

                const start =
                    (currentPage - 1) *
                    pageSize +
                    1;


                const end =
                    Math.min(
                        currentPage *
                            pageSize,
                        data.length
                    );


                els.tableResultInfo.textContent =
                    `Menampilkan ${start}–${end} dari ${fmt(data.length)} equipment`;
            }
        }
    }

    function setupPagination() {

        els.prevPage?.addEventListener(
            "click",
            () => {

                if (currentPage > 1) {

                    currentPage--;

                    renderTable();
                }
            }
        );


        els.nextPage?.addEventListener(
            "click",
            () => {

                const total =
                    Math.max(
                        1,
                        Math.ceil(
                            getDisplayData().length /
                            pageSize
                        )
                    );


                if (
                    currentPage <
                    total
                ) {

                    currentPage++;

                    renderTable();
                }
            }
        );


        els.pageSizeSelect?.addEventListener(
            "change",
            () => {

                pageSize =
                    Number(
                        els.pageSizeSelect.value
                    ) || 10;

                currentPage = 1;

                renderTable();
            }
        );
    }

    /* =========================================================
       DRILLDOWN
       ========================================================= */

    function setDrilldown(category) {

        drilldownCategory =
            category ||
            "All Data";

        currentPage = 1;

        renderTable();

        updateTableHeader();


        document
            .getElementById("erTableSection")
            ?.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
    }

    function updateTableHeader() {

        const titleMap = {

            "All Data":
                "Semua Equipment (Safe &amp; Not Safe)",

            "SafeToReloc":
                "Equipment Safe to Reloc",

            "Belum Diisi":
                "Equipment Belum Diisi Data Relokasi",

            "NOT YET":
                "Relokasi Belum Berjalan (Not Yet)",

            "ON GOING":
                "Relokasi Sedang Berjalan (On Going)",

            "DONE":
                "Relokasi Selesai (Done)"
        };


        let label;


        if (
            drilldownCategory.startsWith(
                "NOP:"
            )
        ) {

            label =
                `Equipment di ${drilldownCategory.slice(4)}`;
        }

        else if (
            /^(RU|BBP)Type:/.test(
                drilldownCategory
            )
        ) {

            const [, typeGroup, type] =
                drilldownCategory.match(
                    /^(RU|BBP)Type:(.*)$/
                );

            label =
                `Equipment ${typeGroup} - Tipe ${type}`;
        }

        else {

            label =
                titleMap[
                    drilldownCategory
                ] ||
                "Semua Equipment";
        }


        if (els.tableTitle) {

            els.tableTitle.textContent =
                label.replace(/&amp;/g, "&");
        }


        if (els.tableDescription) {

            els.tableDescription.textContent =
                drilldownCategory === "All Data"

                    ? "Menampilkan seluruh equipment (RU & BBP), baik Safe maupun Not Safe to Reloc."

                    : `Menampilkan data untuk ${label
                        .replace(
                            /&amp;/g,
                            "&"
                        )
                        .toLowerCase()}.`;
        }


        if (els.activeFilterBadge) {

            els.activeFilterBadge.textContent =
                drilldownCategory;
        }
    }

    /* =========================================================
       FILTER PANEL
       ========================================================= */

    function fillSelect(
        el,
        values,
        label
    ) {

        if (!el) {
            return;
        }


        const current =
            el.value || ALL;


        const unique =
            [
                ...new Set(
                    values
                        .map(
                            v =>
                                String(
                                    v ?? ""
                                ).trim()
                        )
                        .filter(Boolean)
                )
            ].sort(
                (a, b) =>
                    a.localeCompare(
                        b,
                        "id"
                    )
            );


        el.innerHTML =
            `<option value="All">${escapeHtml(label)}</option>`;


        unique.forEach(v => {

            const opt =
                document.createElement(
                    "option"
                );

            opt.value = v;

            opt.textContent = v;

            el.appendChild(opt);
        });


        el.value =
            unique.includes(current)
                ? current
                : ALL;
    }

    function populateFilterOptions() {

        fillSelect(
            els.filterNop,
            allData.map(
                i => i.nop
            ),
            "Semua NOP"
        );


        fillSelect(
            els.filterType,
            allData.map(
                i =>
                    i.equipment_type
            ),
            "Semua Tipe"
        );
    }

    function syncFilterInputs() {

        if (els.filterNop) {
            els.filterNop.value =
                appliedFilters.nop;
        }

        if (els.filterGroup) {
            els.filterGroup.value =
                appliedFilters.group;
        }

        if (els.filterType) {
            els.filterType.value =
                appliedFilters.type;
        }

        if (els.filterStatus) {
            els.filterStatus.value =
                appliedFilters.status;
        }

        if (els.filterPic) {
            els.filterPic.value =
                appliedFilters.pic;
        }

        if (els.filterSafe) {
            els.filterSafe.value =
                appliedFilters.safe;
        }
    }

    function applyFiltersFromPanel() {

        appliedFilters.nop =
            els.filterNop?.value ||
            ALL;

        appliedFilters.group =
            els.filterGroup?.value ||
            ALL;

        appliedFilters.type =
            els.filterType?.value ||
            ALL;

        appliedFilters.status =
            els.filterStatus?.value ||
            ALL;

        appliedFilters.pic =
            els.filterPic?.value ||
            ALL;

        appliedFilters.safe =
            els.filterSafe?.value ||
            ALL;


        drilldownCategory =
            "All Data";

        currentPage = 1;

        renderDashboard();

        closeFilterPanel();
    }

    function clearFilters() {

        appliedFilters.nop = ALL;
        appliedFilters.group = ALL;
        appliedFilters.type = ALL;
        appliedFilters.status = ALL;
        appliedFilters.pic = ALL;
        appliedFilters.safe = ALL;

        drilldownCategory =
            "All Data";

        searchKeyword = "";

        if (els.search) {
            els.search.value = "";
        }

        currentPage = 1;

        syncFilterInputs();

        renderDashboard();
    }

    function openFilterPanel() {

        syncFilterInputs();

        els.filterOverlay?.classList.add(
            "show"
        );

        els.filterOverlay?.setAttribute(
            "aria-hidden",
            "false"
        );
    }

    function closeFilterPanel() {

        els.filterOverlay?.classList.remove(
            "show"
        );

        els.filterOverlay?.setAttribute(
            "aria-hidden",
            "true"
        );
    }

    function setupFilterPanel() {

        els.openFilterBtn?.addEventListener(
            "click",
            openFilterPanel
        );

        els.closeFilterBtn?.addEventListener(
            "click",
            closeFilterPanel
        );

        els.filterOverlay?.addEventListener(
            "click",
            e => {

                if (
                    e.target ===
                    els.filterOverlay
                ) {
                    closeFilterPanel();
                }
            }
        );

        els.applyGlobalFilterBtn?.addEventListener(
            "click",
            applyFiltersFromPanel
        );

        els.clearGlobalFilterBtn?.addEventListener(
            "click",
            () => {

                clearFilters();

                closeFilterPanel();
            }
        );

        els.resetFilter?.addEventListener(
            "click",
            clearFilters
        );
    }

    /* =========================================================
       SEARCH
       ========================================================= */

    function setupSearch() {

        let timer = null;

        els.search?.addEventListener(
            "input",
            () => {

                clearTimeout(timer);

                timer =
                    setTimeout(
                        () => {

                            searchKeyword =
                                els.search.value.trim();

                            currentPage = 1;

                            renderTable();
                        },
                        200
                    );
            }
        );
    }

    /* =========================================================
       EXPORT CSV
       ========================================================= */

    function exportCsv() {

        const data =
            getDisplayData();


        const header = [
            "Site ID",
            "NOP",
            "Tipe RU/BBP",
            "Serial Number",
            "Utilisasi",
            "Safe to Reloc",
            "Status",
            "Donor/Acceptor",
            "Site Target/Source",
            "PIC",
            "Progress",
            "Remark"
        ];


        const lines = [
            header.join(",")
        ];


        data.forEach(item => {

            const row = [

                item.site_id,

                item.nop || "",

                typeLabel(item),

                item.serial_number || "",

                item.utilization_status || "",

                safeLabel(item),

                statusOf(item),

                item.donor_acceptor || "",

                item.site_target_source || "",

                item.pic || "",

                item.progress || "",

                (item.remark || "")
                    .replace(
                        /[\r\n,]+/g,
                        " "
                    )
            ];


            lines.push(
                row
                    .map(v => {
                        const safeValue = String(v ?? "").replace(/^([=+\-@])/, "'$1");
                        return `"${safeValue.replace(/"/g, '""')}"`;
                    })
                    .join(",")
            );
        });


        const blob =
            new Blob(
                [
                    "\uFEFF" +
                    lines.join("\r\n")
                ],
                {
                    type:
                        "text/csv;charset=utf-8;"
                }
            );


        const url =
            URL.createObjectURL(
                blob
            );


        const a =
            document.createElement(
                "a"
            );


        a.href = url;

        a.download =
            `equipment-relocation-${new Date()
                .toISOString()
                .slice(0, 10)}.csv`;


        document.body.appendChild(a);

        a.click();

        a.remove();

        URL.revokeObjectURL(url);
    }

    /* =========================================================
       MODAL HELPERS
       ========================================================= */

    function readonlyField(
        label,
        value
    ) {

        return `
            <div class="er-readonly-item">
                <span>
                    ${escapeHtml(label)}
                </span>

                <strong>
                    ${escapeHtml(
                        value ?? "-"
                    )}
                </strong>
            </div>
        `;
    }

    function optionalSelectField(
        label,
        id,
        options,
        value
    ) {

        return `
            <div class="er-form-field">

                <label for="${id}">
                    ${escapeHtml(label)}
                </label>

                <select id="${id}">

                    <option value="">
                        - Belum diisi -
                    </option>

                    ${options
                        .map(
                            o => `
                                <option
                                    value="${escapeHtml(o)}"
                                    ${
                                        normalize(o) ===
                                        normalize(value)
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    ${escapeHtml(o)}
                                </option>
                            `
                        )
                        .join("")}

                </select>
            </div>
        `;
    }

    function closeModal(
        overlayId
    ) {

        $(overlayId)?.remove();
    }

    /* =========================================================
    Laravel endpoints are the source of truth for inventory and monitoring.
    ========================================================= */

    const API_URLS = window.__equipmentRelocationUrls || {};

    async function persistRelocation(url, method, payload) {
        if (!url) throw new Error("Endpoint penyimpanan tidak tersedia.");

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok || body.success === false) {
            throw new Error(body.message || `HTTP ${response.status}`);
        }
        return body;
    }


    /* =========================================================
    SAVE - Laravel API
    ========================================================= */

    async function submitSave(
        payload,
        overlayId
    ) {

        const errorBox =
            document.querySelector(
                `#${overlayId} .er-inline-error`
            );

        try {

            const itemIndex =
                allData.findIndex(
                    item =>
                        String(item.uniq_key) ===
                        String(payload.donor_uniq_key)
                );


            if (itemIndex === -1) {

                throw new Error(
                    "Equipment tidak ditemukan."
                );
            }

            await persistRelocation(API_URLS.save, 'POST', payload);


            /*
            * ---------------------------------------------------
            * UPDATE DATA DI MEMORY
            * ---------------------------------------------------
            */

            allData[itemIndex] = {

                ...allData[itemIndex],

                donor_acceptor:
                    payload.donor_acceptor || "",

                site_target_source:
                    payload.site_target_source || "",

                pic:
                    payload.pic || "",

                progress:
                    payload.progress || "",

                remark:
                    payload.remark || "",

                has_data: true
            };


            /*
            * ---------------------------------------------------
            * CLOSE MODAL
            * ---------------------------------------------------
            */

            closeModal(
                overlayId
            );


            /*
            * ---------------------------------------------------
            * REFRESH UI
            * ---------------------------------------------------
            */

            populateFilterOptions();

            renderDashboard();


        } catch (err) {

            if (errorBox) {

                errorBox.textContent =
                    err.message;

                errorBox.classList.add(
                    "show"
                );

            } else {

                alert(
                    err.message
                );
            }
        }
    }

    /* =========================================================
    DELETE - Laravel API
    ========================================================= */

    async function submitDelete(
        uniqKey,
        overlayId
    ) {

        const errorBox =
            document.querySelector(
                `#${overlayId} .er-inline-error`
            );

        try {

            const itemIndex =
                allData.findIndex(
                    item =>
                        String(item.uniq_key) ===
                        String(uniqKey)
                );


            if (itemIndex === -1) {

                throw new Error(
                    "Equipment tidak ditemukan."
                );
            }

            await persistRelocation(API_URLS.destroy, 'DELETE', {
                donor_uniq_key: uniqKey
            });


            const item =
                allData[itemIndex];


            /*
            * ---------------------------------------------------
            * RESET DATA DI MEMORY
            * ---------------------------------------------------
            */

            allData[itemIndex] = {

                ...item,

                donor_acceptor: "",

                site_target_source: "",

                pic: "",

                progress: "",

                remark: "",

                has_data: false
            };


            /*
            * ---------------------------------------------------
            * CLOSE + REFRESH
            * ---------------------------------------------------
            */

            closeModal(
                overlayId
            );


            populateFilterOptions();

            renderDashboard();


        } catch (err) {

            if (errorBox) {

                errorBox.textContent =
                    err.message;

                errorBox.classList.add(
                    "show"
                );

            } else {

                alert(
                    err.message
                );
            }
        }
    }

    /* =========================================================
       EDIT MODAL
       ========================================================= */

    function openEquipmentRowDetail(item) {
        const overlayId = "erRowDetailOverlay";
        $(overlayId)?.remove();
        const overlay = document.createElement("div");
        overlay.id = overlayId;
        overlay.className = "er-modal-overlay";
        const fields = [
            ["Site ID", item.site_id], ["NOP", item.nop], ["TO", item.to_name],
            ["Region", item.region], ["NE Name", item.ne_name],
            ["Kategori", typeLabel(item)], ["Board", item.board_name],
            ["Tipe Board", item.board_type], ["Serial Number", item.serial_number],
            ["Utilisasi", item.utilization_status], ["Safe to Reloc", safeLabel(item)],
            ["Status", statusOf(item)], ["Donor / Acceptor", item.donor_acceptor],
            ["Site Target / Source", item.site_target_source], ["PIC", item.pic],
            ["Progress", item.progress], ["Remark", item.remark]
        ];
        overlay.innerHTML = `
            <div class="er-modal" role="dialog" aria-modal="true">
                <div class="er-modal-header"><div><h2>Detail Equipment Relocation</h2><p>${escapeHtml(item.site_id || "-")} · ${escapeHtml(item.nop || "-")}</p></div><button type="button" class="er-modal-close" data-close>&times;</button></div>
                <div class="er-modal-body"><div class="er-readonly-grid">${fields.map(([label, value]) => readonlyField(label, value)).join("")}</div></div>
                <div class="er-modal-footer"><button type="button" class="btn-secondary" data-close>Tutup</button></div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.querySelectorAll("[data-close]").forEach(btn => btn.addEventListener("click", () => closeModal(overlayId)));
        overlay.addEventListener("click", e => { if (e.target === overlay) closeModal(overlayId); });
    }

    function openEditModal(item) {

        const overlayId =
            "erEditOverlay";


        $(overlayId)?.remove();


        const overlay =
            document.createElement(
                "div"
            );


        overlay.id =
            overlayId;

        overlay.className =
            "er-modal-overlay";


        overlay.innerHTML = `

            <div
                class="er-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="er-modal-header">

                    <div>

                        <h2>
                            ${
                                item.has_data
                                    ? "Edit"
                                    : "Isi"
                            }
                            Data Relokasi
                        </h2>

                        <p>
                            ${escapeHtml(
                                item.site_id
                            )}
                            ·
                            ${escapeHtml(
                                typeLabel(item)
                            )}
                            ·
                            ${escapeHtml(
                                item.nop || "-"
                            )}
                        </p>

                    </div>

                    <button
                        type="button"
                        class="er-modal-close"
                        data-close
                    >
                        &times;
                    </button>

                </div>


                <div class="er-modal-body">

                    <p class="er-modal-section-title">
                        Data Equipment
                        (Inventory - Read Only)
                    </p>


                    <div class="er-readonly-grid">

                        ${readonlyField(
                            "Site ID",
                            item.site_id
                        )}

                        ${readonlyField(
                            "NOP",
                            item.nop
                        )}

                        ${readonlyField(
                            "Kategori",
                            item.equipment_group
                        )}

                        ${readonlyField(
                            "Tipe Equipment",
                            item.equipment_type
                        )}

                        ${readonlyField("Serial Number", item.serial_number)}
                        ${readonlyField("Utilisasi", item.utilization_status)}

                        ${readonlyField(
                            "Safe to Reloc",
                            safeLabel(item)
                        )}

                    </div>


                    <p class="er-modal-section-title">
                        Data Relokasi
                        (Diisi oleh NOP / BBP - Opsional)
                    </p>


                    <div class="er-inline-error"></div>


                    <div class="er-form-grid">

                        <div class="er-form-field">

                            <label for="erDonorAcceptor">
                                Donor/Acceptor
                            </label>

                            <input
                                type="text"
                                id="erDonorAcceptor"
                                value="${escapeHtml(
                                    item.donor_acceptor || ""
                                )}"
                                placeholder="mis. Donor / Acceptor info"
                            >

                        </div>


                        <div class="er-form-field">

                            <label for="erSiteTargetSource">
                                Site Target/Source
                            </label>

                            <input
                                type="text"
                                id="erSiteTargetSource"
                                value="${escapeHtml(
                                    item.site_target_source || ""
                                )}"
                                placeholder="mis. Site tujuan / sumber"
                            >

                        </div>


                        ${optionalSelectField(
                            "PIC",
                            "erPic",
                            PIC_OPTIONS,
                            item.pic
                        )}


                        ${optionalSelectField(
                            "Progress",
                            "erProgress",
                            PROGRESS_OPTIONS,
                            item.progress
                        )}


                        <div class="er-form-field full-width">

                            <label for="erRemark">
                                Remark
                            </label>

                            <textarea
                                id="erRemark"
                                placeholder="Catatan tambahan..."
                            >${escapeHtml(
                                item.remark || ""
                            )}</textarea>

                        </div>

                    </div>


                    <p class="er-form-hint">
                        Semua kolom di atas opsional dan bisa diisi bertahap.
                    </p>

                </div>


                <div class="er-modal-footer">

                    <button
                        type="button"
                        class="btn-secondary"
                        data-close
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="btn-primary"
                        id="erSaveBtn"
                    >
                        Simpan
                    </button>

                </div>

            </div>
        `;


        document.body.appendChild(
            overlay
        );


        overlay
            .querySelectorAll(
                "[data-close]"
            )
            .forEach(btn =>
                btn.addEventListener(
                    "click",
                    () =>
                        closeModal(
                            overlayId
                        )
                )
            );


        overlay.addEventListener(
            "click",
            e => {

                if (
                    e.target ===
                    overlay
                ) {
                    closeModal(
                        overlayId
                    );
                }
            }
        );


        $("erSaveBtn").addEventListener(
            "click",
            () => {

                submitSave(
                    {
                        donor_uniq_key:
                            item.uniq_key,

                        donor_acceptor:
                            $("erDonorAcceptor")
                                .value
                                .trim(),

                        site_target_source:
                            $("erSiteTargetSource")
                                .value
                                .trim(),

                        pic:
                            $("erPic")
                                .value,

                        progress:
                            $("erProgress")
                                .value,

                        remark:
                            $("erRemark")
                                .value
                                .trim()
                    },
                    overlayId
                );
            }
        );
    }

    /* =========================================================
       DELETE MODAL
       ========================================================= */

    function openDeleteConfirm(
        item
    ) {

        const overlayId =
            "erDeleteOverlay";


        $(overlayId)?.remove();


        const overlay =
            document.createElement(
                "div"
            );


        overlay.id =
            overlayId;

        overlay.className =
            "er-modal-overlay";


        overlay.innerHTML = `

            <div
                class="er-modal"
                style="width:min(460px,96vw);"
                role="dialog"
                aria-modal="true"
            >

                <div class="er-modal-header">

                    <div>

                        <h2>
                            Hapus Data Relokasi
                        </h2>

                        <p>
                            ${escapeHtml(
                                item.site_id
                            )}
                            ·
                            ${escapeHtml(
                                typeLabel(item)
                            )}
                        </p>

                    </div>

                    <button
                        type="button"
                        class="er-modal-close"
                        data-close
                    >
                        &times;
                    </button>

                </div>


                <div class="er-modal-body">

                    <div class="er-inline-error"></div>

                    <p
                        style="
                            margin:0;
                            color:var(--text-secondary);
                            font-size:14px;
                        "
                    >
                        Yakin ingin menghapus data relokasi
                        untuk equipment ini?

                        Equipment
                        <strong>
                            tidak akan terhapus
                        </strong>
                        dari inventory dan akan kembali
                        berstatus
                        <em>
                            Belum Diisi
                        </em>.
                    </p>

                </div>


                <div class="er-modal-footer">

                    <button
                        type="button"
                        class="btn-secondary"
                        data-close
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="btn-primary danger"
                        id="erDeleteConfirm"
                    >
                        Hapus
                    </button>

                </div>

            </div>
        `;


        document.body.appendChild(
            overlay
        );


        overlay
            .querySelectorAll(
                "[data-close]"
            )
            .forEach(btn =>
                btn.addEventListener(
                    "click",
                    () =>
                        closeModal(
                            overlayId
                        )
                )
            );


        overlay.addEventListener(
            "click",
            e => {

                if (
                    e.target ===
                    overlay
                ) {

                    closeModal(
                        overlayId
                    );
                }
            }
        );


        $("erDeleteConfirm")
            .addEventListener(
                "click",
                () =>
                    submitDelete(
                        item.uniq_key,
                        overlayId
                    )
            );
    }

    /* =========================================================
       RENDER DASHBOARD
       ========================================================= */

    function renderDashboard() {

        updateKPI();

        renderCharts();

        updateTableHeader();

        renderTable();
    }

    /* =========================================================
       INIT
       ========================================================= */

    async function init() {

        setupKPIClick();

        setupPagination();

        setupFilterPanel();

        setupSearch();

        new MutationObserver(mutations => {
            if (mutations.some(mutation => mutation.attributeName === "data-bs-theme")) renderCharts();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ["data-bs-theme"] });

        document.addEventListener("keydown", event => {
            if (event.key !== "Escape") return;
            closeFilterPanel();
            document.querySelectorAll(".er-modal-overlay").forEach(modal => modal.remove());
        });


        els.exportData?.addEventListener(
            "click",
            exportCsv
        );


        syncFilterInputs();


        /*
        * Load inventory and monitoring from MySQL
        */

        populateFilterOptions();
        renderDashboard();
    }


    init();

});
