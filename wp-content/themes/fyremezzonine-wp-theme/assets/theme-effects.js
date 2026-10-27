(function () {
  'use strict';

  function hideBrokenImage(image) {
    image.hidden = true;
    image.classList.add('is-broken');

    var removableContainer = image.closest('.conference-collage');
    if (removableContainer) {
      removableContainer.hidden = true;
    }
  }

  Array.prototype.forEach.call(document.querySelectorAll('img'), function (image) {
    image.addEventListener('error', function () {
      hideBrokenImage(image);
    });

    if (image.complete && image.naturalWidth === 0) {
      hideBrokenImage(image);
    }
  });

  var menuToggle = document.querySelector('.mobile-menu-toggle');
  var menuPanel = document.querySelector('.header-navigation-content');
  if (menuToggle && menuPanel) {
    function closeMobileMenu() {
      menuPanel.classList.remove('is-open');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Открыть меню');
    }

    menuToggle.addEventListener('click', function () {
      var open = menuPanel.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      menuToggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    });

    menuPanel.addEventListener('click', function (event) {
      if (event.target.closest('a') && window.matchMedia('(max-width: 980px)').matches) {
        closeMobileMenu();
      }
    });

    document.addEventListener('click', function (event) {
      if (!menuPanel.contains(event.target) && !menuToggle.contains(event.target)) {
        closeMobileMenu();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMobileMenu();
        menuToggle.focus();
      }
    });
  }

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function initArcticSnow() {
    var arcticTheme = document.querySelector('.conference-theme-arctic');
    if (!arcticTheme || reducedMotion.matches) {
      return;
    }

    var snowCanvas = document.createElement('canvas');
    var snowContext = snowCanvas.getContext('2d');
    if (!snowContext) {
      return;
    }

    snowCanvas.className = 'arctic-snow-canvas';
    snowCanvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(snowCanvas);

    var snowflakes = [];
    var snowWidth = 0;
    var snowHeight = 0;
    var snowScale = 1;
    var snowFrame = 0;
    var snowRunning = true;
    var snowLastDraw = performance.now();
    var snowFrameInterval = 1000 / 36;

    function snowRandom(min, max) {
      return min + Math.random() * (max - min);
    }

    function createSnowflake(randomY) {
      var depth = snowRandom(0.45, 1);
      return {
        x: snowRandom(0, snowWidth),
        y: randomY ? snowRandom(0, snowHeight) : snowRandom(-60, -8),
        radius: snowRandom(1.15, 3.15) * depth,
        speed: snowRandom(22, 54) * depth,
        drift: snowRandom(8, 22),
        phase: snowRandom(0, Math.PI * 2),
        frequency: snowRandom(0.35, 0.8),
        opacity: snowRandom(0.58, 0.98) * depth
      };
    }

    function snowflakeLimit() {
      return snowWidth < 720 ? 72 : 132;
    }

    function resizeSnowCanvas() {
      snowWidth = Math.max(1, window.innerWidth);
      snowHeight = Math.max(1, window.innerHeight);
      snowScale = Math.min(window.devicePixelRatio || 1, 1.25);
      snowCanvas.width = Math.round(snowWidth * snowScale);
      snowCanvas.height = Math.round(snowHeight * snowScale);
      snowCanvas.style.width = snowWidth + 'px';
      snowCanvas.style.height = snowHeight + 'px';
      snowContext.setTransform(snowScale, 0, 0, snowScale, 0, 0);

      var limit = snowflakeLimit();
      if (snowflakes.length > limit) {
        snowflakes.length = limit;
      }
      while (snowflakes.length < limit) {
        snowflakes.push(createSnowflake(true));
      }
    }

    function drawSnow(now) {
      if (!snowRunning) {
        return;
      }

      snowFrame = window.requestAnimationFrame(drawSnow);
      var elapsed = now - snowLastDraw;
      if (elapsed < snowFrameInterval) {
        return;
      }

      var delta = Math.min(elapsed / 1000, 0.05);
      snowLastDraw = now;
      snowContext.clearRect(0, 0, snowWidth, snowHeight);
      snowContext.fillStyle = '#ffffff';

      snowflakes.forEach(function (flake) {
        flake.phase += flake.frequency * delta;
        flake.x += Math.sin(flake.phase) * flake.drift * delta;
        flake.y += flake.speed * delta;

        if (flake.y > snowHeight + 8) {
          Object.assign(flake, createSnowflake(false));
        }
        if (flake.x < -8) {
          flake.x = snowWidth + 8;
        } else if (flake.x > snowWidth + 8) {
          flake.x = -8;
        }

        snowContext.globalAlpha = flake.opacity;
        snowContext.beginPath();
        snowContext.arc(flake.x, flake.y, flake.radius, 0, Math.PI * 2);
        snowContext.fill();
      });

      snowContext.globalAlpha = 1;
    }

    function syncSnowVisibility() {
      var shouldRun = !document.hidden && !reducedMotion.matches;
      if (snowRunning === shouldRun) {
        return;
      }

      snowRunning = shouldRun;
      if (snowRunning) {
        snowLastDraw = performance.now();
        snowFrame = window.requestAnimationFrame(drawSnow);
      } else {
        window.cancelAnimationFrame(snowFrame);
        snowContext.clearRect(0, 0, snowWidth, snowHeight);
      }
    }

    resizeSnowCanvas();
    snowFrame = window.requestAnimationFrame(drawSnow);
    window.addEventListener('resize', resizeSnowCanvas, { passive: true });
    document.addEventListener('visibilitychange', syncSnowVisibility);
    if (typeof reducedMotion.addEventListener === 'function') {
      reducedMotion.addEventListener('change', syncSnowVisibility);
    }
  }

  initArcticSnow();

  var theme = document.querySelector('.conference-theme-ember');
  var hero = theme ? theme.querySelector('.hero') : null;
  var atmosphere = hero ? hero.querySelector('.theme-atmosphere') : null;

  if (!hero || !atmosphere || reducedMotion.matches) {
    return;
  }

  var canvas = document.createElement('canvas');
  var context = canvas.getContext('2d');

  if (!context) {
    return;
  }

  canvas.className = 'ember-particle-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  atmosphere.appendChild(canvas);

  var particles = [];
  var width = 0;
  var height = 0;
  var frameId = 0;
  var active = false;
  var lastFrame = performance.now();
  var nextEmission = lastFrame;

  function random(min, max) {
    return min + Math.random() * (max - min);
  }

  function resizeCanvas() {
    var bounds = hero.getBoundingClientRect();
    var scale = Math.min(window.devicePixelRatio || 1, 1.5);

    width = Math.max(1, bounds.width);
    height = Math.max(1, bounds.height);
    canvas.width = Math.round(width * scale);
    canvas.height = Math.round(height * scale);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    context.setTransform(scale, 0, 0, scale, 0, 0);
  }

  function particleLimit() {
    return width < 700 ? 36 : 54;
  }

  function createParticle(forceAmbient) {
    var ambient = forceAmbient || Math.random() < 0.34;
    var life = ambient ? random(1.15, 2.7) : random(1, 2.55);
    var x = random(width * 0.03, width * 0.98);
    var y = ambient ? random(height * 0.16, height * 0.88) : random(height * 0.72, height * 1.03);

    particles.push({
      x: x,
      y: y,
      previousX: x,
      previousY: y,
      velocityX: ambient ? random(-48, 48) : random(-90, 90),
      velocityY: ambient ? random(-190, -82) : random(-370, -165),
      age: 0,
      life: life,
      radius: ambient ? random(0.65, 1.65) : random(0.8, 2.35),
      hue: random(18, 52),
      lightness: random(58, 88),
      phase: random(0, Math.PI * 2),
      turbulence: ambient ? random(22, 70) : random(32, 110),
      frequency: random(4.5, 10.5)
    });
  }

  function emitParticles(now) {
    var limit = particleLimit();

    if (now < nextEmission || particles.length >= limit) {
      return;
    }

    var burst = Math.random() < 0.32 ? Math.floor(random(4, 8)) : Math.floor(random(2, 4));

    for (var index = 0; index < burst && particles.length < limit; index += 1) {
      createParticle(false);
    }

    nextEmission = now + random(55, 220);
  }

  function drawParticle(particle, delta) {
    var progress = particle.age / particle.life;
    var fadeIn = Math.min(1, progress * 8);
    var fadeOut = Math.pow(Math.max(0, 1 - progress), 1.7);
    var flicker = 0.78 + Math.sin(particle.age * 31 + particle.phase) * 0.16;
    var alpha = fadeIn * fadeOut * flicker;
    var wind = Math.sin(particle.age * particle.frequency + particle.phase) * particle.turbulence;

    particle.previousX = particle.x;
    particle.previousY = particle.y;
    particle.velocityX += wind * delta;
    particle.velocityX *= Math.pow(0.91, delta);
    particle.velocityY += 58 * delta;
    particle.x += particle.velocityX * delta;
    particle.y += particle.velocityY * delta;
    particle.age += delta;

    context.beginPath();
    context.moveTo(particle.previousX, particle.previousY);
    context.lineTo(
      particle.x - particle.velocityX * delta * 1.8,
      particle.y - particle.velocityY * delta * 1.8
    );
    context.lineWidth = particle.radius;
    context.lineCap = 'round';
    context.strokeStyle = 'hsla(' + particle.hue + ', 100%, ' + particle.lightness + '%, ' + alpha + ')';
    context.shadowBlur = 8 + particle.radius * 4;
    context.shadowColor = 'hsla(' + particle.hue + ', 100%, 58%, ' + alpha + ')';
    context.stroke();

    context.beginPath();
    context.arc(particle.x, particle.y, particle.radius * 0.65, 0, Math.PI * 2);
    context.fillStyle = 'hsla(' + Math.min(58, particle.hue + 8) + ', 100%, 88%, ' + alpha + ')';
    context.fill();
  }

  function render(now) {
    if (!active) {
      return;
    }

    var delta = Math.min((now - lastFrame) / 1000, 0.034);
    lastFrame = now;
    context.clearRect(0, 0, width, height);
    context.shadowBlur = 0;
    emitParticles(now);

    particles.forEach(function (particle) {
      drawParticle(particle, delta);
    });

    particles = particles.filter(function (particle) {
      return particle.age < particle.life && particle.y > -40;
    });

    frameId = window.requestAnimationFrame(render);
  }

  function setActive(isActive) {
    if (active === isActive) {
      return;
    }

    active = isActive;

    if (active) {
      lastFrame = performance.now();
      nextEmission = lastFrame;

      for (var index = 0, initialCount = width < 700 ? 12 : 20; index < initialCount; index += 1) {
        createParticle(true);
      }

      frameId = window.requestAnimationFrame(render);
      return;
    }

    window.cancelAnimationFrame(frameId);
    particles = [];
    context.clearRect(0, 0, width, height);
  }

  resizeCanvas();

  if ('ResizeObserver' in window) {
    new ResizeObserver(resizeCanvas).observe(hero);
  } else {
    window.addEventListener('resize', resizeCanvas);
  }

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      setActive(entries[0].isIntersecting);
    }, { threshold: 0.08 }).observe(hero);
  } else {
    setActive(true);
  }
}());
