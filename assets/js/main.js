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


    /* =====================================
       PROGRESS TOOLTIP SYSTEM
    ===================================== */

    document.querySelectorAll(".pw-progress-box").forEach(box=>{

        box.addEventListener("mouseenter",function(){

            let tooltip=document.createElement("div")

            tooltip.className="pw-tooltip"
            tooltip.innerText=this.dataset.title

            document.body.appendChild(tooltip)

            let rect=this.getBoundingClientRect()

            tooltip.style.position="absolute"
            tooltip.style.left=rect.left+"px"
            tooltip.style.top=(rect.top-30)+"px"

            this.tooltip=tooltip

        })

        box.addEventListener("mouseleave",function(){

            if(this.tooltip){
                this.tooltip.remove()
            }

        })

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

if(document.getElementById("visitChart")){

new Chart(document.getElementById("visitChart"),{

type:"bar",

data:{
labels:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],

datasets:[{
label:"Visits",
data:window.visitChartData,
backgroundColor:"#3b82f6"
}]

}

})

}