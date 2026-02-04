// Theme toggle with preference persistence
const themeToggle = document.querySelector(".theme-toggle");
const rootElement = document.documentElement;

const applyTheme = (theme) => {
  rootElement.setAttribute("data-theme", theme);
  const isLight = theme === "light";
  themeToggle.setAttribute("aria-pressed", isLight);
  themeToggle.setAttribute("aria-label", `Switch to ${isLight ? "dark" : "light"} mode`);
  themeToggle.querySelector(".theme-toggle-icon").textContent = isLight ? "☀️" : "🌙";
  themeToggle.querySelector(".theme-toggle-text").textContent = isLight ? "Light" : "Dark";
};

const storedTheme = localStorage.getItem("theme");
const prefersLight = window.matchMedia("(prefers-color-scheme: light)").matches;
applyTheme(storedTheme || (prefersLight ? "light" : "dark"));

themeToggle.addEventListener("click", () => {
  const nextTheme = rootElement.getAttribute("data-theme") === "light" ? "dark" : "light";
  localStorage.setItem("theme", nextTheme);
  applyTheme(nextTheme);
});

// Mobile navigation toggle
const menuToggle = document.querySelector(".menu-toggle");
const navLinks = document.querySelector(".nav-links");
const siteHeader = document.querySelector(".site-header");

menuToggle.addEventListener("click", () => {
  const isOpen = navLinks.classList.toggle("open");
  menuToggle.classList.toggle("active", isOpen);
  menuToggle.setAttribute("aria-expanded", isOpen);
});

// Close the mobile menu when a link is clicked
navLinks.addEventListener("click", (event) => {
  if (event.target.tagName === "A") {
    navLinks.classList.remove("open");
    menuToggle.classList.remove("active");
    menuToggle.setAttribute("aria-expanded", false);
  }
});

// Header scroll effect
window.addEventListener("scroll", () => {
  siteHeader.classList.toggle("scrolled", window.scrollY > 10);
});

// Services accordion interaction for added focus on details
const serviceCards = document.querySelectorAll(".service-card");

serviceCards.forEach((card) => {
  const toggle = card.querySelector(".service-toggle");
  toggle.addEventListener("click", () => {
    card.classList.toggle("active");
  });
});

// Portfolio filtering
const filterButtons = document.querySelectorAll(".filter-button");
const portfolioCards = document.querySelectorAll(".portfolio-card");

filterButtons.forEach((button) => {
  button.addEventListener("click", () => {
    filterButtons.forEach((btn) => btn.classList.remove("active"));
    button.classList.add("active");
    const filter = button.dataset.filter;

    portfolioCards.forEach((card) => {
      const matches = filter === "all" || card.dataset.category === filter;
      card.style.display = matches ? "block" : "none";
    });
  });
});

// Reveal animations on scroll
const revealElements = document.querySelectorAll("[data-reveal]");
const revealObserver = new IntersectionObserver(
  (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("reveal-visible");
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.2 }
);

revealElements.forEach((element) => {
  revealObserver.observe(element);
});

// Contact form validation and smooth feedback message
const contactForm = document.querySelector(".contact-form");
const formMessage = document.querySelector(".form-message");

contactForm.addEventListener("submit", (event) => {
  event.preventDefault();
  formMessage.textContent = "";

  const formData = new FormData(contactForm);
  const name = formData.get("name").trim();
  const email = formData.get("email").trim();
  const project = formData.get("project");
  const message = formData.get("message").trim();

  if (!name || !email || !project || !message) {
    formMessage.textContent = "Please complete all required fields.";
    formMessage.style.color = "#ffb347";
    return;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    formMessage.textContent = "Please enter a valid email address.";
    formMessage.style.color = "#ffb347";
    return;
  }

  // Simulate smooth submission feedback
  formMessage.textContent = "Submitting...";
  formMessage.style.color = "#a7b0c2";

  window.setTimeout(() => {
    formMessage.textContent = "Thanks! We'll reach out shortly.";
    formMessage.style.color = "#52e0c4";
    contactForm.reset();
  }, 900);
});
