const page = document.body.dataset.page;
document.querySelectorAll(".nav a").forEach((a) => {
  if (a.dataset.page === page) a.classList.add("active");
});
// const user = JSON.parse(
//   localStorage.getItem("astUser") || '{"name":"Alex Morgan"}',
// );
document
  .querySelectorAll("[data-user-name]")
  .forEach((el) => (el.textContent = user.name));
document
  .querySelectorAll("[data-toast]")
  .forEach((btn) =>
    btn.addEventListener("click", () => alert(btn.dataset.toast)),
  );
