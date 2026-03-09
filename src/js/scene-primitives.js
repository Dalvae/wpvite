const prefersReducedMotion = () =>
  window.matchMedia("(prefers-reduced-motion: reduce)").matches;

const createRevealObserver = () => {
  if (typeof IntersectionObserver === "undefined" || prefersReducedMotion()) {
    return null;
  }

  return new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    {
      rootMargin: "0px 0px -10% 0px",
      threshold: 0.15,
    },
  );
};

const animateCount = (element) => {
  if (element.dataset.counted === "true") {
    return;
  }

  const rawValue = Number.parseFloat(
    element.dataset.countUpValue ?? element.textContent ?? "0",
  );
  const decimals = Number.parseInt(element.dataset.countUpDecimals ?? "0", 10);

  if (Number.isNaN(rawValue)) {
    return;
  }

  if (prefersReducedMotion()) {
    element.textContent = rawValue.toFixed(decimals);
    element.dataset.counted = "true";
    return;
  }

  const duration = 1400;
  const startTime = performance.now();

  const frame = (now) => {
    const progress = Math.min((now - startTime) / duration, 1);
    const eased = 1 - (1 - progress) ** 3;
    element.textContent = (rawValue * eased).toFixed(decimals);

    if (progress < 1) {
      window.requestAnimationFrame(frame);
      return;
    }

    element.dataset.counted = "true";
  };

  window.requestAnimationFrame(frame);
};

const initReveal = (root = document) => {
  const elements = root.querySelectorAll("[data-reveal]");
  if (!elements.length) {
    return;
  }

  const observer = createRevealObserver();

  elements.forEach((element, index) => {
    if (!element.classList.contains("scene-reveal")) {
      element.classList.add("scene-reveal");
    }

    element.style.setProperty("--reveal-delay", `${index * 70}ms`);

    if (!observer) {
      element.classList.add("is-visible");
      return;
    }

    observer.observe(element);
  });
};

const initCountUps = (root = document) => {
  const elements = root.querySelectorAll("[data-count-up]");
  if (!elements.length) {
    return;
  }

  if (typeof IntersectionObserver === "undefined" || prefersReducedMotion()) {
    elements.forEach((element) => animateCount(element));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        animateCount(entry.target);
        observer.unobserve(entry.target);
      });
    },
    {
      rootMargin: "0px 0px -10% 0px",
      threshold: 0.2,
    },
  );

  elements.forEach((element) => observer.observe(element));
};

const initScenePrimitives = () => {
  initReveal(document);
  initCountUps(document);
};

if (!window.__starterScenePrimitivesBound) {
  window.__starterScenePrimitivesBound = true;
  document.addEventListener("turbo:load", initScenePrimitives);
  document.addEventListener("DOMContentLoaded", initScenePrimitives);
}
