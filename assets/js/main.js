document.addEventListener("DOMContentLoaded", function () {
    console.log("PlotWatch System Loaded");

    // Close modal when clicking outside
    const modal = document.getElementById("pwModal");
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
});

/* ===============================
   PASSWORD TOGGLE
================================ */

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

/* ===============================
   GLOBAL MODAL SYSTEM
================================ */

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