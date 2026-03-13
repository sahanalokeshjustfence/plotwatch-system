/* =====================================
   DOM READY
===================================== */

document.addEventListener("DOMContentLoaded", function () {

    console.log("PlotWatch System Loaded");

    /* ===============================
       CLOSE MODAL OUTSIDE CLICK
    ================================= */

    const modal = document.getElementById("pwModal");

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    /* ===============================
       UNIVERSAL LOADER SYSTEM
    ================================= */

    function showLoader() {

        let loader = document.getElementById("pw-loader");

        if (loader) {
            loader.style.display = "flex";
        }

    }

    function hideLoader() {

        setTimeout(function () {

            let loader = document.getElementById("pw-loader");

            if (loader) {
                loader.style.display = "none";
            }

        }, 500);

    }

    /* ===============================
       FORM SUBMIT
    ================================= */

    document.querySelectorAll("form").forEach(function (form) {

        form.addEventListener("submit", function () {

            showLoader();

            let btn = form.querySelector("button[type='submit']");

            if (btn) {
                btn.disabled = true;
                btn.innerText = "Processing...";
            }

        });

    });

    /* ===============================
       PAGE LINK LOADER
    ================================= */

    document.querySelectorAll("a").forEach(function (link) {

        link.addEventListener("click", function () {

            let href = link.getAttribute("href");

            if (href && !href.startsWith("#") && !href.startsWith("javascript")) {
                showLoader();
            }

        });

    });

});


/* =====================================
GLOBAL DASHBOARD TOOLTIP
===================================== */

let tooltip = document.createElement("div");
tooltip.className = "pw-tooltip";
document.body.appendChild(tooltip);

document.addEventListener("mouseover", function (e) {

    let target = e.target;

    if (target && target.dataset && target.dataset.tooltip) {

        tooltip.innerHTML = target.dataset.tooltip;
        tooltip.classList.add("show");

    }

});

document.addEventListener("mousemove", function (e) {

    tooltip.style.left = (e.clientX + 15) + "px";
    tooltip.style.top = (e.clientY + 15) + "px";

});

document.addEventListener("mouseout", function (e) {

    let target = e.target;

    if (target && target.dataset && target.dataset.tooltip) {

        tooltip.classList.remove("show");

    }

});


/* =====================================
   PASSWORD TOGGLE
===================================== */

function pwTogglePassword() {

    var x = document.getElementById("pw_password");

    if (x) {
        x.type = x.type === "password" ? "text" : "password";
    }

}

function pwTogglePasswordRegister() {

    var x = document.getElementById("pw_reg_password");

    if (x) {
        x.type = x.type === "password" ? "text" : "password";
    }

}


/* =====================================
   GLOBAL MODAL SYSTEM
===================================== */

function openModal(content) {

    var modal = document.getElementById("pwModal");
    var modalContent = document.getElementById("pwModalContent");

    if (!modal || !modalContent) return;

    modalContent.innerHTML = content;
    modal.style.display = "flex";

}

function closeModal() {

    var modal = document.getElementById("pwModal");
    var modalContent = document.getElementById("pwModalContent");

    if (!modal || !modalContent) return;

    modal.style.display = "none";
    modalContent.innerHTML = "";

}


/* =====================================
   HIDE LOADER WHEN PAGE LOADS
===================================== */

window.addEventListener("load", function () {

    let loader = document.getElementById("pw-loader");

    if (loader) {
        loader.style.display = "none";
    }

});


/* =====================================
   DONUT VISIT ANALYTICS
===================================== */

function createDonut(id, value, color) {

    let el = document.getElementById(id);

    if (!el) return;

    new Chart(el, {

        type: 'doughnut',

        data: {
            datasets: [{
                data: [value, Math.max(1, value)],
                backgroundColor: [color, "#e5e7eb"],
                borderWidth: 0
            }]
        },

        options: {
            cutout: "70%",
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: { display: false },

                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (context) {

                            let label = context.chart.canvas.id.replace("Chart", "");
                            label = label.charAt(0).toUpperCase() + label.slice(1);

                            return label + " : " + context.raw;

                        }
                    }
                }
            }
        },

        plugins: [{

            id: 'centerText',

            afterDraw(chart) {

                let ctx = chart.ctx;
                let width = chart.width;
                let height = chart.height;

                ctx.save();

                let fontSize = (height / 110).toFixed(2);

                ctx.font = fontSize + "em sans-serif";
                ctx.textBaseline = "middle";

                let text = value;
                let textX = Math.round((width - ctx.measureText(text).width) / 2);
                let textY = height / 2;

                ctx.fillStyle = "#333";
                ctx.fillText(text, textX, textY);

                ctx.restore();

            }

        }]

    });

}


/* =====================================
   FILTER MODAL
===================================== */

function openFilter() {

    let f = document.getElementById("pwFilterModal");

    if (!f) return;

    if (f.style.display === "block") {
        f.style.display = "none";
    } else {
        f.style.display = "block";
    }

}

window.addEventListener("click", function (e) {

    let modal = document.getElementById("pwFilterModal");

    if (modal && e.target === modal) {
        modal.style.display = "none";
    }

});