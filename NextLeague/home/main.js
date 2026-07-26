console.log("JS conectado");
const nav = document.querySelector("#nav");
const open= document.querySelector("#btnopenmenu");
const close= document.querySelector ("#close");
open.addEventListener("click", () => {
    nav.classList.add("visible")
})

close.addEventListener("click", () => {
    nav.classList.remove("visible");
});

console.log(nav);
console.log(open);
console.log(close);