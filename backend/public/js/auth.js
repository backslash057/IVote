document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const errorFrame = document.querySelector(".error_frame");

    console.log(form);

    if (!form || !errorFrame) return;

    function display_result(message, positive) {
        errorFrame.innerText = message;
        errorFrame.classList.remove("hidden");

        if (positive) {
            errorFrame.className = "error_frame mb-4 p-3 rounded-xl text-xs text-center bg-emerald-500/10 border border-emerald-500/30 text-emerald-400";
        } else {
            errorFrame.className = "error_frame mb-4 p-3 rounded-xl text-xs text-center bg-rose-500/10 border border-rose-500/30 text-rose-400";
        }
    }

    function authenticate(path, datas) {
        const submitBtn = form.querySelector("button[type='submit']");
        if (submitBtn) submitBtn.disabled = true;

        // Use action URL or fallback to current page
        const targetUrl = path && path.trim() !== "" ? path : window.location.pathname;

        fetch(targetUrl, {
            headers: { "Content-Type": "application/json" },
            method: "POST",
            body: JSON.stringify(datas)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                display_result(data.message, true);

                setTimeout(() => {
                    window.location.replace(data.redirect || "/");
                }, 1000);
            } else {
                display_result(data.message || "Identifiants invalides.", false);
                if (submitBtn) submitBtn.disabled = false;
            }
        })
        .catch(() => {
            form.reset();
            display_result("Une erreur est survenue. Veuillez réessayer plus tard.", false);
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    form.addEventListener("submit", event => {
        event.preventDefault();
        errorFrame.classList.add("hidden");

        // Convert FormData cleanly into a key-value object
        const formData = new FormData(event.target);
        const datas = Object.fromEntries(formData.entries());

        authenticate(form.getAttribute("action"), datas);
    });
});