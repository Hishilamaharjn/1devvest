document.addEventListener("DOMContentLoaded", () => {
  // Navbar hover animation
  const navLinks = document.querySelectorAll("nav a");
  navLinks.forEach(link => {
    link.addEventListener("mouseenter", () => link.style.textShadow = "0 0 10px #00d4ff");
    link.addEventListener("mouseleave", () => link.style.textShadow = "none");
  });

  // Project hover dynamic effect
  const cards = document.querySelectorAll(".project-card");
  cards.forEach(card => {
    card.addEventListener("mousemove", e => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      card.style.transform = `rotateY(${(x - rect.width / 2) / 40}deg) rotateX(${-(y - rect.height / 2) / 40}deg)`;
    });
    card.addEventListener("mouseleave", () => {
      card.style.transform = "rotateY(0deg) rotateX(0deg)";
    });
  });
});
