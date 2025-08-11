document.addEventListener("DOMContentLoaded", function () {
    const loginTab = document.querySelector("[data-tab='login']");
    const registerTab = document.querySelector("[data-tab='register']");
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");

    loginTab.addEventListener("click", function () {
        loginForm.classList.remove("hidden");
        registerForm.classList.add("hidden");
        loginTab.classList.add("active");
        registerTab.classList.remove("active");
    });

    registerTab.addEventListener("click", function () {
        registerForm.classList.remove("hidden");
        loginForm.classList.add("hidden");
        registerTab.classList.add("active");
        loginTab.classList.remove("active");
    });

    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch("auth.php", { method: "POST", body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    if (data.user) {
                        localStorage.setItem('currentUser', JSON.stringify(data.user));
                    }
                    window.location.href = data.redirect;
                }
            })
            .catch(error => console.error("Erro:", error));
    });

    registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch("auth.php", { method: "POST", body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => console.error("Erro:", error));
    });
});