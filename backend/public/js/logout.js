button = document.querySelector(".button");
output = document.querySelector(".output");
home = document.querySelector(".home");

button.addEventListener("click", logout);


function logout() {
    fetch("/logout", {method: "POST"})
    .then(response => response.json())
    .then((data) => {
        if(data.success) {
            button.style.display = "none";
            home.style.display = "block";
            output.innerText = data.success
        }
        else if(data.error) {
            output.innerText = data.error;
        }
    })
    .catch(e => {
        output.innerText = "An error occured. Try again later";
    });
}