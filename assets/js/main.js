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

window.addEventListener("load", function(){

    let loader = document.getElementById("pw-loader");

    if(loader){
        loader.style.display = "none";
    }

});