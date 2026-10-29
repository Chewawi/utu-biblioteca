// ============================================================
//  Gráficos de la página de analíticas (Chart.js)
//  Los datos los deja la vista en <script id="datos-graficos">.
//  Los colores salen de las variables de estilo.css, así que al
//  cambiar de tema se vuelven a dibujar con los colores nuevos.
// ============================================================

var datos = JSON.parse(document.getElementById('datos-graficos').textContent);
var graficos = []; // los gráficos dibujados, para poder borrarlos y redibujarlos

/** Valor de una variable de estilo.css con el tema actual. */
function color(variable) {
  return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
}

/** Relleno de rayas diagonales sobre un color: marca vencidos y tarde sin depender solo del color. */
function rayado(fondo) {
  var lienzo = document.createElement('canvas');
  lienzo.width = 6;
  lienzo.height = 6;
  var ctx = lienzo.getContext('2d');
  ctx.fillStyle = fondo;
  ctx.fillRect(0, 0, 6, 6);
  ctx.strokeStyle = color('--color-superficie');
  ctx.lineWidth = 1.5;
  ctx.beginPath();
  ctx.moveTo(0, 6);
  ctx.lineTo(6, 0);
  ctx.moveTo(-1, 1);
  ctx.lineTo(1, -1);
  ctx.moveTo(5, 7);
  ctx.lineTo(7, 5);
  ctx.stroke();
  return ctx.createPattern(lienzo, 'repeat');
}

/** Títulos largos se cortan en el eje; el título completo se ve al pasar el cursor. */
function acortar(texto) {
  return texto.length > 22 ? texto.slice(0, 21) + '…' : texto;
}

function dibujarGraficos() {
  graficos.forEach(function (g) { g.destroy(); });
  graficos = [];

  // Lo que comparten todos los gráficos: letra, texto, líneas y cartelito del cursor.
  Chart.defaults.font.family = color('--fuente-base');
  Chart.defaults.color = color('--color-texto-suave');
  Chart.defaults.borderColor = color('--color-borde');
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.plugins.legend.display = false;
  Chart.defaults.plugins.tooltip.backgroundColor = color('--color-texto');
  Chart.defaults.plugins.tooltip.titleColor = color('--color-superficie');
  Chart.defaults.plugins.tooltip.bodyColor = color('--color-superficie');
  Chart.defaults.plugins.tooltip.displayColors = false;
  Chart.defaults.plugins.tooltip.padding = 10;

  // Eje de los valores en los gráficos de barras: desde 0 y con números enteros.
  // Es una función para que cada gráfico tenga su propia copia.
  function ejeValor() {
    return { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } };
  }

  var lienzoMeses = document.getElementById('grafico-meses');
  if (lienzoMeses) {
    graficos.push(new Chart(lienzoMeses, {
      type: 'bar',
      data: {
        labels: datos.meses.etiquetas,
        datasets: [{ data: datos.meses.valores, backgroundColor: color('--color-acento'), borderRadius: 4, maxBarThickness: 40 }]
      },
      options: {
        scales: { x: { grid: { display: false } }, y: ejeValor() },
        plugins: { tooltip: { callbacks: { label: function (c) { return c.raw + (c.raw === 1 ? ' préstamo' : ' préstamos'); } } } }
      }
    }));
  }

  var lienzoEstados = document.getElementById('grafico-estados');
  if (lienzoEstados) {
    var total = datos.estados.valores.reduce(function (a, b) { return a + b; }, 0);
    graficos.push(new Chart(lienzoEstados, {
      type: 'doughnut',
      data: {
        labels: datos.estados.etiquetas,
        datasets: [{
          data: datos.estados.valores,
          // Mismo orden que $partes en la vista: al día, vencidos, devueltos a tiempo, devueltos tarde.
          backgroundColor: [color('--color-acento'), rayado(color('--grafico-vencido')), color('--grafico-devuelto'), rayado(color('--grafico-tarde'))],
          borderColor: color('--color-superficie'),
          borderWidth: 2
        }]
      },
      options: {
        cutout: '62%',
        plugins: { tooltip: { callbacks: { label: function (c) { return c.raw + ' (' + Math.round(c.raw / total * 100) + ' %)'; } } } }
      }
    }));
  }

  var lienzoItems = document.getElementById('grafico-items');
  if (lienzoItems) {
    graficos.push(new Chart(lienzoItems, {
      type: 'bar',
      data: {
        labels: datos.items.etiquetas,
        datasets: [{ data: datos.items.valores, backgroundColor: color('--color-acento'), borderRadius: 4, maxBarThickness: 28 }]
      },
      options: {
        indexAxis: 'y', // barras acostadas
        scales: {
          x: ejeValor(),
          y: { grid: { display: false }, ticks: { callback: function (valor) { return acortar(this.getLabelForValue(valor)); } } }
        },
        plugins: { tooltip: { callbacks: { label: function (c) { return c.raw + (c.raw === 1 ? ' préstamo' : ' préstamos'); } } } }
      }
    }));
  }
}

dibujarGraficos();

// El botón de tema cambia data-bs-theme en <html>: cuando pasa, redibujamos.
new MutationObserver(dibujarGraficos).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
