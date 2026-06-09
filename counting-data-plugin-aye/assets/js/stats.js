function formatNumber(val, decimals) {
  if (val >= 1000) {
    var divided = val / 1000;
    return decimals
      ? divided.toFixed(decimals).replace(/\.0$/, "") + "K"
      : Math.round(divided) + "K";
  }
  return Math.round(val);
}

function animateCounter(el) {
  var target = parseFloat(el.getAttribute("data-target"));
  var suffix = el.getAttribute("data-suffix") || "";
  var decimals = el.getAttribute("data-decimals") || 0;
  var duration = 1800;
  var start = null;

  function step(timestamp) {
    if (!start) start = timestamp;
    var progress = Math.min((timestamp - start) / duration, 1);
    var ease = 1 - Math.pow(1 - progress, 3);
    var current = ease * target;
    el.textContent = formatNumber(current, decimals) + suffix;
    if (progress < 1) requestAnimationFrame(step);
  }

  requestAnimationFrame(step);
}

var observer = new IntersectionObserver(
  function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.3 },
);

document.querySelectorAll(".counter").forEach(function (el) {
  observer.observe(el);
});
